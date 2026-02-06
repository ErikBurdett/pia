<?php
/**
 * Wrappers.
 *
 * @package AdvancedAds\StickyAds
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   1.9.0
 */

namespace AdvancedAds\StickyAds;

use AdvancedAds\Abstracts\Ad;
use AdvancedAds\Abstracts\Group;
use AdvancedAds\Framework\Utilities\Arr;
use AdvancedAds\Framework\Utilities\Str;
use AdvancedAds\Framework\Utilities\Params;
use AdvancedAds\Framework\Utilities\Formatting;
use AdvancedAds\Framework\Interfaces\Integration_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Wrappers.
 */
class Wrappers implements Integration_Interface {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_filter( 'advanced-ads-ad-output', [ $this, 'after_ad_output' ], 10, 2 );
		add_filter( 'advanced-ads-group-output', [ $this, 'after_group_output' ], 10, 2 );
		add_filter( 'advanced-ads-output-wrapper-options', [ $this, 'add_wrapper_options' ], 20, 2 );

		add_filter( 'advanced-ads-output-wrapper-options-group', [ $this, 'add_wrapper_options_group' ], 10, 2 );
		add_filter( 'advanced-ads-output-wrapper-before-content', [ $this, 'add_button' ], 20, 2 );
		add_filter( 'advanced-ads-output-wrapper-before-content-group', [ $this, 'add_button_group' ], 20, 2 );

