<?php // phpcs:ignoreFile
/**
 * Ads by hours main class
 *
 * @package AdvancedAds\Pro
 * @author  Advanced Ads <info@wpadvancedads.com>
 */

namespace Advanced_Ads_Pro\Ads_By_Hours;

use DateTimeImmutable;
use Advanced_Ads_Utils;
use AdvancedAds\Abstracts\Ad;

/**
 * Main module class
 */
class Module {
	const TIME_COOKIE = 'advanced_ads_browser_time';
	/**
	 * The singleton
	 *
	 * @var Module
	 */
	private static $instance;

	/**
	 * Whether to use client side time
	 *
	 * @var bool
	 */
	private $use_browser_time = false;

	/**
	 * Dashboard helper class
	 *
	 * @var Admin
	 */
	private $admin;

	/**
	 * Private constructor
	 */
	private function __construct() {
		if ( is_admin() ) {
			$this->admin = new Admin( $this );
		}

		add_filter( 'advanced-ads-can-display-ad', [ $this, 'can_display_by_hours' ], 10, 2 );
		$this->use_browser_time = defined( 'ADVANCED_ADS_ADS_BY_BROWSER_HOUR' ) && ADVANCED_ADS_ADS_BY_BROWSER_HOUR;

		if ( $this->use_browser_time ) {
			add_filter( 'advanced-ads-pro-ad-needs-backend-request', [ $this, 'force_cb' ], 10, 2 );
			add_filter( 'advanced-ads-pro-passive-cb-for-ad', [ $this, 'add_passive_cb_info' ], 10, 2 );
			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		}
	}

	/**
	 * Adds info about hours to passive CB ad
	 *
	 * @param array           $passive_cb_info information used by passive cb to decide whether the ad can be displayed.
	 * @param Ad $ad              the ad.
	 *
	 * @return array
	 */
	public function add_passive_cb_info( $passive_cb_info, $ad ) {
		$options = $this->get_ad_by_hour_options( $ad );

		if ( empty( $options['enabled'] ) ) {
			return $passive_cb_info;
		}

		if ( $options['start_hour'] . $options['start_min'] === $options['end_hour'] . $options['end_min'] ) {
			return $passive_cb_info;
		}

		$passive_cb_info['by_hours'] = "{$options['start_hour']}{$options['start_min']}_{$options['end_hour']}{$options['end_min']}";

		return $passive_cb_info;
	}

	/**
	 * @param string $cb_mode
	 * @param Ad $ad
	 *
	 * @return string
	 */
	public function force_cb( $cb_mode, $ad ) {
		return $ad->get_prop( 'ads_by_hours.enabled' ) && 'static' === $cb_mode ? 'passive' : $cb_mode;
	}

	/**
	 * Get the admin helper
	 *
	 * @return Admin
	 */
	public function admin() {
		return $this->admin;
	}

	/**
	 * Getter for `use_browserçtime`
	 *
	 * @return bool
	 */
	public function use_browser_time() {
		return $this->use_browser_time;
	}

	/**
	 * Enqueue scripts on the frontend
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			'AdvancedAds\Pro\ads-by-hours',
			trailingslashit( plugin_dir_url( BASE_FILE ) ) . 'assets/frontend.js',
			[ 'advanced-ads-pro/cache_busting' ],
			AAP_VERSION,
			false
		);
	}

	/**
	 * Should an ad be displayed given current DateTime
	 *
	 * @param bool            $can_display current value of $can_display.
	 * @param Ad $ad          current ad.
	 *
	 * @return bool|mixed
	 * @throws \Exception Can't create date object.
	 */
	public function can_display_by_hours( $can_display, $ad ) {
		if ( ! $can_display ) {
			return false;
		}

		$module_options = $this->get_ad_by_hour_options( $ad );

		if ( empty( $module_options['enabled'] ) ) {
			return true;
		}

		if ( $module_options['start_hour'] . $module_options['start_min'] === $module_options['end_hour'] . $module_options['end_min'] ) {
			return true;
		}

		if ( wp_doing_ajax() ) {
			// AJAX CB.
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			$post_vars = wp_unslash( $_POST );
			if ( 'advads_ad_select' === sanitize_key( $post_vars['action'] ) && ! empty( $post_vars['browserTime'] ) ) {
				$browser_time = explode( ':', sanitize_text_field( $post_vars['browserTime'] ) );
				$now          = (int) zeroise( $browser_time[0], 2 ) . zeroise( $browser_time[1], 2 );
				$start        = (int) $module_options['start_hour'] . $module_options['start_min'];
				$end          = (int) $module_options['end_hour'] . $module_options['end_min'];

				// Show if $now is between $start and $end.
				$can_display = $now > $start && $now < $end;

				if ( $start > $end ) {
					// In case of overnight, show if $now is after $start OR before $end.
					$can_display = $now > $start || $now < $end;
				}

				return apply_filters( 'advanced-ads-can-display-ads-by-hours', $can_display, $ad, $this );
			}
			// phpcs:enable
		}

		if ( $this->use_browser_time ) {
			$pro_options = \Advanced_Ads_Pro::get_instance()->get_options();
			if ( ! ( isset( $pro_options['cache-busting']['enabled'] ) && $pro_options['cache-busting']['enabled'] ) ) {
				// Use browser time but cache busting not enabled - abort.
				return apply_filters( 'advanced-ads-can-display-ads-by-hours', false, $ad, $this );
			}

			if ( 'off' === $ad->get_prop( 'cache-busting' ) ) {
				return apply_filters( 'advanced-ads-can-display-ads-by-hours', false, $ad, $this );
			}

			// otherwise let CB handle it.
			return apply_filters( 'advanced-ads-can-display-ads-by-hours', true, $ad, $this );
		}

		$now         = (int) ( new DateTimeImmutable( 'now', Advanced_Ads_Utils::get_wp_timezone() ) )->format( 'U' );
		$start       = (int) ( new DateTimeImmutable( "today {$module_options['start_hour']}:{$module_options['start_min']}", Advanced_Ads_Utils::get_wp_timezone() ) )->format( 'U' );
		$end         = (int) ( new DateTimeImmutable( "today {$module_options['end_hour']}:{$module_options['end_min']}", Advanced_Ads_Utils::get_wp_timezone() ) )->format( 'U' );
		$can_display = $start < $end ? $now > $start && $now < $end : $now > $start || $now < $end;

		return apply_filters( 'advanced-ads-can-display-ads-by-hours', $can_display, $ad, $this );
	}

	/**
	 * Get minutes intervals
	 *
	 * @return string[]
	 */
	public function get_minutes() {
		return [ '00', '15', '30', '45' ];
	}

	/**
	 * Get ads by hour option for a given ad, fall back to default value if not existing
	 *
	 * @param Ad $ad current ad.
	 *
	 * @return array
	 */
	public function get_ad_by_hour_options( $ad ) {
		return $ad->get_prop( 'ads_by_hours' ) ?? [
			'enabled'    => false,
			'start_hour' => '00',
			'start_min'  => '00',
			'end_hour'   => '00',
			'end_min'    => '00',
		];
	}

	/**
	 * Get list of hours, from 00 to 23
	 *
	 * @return array
	 */
	public function get_hours() {
		$hours = [];
		for ( $i = 0; $i <= 23; $i++ ) {
			$hours [] = zeroise( $i, 2 );
		}

		return $hours;
	}

	/**
	 * Returns the singleton
	 *
	 * @return Module the singleton.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
