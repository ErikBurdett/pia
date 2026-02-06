<?php
/**
 * Assets manager handles the registration of stylesheets and scripts required for plugin functionality.
 *
 * @package AdvancedAds
 * @author  Advanced Ads <info@wpadvancedads.com>
 */

namespace AdvancedAds\Pro;

use AdvancedAds\Framework\Assets_Registry;

/**
 * Pro's assets manager
 */
class Assets_Manager extends Assets_Registry {
	/**
	 * Base URL for plugin local assets.
	 *
	 * @return string
	 */
	public function get_base_url(): string {
		return AA_PRO_BASE_URL;
	}

	/**
	 * Prefix to use in handle to make it unique.
	 *
	 * @return string
	 */
	public function get_prefix(): string {
		return AA_PRO_SLUG;
	}

	/**
	 * Version for plugin local assets.
	 *
	 * @return string
	 */
	public function get_version(): string {
		return AAP_VERSION;
	}

	/**
	 * Register styles
	 *
	 * @return void
	 */
	public function register_styles(): void {
		$this->register_style( 'admin-styles', 'assets/admin.css' );
	}

	/**
	 * Register scripts
	 *
	 * @return void
	 */
	public function register_scripts(): void {
		$this->register_script(
			'main',
			'assets/dist/advanced-ads-pro.js',
			[ 'jquery' ],
			false,
			true
		);

		$this->localize_data();
	}

	/**
	 * Enqueue localize data
	 *
	 * phpcs:disable WordPress.WP.I18n.MissingTranslatorsComment
	 *
	 * @return void
	 */
	private function localize_data(): void {
		$cookies = [
			'cookie_path'   => COOKIEPATH,
			'cookie_domain' => COOKIE_DOMAIN,
		];

		wp_localize_script( $this->prefix_it( 'main' ), 'advanced_ads_cookies', $cookies );
	}
}