		add_filter( 'advanced-ads-can-display-placement', [ $this, 'placement_can_display' ], 10, 2 );
		add_filter( 'advanced-ads-pro-passive-cb-group-data', [ $this, 'after_group_output_passive' ], 11, 2 );
		add_filter( 'advanced-ads-set-wrapper', [ $this, 'create_ad_wrapper' ], 10, 2 );
	}

	/**
	 * Whether cache-busting module is enabled
	 *
	 * @return bool
	 */
	public function cb_enabled(): bool {
		static $cb;
		if ( null === $cb ) {
			if ( ! defined( 'AAP_VERSION' ) ) {
				$cb = false;

				return false;
			}
			$cb_options = \Advanced_Ads_Pro::get_instance()->get_options();
			if ( ! isset( $cb_options['cache-busting']['enabled'] ) ) {
				$cb = false;

				return false;
			}
			$cb = (bool) $cb_options['cache-busting']['enabled'] ?? false;
		}

		return $cb;
	}

	/**
	 * Add ID when creating ad wrapper
	 *
	 * @param array $wrapper wrapper data.
	 * @param Ad    $ad      the ad.
	 *
	 * @return array
	 */
	public function create_ad_wrapper( $wrapper, $ad ) {
		$placement = $ad->get_root_placement();

		if ( ! $placement || ! wp_advads_stickyads()->is_sticky_placement( $placement->get_type() ) ) {
			return $wrapper;
		}

		if ( ! empty( $wrapper['id'] ) ) {
			return $wrapper;
		}

		$wrapper['id'] = $ad->create_wrapper_id();

		return $wrapper;
	}

	/**
	 * Inject js code to move the ad into another element.
	 *
	 * @since 1.2.3
	 *
	 * @param string $content The ad content.
	 * @param Ad     $ad      Ad instance.
	 *
	 * @return string
	 */
	public function after_ad_output( $content, Ad $ad ): string {
		// Don’t use this if we are not in a sticky placement.
		$placement = $ad->get_root_placement();

		if ( ! $placement || ! wp_advads_stickyads()->is_sticky_placement( $placement->get_type() ) ) {
			return $content;
		}

		// Don’t inject script on a per ad-basis if this is a group.
		if ( $ad->is_parent_group() ) {
			return $content;
		}

		$wrapper = $ad->create_wrapper();

		if ( isset( $wrapper['id'] ) ) {
			$placement = $ad->get_parent();
			$width     = $placement->get_prop( 'sticky.placement_width' ) ?? $ad->get_width();
			$height    = $placement->get_prop( 'sticky.placement_height' ) ?? $ad->get_height();
			return $this->build_sticky_output( $content, $placement->get_type(), $ad, $wrapper['id'], $width, $height );
		}

		return $content;
	}

	/**
	 * Inject js code to move the group into another element.
	 *
	 * @since 1.4.6.1
	 *
	 * @param string $content The ad content.
	 * @param Group  $group   Group instance.
	 *
	 * @return string
	 */
	public function after_group_output( $content, Group $group ): string {
		$placement = $group->get_root_placement();

		if ( ! $placement ) {
			return $content;
		}

		$wrapper = $group->create_wrapper();

		if ( isset( $wrapper['id'] ) ) {
			$width  = $group->get_parent()->get_prop( 'width' ) ?? 0;
			$height = $group->get_parent()->get_prop( 'height' ) ?? 0;
			return $this->build_sticky_output( $content, $group->get_parent()->get_type(), $group, $wrapper['id'], $width, $height );
		}

		return $content;
	}

	/**
	 * Add sticky options to the ad wrapper.
	 *
	 * @since 1.2.3
	 *
	 * @param array $options Wrapper options.
	 * @param Ad    $ad      Ad instance.
	 *
	 * @return array
	 */
	public function add_wrapper_options( $options, Ad $ad ): array {
		$placement = $ad->get_root_placement();

		if ( ! $placement ) {
			return $options;
		}

		// Don’t use this if we are not in a sticky ad.
		if ( ! wp_advads_stickyads()->is_sticky_placement( $placement->get_type() ) ) {
			return $options;
		}

		if ( $ad->is_parent_group() ) {
			$ad_options = $ad->get_prop( 'close' );
			if ( isset( $ad_options['enabled'] ) && $ad_options['enabled'] ) {
				$options['style']['position'] = 'relative';
			}
			return $options;
		}

		$width = ! empty( $placement->get_prop( 'placement_width' ) ) ? $placement->get_prop( 'placement_width' ) : $ad->get_width();
		return $this->add_wrapper_options_to_ad_or_group( $options, $ad, $width );
	}

	/**
	 * Add sticky options to the group wrapper.
	 *
	 * @param array $options Wrapper options.
	 * @param Group $group   The group object.
	 *
	 * @return array
	 */
	public function add_wrapper_options_group( $options, Group $group ) {
		$placement = $group->get_root_placement();

		if ( ! $placement || ! wp_advads_stickyads()->is_sticky_placement( $placement->get_type() ) ) {
			return $options;
		}

		if ( empty( $options['id'] ) ) {
			$options['id'] = wp_advads()->get_frontend_prefix() . wp_rand();
		}

		$width     = ! empty( $group->get_prop( 'placement_width' ) ) ? $group->get_prop( 'placement_width' ) : 0;
		$add_width = $group->is_type( 'slider' ) && $width;

		return $this->add_wrapper_options_to_ad_or_group( $options, $group, $width, $add_width );
	}

	/**
	 * Add the close button to the wrapper
	 *
	 * @since 1.4.1
	 *
	 * @param string $content Ad content.
	 * @param Ad     $ad      Ad instance.
	 *
	 * @return string
	 */
	public function add_button( $content, Ad $ad ) {
		$placement = $ad->get_root_placement();
		if ( ! $placement ) {
			return $content;
		}
		$options   = $placement->get_prop( 'close' );
		$top_level = ! $ad->get_parent() || $ad->is_parent_placement();
		if ( $top_level && isset( $options['enabled'] ) && $options['enabled'] ) {
			$content .= $this->build_close_button( $options );
		}

		return $content;
	}

	/**
	 * Add the close button to the group wrapper.
	 *
	 * @param string $content Group content.
	 * @param Group  $group   Group instance.
	 *
	 * @return string
	 */
	public function add_button_group( $content, Group $group ) {
		$placement = $group->get_root_placement();

		if ( ! $placement ) {
			return $content;
		}

		$cb = $placement->get_prop( 'cache-busting' );

		// Don't use this if Refresh interval is enabled and the group is nested inside an ad.
		if ( $this->cb_enabled() && 'off' !== $cb && Formatting::string_to_bool( $group->get_prop( 'options.refresh.enabled' ) ) && ! $group->get_prop( 'is_top_level' ) ) {
			return $content;
		}

		$close     = $placement->get_prop( 'close' );
		$top_level = ! $group->get_parent() || $group->is_parent_placement();
		if ( $top_level && isset( $close['enabled'] ) && $close['enabled'] ) {
			$content .= $this->build_close_button( $close );
		}

		return $content;
	}

	/**
	 * Check if placement was closed with a cookie before
	 *
	 * @since 1.4.1
	 *
	 * @param bool $check Whether placement can be displayed or not.
	 * @param int  $id    Placement id.
	 *
	 * @return bool Whether placement can be displayed or not, false if placement was closed for this user
	 */
	public function placement_can_display( $check, $id = 0 ) {
		$placement = wp_advads_get_placement( $id );
		$options   = $placement->get_data();

		if ( ! isset( $options['close']['enabled'] ) || ! $options['close']['enabled'] ) {
			return $check;
		}

		if ( isset( $options['close']['timeout_enabled'] ) && $options['close']['timeout_enabled'] ) {
			$slug = sanitize_title( $placement->get_slug() );
			if ( Params::cookie( 'timeout_placement_' . $slug ) ) {
				return false;
			}
		}

		return $check;
	}

	/**
	 * Inject js code to move ad group into another element (passive cache-busting).
	 *
	 * @param array $group_data Data to inject after the group.
	 * @param Group $group      Group instance.
	 *
	 * @since untagged
	 */
	public function after_group_output_passive( $group_data, Group $group ) {
		// Don’t use this if we are not in a sticky placement.
		if ( ! $group->is_parent_placement() || ! wp_advads_stickyads()->is_sticky_placement( $group->get_parent()->get_type() ) ) {
			return $group_data;
		}

		$wrapper = $group->create_wrapper();
		if ( isset( $wrapper['id'] ) ) {
			$width  = $group->get_parent()->get_prop( 'width' ) ?? 0;
			$height = $group->get_parent()->get_prop( 'height' ) ?? 0;

			$js_output = $this->build_sticky_output( '', $group->get_parent()->get_type(), $group, $wrapper['id'], $width, $height );

			$group_data['group_wrap'][] = [ 'after' => $js_output ];
		}

		return $group_data;
	}

	/**
	 * Builds the sticky ad output.
	 *
	 * @since 1.4.1
	 *
	 * @param string   $content    The content of the ad.
	 * @param string   $type       The type of the ad.
	 * @param Ad|Group $entity     The entity instance.
	 * @param string   $wrapper_id The ID of the wrapper element.
	 * @param int      $width      The width of the ad.
	 * @param int      $height     The height of the ad.
	 *
	 * @return string The built sticky ad output.
	 */
	private function build_sticky_output( $content = '', $type = '', $entity = [], $wrapper_id = '', $width = 0, $height = 0 ) {
		$placement = $entity->get_root_placement();
		if ( ! $placement ) {
			return $content;
		}

		// Don’t use this if we are not in a sticky ad.
		if ( '' === $type || ! wp_advads_stickyads()->is_sticky_placement( $type ) ) {
			return $content;
		}

		$target             = '';
		$options            = [];
		$fixed              = $placement->get_prop( 'sticky_is_fixed' ) ? true : false;
		$centered           = false;
		$can_convert_to_abs = false;
		$width_missing      = false;

		// Whether we can convert 'fixed' position to 'absolute' in case 'fixed' is not supported.
		$sticky_element = $placement->get_prop( 'sticky_element' ) ?? '';

		switch ( $type ) {
			case 'sticky_left_sidebar':
			case 'sticky_right_sidebar':
				if ( '' !== $sticky_element ) {
					$target = $sticky_element;
				} else {
					$options[] = 'target:"wrapper"';
					$options[] = 'sticky_left_sidebar' === $type ? 'offset:"left"' : 'offset:"right"';
				}
				$width_missing = empty( $width );
				break;
			case 'sticky_header':
			case 'sticky_footer':
				$centered           = true;
				$can_convert_to_abs = true;
				break;
			case 'sticky_left_window':
			case 'sticky_right_window':
				$target             = 'body';
				$can_convert_to_abs = true;
				break;
			default:
				return $content;
		}

		// Show warning, if width is missing.
		if ( $width_missing ) {
			$content .= '<script>console.log("Advanced Ads Sticky: Can not place sticky ad due to missing width attribute of the ad.");</script>';
		}

		$content .= '<script>( window.advanced_ads_ready || jQuery( document ).ready ).call( null, function() {';
		$content .= 'var wrapper_id = "#' . $wrapper_id . '"; var $wrapper = jQuery( wrapper_id );';

		$cache_busting_elementid = $entity->get_prop( 'cache_busting_elementid' );

		if ( wp_doing_ajax() && 'advads_ad_select' === Params::post( 'action' ) ) {
			$cache_busting_elementid = Arr::get( $entity->get_prop( 'ad_args' ), 'cache_busting_elementid' );
		}

		if ( ! empty( $cache_busting_elementid ) ) {
			$content         = '<script>advads.move("#' . $cache_busting_elementid . '", "' . $target . '", { ' . implode( ',', $options ) . ' });</script>' . $content;
			$use_grandparent = true;
		} else {
			$content        .= 'advads.move( wrapper_id, "' . $target . '", { ' . implode( ',', $options ) . ' });';
			$use_grandparent = false;
		}

		$content .= 'window.advanced_ads_sticky_items = window.advanced_ads_sticky_items || {};'
			. 'advanced_ads_sticky_items[ "' . $wrapper_id . '" ] = { '
			. '"can_convert_to_abs": "' . $can_convert_to_abs . '", '
			. '"initial_css": $wrapper.attr( "style" ), '
			. '"modifying_func": function() { ';

		if ( $fixed ) {
			$options = [
				'use_grandparent' => $use_grandparent,
				'offset'          => 'sticky_left_sidebar' === $type ? 'left' : 'right',
			];

			// Add is_invisible option, if trigger and duration are set.
			$trigger = $placement->get_prop( 'sticky.trigger' );
			if ( Str::is_non_empty( $trigger ) ) {
				$options['is_invisible'] = true;
			}

			$options  = wp_json_encode( $options );
			$content .= 'advads.fix_element( $wrapper, ' . $options . ' );';
		} elseif ( in_array( $type, [ 'sticky_left_sidebar', 'sticky_right_sidebar' ], true ) ) {
			$options  = [ 'use_grandparent' => $use_grandparent ];
			$options  = wp_json_encode( $options );
			$content .= 'advads.set_parent_relative( $wrapper, ' . $options . ' );';
		}

		if ( $centered ) {
			// Use width to center the ad might be resent, if background given.
			if ( $width ) {
				$content .= '$wrapper.css("width", ' . absint( $width ) . ');';
			}

			// Center element with text-align, if background is selected.
			$bg_color = $placement->get_prop( 'sticky_bg_color' );
			if ( Str::is_non_empty( $bg_color ) ) {
				// Check if there is a display setting already (maybe due to timeout.
				$trigger  = $placement->get_prop( 'sticky.trigger' );
				$display  = Str::is_non_empty( $trigger ) ? 'none' : 'block';
				$content .= '$wrapper.css({ textAlign: "center", display: "' . $display . '", width: "auto" });';
			} elseif ( $width ) {
				$content .= 'advads.center_fixed_element( $wrapper );';
			} else {
				$content .= '$wrapper.css({ "-webkit-transform": "translateX(-50%)", "-moz-transform": "translateX(-50%)", "transform": "translateX(-50%)", "left": "50%", "margin-right": "-50%" });';
			}
		}

		// Center ad container vertically.
		$center_vertical = $placement->get_prop( 'sticky_center_vertical' );
		if ( $center_vertical ) {
			// Use height to center the ad.
			if ( $height ) {
				$content .= '$wrapper.css("height", ' . absint( $height ) . ');';

			}
			$content .= 'advads.center_vertically( $wrapper );';
		}

		// Choose effect and duration.
		$effect        = '';
		$trigger       = $placement->get_prop( 'sticky.trigger' );
		$sticky_effect = $placement->get_prop( 'sticky.effect' );
		if ( Str::is_non_empty( $trigger ) && $sticky_effect ) {

			$duration = $placement->get_prop( 'sticky.duration' ) ? absint( $placement->get_prop( 'sticky.duration' ) ) : 0;

			switch ( $sticky_effect ) {
				case 'fadein':
					$effect = "fadeIn($duration).";
					break;
				case 'slidedown':
					$effect = "slideDown($duration).";
					break;
				default:
					$effect = "show($duration).";
			}
		}

		// Use trigger.
		if ( $trigger ) {
			$effect .= "css( 'display', 'block' );";

			switch ( $trigger ) {
				case 'effect':
					$content .= '$wrapper.' . $effect;
					break;
				case 'timeout':
					$delay    = $placement->get_prop( 'sticky.delay' ) ? absint( $placement->get_prop( 'sticky.delay' ) ) * 1000 : 0;
					$content .= 'setTimeout( function() { $wrapper.trigger( "advads-sticky-trigger" ).' . $effect . "}, $delay );";
					break;
			}
		}

		$content = $this->close_script( $content, $entity, $wrapper_id );

		// End of modifying function declaration.
		$content .= "}};\n";

		// Check if the function for waiting until images are ready exists.
		$content .= 'if ( advads.wait_for_images ) { ' . "\n";
		$content .= '    var sticky_wait_for_images_time = new Date().getTime();' . "\n";
		$content .= '    $wrapper.data( "sticky_wait_for_images_time", sticky_wait_for_images_time );' . "\n";
		$content .= '    advads.wait_for_images( $wrapper, function() {' . "\n";
		$content .= '        // At the moment when this function is called, it is possible that ' . "\n";
		$content .= '        // the placement has been updated using "Reload ads on resize" feature of Responsive add-on ' . "\n";
		$content .= '        if ( $wrapper.data( "sticky_wait_for_images_time" ) === sticky_wait_for_images_time ) {' . "\n";
		$content .= '            advanced_ads_sticky_items[ "' . $wrapper_id . '" ]["modifying_func"]();' . "\n";
		$content .= '        } ' . "\n";
		$content .= '    } );' . "\n";
		$content .= '} else { ' . "\n";
		$content .= '    advanced_ads_sticky_items[ "' . $wrapper_id . '" ]["modifying_func"]();' . "\n";
		$content .= '};' . "\n";

		// End of document ready.
		$content .= '});</script>';

		return $content;
	}

	/**
	 * Add the javascript for close and timeout feature
	 *
	 * This method adds the necessary JavaScript code for the close and timeout feature to the given content.
	 *
	 * @since 1.4.1
	 *
	 * @param string   $content    Existing content.
	 * @param Ad|Group $entity     The entity instance.
	 * @param string   $wrapper_id The ID of the wrapper element.
	 *
	 * @return string $content Modified content.
	 */
	private function close_script( $content, $entity = false, $wrapper_id = '' ): string {
		$placement = $entity->get_root_placement();
		$close     = $placement->get_prop( 'close' );
		if ( isset( $close['enabled'] ) && $close['enabled'] ) {
			$script = 'jQuery( "#' . $wrapper_id . '" ).on( "click", "span", function() { advads.close( "#' . $wrapper_id . '" ); ';
			if ( ! empty( $close['timeout_enabled'] ) ) {
				$timeout = absint( $close['timeout'] ) ? absint( $close['timeout'] ) : null;
				$script .= 'advads.set_cookie( "timeout_placement_' . sanitize_title( $placement->get_slug() ) . '", 1, ' . $timeout . ');';
			}
			$content .= $script . '});';
		}

		return $content;
	}

	/**
	 * Add sticky wrapper options to ad or group.
	 *
	 * @param array    $options   Wrapper options.
	 * @param Ad|group $entity    Ad/Group instance.
	 * @param int      $width     Width of the wrapper.
	 * @param bool     $add_width Whether to add width to the wrapper.
	 *
	 * @return array
	 */
	private function add_wrapper_options_to_ad_or_group( $options, $entity, $width, $add_width = false ): array {
		$placement = $entity->get_root_placement();
		if ( wp_advads_stickyads()->is_sticky_placement( $placement->get_type() ) ) {
			$width = absint( $width );

			if ( ! empty( $entity->get_prop( 'sticky_is_fixed' ) ) ) {
				$options['class'][] = wp_advads_stickyads()->get_sticky_class();
			}

			$bg_color = $placement->get_prop( 'sticky_bg_color' );
			switch ( $placement->get_type() ) {
				case 'sticky_header':
					$options['style']['position'] = 'fixed';
					$options['style']['top']      = '0';

					if ( Str::is_non_empty( $bg_color ) ) {
						$options['style']['left']             = '0';
						$options['style']['right']            = '0';
						$options['style']['background-color'] = $bg_color;
					}
					$options['style']['z-index'] = '10000';
					$options['class'][]          = wp_advads_stickyads()->get_sticky_class();
					break;
				case 'sticky_footer':
					$options['style']['position'] = 'fixed';
					$options['style']['bottom']   = '0';
					if ( Str::is_non_empty( $bg_color ) ) {
						$options['style']['left']             = '0';
						$options['style']['right']            = '0';
						$options['style']['background-color'] = $bg_color;
					}
					$options['style']['z-index'] = '10000';
					$options['class'][]          = wp_advads_stickyads()->get_sticky_class();
					break;
				case 'sticky_left_sidebar':
					$options['style']['position'] = 'absolute';
					$options['style']['display']  = 'inline-block';

					if ( $width ) {
						$options['style']['left'] = '-' . $width . 'px';
					} else {
						$options['style']['right'] = '100%';
					}

					$options['style']['top']     = '0';
					$options['style']['z-index'] = '10000';
					break;
				case 'sticky_right_sidebar':
					$options['style']['position'] = 'absolute';
					$options['style']['display']  = 'inline-block';

					if ( $width ) {
						$options['style']['right'] = '-' . absint( $width ) . 'px';
					} else {
						$options['style']['left'] = '100%';
					}

					$options['style']['top']     = '0';
					$options['style']['z-index'] = '10000';
					break;
				case 'sticky_left_window':
					$options['style']['position'] = 'absolute';
					$options['style']['display']  = 'inline-block';
					$options['style']['left']     = '0px';
					$options['style']['top']      = '0';
					$options['style']['z-index']  = '10000';
					break;
				case 'sticky_right_window':
					$options['style']['position'] = 'absolute';
					$options['style']['display']  = 'inline-block';
					$options['style']['right']    = '0px';
					$options['style']['top']      = '0';
					$options['style']['z-index']  = '10000';
					break;
				default:
					break;
			}

			// Hide ad if sticky trigger is given.
			$trigger = $placement->get_prop( 'sticky.trigger' );
			if ( Str::is_non_empty( $trigger ) ) {
				$options['style']['display'] = 'none';
			}

			if ( $add_width ) {
				$options['style']['width'] = absint( $width ) . 'px';
			}
		}

		return $options;
	}

	/**
	 * Build the close button
	 *
	 * @since 1.4.1
	 *
	 * @param array $options original [close] part of the ad options array.
	 */
	private function build_close_button( $options ) {
		$closebutton = '';

		if ( ! empty( $options['where'] ) && ! empty( $options['side'] ) ) {
			$side     = 'right';
			$opposite = 'left';
			$offset   = 'inside' === $options['where'] ? '0' : '-15px';
			$prefix   = wp_advads()->get_frontend_prefix();

			if ( 'left' === $options['side'] ) {
				$side     = 'left';
				$opposite = 'right';
			}

			// Add a dummy `onclick` attribute so that the `click` event gets fired in all browsers.
			$styles = [
				'width: 15px;',
				'height: 15px;',
				'background: #fff;',
				'position: relative;',
				'line-height: 15px;',
				'text-align: center;',
				'cursor: pointer;',
				'z-index: 10000;',
				sprintf( '%s: %s;', $side, $offset ),
				sprintf( 'float: %s;', $side ),
				sprintf( 'margin-%s: -15px;', $opposite ),
			];

			$closebutton = sprintf(
				'<span class="%s" onclick="void(0)" title="%s" style="%s">×</span>',
				$prefix . 'close-button',
				__( 'close', 'advanced-ads-sticky' ),
				implode( '', $styles )
			);
		}

		return $closebutton;
	}
}
