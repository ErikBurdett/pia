<?php
/**
 * Plugin Name: PIA Vimeo Showcase Proxy (MU)
 * Description: Server-side proxy for Vimeo Showcase videos (paginated), cached, allowlisted.
 * Version: 1.2.0
 */

defined('ABSPATH') || exit;

function pia_vimeo_access_token(): string {
  if (defined('PIA_VIMEO_ACCESS_TOKEN') && PIA_VIMEO_ACCESS_TOKEN) {
    return (string) PIA_VIMEO_ACCESS_TOKEN;
  }
  $env = getenv('PIA_VIMEO_ACCESS_TOKEN');
  if ($env) return (string) $env;
  return '';
}

/**
 * Optional: if your showcase belongs to a specific user/team account and /me doesn't work,
 * set this in wp-config.php:
 *   define('PIA_VIMEO_USER_ID', '1234567');
 */
function pia_vimeo_user_id(): string {
  if (defined('PIA_VIMEO_USER_ID') && PIA_VIMEO_USER_ID) {
    return preg_replace('/\D+/', '', (string) PIA_VIMEO_USER_ID);
  }
  return '';
}

function pia_vimeo_allowed_showcases(): array {
  return [
    '12047150' => true,
  ];
}

function pia_vimeo_require_same_origin(): bool {
  return false;
}

function pia_vimeo_is_same_origin_request(): bool {
  $host    = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
  $origin  = strtolower((string)($_SERVER['HTTP_ORIGIN'] ?? ''));
  $referer = strtolower((string)($_SERVER['HTTP_REFERER'] ?? ''));
  if (!$host) return true;
  if ($origin && strpos($origin, $host) !== false) return true;
  if ($referer && strpos($referer, $host) !== false) return true;
  if (!$origin && !$referer) return true;
  return false;
}

add_action('rest_api_init', function () {
  register_rest_route('pia/v1', '/vimeo-showcase', [
    'methods'  => 'GET',
    'callback' => 'pia_vimeo_showcase_handler',
    'permission_callback' => '__return_true',
    'args' => [
      'showcase_id' => ['required' => true],
      'page'        => ['required' => false],
      'per_page'    => ['required' => false],
    ],
  ]);
});

function pia_vimeo_wp_remote_get_json(string $url, string $token): array {
  $resp = wp_remote_get($url, [
    'timeout' => 20,
    'headers' => [
      'Authorization' => 'Bearer ' . $token,
      'Accept'        => 'application/vnd.vimeo.*+json;version=3.4',
    ],
  ]);

  if (is_wp_error($resp)) {
    return ['ok' => false, 'status' => 0, 'body' => $resp->get_error_message(), 'json' => null];
  }

  $code = (int) wp_remote_retrieve_response_code($resp);
  $body = (string) wp_remote_retrieve_body($resp);
  $json = json_decode($body, true);

  return [
    'ok'     => ($code >= 200 && $code < 300 && is_array($json)),
    'status' => $code,
    'body'   => $body,
    'json'   => is_array($json) ? $json : null,
  ];
}

function pia_vimeo_showcase_handler(WP_REST_Request $req) {
  if (pia_vimeo_require_same_origin() && !pia_vimeo_is_same_origin_request()) {
    return new WP_REST_Response(['error' => 'Forbidden'], 403);
  }

  $token = pia_vimeo_access_token();
  if (!$token) {
    return new WP_REST_Response([
      'error' => 'Missing Vimeo token. Define PIA_VIMEO_ACCESS_TOKEN in wp-config.php or env var.'
    ], 500);
  }

  $showcase_id = preg_replace('/\D+/', '', (string) $req->get_param('showcase_id'));
  if (!$showcase_id) {
    return new WP_REST_Response(['error' => 'Invalid showcase_id'], 400);
  }

  $allowed = pia_vimeo_allowed_showcases();
  if (empty($allowed[$showcase_id])) {
    return new WP_REST_Response(['error' => 'Showcase not allowed'], 403);
  }

  $page = max(1, (int) ($req->get_param('page') ?: 1));
  if ($page > 50) $page = 50;

  $per_page = (int) ($req->get_param('per_page') ?: 50);
  $per_page = min(max($per_page, 1), 50);

  $cache_key = 'pia_vimeo_showcase_' . $showcase_id . '_p' . $page . '_pp' . $per_page;
  $cached = get_site_transient($cache_key);
  if ($cached) {
    return new WP_REST_Response($cached, 200);
  }

  $fields = implode(',', [
    'uri','name','description','link',
    'created_time','release_time',
    'duration',
    'pictures.sizes.link','pictures.sizes.width','pictures.sizes.height',
    'player_embed_url',
    'paging.next','paging.previous','paging.first','paging.last',
    'total',
  ]);

  // Try endpoints in order. Showcases are “previously albums”, and /me/albums is often the most reliable
  // when the token belongs to the owning account. :contentReference[oaicite:2]{index=2}
  $candidates = [];

  // 1) Showcases endpoint
  $candidates[] = add_query_arg([
    'page' => $page,
    'per_page' => $per_page,
    'fields' => $fields,
  ], 'https://api.vimeo.com/showcases/' . rawurlencode($showcase_id) . '/videos');

  // 2) Album endpoint for the token owner
  $candidates[] = add_query_arg([
    'page' => $page,
    'per_page' => $per_page,
    'fields' => $fields,
  ], 'https://api.vimeo.com/me/albums/' . rawurlencode($showcase_id) . '/videos');

  // 3) Album endpoint for a specific user (optional)
  $user_id = pia_vimeo_user_id();
  if ($user_id) {
    $candidates[] = add_query_arg([
      'page' => $page,
      'per_page' => $per_page,
      'fields' => $fields,
    ], 'https://api.vimeo.com/users/' . rawurlencode($user_id) . '/albums/' . rawurlencode($showcase_id) . '/videos');
  }

  $last = null;
  foreach ($candidates as $tryUrl) {
    $res = pia_vimeo_wp_remote_get_json($tryUrl, $token);
    $last = ['url' => $tryUrl, 'status' => $res['status'], 'body' => $res['body']];

    if ($res['ok']) {
      // cache good responses only
      set_site_transient($cache_key, $res['json'], 10 * MINUTE_IN_SECONDS);
      return new WP_REST_Response($res['json'], 200);
    }

    // If not found, try next candidate. If it's another error (401/403/5xx), stop early.
    if ((int)$res['status'] !== 404) {
      break;
    }
  }

  // If we got here, all candidates failed.
  return new WP_REST_Response([
    'error'  => 'Vimeo API error',
    'status' => $last ? $last['status'] : 0,
    'tried'  => $last ? $last['url'] : '',
    'body'   => $last && is_string($last['body']) ? substr($last['body'], 0, 800) : $last['body'],
    'hint'   => '404 often means the token user cannot access that showcase. Ensure the token was created under the same Vimeo account that owns the showcase, or set PIA_VIMEO_USER_ID for /users/{id}/albums/{album_id}/videos.',
  ], 502);
}
