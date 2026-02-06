<?php
/**
 * Add quick/bulk edit fields on the ad overview page.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking\Admin;

use AdvancedAds\Constants;
use AdvancedAds\Abstracts\Ad;
use AdvancedAds\Tracking\Helpers;
use AdvancedAds\Framework\Utilities\Params;

defined( 'ABSPATH' ) || exit;

/**
 * WP Integration.
 */
class Quick_Bulk_Edit {
	/**
	 * Hooks into WordPress
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		add_action( 'advanced-ads-quick-edit-fields', [ $this, 'add_quick_edit_fields' ] );
		add_filter( 'advanced-ads-quick-edit-save', [ $this, 'save_quick_edit' ] );

		add_action( 'advanced-ads-bulk-edit-fields', [ $this, 'add_bulk_edit_fields' ] );
		add_filter( 'advanced-ads-bulk-edit-has-change', [ $this, 'bulk_edit_has_changes' ] );
		add_filter( 'advanced-ads-bulk-edit-save', [ $this, 'save_bulk_edit' ] );
	}

	/**
	 * Enqueue assets.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		$screen = get_current_screen();

		if ( 'edit-' . Constants::POST_TYPE_AD !== $screen->id ) {
			return;
		}

		wp_advads_tracking()->registry->enqueue_style( 'screen-ads-listing-tracking' );
		wp_advads_tracking()->registry->enqueue_script( 'screen-ads-listing-tracking' );
	}

	/**
	 * Render quick edit inputs
	 *
	 * @return void
	 */
	public function add_quick_edit_fields() {
		include_once AA_TRACKING_ABSPATH . 'views/admin/ads/quick-edit.php';
	}

	/**
	 * Save ad edited with quick edit
	 *
	 * @param Ad $ad the ad being saved.
	 *
	 * @return Ad
	 */
	public function save_quick_edit( $ad ): Ad {
		$tracking_method  = Params::post( 'tracking_method' );
		$target_url       = ! empty( Params::post( 'target_url', '' ) ) ? esc_url_raw( Params::post( 'target_url', false ) ) : '';
		$target_window    = Params::post( 'target_window' );
		$nofollow         = Params::post( 'nofollow', false );
		$sponsored        = Params::post( 'sponsored', false );
		$report_recipient = ! empty( Params::post( 'report_recipient', '' ) ) ? sanitize_email( Params::post( 'report_recipient' ) ) : '';

		if ( $tracking_method ) {
			$ad->set_prop( 'tracking.enabled', $tracking_method );
		}

		if ( $target_window ) {
			$ad->set_prop( 'tracking.target', $target_window );
		}

		if ( false !== $nofollow ) {
			$ad->set_prop( 'tracking.nofollow', $nofollow );
		}

		if ( false !== $sponsored ) {
			$ad->set_prop( 'tracking.sponsored', $sponsored );
		}

		$ad->set_prop( 'tracking.cloaking', Params::post( 'cloak_link', false, FILTER_VALIDATE_BOOLEAN ) );
		$ad->set_prop( 'url', $target_url );
		$ad->set_prop( 'tracking.report-recip', $report_recipient );

		return $ad;
	}

	/**
	 * Add the bulk edit inputs
	 *
	 * @return void
	 */
	public function add_bulk_edit_fields(): void {
		include_once AA_TRACKING_ABSPATH . 'views/admin/ads/bulk-edit.php';
	}

	/**
	 * Check if bulk edit fields have changes.
	 *
	 * @param bool $has_change whether some ads have been changed.
	 *
	 * @return bool
	 */
	public function bulk_edit_has_changes( $has_change ): bool {
		$tracking_method  = Params::get( 'tracking_method', '-1' );
		$target_url       = esc_url_raw( Params::get( 'target_url', '' ) );
		$cloak_link       = Params::get( 'cloak_link', '-1' );
		$target_window    = Params::get( 'target_window', '-1' );
		$nofollow         = Params::get( 'nofollow', '-1' );
		$sponsored        = Params::get( 'sponsored', '-1' );
		$report_recipient = sanitize_email( Params::get( 'report_recipient', '' ) );

		if ( '-1' !== $tracking_method || ! empty( $target_url ) || '-1' !== $cloak_link || '-1' !== $target_window || '-1' !== $nofollow || '-1' !== $sponsored || ! empty( $report_recipient ) ) {
			$has_change = true;
		}

		return $has_change;
	}

	/**
	 * Save changes made during bulk edit
	 *
	 * @param Ad $ad current ad being saved.
	 *
	 * @return Ad
	 */
	public function save_bulk_edit( $ad ): Ad {
		$tracking_method  = Params::get( 'tracking_method', '-1' );
		$target_url       = Params::get( 'target_url', '' );
		$cloak_link       = Params::get( 'cloak_link', '-1' );
		$target_window    = Params::get( 'target_window', '-1' );
		$nofollow         = Params::get( 'nofollow', '-1' );
		$sponsored        = Params::get( 'sponsored', '-1' );
		$report_recipient = Params::get( 'report_recipient', '' );

		if ( '-1' !== $tracking_method && ( ! in_array( $tracking_method, [ 'clicks', 'impressions' ], true ) || Helpers::is_clickable_type( $ad->get_type() ) ) ) {
			$ad->set_prop( 'tracking.enabled', $tracking_method );
		}

		if ( ! empty( $target_url ) ) {
			$ad->set_prop( 'url', $target_url );
		}

		if ( '-1' !== $cloak_link ) {
			$ad->set_prop( 'tracking.cloaking', 'on' === $cloak_link );
		}

		if ( '-1' !== $target_window ) {
			$ad->set_prop( 'tracking.target', $target_window );
		}

		if ( '-1' !== $nofollow ) {
			$ad->set_prop( 'tracking.nofollow', $nofollow );
		}

		if ( '-1' !== $sponsored ) {
			$ad->set_prop( 'tracking.sponsored', $sponsored );
		}

		if ( ! empty( $report_recipient ) ) {
			$ad->set_prop( 'tracking.report-recip', $report_recipient );
		}

		return $ad;
	}
}
