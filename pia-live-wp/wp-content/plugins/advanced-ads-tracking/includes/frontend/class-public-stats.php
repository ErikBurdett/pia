<?php
/**
 * Frontend Public Stats.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking\Frontend;

use AdvancedAds\Tracking\Helpers;
use AdvancedAds\Tracking\Database;
use AdvancedAds\Framework\Utilities\Params;
use AdvancedAds\Framework\Interfaces\Initializer_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Frontend Public Stats.
 */
class Public_Stats implements Initializer_Interface {

	/**
	 * Runs this initializer.
	 *
	 * @return void
	 */
	public function initialize(): void {
		add_action( 'init', [ $this, 'register_endpoint' ] );
		add_action( 'query_vars', [ $this, 'register_query_vars' ] );
		add_action( 'template_redirect', [ $this, 'load_stats' ] );
		add_action( 'advanced-ads-public-stats-head', [ $this, 'enqueue_styles' ] );
	}

	/**
	 * Register the public stats endpoint.
	 *
	 * @return void
	 */
	public function register_endpoint(): void {
		add_rewrite_rule(
			'^' . Helpers::get_public_stats_slug() . '/([^/]+)/?$',
			'index.php?ad_stats=$matches[1]',
			'top'
		);
	}

	/**
	 * Register the public stats query var.
	 *
	 * @param array $vars Query vars.
	 *
	 * @return array
	 */
	public function register_query_vars( array $vars ): array {
		$vars[] = 'ad_stats';

		return $vars;
	}

	/**
	 * Load public stats template.
	 *
	 * @return void
	 */
	public function load_stats(): void {
		$hash_id = $this->get_ad_hasd_id();

		// Early bail!!
		if ( empty( $hash_id ) ) {
			return;
		}

		$ad_id = Database::get_ad_by_hash( $hash_id );
		if ( false !== $ad_id ) {
			require_once AA_TRACKING_ABSPATH . 'views/frontend/public-stats.php';
			die();
		}
	}

	/**
	 * Enqueue styles for the public stats page.
	 *
	 * @return void
	 */
	public function enqueue_styles(): void {
		wp_enqueue_scripts();
		echo "\n";
		wp_print_styles(
			[
				wp_advads_tracking()->registry->prefix_it( 'jquery-ui' ),
				wp_advads_tracking()->registry->prefix_it( 'jqplot' ),
				wp_advads_tracking()->registry->prefix_it( 'public-stats' ),
			]
		);

		echo "\n";
		wp_print_scripts(
			[
				wp_advads_tracking()->registry->prefix_it( 'frontend-public-stats' ),
			]
		);
	}

	/**
	 * Get the ad hash id from the request.
	 *
	 * @return string
	 */
	private function get_ad_hasd_id(): string {
		global $wp_rewrite;

		// If permalinks are "Plain", extract from QueryString.
		$qs = Params::get( Helpers::get_public_stats_slug() );
		if ( $qs ) {
			return sanitize_text_field( $qs );
		}

		// If permalinks are "Plain", extract from REQUEST_URI.
		if ( $wp_rewrite->using_index_permalinks() || ! $wp_rewrite->using_permalinks() ) {
			if ( preg_match( '/' . Helpers::get_public_stats_slug() . '\/([^\/]+)/', Params::server( 'REQUEST_URI' ), $matches ) ) {
				return sanitize_text_field( $matches[1] );
			}
		}

		return get_query_var( 'ad_stats' );
	}
}
