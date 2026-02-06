<?php
/**
 * Backend helper
 *
 * @package AdvancedAds\Pro
 * @author  Advanced Ads <info@wpadvancedads.com>
 */

namespace Advanced_Ads_Pro\Ads_By_Hours;

use Advanced_Ads_Utils;
use AdvancedAds\Abstracts\Ad;
use AdvancedAds\Constants;
use DateTime;

/**
 * Dashboard class
 */
class Admin {
	/**
	 * Ads by hours module class
	 *
	 * @var Module
	 */
	private $module;

	/**
	 * Constructor
	 *
	 * @param Module $module main module class.
	 */
	public function __construct( $module ) {
		$this->module = $module;
		if ( ! wp_doing_ajax() ) {
			add_action( 'post_submitbox_misc_actions', [ $this, 'publish_metabox_markup' ], 20 );
			add_filter( 'advanced-ads-ad-pre-save', [ $this, 'on_save' ], 10, 2 );
		}
	}

	/**
	 * Save ad options.
	 *
	 * @param Ad    $ad        Ad instance.
	 * @param array $post_data Post data array.
	 *
	 * @return void
	 */
	public function on_save( $ad, $post_data ) {
		$options['enabled'] = ! empty( $post_data['ads_by_hours']['enabled'] );
		$posted             = wp_unslash( $post_data['ads_by_hours'] ?? [] );

		if ( ! isset( $options['start_hour'], $options['start_min'], $options['end_hour'], $options['end_min'] ) ) {
			return;
		}

		$options['start_hour'] = in_array( $posted['start_hour'], $this->module->get_hours(), true ) ? sanitize_key( $posted['start_hour'] ) : '00';
		$options['start_min']  = in_array( $posted['start_min'], $this->module->get_minutes(), true ) ? sanitize_key( $posted['start_min'] ) : '00';
		$options['end_hour']   = in_array( $posted['end_hour'], $this->module->get_hours(), true ) ? sanitize_key( $posted['end_hour'] ) : '00';
		$options['end_min']    = in_array( $posted['end_min'], $this->module->get_minutes(), true ) ? sanitize_key( $posted['end_min'] ) : '00';

		$ad->set_prop( 'ads_by_hours', $options );
	}

	/**
	 * Get the warning about cache plugin
	 *
	 * @return string
	 */
	public function get_cache_plugin_warning() {
		return __( "A cache plugin has been detected. It is recommended to enable Cache Busting and check the visitor's time when displaying the ad on the frontend.", 'advanced-ads-pro' );
	}

	/**
	 * Get the message for when CB is not detected
	 *
	 * @return string
	 */
	public function get_cb_warning_message() {
		return __( "Showing ads depending on the visitor's time requires enabling Cache Busting.", 'advanced-ads-pro' );
	}

	/**
	 * Get localized hour to use in the publish metabox
	 *
	 * @param string $hour hour (00 to 23) to be localized.
	 *
	 * @return string
	 */
	public function get_localized_hour( $hour ) {
		// From the saved WP tme format, replace all constants that are not hour nor timezone, preserve white spaces.
		return ( new DateTime( "today $hour:00" ) )->format( preg_replace( '/[^gGhHaAeIOPTZ ]/', '', self::get_time_format() ) );
	}

	/**
	 * Get localized hour interval for the ad planning column
	 *
	 * @param Ad $ad Ad instance.
	 *
	 * @return array
	 */
	public function get_localized_intervals( $ad ) {
		$interval = $ad->get_prop( 'ads_by_hours', [] );
		if ( empty( $interval ) ) {
			return [];
		}

		return [
			'start' => ( new DateTime( "today {$interval['start_hour']}:{$interval['start_min']}" ) )->format( self::get_time_format() ),
			'end'   => ( new DateTime( "today {$interval['end_hour']}:{$interval['end_min']}" ) )->format( self::get_time_format() ),
		];
	}

	/**
	 * Show ads by hours inputs on the publish metabox
	 *
	 * @return void
	 */
	public function publish_metabox_markup() {
		if ( get_post_type() !== Constants::POST_TYPE_AD ) {
			return;
		}
		$post          = get_post();
		$ad            = wp_advads_get_ad( $post->ID );
		$options       = $this->module->get_ad_by_hour_options( $ad );
		$addon_options = \Advanced_Ads_Pro::get_instance()->get_options();
		require_once BASE_DIR . '/views/publish-metabox-inputs.php';
	}

	/**
	 * Get timezone used for the front end checks.
	 *
	 * @return string
	 */
	public function get_time_zone_string() {
		return $this->module->use_browser_time() ? __( 'viewer time zone', 'advanced-ads-pro' ) : Advanced_Ads_Utils::get_timezone_name();
	}

	/**
	 * Get the WP time format option
	 *
	 * @return string
	 */
	public static function get_time_format() {
		static $time_format;

		if ( is_null( $time_format ) ) {
			$time_format = get_option( 'time_format' );
		}

		return $time_format;
	}
}
