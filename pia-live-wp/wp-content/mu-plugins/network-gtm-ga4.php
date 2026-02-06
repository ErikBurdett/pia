<?php
/**
 * Plugin Name: Network GTM + County DataLayer
 * Description: Adds GTM on every multisite + pushes county/site metadata into dataLayer (disabled on *.kinsta.cloud staging).
 */
defined('ABSPATH') || exit;

/**
 * GTM Container ID
 * You can optionally override this in wp-config.php:
 * define('NETWORK_GTM_ID', 'GTM-KDBQZPN6');
 */
if (!defined('NETWORK_GTM_ID')) {
  define('NETWORK_GTM_ID', 'GTM-KDBQZPN6');
}

function pia_get_request_host(): string {
  $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? ($_SERVER['HTTP_HOST'] ?? '');
  $host = strtolower(trim($host));

  if ($host === '') return '';

  // If multiple hosts are provided, take the first
  if (strpos($host, ',') !== false) {
    $host = trim(explode(',', $host)[0]);
  }

  // Strip any port suffix (e.g., :443)
  $host = preg_replace('~:\d+$~', '', $host);

  return $host ?: '';
}

function pia_is_staging_environment(): bool {
  $host = pia_get_request_host();
  if ($host === '') return false;

  // Kinsta staging domains commonly end with .kinsta.cloud
  return (bool) preg_match('~\.kinsta\.cloud$~i', $host);
}

function pia_should_output_tracking(): bool {
  if (pia_is_staging_environment()) return false;
  if (!NETWORK_GTM_ID) return false;

  if (is_admin()) return false;
  if (function_exists('wp_doing_ajax') && wp_doing_ajax()) return false;

  // Extra safety: skip REST/JSON contexts
  if (defined('REST_REQUEST') && REST_REQUEST) return false;
  if (function_exists('wp_is_json_request') && wp_is_json_request()) return false;

  return true;
}

function pia_get_county_slug(): string {
  if (!is_multisite()) return 'single-site';

  $details = get_blog_details(get_current_blog_id());
  $path = isset($details->path) ? trim((string)$details->path, '/') : '';

  // Root site: https://patriotsinactiontx.com/
  if ($path === '') return 'statewide';

  $parts = array_values(array_filter(explode('/', $path)));
  return strtolower(end($parts) ?: 'unknown');
}

function pia_pretty_county_name(string $slug): string {
  $map = [
    'potter'    => 'Potter',
    'randall'   => 'Randall',
    'midland'   => 'Midland',
    'ector'     => 'Ector',
    'hale'      => 'Hale',
    'hardeman'  => 'Hardeman',
    'gray'      => 'Gray',
    'parker'    => 'Parker',
    'statewide' => 'Statewide',
  ];

  if (isset($map[$slug])) return $map[$slug];

  $name = str_replace(['-', '_'], ' ', $slug);
  return ucwords($name);
}

function pia_output_datalayer_and_gtm() {
  if (!pia_should_output_tracking()) return;

  // DEBUG marker so you can confirm the MU-plugin is running in View Source
  echo "\n<!-- PIA MU GTM LOADED -->\n";

  $site_id     = get_current_blog_id();
  $site_name   = get_bloginfo('name');
  $site_url    = home_url('/');
  $county_slug = pia_get_county_slug();
  $county_name = pia_pretty_county_name($county_slug);
  ?>
  <script>
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
      wp_site_id: <?php echo (int) $site_id; ?>,
      wp_site_name: <?php echo wp_json_encode($site_name); ?>,
      wp_site_url: <?php echo wp_json_encode($site_url); ?>,
      county_slug: <?php echo wp_json_encode($county_slug); ?>,
      county: <?php echo wp_json_encode($county_name); ?>
    });
  </script>

  <!-- Google Tag Manager -->
  <script>
    (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
      new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
      j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
      'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','<?php echo esc_js(NETWORK_GTM_ID); ?>');
  </script>
  <!-- End Google Tag Manager -->
  <?php
}
add_action('wp_head', 'pia_output_datalayer_and_gtm', 1);

function pia_output_gtm_noscript() {
  if (!pia_should_output_tracking()) return;
  ?>
  <!-- Google Tag Manager (noscript) -->
  <noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr(NETWORK_GTM_ID); ?>"
            height="0" width="0" style="display:none;visibility:hidden"></iframe>
  </noscript>
  <!-- End Google Tag Manager (noscript) -->
  <?php
}
add_action('wp_body_open', 'pia_output_gtm_noscript', 1);

// Fallback if the theme doesn't call wp_body_open
add_action('wp_footer', function () {
  if (!did_action('wp_body_open')) {
    pia_output_gtm_noscript();
  }
}, 1);
