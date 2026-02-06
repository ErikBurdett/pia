<?php
/**
 * Admin Metaboxes.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking\Admin;

use AdvancedAds\Constants;
use AdvancedAds\Abstracts\Ad;
use AdvancedAds\Tracking\Helpers;
use AdvancedAds\Tracking\Database;
use AdvancedAds\Tracking\Public_Ad;
use AdvancedAds\Utilities\Sanitize;
use AdvancedAds\Tracking\Ad_Limiter;
use AdvancedAds\Framework\Interfaces\Integration_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Admin Metaboxes.
 */
class Metaboxes implements Integration_Interface {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'add_meta_boxes_' . Constants::POST_TYPE_AD, [ $this, 'add_meta_box' ] );
		add_action( 'advanced-ads-ad-params-after', [ $this, 'add_ad_params' ] );
		add_action( 'advanced-ads-ad-pre-save', [ $this, 'save_ad_options' ], 10, 2 );
	}

	/**
	 * Add meta box for stats
	 *
	 * @since 1.2.6
	 *
	 * @return void
	 */
	public function add_meta_box(): void {
		add_meta_box(
			'tracking-ads-box',
			esc_attr__( 'Statistics', 'advanced-ads-tracking' ),
			[ $this, 'display' ],
			Constants::POST_TYPE_AD,
			'normal',
			'low'
		);

		add_filter( 'advanced-ads-unhide-meta-boxes', [ $this, 'unhide_metabox' ] );
	}

	/**
	 * Add tracking meta box to list of meta boxes to unhide.
	 *
	 * @param array $meta_boxes array of meta box ids to not be hidden.
	 *
	 * @return array
	 */
	public function unhide_metabox( $meta_boxes ): array {
		$meta_boxes[] = 'tracking-ads-box';

		return $meta_boxes;
	}

	/**
	 * Render options for tracking meta box
	 *
	 * @since 1.2.6
	 *
	 * @return void
	 */
	public function display(): void {
		global $post, $woocommerce;

		$ad = wp_advads_get_ad( $post->ID );
		$this->display_warnings( $ad );

		// Early bail!!
		if ( Helpers::is_tracking_method( 'ga' ) ) {
			return;
		}

		// limiter options.
		$impression_limit = absint( $ad->get_prop( 'tracking.impression_limit' ) ?? 0 );
		$click_limit      = absint( $ad->get_prop( 'tracking.click_limit' ) ?? 0 );
		$use_clicks       = Helpers::is_clickable_type( $ad->get_type() );
		$sums             = Database::get_sums_for_ad( $ad->get_id(), $use_clicks );
		$limiter          = new Ad_Limiter( $ad->get_id() );

		// public stats.
		$public      = new Public_Ad( $ad->get_id() );
		$public_id   = $public->get_id();
		$public_link = $public->get_url();
		$public_name = $public->get_name();

		$billing_email    = false;
		$report_recip     = $ad->get_prop( 'tracking.report-recip' ) ?? '';
		$report_period    = Helpers::sanitize_report_period( $ad->get_prop( 'tracking.report-period' ) );
		$report_frequency = Helpers::sanitize_report_frequency( $ad->get_prop( 'tracking.report-frequency' ) );

		// If ad was sold via WooCommerce.
		$order_id = get_post_meta( $ad->get_id(), 'advanced_ads_selling_order', true );
		if ( $order_id && function_exists( 'wc_get_order' ) ) {
			$order         = \wc_get_order( $order_id );
			$newer_wc      = isset( $woocommerce->version ) && version_compare( $woocommerce->version, '3.0', '>=' );
			$billing_email = $newer_wc ? $order->get_billing_email() : $order->billing_email;
		}

		require_once AA_TRACKING_ABSPATH . 'views/admin/metaboxes/metabox-ad.php';
	}

	/**
	 * Add tracking options to ad edit page
	 *
	 * @param Ad $ad Ad object.
	 *
	 * @return void
	 */
	public function add_ad_params( Ad $ad ): void {
		if ( empty( $ad->get_id() ) ) {
			return;
		}

		$enabled              = $ad->get_prop( 'tracking.enabled' ) ?? 'default';
		$target               = $ad->get_prop( 'tracking.target' ) ?? 'default';
		$nofollow             = $ad->get_prop( 'tracking.nofollow' ) ?? 'default';
		$sponsored            = $ad->get_prop( 'tracking.sponsored' ) ?? 'default';
		$cloaking_enabled     = boolval( $ad->get_prop( 'tracking.cloaking' ) ?? false );
		$link                 = Helpers::get_ad_link( $ad );
		$cloaking_cb_disabled = has_filter( 'advanced-ads-ad-get-tracking.cloaking', '__return_true' )
			|| has_filter( 'advanced-ads-ad-get-tracking.cloaking', '__return_false' )
			|| has_filter( 'advanced-ads-ad-option-tracking.cloaking', '__return_true' )
			|| has_filter( 'advanced-ads-ad-option-tracking.cloaking', '__return_false' );

		$tracking_choices = [
			'default'  => __( 'default', 'advanced-ads-tracking' ),
			'disabled' => __( 'disabled', 'advanced-ads-tracking' ),
		];

		if ( Helpers::is_clickable_type( $ad->get_type() ) ) {
			$tracking_choices['clicks']      = __( 'clicks only', 'advanced-ads-tracking' );
			$tracking_choices['impressions'] = __( 'impressions only', 'advanced-ads-tracking' );
			$tracking_choices['enabled']     = __( 'impressions & clicks', 'advanced-ads-tracking' );
		} else {
			$tracking_choices['enabled'] = __( 'enabled', 'advanced-ads-tracking' );
		}

		include_once AA_TRACKING_ABSPATH . 'views/admin/ads/ad-tracking-params.php';
	}

	/**
	 * Save ad tracking options.
	 *
	 * @param Ad    $ad        Ad instance.
	 * @param array $post_data Post data array.
	 *
	 * @return void
	 */
	public function save_ad_options( Ad $ad, $post_data ): void {
		$raw_data = $post_data['tracking'] ?? [];
		$options  = $ad->get_prop( 'tracking', 'edit' );

		if ( empty( $raw_data ) ) {
			return;
		}

		$options['enabled']          = $raw_data['enabled'] ?? 'default';
		$options['cloaking']         = $raw_data['cloaking'] ?? false;
		$options['impression_limit'] = absint( $raw_data['impression_limit'] ?? 0 );
		$options['click_limit']      = absint( $raw_data['click_limit'] ?? 0 );
		$options['public-id']        = stripslashes( $raw_data['public-id'] ?? '' );
		$options['public-name']      = stripslashes( $raw_data['public-name'] ?? '' );

		$target_values     = [ 'default', 'same', 'new' ];
		$options['target'] = isset( $raw_data['target'] ) && in_array( $raw_data['target'], $target_values, true )
			? $raw_data['target'] : 'default';

		foreach ( [ 'nofollow', 'sponsored' ] as $relationship ) {
			$options[ $relationship ] = isset( $raw_data[ $relationship ] ) && in_array( $raw_data[ $relationship ], [ 'default', '1', '0' ], true )
				? $raw_data[ $relationship ] : 'default';
		}

		// Email reports.
		$options['report-recip']     = Sanitize::email_addresses( $raw_data['report-recip'] ?? '' );
		$options['report-period']    = Helpers::sanitize_report_period( $raw_data['report-period'] ?? false );
		$options['report-frequency'] = Helpers::sanitize_report_frequency( $raw_data['report-frequency'] ?? false );

		$ad->set_prop( 'tracking', $options );
	}

	/**
	 * Display warnings for the ad.
	 *
	 * @param Ad $ad Ad object.
	 *
	 * @return void
	 */
	private function display_warnings( $ad ): void {
		$warnings = [];

		// add warning if we are tracking with Analytics.
		if ( Helpers::is_tracking_method( 'ga' ) ) {
			$warnings[] = [
				'text'  => __( '<a href="https://wpadvancedads.com/share-custom-reports-google-analytics/?utm_source=advanced-ads&utm_medium=link&utm_campaign=edit-ad-reports-google-analytics" target="_blank">How to share Google Analytics ad reports with your customers.</a>', 'advanced-ads-tracking' ),
				'class' => 'advads-notice-inline advads-idea',
			];
		} elseif ( $ad->is_type( 'adsense' ) ) {
			$warnings[] = [
				'text'  => sprintf(
					'%s <a href="%s" target="_blank">%s</a>',
					__( 'The number of impressions and clicks can vary from those in your AdSense account.', 'advanced-ads-tracking' ),
					'https://wpadvancedads.com/manual/tracking-issues?utm_source=advanced-ads&utm_medium=link&utm_campaign=ad-edit-adsense#Different_numbers_compared_to_AdSense_and_other_ad_networks',
					__( 'Manual', 'advanced-ads-tracking' )
				),
				'class' => 'advads-notice-inline advads-idea',
			];
		}

		if ( ! empty( $warnings ) ) {
			require_once AA_TRACKING_ABSPATH . 'views/admin/metaboxes/metabox-ad-warnings.php';
		}
	}
}
