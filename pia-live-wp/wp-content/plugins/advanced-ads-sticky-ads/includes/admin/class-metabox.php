<?php
/**
 * Admin Metabox.
 *
 * @package AdvancedAds\StickyAds
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   1.9.0
 */

namespace AdvancedAds\StickyAds\Admin;

use AdvancedAds\Constants;
use AdvancedAds\Abstracts\Ad;
use AdvancedAds\Framework\Utilities\Params;
use AdvancedAds\Framework\Interfaces\Integration_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Admin Metabox.
 */
class Metabox implements Integration_Interface {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'admin_init', [ $this, 'add_meta_box' ] );
		add_action( 'advanced-ads-ad-pre-save', [ $this, 'save_ad' ], 10, 2 );
	}

	/**
	 * Add meta box for the ad parameters
	 *
	 * @since 1.0.1
	 */
	public function add_meta_box() {
		$post_id = Params::get( 'post', 0, FILTER_VALIDATE_INT );
		if ( ! $post_id ) {
			return;
		}

		$ad = wp_advads_get_ad( $post_id );
		if ( ! $ad ) {
			return;
		}

		$options = $ad->get_data();
		$enabled = $options['sticky']['enabled'] ?? false;

		// Return if not enabled, because this is deprecated.
		if ( ! $enabled ) {
			return;
		}

		add_meta_box(
			'ad-sticky-ads-box',
			__( 'Sticky Ads', 'advanced-ads-sticky' ),
			[ $this, 'render_metabox' ],
			Constants::POST_TYPE_AD,
			'normal',
			'low'
		);
	}

	/**
	 * Render options for ad parameters
	 *
	 * @since 1.0.0
	 */
	public function render_metabox() {
		global $post;

		$ad      = wp_advads_get_ad( $post->ID );
		$options = $ad->get_data();
		$options = $options['sticky'] ?? [];

		$enabled   = $options['enabled'] ?? false;
		$assistant = $options['assistant'] ?? false;
		$type      = $options['type'] ?? '';
		$top       = $options['position']['top'] ?? '';
		$right     = $options['position']['right'] ?? '';
		$bottom    = $options['position']['bottom'] ?? '';
		$left      = $options['position']['left'] ?? '';
		$width     = $options['position']['width'] ?? 0;

		require_once AA_STICKY_ADS_ABSPATH . 'views/admin/metabox.php';
	}

	/**
	 * Save ad sticky options.
	 *
	 * @param Ad    $ad        Ad instance.
	 * @param array $post_data Post data array.
	 *
	 * @return void
	 */
	public function save_ad( Ad $ad, $post_data ): void {
		$raw_data = $post_data['sticky'] ?? [];
		$options  = $ad->get_prop( 'sticky', 'edit' );

		if ( empty( $raw_data ) ) {
			return;
		}

		$options['enabled']   = absint( $raw_data['enabled'] ?? 0 );
		$options['type']      = $raw_data['type'] ?? '';
		$options['assistant'] = $raw_data['assistant'] ?? '';

		if ( ! empty( $raw_data['position'] ) ) {
			$options['position'] = [];
			foreach ( $raw_data['position'] as $position => $value ) {
				if ( '' !== $value ) {
					$options['position'][ $position ] = absint( $value );
				}
			}
		}

		$ad->set_prop( 'sticky', $options );
	}
}
