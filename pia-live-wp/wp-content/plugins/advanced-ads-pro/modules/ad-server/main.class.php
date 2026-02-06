<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

use AdvancedAds\Options;
use AdvancedAds\Abstracts\Ad;
use AdvancedAds\Framework\Utilities\Params;

/**
 * Allow serving ads on external URLs.
 */
class Advanced_Ads_Pro_Module_Ad_Server {
	/**
	 * Advanced_Ads_Pro_Module_Ad_Server constructor.
	 */
	public function __construct() {
		// Register frontend AJAX calls.
		add_action( 'wp_ajax_aa-server-select', [ $this, 'get_placement' ] );
		add_action( 'wp_ajax_nopriv_aa-server-select', [ $this, 'get_placement' ] );
		add_filter( 'advanced-ads-set-wrapper', [ $this, 'ad_wrapper' ], 10, 2 );

		// Add allowed HTTP origins.
		if ( wp_doing_ajax() ) {
			add_filter( 'allowed_http_origins', [ $this, 'add_allowed_origins' ] );
		}
	}

	/**
	 * Add a wrapper to served top level ads
	 *
	 * @param array $wrapper existing wrapper data.
	 * @param Ad    $ad      the ad.
	 *
	 * @return array
	 */
	public function ad_wrapper( $wrapper, $ad ) {
		$placement = $ad->get_root_placement();

		if ( ! $placement || ! $placement->is_type( 'server' ) ) {
			return $wrapper;
		}
		if ( ! $ad->is_top_level() ) {
			return $wrapper;
		}
		if ( ! is_array( $wrapper ) || ! isset( $wrapper['id'] ) ) {
			$wrapper['id'] = $ad->create_wrapper_id();
		}

		return $wrapper;
	}

	/**
	 * Load placement content
	 *
	 * Based on Advanced_Ads_Ajax::advads_ajax_ad_select()
	 */
	public function get_placement() {
		// Prevent direct access through the URL.
		if ( Options::instance()->get( 'pro.ad-server.block-no-referrer', false ) && ! Params::server( 'HTTP_REFERER' ) ) {
			die( 'direct access forbidden' );
		}

		// Set correct frontend headers.
		header( 'X-Robots-Tag: noindex,nofollow' );
		header( 'Content-Type: text/html; charset=UTF-8' );

		$embedding_urls = $this->get_embedding_urls();

		// Cross Origin Resource Sharing.
		if ( ! empty( $embedding_urls ) ) {
			$request_origin          = Params::server( 'HTTP_ORIGIN' );
			$request_host            = wp_parse_url( $request_origin, PHP_URL_HOST ) ?? '';
			$normalized_request_host = preg_replace( '/^www\./', '', strtolower( $request_host ) );

			if ( in_array( $normalized_request_host, $embedding_urls, true ) ) {
				header( 'Access-Control-Allow-Origin: ' . $request_origin );
			}

			// Set Content-Security-Policy for frame-ancestors.
			header( 'Content-Security-Policy: frame-ancestors ' . implode( ' ', $embedding_urls ) );
		}

		$public_slug = Params::request( 'p', null );
		if ( empty( $public_slug || ! is_string( $public_slug ) ) ) {
			die( 'missing p parameter' );
		}

		// Get placement output by public slug.
		$placement_content = $this->get_placement_output_by_public_slug( $public_slug );
		include __DIR__ . '/views/frontend-template.php';

		die();
	}

	/**
	 * Modify the ad object before serving
	 *
	 * @param false|string $override overridden ad output.
	 * @param Ad           $ad       the ad.
	 *
	 * @return false
	 */
	public function override_ad_object( $override, $ad ) {
		/**
		 * We need to force the ad to open in a new window when the link is created through Advanced Ads. Otherwise,
		 * clicking the ad in an iframe would load the target page in the iframe, too.
		 *
		 * 1. The Tracking add-on has a dedicated option on the ad edit page for this.
		 * We are setting it to open in a new window here and ignore the options the user might have set.
		 */
		$ad->set_prop_temp( 'tracking.target', 'new' );

		// Ignore consent settings for ad-server ads.
		$ad->set_prop_temp( 'privacy.ignore-consent', 'on' );

		/**
		 * 2. The Advanced Ads plugin adds target="_blank" based on a global option
		 * We change force that option to open ads in a new window by hooking into the advanced-ads-options filter below.
		 */
		add_filter(
			'advanced-ads-options',
			function ( $options ) {
				$options['target-blank'] = 1;

				return $options;
			}
		);

		return false;
	}

	/**
	 * Get the content of a placement based on the public slug.
	 *
	 * @param string $public_slug placement ID or public slug.
	 */
	private function get_placement_output_by_public_slug( $public_slug = '' ) {
		if ( '' === $public_slug ) {
			return '';
		}

		$placement = wp_advads_get_placement( $public_slug );

		// Return placement if there is one with public_slug being the placement ID.
		if ( $placement ) {
			add_filter( 'advanced-ads-ad-select-override-by-ad', [ $this, 'override_ad_object' ], 10, 2 );
			return $placement->output();
		}

		// Load all placements.
		$placements = wp_advads_get_placements();

		// Iterate through "ad-server" placements and look for the one with the public slug.
		foreach ( $placements as $placement ) {
			if ( $placement->is_type( 'server' ) && $public_slug === $placement->get_prop( 'ad-server-slug' ) ) {
				add_filter( 'advanced-ads-ad-select-override-by-ad', [ $this, 'override_ad_object' ], 10, 3 );

				return $placement->output();
			}
		}
	}

	/**
	 * Add allowed HTTP origins.
	 * Needed for the JavaScript-based implementation of the placement.
	 *
	 * @param array $origins Allowed HTTP origins.
	 * @return array $origins Allowed HTTP origins.
	 */
	public function add_allowed_origins( $origins ) {

		$embedding_urls = $this->get_embedding_urls();

		if ( is_array( $embedding_urls ) && count( $embedding_urls ) ) {
			$origins = array_merge( $origins, $embedding_urls );
		}
		return $origins;
	}

	/**
	 * Get the embedding URL array
	 *
	 * @return array
	 */
	private function get_embedding_urls() {
		$embedding_urls_raw = explode( ',', Options::instance()->get( 'pro.ad-server.embedding-url', '' ) );
		$embedding_urls     = [];

		foreach ( $embedding_urls_raw as $_url ) {
			$parsed_host = wp_parse_url( $_url, PHP_URL_HOST ) ?: $_url; // phpcs:ignore Universal.Operators.DisallowShortTernary
			$parsed_host = preg_replace( '/^www\./', '', $parsed_host );

			if ( $parsed_host ) {
				$embedding_urls[] = strtolower( $parsed_host );
			}
		}

		// Automatically include the WordPress site's domain.
		$wp_host = preg_replace( '/^www\./', '', strtolower( wp_parse_url( get_site_url(), PHP_URL_HOST ) ) );
		if ( $wp_host ) {
			$embedding_urls[] = $wp_host;
		}

		return array_unique( $embedding_urls );
	}
}
