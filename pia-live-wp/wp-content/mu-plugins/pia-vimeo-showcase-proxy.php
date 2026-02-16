<?php
/**
 * Plugin Name: PIA Vimeo Showcase Proxy (MU)
 * Description: Server-side proxy for Vimeo Showcase videos (paginated), cached.
 * Version: 1.0.1
 */

defined('ABSPATH') || exit;

/**
 * Put your Vimeo Personal Access Token (public scope) in wp-config.php:
 *   define('PIA_VIMEO_ACCESS_TOKEN', 'vimeo_pat_xxx...');
 *
 * This token stays server-side only.
 */
function pia_vimeo_access_token(): string {
  if (defined('PIA_VIMEO_ACCESS_TOKEN') && PIA_VIMEO_ACCESS_TOKEN) {
    return (string) PIA_VIMEO_ACCESS_TOKEN;
  }
  return '';
}

/**
 * Optional: set a default showcase ID so the endpoint works without query params.
 * Update this if you ever change showcases.
 */
function pia_vimeo_default_showcase_id(): string {
  return '12047150';
}

/**
 * GET /wp-json/pia/v1/vimeo-showcase?showcase_id=12047150&page=1&per_page=100
 *
 * Notes:
 * - This endpoint is public because your feed page is public.
 * - Token is only used server-side (never exposed to the browser).
 */
add_action('rest_api_init', function () {
  register_rest_route('pia/v1', '/vimeo-showcase', [
    'methods'  => 'GET',
    'callback' => 'pia_vimeo_showcase_handler',
    'permission_callback' => '__return_true', // public read
    'args' => [
      // Make showcase_id optional; default to your showcase so the base URL works.
      'showcase_id' => [
        'required' => false,
        'default'  => pia_vimeo_default_showcase_id(),
      ],
      'page' => [
        'required' => false,
        'default'  => 1,
      ],
      // ✅ Default to 100 per page (max allowed by Vimeo API is typically 100)
      'per_page' => [
        'required' => false,
        'default'  => 100,
      ],
    ],
  ]);
});

function pia_vimeo_showcase_handler(WP_REST_Request $req) {
  $token = pia_vimeo_access_token();
  if (!$token) {
    return new WP_REST_Response([
      'error' => 'Missing Vimeo token. Define PIA_VIMEO_ACCESS_TOKEN in wp-config.php.'
    ], 500);
  }

  // Default showcase_id if missing, then sanitize to digits only.
  $showcase_id = (string) ($req->get_param('showcase_id') ?: pia_vimeo_default_showcase_id());
  $showcase_id = preg_replace('/\D+/', '', $showcase_id);

  if (!$showcase_id) {
    return new WP_REST_Response(['error' => 'Invalid showcase_id'], 400);
  }

  $page = max(1, (int) ($req->get_param('page') ?: 1));

  // ✅ Default 100, clamp to 1..100
  $per_page = (int) ($req->get_param('per_page') ?: 100);
  $per_page = min(max($per_page, 1), 100);

  // Cache each page separately
  $cache_key = 'pia_vimeo_showcase_' . $showcase_id . '_p' . $page . '_pp' . $per_page;
  $cached = get_transient($cache_key);
  if ($cached) {
    return new WP_REST_Response($cached, 200);
  }

  // Vimeo API: list videos in a showcase
  // Docs: Vimeo API Reference -> Showcases -> Get videos in a showcase
  $base = 'https://api.vimeo.com/showcases/' . rawurlencode($showcase_id) . '/videos';

  // Keep response smaller with fields selection
  $query = [
    'page'     => $page,
    'per_page' => $per_page,
    'fields'   => implode(',', [
      'uri', 'name', 'description', 'link',
      'created_time', 'release_time',
      'duration',
      'pictures.sizes.link', 'pictures.sizes.width', 'pictures.sizes.height',
      'player_embed_url',
      'paging.next', 'paging.previous', 'paging.first', 'paging.last',
      'total',
    ]),
    // Optional: order (uncomment if desired)
    // 'sort' => 'date',
    // 'direction' => 'desc',
  ];
  $url = add_query_arg($query, $base);

  $resp = wp_remote_get($url, [
    'timeout' => 20,
    'headers' => [
      'Authorization' => 'Bearer ' . $token,
      'Accept'        => 'application/vnd.vimeo.*+json;version=3.4',
    ],
  ]);

  if (is_wp_error($resp)) {
    return new WP_REST_Response(['error' => $resp->get_error_message()], 502);
  }

  $code = wp_remote_retrieve_response_code($resp);
  $body = wp_remote_retrieve_body($resp);
  $json = json_decode($body, true);

  if ($code < 200 || $code >= 300 || !is_array($json)) {
    return new WP_REST_Response([
      'error'  => 'Vimeo API error',
      'status' => $code,
      'body'   => is_string($body) ? substr($body, 0, 800) : $body,
    ], 502);
  }

  // Cache ~10 minutes (tweak as needed)
  set_transient($cache_key, $json, 10 * MINUTE_IN_SECONDS);

  return new WP_REST_Response($json, 200);
}
