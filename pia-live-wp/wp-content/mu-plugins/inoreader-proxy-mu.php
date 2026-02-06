<?php
/**
 * Plugin Name: PIA Inoreader Proxy (MU)
 * Description: Proxies Inoreader JSON feed via same-origin REST endpoint to avoid CORS, with network-wide caching.
 * Version: 1.1.0
 */

defined('ABSPATH') || exit;

function pia_inoreader_allowed_feeds() {
  return [
    'pia_latest_from_us' => 'https://www.inoreader.com/stream/user/1003920593/tag/PIA%20Latest%20from%20Us/view/json',
  ];
}

add_action('rest_api_init', function () {

  register_rest_route('pia/v1', '/hello', [
    'methods'  => 'GET',
    'callback' => function () {
      return new WP_REST_Response([
        'ok' => true,
        'site' => home_url('/'),
        'time' => time(),
      ], 200);
    },
    'permission_callback' => '__return_true',
  ]);

  register_rest_route('pia/v1', '/inoreader', [
    'methods'  => 'GET',
    'callback' => 'pia_inoreader_proxy_handler',
    'permission_callback' => '__return_true',
    'args' => [
      'feed' => ['required' => true, 'type' => 'string'],
      'nocache' => ['required' => false, 'type' => 'boolean', 'default' => false],
    ],
  ]);
});

function pia_inoreader_proxy_handler(WP_REST_Request $request) {
  $feed_key = sanitize_key((string) $request->get_param('feed'));
  $feeds = pia_inoreader_allowed_feeds();

  if (!isset($feeds[$feed_key])) {
    return new WP_REST_Response(['error' => 'Unknown feed key.'], 400);
  }

  $feed_url  = $feeds[$feed_key];
  $cache_key = 'pia_inoreader_proxy_' . $feed_key;

  $nocache  = (bool) $request->get_param('nocache');
  $is_admin = is_user_logged_in() && current_user_can('manage_options');
  $ttl      = 300;

  // ✅ Network-wide cache (shared across multisite)
  if (!$nocache || !$is_admin) {
    $cached = get_site_transient($cache_key);
    if ($cached) {
      return pia_inoreader_json_response($cached, true, $ttl);
    }
  }

  $resp = wp_remote_get($feed_url, [
    'timeout' => 15,
    'redirection' => 3,
    'headers' => [
      'Accept' => 'application/json',
      'User-Agent' => 'PIA-WP-Inoreader-Proxy/1.1.0',
    ],
  ]);

  if (is_wp_error($resp)) {
    return new WP_REST_Response([
      'error' => 'Fetch failed.',
      'details' => $resp->get_error_message(),
    ], 502);
  }

  $code = wp_remote_retrieve_response_code($resp);
  $body = wp_remote_retrieve_body($resp);

  if ($code < 200 || $code >= 300) {
    return new WP_REST_Response([
      'error' => 'Upstream returned non-2xx.',
      'status' => $code,
      'body_snippet' => substr((string)$body, 0, 300),
    ], 502);
  }

  $json = json_decode($body, true);
  if (!is_array($json)) {
    return new WP_REST_Response([
      'error' => 'Upstream response was not valid JSON.',
      'body_snippet' => substr((string)$body, 0, 300),
    ], 502);
  }

  set_site_transient($cache_key, $body, $ttl);

  return pia_inoreader_json_response($body, false, $ttl);
}

function pia_inoreader_json_response(string $body, bool $from_cache, int $ttl) {
  $response = new WP_REST_Response(json_decode($body, true), 200);
  $response->header('Content-Type', 'application/json; charset=utf-8');
  $response->header('Cache-Control', 'public, max-age=' . $ttl);
  $response->header('X-PIA-Inoreader-Cache', $from_cache ? 'HIT' : 'MISS');
  return $response;
}
