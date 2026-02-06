<?php
/**
 * Frontend.
 *
 * @package AdvancedAds\StickyAds
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   1.9.0
 */

namespace AdvancedAds\StickyAds;

use AdvancedAds\Abstracts\Ad;
use AdvancedAds\Utilities\Conditional;
use AdvancedAds\Framework\Interfaces\Integration_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Frontend.
 */
class Frontend implements Integration_Interface {

	/**
	 * Collect the ids of placements.
	 *
	 * @var array
	 */
	private $placements_ids = null;

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_filter( 'advanced-ads-set-wrapper', [ $this, 'set_wrapper' ], 20, 2 );
		add_action( 'wp_footer', [ $this, 'footer_injection' ], 10 );
	}

	/**
	 * Append js file in footer
	 *
	 * @return void
	 */
	public function enqueue_scripts(): void {
		if ( Conditional::is_amp() ) {
			return;
		}

		$options = wp_advads_stickyads()->get_options();

		wp_enqueue_script( 'advanced-ads-sticky-footer-js', AA_STICKY_ADS_BASE_URL . 'assets/dist/sticky.js', [], AA_STICKY_ADS_VERSION, true );
		wp_localize_script(
			'advanced-ads-sticky-footer-js',
			'advanced_ads_sticky_settings',
			[
				'check_position_fixed' => $options['sticky']['check-position-fixed'] ?? '',
				'sticky_class'         => wp_advads_stickyads()->get_sticky_class(),
				'placements'           => $this->get_sticky_placements_ids(),
			]
		);
	}

	/**
	 * Injects the ads into the footer.
	 *
	 * @since 1.2.3
	 *
	 * @return void
	 */
	public function footer_injection(): void {
		foreach ( $this->get_sticky_placements_ids() as $placement_id ) {
			the_ad_placement( $placement_id );
		}
	}

	/**
	 * Set the ad wrapper options.
	 *
	 * @since 1.0.0
	 * @deprecated since 1.2.2  (16 Jul 2015)
	 *
	 * @param array $wrapper Wrapper options.
	 * @param Ad    $ad      Ad instance.
	 *
	 * @return array Wrapper options.
	 */
	public function set_wrapper( $wrapper, Ad $ad ) {
		$options = $ad->get_prop( 'sticky' ) ?? [];

		// Define basic layer options.
		if ( ! empty( $options['enabled'] ) && ! empty( $options['type'] ) ) {
			$wrapper['class'][]           = wp_advads_stickyads()->get_sticky_class();
			$wrapper['style']['position'] = 'fixed';
			$wrapper['style']['z-index']  = 9999;

			$type_func = 'set_wrapper_type_' . $options['type'];
			$this->$type_func( $wrapper, $options );
		}

		return $wrapper;
	}

	/**
	 * Sets the wrapper type to absolute position.
	 *
	 * This method updates the position style of the wrapper to 'absolute'.
	 *
	 * @param array $wrapper The wrapper array to be updated.
	 * @param array $options The options array.
	 *
	 * @return void
	 */
	private function set_wrapper_type_absolute( &$wrapper, $options ): void {
		$positions = [ 'top', 'right', 'bottom', 'left' ];

		foreach ( $positions as $position ) {
			if ( isset( $options['position'][ $position ] ) ) {
				$wrapper['style'][ $position ] = absint( $options['position'][ $position ] ) . 'px';
			}
		}
	}

	/**
	 * Sets the wrapper type to assistant sticky ad.
	 *
	 * This method updates the position style of the wrapper to 'assistant'.
	 *
	 * @param array $wrapper The wrapper element for the sticky ad.
	 * @param array $options The options for the sticky ad.
	 * @return void
	 */
	private function set_wrapper_type_assistant( &$wrapper, $options ): void {
		$width = absint( $options['position']['width'] );

		switch ( $options['assistant'] ) {
			case 'topleft':
				$wrapper['style']['top']  = 0;
				$wrapper['style']['left'] = 0;
				break;
			case 'topcenter':
				$wrapper['style']['margin-left'] = '-' . $width / 2 . 'px';
				$wrapper['style']['top']         = 0;
				$wrapper['style']['left']        = '50%';
				break;
			case 'topright':
				$wrapper['style']['top']   = 0;
				$wrapper['style']['right'] = 0;
				break;
			case 'centerleft':
				$wrapper['style']['bottom'] = '50%';
				$wrapper['style']['left']   = 0;
				break;
			case 'center':
				$wrapper['style']['margin-left'] = '-' . $width / 2 . 'px';
				$wrapper['style']['bottom']      = '50%';
				$wrapper['style']['left']        = '50%';
				break;
			case 'centerright':
				$wrapper['style']['bottom'] = '50%';
				$wrapper['style']['right']  = 0;
				break;
			case 'bottomleft':
				$wrapper['style']['bottom'] = 0;
				$wrapper['style']['left']   = 0;
				break;
			case 'bottomcenter':
				$wrapper['style']['margin-left'] = '-' . $width / 2 . 'px';
				$wrapper['style']['bottom']      = 0;
				$wrapper['style']['left']        = '50%';
				break;
			case 'bottomright':
				$wrapper['style']['bottom'] = 0;
				$wrapper['style']['right']  = 0;
				break;
		}
	}

	/**
	 * Retrieves an array of sticky placements.
	 *
	 * @return array The array of sticky placements.
	 */
	private function get_sticky_placements_ids(): array {
		if ( null === $this->placements_ids ) {
			$sticky_placements = [
				'sticky_left_sidebar',
				'sticky_right_sidebar',
				'sticky_header',
				'sticky_footer',
				'sticky_left_window',
				'sticky_right_window',
			];

			$this->placements_ids = [];
			$this->placements_ids = wp_advads_get_placements_by_types( $sticky_placements, 'ids' );
		}

		return $this->placements_ids;
	}
}
