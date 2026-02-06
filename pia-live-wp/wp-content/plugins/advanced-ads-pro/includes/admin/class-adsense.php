<?php
/**
 * Backend helper for the AdSense fallback
 *
 * @package AdvancedAds\Pro
 * @author  Advanced Ads <info@wpadvancedads.com>
 */

namespace AdvancedAds\Pro\Admin;

use AdvancedAds\Options;
use AdvancedAds\Abstracts\Ad;
use AdvancedAds\Framework\Interfaces\Integration_Interface;

/**
 * Backend class
 */
class Adsense implements Integration_Interface {
	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'advanced-ads-gadsense-extra-ad-param', [ $this, 'ad_parameter_setting' ], 10, 3 );
		add_action( 'advanced-ads-ad-pre-save', [ $this, 'save_ad_callback' ], 10, 2 );
		add_action( 'admin_init', [ $this, 'add_setting_field' ] );
	}

	/**
	 * Add setting field on the AdSense tab of the settings page
	 *
	 * @return void
	 */
	public function add_setting_field() {
		add_settings_field(
			'adsense-fallback',
			__( 'Fallback ad/group', 'advanced-ads-pro' ),
			[ $this, 'render_adsense_tab_setting' ],
			'advanced-ads-adsense-settings-page',
			'advanced_ads_adsense_setting_section'
		);
	}

	/**
	 * Save fallback option when saving the ad
	 *
	 * @param Ad    $ad        the ad.
	 * @param array $post_data content of $_POST (without the post content).
	 *
	 * @return void
	 */
	public function save_ad_callback( $ad, $post_data ) {
		if ( empty( $post_data['adsense_fallback'] ) ) {
			return;
		}

		preg_match( '/^none|default|ad_.+|group_.+|$/', $post_data['adsense_fallback'], $m );

		if ( empty( $m[0] ) ) {
			return;
		}

		$ad->set_prop( 'adsense_fallback', $m[0] );
	}

	/**
	 * Add fallback settings on the ad edit page
	 *
	 * @param array  $params  extra template parameters.
	 * @param string $content ad content.
	 * @param Ad     $ad      the ad.
	 *
	 * @return void
	 */
	public function ad_parameter_setting( $params, $content, $ad ) {
		$this->render_fallback_setting( false, $ad );
	}

	/**
	 * Render setting field on the AdSense tab of the settings page
	 *
	 * @return void
	 */
	public function render_adsense_tab_setting() {
		$this->render_fallback_setting( true );
	}

	/**
	 * Render the fallback setting markup
	 *
	 * @param bool $is_global whether it's the global fallback or not.
	 * @param Ad   $ad        the adsense ad.
	 *
	 * @return void
	 */
	public function render_fallback_setting( $is_global, $ad = null ) {
		$global_fallback        = \AdvancedAds\Pro\Adsense::get_global_fallback();
		$global_fallback_object = \AdvancedAds\Pro\Adsense::get_global_fallback_object();
		$fallback               = $is_global ? $global_fallback : \AdvancedAds\Pro\Adsense::get_fallback( $ad );
		$cache_busting          = Options::instance()->get( 'pro.cache-busting' );
		$items                  = self::get_fallback_items();

		require_once AA_PRO_ABSPATH . 'views/admin/tables/ads/adsense-fallback.php';
	}

	/**
	 * List of available fallback ad or group for an unfilled AdSense ad
	 *
	 * @return array[]
	 */
	private static function get_fallback_items() {
		static $result;

		if ( null !== $result ) {
			return $result;
		}

		$result = [
			'ads'    => [],
			'groups' => [],
		];

		foreach ( wp_advads_get_all_ads() as $ad ) {
			if ( ! $ad->is_type( 'adsense' ) && $ad->is_status( 'publish' ) ) {
				$result['ads'][ $ad->get_id() ] = $ad;
			}
		}

		foreach ( wp_advads_get_all_groups() as $group ) {
			$result['groups'][ $group->get_id() ] = $group;
		}

		return $result;
	}
}
