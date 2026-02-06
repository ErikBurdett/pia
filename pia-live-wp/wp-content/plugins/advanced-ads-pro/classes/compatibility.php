<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

use AdvancedAds\Framework\Utilities\Params;

/**
 * Compatibility fixes with other plugins.
 *
 * @package AdvancedAds\Pro
 * @author  Advanced Ads <info@wpadvancedads.com>
 */

/**
 * Handles compatibility with various plugins and themes.
 */
class Advanced_Ads_Pro_Compatibility {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'after_setup_theme', [ $this, 'after_setup_theme' ] );

		// Set WPML Language.
		// Note: the "Language filtering for AJAX operations" feature of WPML does not work
		// because it sets cookie later then our ajax requests are sent.
		if (
			wp_doing_ajax() &&
			defined( 'ICL_SITEPRESS_VERSION' ) &&
			! empty( Params::request( 'wpml_lang' ) )
		) {
			do_action( 'wpml_switch_language', Params::request( 'wpml_lang' ) );
		}

		// Weglot plugin.
		if ( function_exists( 'weglot_get_current_full_url' ) ) {
			add_filter( 'advanced-ads-pro-display-condition-url-string', [ $this, 'weglot_get_current_full_url' ], 0 );
		}

		// Gravity forms plugin.
		add_action( 'wp_loaded', [ $this, 'gravity_forms_init' ] );
	}

	/**
	 * After the theme is loaded.
	 */
	public function after_setup_theme() {
		// Newspaper theme.
		if ( defined( 'TD_THEME_NAME' ) && 'Newspaper' === TD_THEME_NAME ) {
			$options = get_option( 'td_011' );
			// Check if lazy load is enabled (non-existent key or '').
			if ( empty( $options['tds_animation_stack'] ) ) {
				add_filter( 'advanced-ads-ad-image-tag-style', [ $this, 'newspaper_theme_disable_lazy_load' ] );
			}
		}
	}

	/**
	 * Newspaper theme: disable lazy load of the theme to prevent conflict with cache-busting/lazy-load of the Pro add-on.
	 *
	 * @param  string $style Styles.
	 *
	 * @return string
	 */
	public function newspaper_theme_disable_lazy_load( $style ) {
		$style .= 'opacity: 1 !important;';
		return $style;
	}

	/**
	 * Weglot plugin: Get the current full url that contains a lauguage.
	 *
	 * @param string $url_parameter Current URI string.
	 *
	 * @return string The modified URL parameter.
	 */
	public function weglot_get_current_full_url( $url_parameter ) {
		if ( wp_doing_ajax() ) {
			return $url_parameter;
		}

		$url_parsed    = wp_parse_url( weglot_get_current_full_url() );
		$url_parameter = $url_parsed['path'];
		if ( isset( $url_parsed['query'] ) ) {
			$url_parameter .= '?' . $url_parsed['query'];
		}

		return $url_parameter;
	}

	/**
	 * Gravity Forms plugin: Do JS initialization
	 *
	 * @return void
	 */
	public function gravity_forms_init() {
		if ( is_admin() || ! function_exists( 'gravity_form_enqueue_scripts' ) ) {
			return;
		}

		$has_ajaxcb_placement = false;
		$gravity_form_ads     = wp_advads_ad_query(
			[
				's'          => '[gravityform id=',
				'meta_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'key'     => 'allow_shortcodes',
					'value'   => '1',
					'compare' => 'LIKE',
				],
			]
		)->posts;

		if ( empty( $gravity_form_ads ) ) {
			return;
		}

		foreach ( wp_advads_get_placements() as $placement ) {
			if ( 'on' === $placement->get_prop( 'cache-busting' ) ) {
				$has_ajaxcb_placement = true;
				break;
			}
		}

		if ( ! $has_ajaxcb_placement ) {
			return;
		}

		foreach ( $gravity_form_ads as $gravity_form_ad ) {
			if ( preg_match( '#gravityform id="([0-9]+)".*#', $gravity_form_ad->post_content, $form_ids ) ) {
				gravity_form_enqueue_scripts( $form_ids[1], true );
			}
		}
	}
}
