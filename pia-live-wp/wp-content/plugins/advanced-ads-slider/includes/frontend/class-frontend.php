<?php
/**
 * Frontend Frontend.
 *
 * @package AdvancedAds\Slider
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   1.5.0
 */

namespace AdvancedAds\Slider\Frontend;

use AdvancedAds\Abstracts\Group;
use AdvancedAds\Framework\Interfaces\Integration_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Frontend Frontend.
 */
class Frontend implements Integration_Interface {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_scripts' ] );
		add_filter( 'advanced-ads-group-output-ad-ids', [ $this, 'output_ad_ids' ], 10, 5 );
		add_filter( 'advanced-ads-group-output-array', [ $this, 'output_slider_markup' ], 10, 2 );
		add_filter( 'advanced-ads-group-ad-count', [ $this, 'adjust_ad_group_number' ], 10, 2 );
		add_filter( 'advanced-ads-pro-passive-cb-group-data', [ $this, 'add_slider_markup_passive' ], 10, 3 );
	}

	/**
	 * Append js file in footer
	 *
	 * @since 1.0.0
	 */
	public function register_scripts() {
		if ( ! defined( 'ADVANCED_ADS_SLIDER_USE_CDN' ) ) {
			wp_enqueue_script( 'unslider-js', AA_SLIDER_BASE_URL . 'public/assets/js/unslider.min.js', [ 'jquery' ], AAS_VERSION, true );
			wp_enqueue_style( 'unslider-css', AA_SLIDER_BASE_URL . 'public/assets/css/unslider.css', [], AAS_VERSION );
		} else {
			// Using a CDN to prevend encoding issues in certain cases.
			wp_enqueue_script( 'unslider-js', 'https://cdnjs.cloudflare.com/ajax/libs/unslider/2.0.3/js/unslider-min.js', [ 'jquery' ], AAS_VERSION, true );
			wp_enqueue_style( 'unslider-css', 'https://cdnjs.cloudflare.com/ajax/libs/unslider/2.0.3/css/unslider.css', [], AAS_VERSION );
		}

		wp_enqueue_style( 'slider-css', AA_SLIDER_BASE_URL . 'public/assets/css/slider.css', [], AAS_VERSION );
		if ( ! defined( 'ADVANCED_ADS_NO_SWIPE' ) ) {
			wp_enqueue_script( 'unslider-move-js', AA_SLIDER_BASE_URL . 'public/assets/js/jquery.event.move.js', [ 'jquery' ], AAS_VERSION, true );
			wp_enqueue_script( 'unslider-swipe-js', AA_SLIDER_BASE_URL . 'public/assets/js/jquery.event.swipe.js', [ 'jquery' ], AAS_VERSION, true );
		}
	}

	/**
	 * Get ids from ads in the order they should be displayed
	 *
	 * @param array  $ordered_ad_ids Ad ids in the order from the main plugin.
	 * @param string $type           Group type.
	 * @param array  $ads            Array with ad objects.
	 * @param array  $weights        Array with ad weights.
	 * @param Group  $group          Group instance.
	 *
	 * @return array $ad_ids
	 */
	public function output_ad_ids( $ordered_ad_ids, $type, $ads, $weights, Group $group ) {
		if ( 'slider' === $type ) {
			// Shuffle if this was set or we are on AMP.
			$options = $group->get_prop( 'slider' );
			if (
				( $options && isset( $options['random'] ) ) ||
				( function_exists( 'advads_is_amp' ) && advads_is_amp() )
			) {
				return $group->shuffle_ads( $ads, $weights );
			} else {
				return array_keys( $weights );
			}
		}

		return $ordered_ad_ids;
	}

	/**
	 * Adjust the ad group number if the ad type is a slider.
	 *
	 * @param int|string $ad_count The number of ads, is an integer or string 'all'.
	 * @param Group      $group    Group instance.
	 *
	 * @return int|string The number of ads, either an integer or string 'all'.
	 */
	public function adjust_ad_group_number( $ad_count, Group $group ) {
		if ( ! $group->is_type( 'slider' ) || ( function_exists( 'advads_is_amp' ) && advads_is_amp() ) ) {
			return $ad_count;
		}

		return 'all';
	}

	/**
	 * Add extra output markup for slider group
	 *
	 * @param array $ad_content Array with ad contents.
	 * @param Group $group      Group instance.
	 *
	 * @return array $ad_content with extra markup
	 */
	public function output_slider_markup( array $ad_content, Group $group ) {
		if ( function_exists( 'advads_is_amp' ) && advads_is_amp() ) {
			return $ad_content;
		}

		if ( count( $ad_content ) <= 1 || ! $group->is_type( 'slider' ) ) {
			return $ad_content;
		}

		$markup = $this->get_slider_markup( $group );

		foreach ( $ad_content as $_key => $_content ) {
			$ad_content[ $_key ] = sprintf( $markup['each'], $_content );
		}

		$markup = $this->get_slider_markup( $group );
		array_unshift( $ad_content, $markup['before'] );
		array_push( $ad_content, $markup['after'] );

		return $ad_content;
	}

	/**
	 * Get markup to inject around each slide and around set of slides.
	 *
	 * @param Group $group      Group instance.
	 *
	 * @return array
	 */
	public function get_slider_markup( Group $group ) {
		$slider_options = self::get_slider_options( $group );

		$slider_var = '$' . preg_replace( '/[^\da-z]/i', '', $slider_options['init_class'] );

		$script = '<script>( window.advanced_ads_ready || jQuery( document ).ready ).call( null, function() {'
		. 'var ' . $slider_var . ' = jQuery( ".' . $slider_options['init_class'] . '" );'
		// Display all ads after slider is loaded to avoid all ads being displayed as a list'.
		. $slider_var . '.on( "unslider.ready", function() { jQuery( "div.custom-slider ul li" ).css( "display", "block" ); });'
		. $slider_var . '.unslider({ ' . $slider_options['settings'] . ' });'
		. $slider_var . '.on("mouseover", function() {' . $slider_var . '.unslider("stop");}).on("mouseout", function() {' . $slider_var . '.unslider("start");});});</script>';

		return [
			'before'  => '<div id="' . $slider_options['slider_id'] . '" class="custom-slider ' . $slider_options['init_class'] . ' ' . $slider_options['prefix'] . 'slider"><ul>',
			'after'   => '</ul></div>' . $script,
			'each'    => '<li>%s</li>',
			'min_ads' => 2,
		];
	}

	/**
	 * Add slider markup to passive cache-busting.
	 *
	 * @param array  $group_data  Group data.
	 * @param Group  $group       Group instance.
	 * @param string $element_id  Element ID.
	 */
	public function add_slider_markup_passive( $group_data, Group $group, $element_id ) {
		if ( $element_id && $group->is_type( 'slider' ) ) {
			$group_data['group_wrap'][] = $this->get_slider_markup( $group );
		}

		return $group_data;
	}

	/**
	 * Return slider options
	 *
	 * @param Group $group Group instance.
	 *
	 * @return array that contains slider options
	 */
	public static function get_slider_options( Group $group ) {
		$settings = [];
		$options  = $group->get_prop( 'options.slider' );

		if ( isset( $options['delay'] ) ) {
			$settings['delay']    = absint( $options['delay'] );
			$settings['autoplay'] = 'true';
			$settings['nav']      = 'false';
			$settings['arrows']   = 'false';
			$settings['infinite'] = 'true';
		}

		$settings = apply_filters( 'advanced-ads-slider-settings', $settings, $group );

		// Merge option keys and values in preparation for the option string.
		$setting_attributes = array_map(
			[ self::class, 'map_settings' ],
			array_values( $settings ),
			array_keys( $settings )
		);

		$settings = implode( ', ', $setting_attributes );

		$prefix            = wp_advads()->get_frontend_prefix();
		$slider_id         = $prefix . 'slider-' . $group->get_id();
		$slider_init_class = $prefix . 'slider-' . wp_rand();

		return [
			'prefix'     => $prefix,
			'slider_id'  => $slider_id,
			'init_class' => $slider_init_class,
			'settings'   => $settings,
		];
	}

	/**
	 * Helper function for array_map, see above
	 * needed for php prior 5.3
	 *
	 * @param string $value Value of the array.
	 * @param string $key   Key of the array.
	 */
	public static function map_settings( $value, $key ) {
		return $key . ':' . $value . '';
	}
}
