<?php // phpcs:ignore WordPress.Files.FileName

use AdvancedAds\Abstracts\Ad;
use AdvancedAds\Abstracts\Group;
use AdvancedAds\Framework\Utilities\HTML;
use AdvancedAds\Framework\Utilities\Params;

/**
 * Class Advanced_Ads_Layer
 */
class Advanced_Ads_Layer {

	/**
	 * Holds plugin base class.
	 *
	 * @var Advanced_Ads_Layer_Plugin
	 */
	protected $plugin;

	/**
	 * Whether Fancybox is enabled.
	 *
	 * @var bool
	 */
	protected $fancybox_is_enabled;

	/**
	 * Array to hold placement ids.
	 *
	 * @var array
	 */
	private $placement_ids = [];

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->plugin = Advanced_Ads_Layer_Plugin::get_instance();
		add_action( 'plugins_loaded', [ $this, 'wp_plugins_loaded_ad_actions' ], 20 );
		add_filter( 'advanced-ads-set-wrapper', [ $this, 'set_wrapper_id' ], 21, 2 );

		if ( ! is_admin() ) {
			add_action( 'plugins_loaded', [ $this, 'wp_plugins_loaded' ] );
		}
	}

	/**
	 * Load actions and filters needed only for ad rendering
	 * this will make sure options get loaded for ajax and non-ajax-calls
	 */
	public function wp_plugins_loaded_ad_actions() {
		$advads_options            = $this->plugin->options();
		$this->fancybox_is_enabled = $advads_options['layer']['use-fancybox'] ?? 0;

		add_filter( 'advanced-ads-set-wrapper', [ $this, 'set_wrapper' ], 21, 2 );
		add_filter( 'advanced-ads-output-wrapper-options', [ $this, 'add_wrapper_options' ], 21, 2 );
		add_filter( 'advanced-ads-output-wrapper-options-group', [ $this, 'add_wrapper_options_group' ], 10, 2 );
		add_filter( 'advanced-ads-ad-output', [ $this, 'add_content_after' ], 20, 2 );
		add_filter( 'advanced-ads-group-output', [ $this, 'add_content_after_group' ], 10, 2 );
		add_filter( 'advanced-ads-pro-passive-cb-group-data', [ $this, 'after_group_output_passive' ], 11, 2 );

		if ( ! $this->fancybox_is_enabled ) {
			add_filter( 'advanced-ads-output-wrapper-before-content', [ $this, 'add_button' ], 20, 2 );
			add_filter( 'advanced-ads-output-wrapper-before-content-group', [ $this, 'add_button_group' ], 20, 2 );
		}

		add_filter( 'advanced-ads-can-display-placement', [ $this, 'placement_can_display' ], 11, 2 );
		add_filter( 'advanced-ads-can-display-ad', [ $this, 'can_display' ], 11, 2 );
	}

	/**
	 * Load actions and filters
	 */
	public function wp_plugins_loaded() {
		add_action( 'init', [ $this, 'collect_placements' ], 99 );
		add_action( 'wp_enqueue_scripts', [ $this, 'footer_scripts' ] );
		add_action( 'wp_head', [ $this, 'header_output' ] );
		add_action( 'wp_footer', [ $this, 'footer_injection' ], 10 );
	}

	/**
	 * Inject ad placement into footer.
	 *
	 * @since 1.2.4
	 */
	public function footer_injection() {
		foreach ( $this->placement_ids as $placement_id ) {
			the_ad_placement( $placement_id );
		}
	}

	/**
	 * Collect pop-up placement ids.
	 */
	public function collect_placements() {
		foreach ( wp_advads_get_placements() as $placement_id => $placement ) {
			if ( $placement->is_type( 'layer' ) ) {
				$this->placement_ids[] = $placement_id;
			}
		}
	}

	/**
	 * Add ID attribute to the ad wrapper
	 *
	 * @param array $wrapper the wrapper array.
	 * @param Ad    $ad      the ad object.
	 *
	 * @return array
	 */
	public function set_wrapper_id( $wrapper, $ad ) {
		$placement = $ad->get_root_placement();
		if ( ! $placement || ! $placement->is_type( 'layer' ) ) {
			return $wrapper;
		}

		if ( empty( $wrapper['id'] ) ) {
			$wrapper['id'] = $ad->create_wrapper_id();
		}

		return $wrapper;
	}

	/**
	 * Add sticky options to the ad wrapper.
	 *
	 * @since 1.2.4
	 *
	 * @param array $options Wrapper options.
	 * @param Ad    $ad      Ad instance.
	 *
	 * @return array
	 */
	public function add_wrapper_options( $options, Ad $ad ) {
		if ( ! $ad->is_parent_placement() ) {
			return $options;
		}

		// Early bail!!
		if ( ! $ad->get_parent()->is_type( 'layer' ) ) {
			return $options;
		}

		// New settings from the ad itself.
		$width  = $ad->get_width() ?? 0;
		$height = $ad->get_height() ?? 0;

		// Obsolete settings from layer placement.
		$layer = $ad->get_prop( 'layer_placement' );
		if ( ! $width ) {
			$width = ! empty( $layer['sticky']['position']['width'] ) ? absint( $layer['sticky']['position']['width'] ) : 0;
		}

		// Obsolete settings from layer placement.
		if ( ! $height ) {
			$height = ( ! empty( $layer['sticky']['position']['height'] ) ) ? absint( $layer['sticky']['position']['height'] ) : 0;
		}

		return $this->add_wrapper_options_to_ad_or_group( $options, $ad, $width, $height );
	}


	/**
	 * Add sticky options to the group wrapper.
	 *
	 * @param array $options Wrapper options.
	 * @param Group $group   Group instance.
	 *
	 * @return array
	 */
	public function add_wrapper_options_group( $options, Group $group ) {
		$placement = $group->get_root_placement();

		// Early bail!!
		if ( ! $placement || ! $placement->is_type( 'layer' ) ) {
			return $options;
		}

		$width     = absint( $group->get_parent()->get_prop( 'placement_width' ) ?? 0 );
		$height    = absint( $group->get_parent()->get_prop( 'placement_height' ) ?? 0 );
		$add_width = $group->is_type( 'slider' ) && $width;

		return $this->add_wrapper_options_to_ad_or_group( $options, $group, $width, $height, $add_width );
	}

	/**
	 * Add wrapper options to ad or group.
	 *
	 * @param array    $options   Wrapper options.
	 * @param Ad|Group $entity    Arguments passed to ads and groups from top level placements/ads/groups.
	 * @param int      $width     Width of the wrapper.
	 * @param int      $height    Height of the wrapper.
	 * @param bool     $add_width Whether to add width to the wrapper.
	 *
	 * @return array
	 */
	private function add_wrapper_options_to_ad_or_group( $options, $entity, $width, $height, $add_width = false ) {
		$placement = $entity->get_root_placement();

		if ( ! $placement || ! $placement->is_type( 'layer' ) ) {
			return $options;
		}

		$layer_class = $this->get_layer_class();

		$options['class'][]       = $layer_class;
		$options['data-width'][]  = $width;
		$options['data-height'][] = $height;

		$layer_settings = $placement->get_prop( 'layer_placement' );

		if ( ! empty( $layer_settings['effect'] ) && ! empty( $layer_settings['duration'] ) ) {
			$options['class'][] = 'advads-effect';
			$options['class'][] = 'advads-effect-' . $layer_settings['effect'];
			$options['class'][] = 'advads-duration-' . absint( $layer_settings['duration'] );
		}

		if ( isset( $layer_settings['trigger'] ) ) {
			// Add trigger options depending on trigger.
			switch ( $layer_settings['trigger'] ) {
				case '':
					$options['class'][] = $layer_class . '-onload';
					break;
				case 'stop':
					$options['class'][] = $layer_class . '-stop';
					break;
				case 'half':
					$options['class'][] = $layer_class . '-half';
					break;
				case 'custom':
					$options['class'][] = $layer_class . '-offset';
					if ( isset( $layer_settings['offset'] ) && $layer_settings['offset'] > 0 ) {
						$options['class'][] = $layer_class . '-offset-' . absint( $layer_settings['offset'] );
					}
					break;
				case 'exit':
					$options['class'][] = $layer_class . '-exit';
					break;
				case 'delay':
					$options['class'][]                   = $layer_class . '-delay';
					$options['data-advads-layer-delay'][] = isset( $layer_settings['delay_sec'] ) ? absint( $layer_settings['delay_sec'] ) * 1000 : 0;
					break;
			}
		} else {
			$options['class'][] = $layer_class . '-onload';
		}

		// Set background arguments (in form of a class).
		if ( ! empty( $layer_settings['background'] ) ) {
			$options['class'][] = 'advads-has-background';
			if ( ! empty( $layer_settings['background_click_close'] ) ) {
				$options['class'][] = 'advads-background-click-close';
			}
		}

		if ( isset( $layer_settings['close']['enabled'] ) && $layer_settings['close']['enabled'] ) {
			$options['class'][] = 'advads-close';
		}

		if ( ! empty( $layer_settings['auto_close']['trigger'] ) ) {
			$auto_close_delay = isset( $layer_settings['auto_close']['delay'] ) ? absint( $layer_settings['auto_close']['delay'] ) * 1000 : 0;
			if ( $auto_close_delay ) {
				$options['data-auto-close-delay'] = $auto_close_delay;
			}
		}

		$is_assistant = ! empty( $layer_settings['sticky']['assistant'] );
		if ( $is_assistant ) {
			$options['class'][]         = 'is-sticky';
			$options['data-position'][] = $layer_settings['sticky']['assistant'];
		}

		$options['style']['display'] = 'none';

		if ( $add_width ) {
			$options['style']['width'] = $width . 'px';
		}

		if ( $this->fancybox_is_enabled ) {
			$options['class'][] = 'use-fancybox';

			return $options;
		}

		$options['style']['z-index']   = '9999';
		$options['style']['position']  = 'fixed';
		$options['style']['max-width'] = '100%';
		$options['style']['width']     = $width ? $width . 'px' : '100%';

		if ( $is_assistant ) {
			switch ( $layer_settings['sticky']['assistant'] ) {
				case 'topleft':
					$options['style']['top']  = 0;
					$options['style']['left'] = 0;
					break;
				case 'topcenter':
					$options['style']['top']       = 0;
					$options['style']['left']      = '50%';
					$options['style']['transform'] = 'translateX(-50%)';
					break;
				case 'topright':
					$options['style']['top']   = 0;
					$options['style']['right'] = 0;
					break;
				case 'centerleft':
					$options['style']['top']       = '50%';
					$options['style']['left']      = 0;
					$options['style']['transform'] = 'translateY(-50%)';
					break;
				case 'center':
					$options['style']['top']       = '50%';
					$options['style']['left']      = '50%';
					$options['style']['transform'] = 'translate(-50%, -50%)';
					break;
				case 'centerright':
					$options['style']['top']       = '50%';
					$options['style']['right']     = 0;
					$options['style']['transform'] = 'translateY(-50%)';
					break;
				case 'bottomleft':
					$options['style']['bottom'] = 0;
					$options['style']['left']   = 0;
					break;
				case 'bottomcenter':
					$options['style']['bottom']    = 0;
					$options['style']['left']      = '50%';
					$options['style']['transform'] = 'translateX(-50%)';
					break;
				case 'bottomright':
					$options['style']['bottom'] = 0;
					$options['style']['right']  = 0;
					break;
			}
		} else {
			$options['style']['margin-left']   = '-' . $width / 2 . 'px';
			$options['style']['margin-bottom'] = '-' . $height / 2 . 'px';
			$options['style']['bottom']        = '50%';
			$options['style']['left']          = '50%';
		}

		return $options;
	}

	/**
	 * Append js file in footer
	 *
	 * @since 1.0.0
	 */
	public function footer_scripts() {
		$options = $this->plugin->options();

		$deps = [ 'jquery' ];

		if ( class_exists( 'Advanced_Ads_Pro' ) ) {
			$pro_options = Advanced_Ads_Pro::get_instance()->get_options();
			if ( ! empty( $pro_options['cache-busting']['enabled'] ) ) {
				$deps[] = 'advanced-ads-pro/cache_busting';
			}
		}

		if ( $this->fancybox_is_enabled ) {
			// Add a patched version of Fancybox that works with new versions of jQuery.
			wp_enqueue_script( 'advanced-ads-layer-fancybox-js', AA_LAYER_ADS_BASE_URL . 'public/assets/fancybox/jquery.fancybox-1.3.4-patched.js', [ 'jquery' ], '1.3.4', true );
			wp_enqueue_style( 'advanced-ads-layer-fancybox-css', AA_LAYER_ADS_BASE_URL . 'public/assets/fancybox/jquery.fancybox-1.3.4.css', [], '1.3.4' );
			$deps[] = 'advanced-ads-layer-fancybox-js';
		}

		wp_enqueue_script( 'advanced-ads-layer-footer-js', AA_LAYER_ADS_BASE_URL . 'public/assets/js/layer.js', $deps, AAPLDS_VERSION, true );
		wp_localize_script(
			'advanced-ads-layer-footer-js',
			'advanced_ads_layer_settings',
			[
				'layer_class' => $this->get_layer_class(),
				'placements'  => $this->placement_ids,
			]
		);
	}

	/**
	 * Content output in the header
	 */
	public function header_output() {
		// Inject js array for banner conditions.
		echo '<script>advads_items = { conditions: {}, display_callbacks: {}, display_effect_callbacks: {}, hide_callbacks: {}, backgrounds: {}, effect_durations: {}, close_functions: {}, showed: [] };</script>';
		echo '<style type="text/css" id="' . self::get_layer_class() . '-custom-css"></style>'; // phpcs:ignore
	}

	/**
	 * Set the ad wrapper options
	 *
	 * @since 1.0.0
	 * @param array $wrapper Wrapper options.
	 * @param Ad    $ad      Ad instance.
	 *
	 * @return array
	 */
	public function set_wrapper( $wrapper, Ad $ad ) {
		return $this->add_css_to_wrapper( $wrapper, $ad );
	}

	/**
	 * Set the ad wrapper options
	 *
	 * @since 1.2.4
	 * @deprecated since 1.3 (Oct 13 2015)
	 *
	 * @param array $wrapper Wrapper options.
	 * @param Ad    $ad      Ad instance.
	 *
	 * @return array
	 */
	public function add_css_to_wrapper( $wrapper, Ad $ad ) {
		$placement = $ad->get_root_placement();

		if ( ! $placement ) {
			return $wrapper;
		}

		$options = $placement->get_data();

		// Define basic layer options.
		if ( isset( $options['layer_placement']['enabled'] ) && $options['layer_placement']['enabled'] ) {
			$layer_class = $this->get_layer_class();
			$width       = $ad->get_width() ?? 0;
			$height      = $ad->get_height() ?? 0;

			$wrapper['class'][]          = $layer_class;
			$wrapper['style']['display'] = 'none';
			$wrapper['style']['z-index'] = '9999';
			$wrapper['data-width'][]     = $width;
			$wrapper['data-height'][]    = $height;

			if ( $this->fancybox_is_enabled ) {
				$wrapper['class'][] = 'use-fancybox';
			}

			if ( ! empty( $options['layer_placement']['effect'] ) && ! empty( $options['layer_placement']['duration'] ) ) {
				$wrapper['class'][] = 'advads-effect';
				$wrapper['class'][] = 'advads-effect-' . $options['layer']['effect'];

				if ( ! empty( $options['layer_placement']['duration'] ) ) {
					$wrapper['class'][] = 'advads-duration-' . absint( $options['layer_placement']['duration'] );
				}
			}

			// Center the ad if position is not set by sticky plugin.
			if ( empty( $options['sticky']['enabled'] ) || empty( $options['sticky']['type'] ) ) {
				$wrapper['style']['position']      = 'fixed';
				$wrapper['style']['margin-left']   = '-' . $width / 2 . 'px';
				$wrapper['style']['margin-bottom'] = '-' . $height / 2 . 'px';
				$wrapper['style']['bottom']        = '50%';
				$wrapper['style']['left']          = '50%';
			}

			// Add trigger options depending on trigger.
			switch ( $options['layer_placement']['trigger'] ) {
				case '':
					$wrapper['class'][] = $layer_class . '-onload';
					break;
				case 'stop':
					$wrapper['class'][] = $layer_class . '-stop';
					break;
				case 'half':
					$wrapper['class'][] = $layer_class . '-half';
					break;
				case 'custom':
					$wrapper['class'][] = $layer_class . '-offset';
					if ( isset( $options['layer']['offset'] ) && $options['layer_placement']['offset'] > 0 ) {
						$wrapper['class'][] = $layer_class . '-offset-' . absint( $options['layer_placement']['offset'] );
					}
					break;
				case 'exit':
					$wrapper['class'][] = $layer_class . '-exit';
					break;
			}

			if ( ! empty( $options['layer_placement']['background'] ) ) {
				$wrapper['class'][] = 'advads-has-background';
			}
		}

		// Set close button options.
		if ( isset( $options['layer_placement']['close']['enabled'] ) && $options['layer_placement']['close']['enabled'] ) {
			$wrapper['class'][] = 'advads-close';
		}

		return $wrapper;
	}

	/**
	 * Add the close button to the wrapper
	 *
	 * @since 1.0.0
	 *
	 * @param string $content Additional content added.
	 * @param Ad     $ad      Ad instance.
	 */
	public function add_button( $content = '', $ad = '' ) {
		// Early bail!!
		if ( ! $ad->is_parent_placement() ) {
			return $content;
		}

		$placement = $ad->get_parent();

		// For button, enabled in layer placement.
		if ( $placement->get_prop( 'layer_placement.close.enabled' ) ) {
			$content .= $this->build_close_button( $placement->get_prop( 'layer_placement.close' ) );
		} elseif ( $placement->get_prop( 'layer.close.enabled' ) ) {
			$content .= $this->build_close_button( $placement->get_prop( 'layer.close' ) );
		}

		return $content;
	}


	/**
	 * Add the close button to the group wrapper
	 *
	 * @param string $content Group content.
	 * @param Group  $group   Group instance.
	 *
	 * @return string
	 */
	public function add_button_group( $content, Group $group ) {
		// Early bail!!
		if ( ! $group->is_parent_placement() ) {
			return $content;
		}

		$placement = $group->get_parent();

		if ( $placement->get_prop( 'layer_placement.close.enabled' ) ) {
			$content .= $this->build_close_button( $placement->get_prop( 'layer_placement.close' ) );
		}

		return $content;
	}

	/**
	 * Build the close button
	 *
	 * @since 1.0.0
	 *
	 * @param array $options original [close] part of the ad options array.
	 */
	public function build_close_button( $options ) {
		// Early bail!!
		if ( empty( $options['where'] ) || empty( $options['side'] ) ) {
			return '';
		}
		$offset   = 'inside' === $options['where'] ? '0' : '-15px';
		$side     = 'right';
		$opposite = 'left';

		if ( 'left' === $options['side'] ) {
			$side     = 'left';
			$opposite = 'right';
		}

		$attributes = [
			'href'  => 'javascript:void(0);',
			'class' => wp_advads()->get_frontend_prefix() . 'close-button',
			'title' => __( 'close', 'advanced-ads-layer' ),
			'style' => 'width: 15px; height: 15px; background: #fff; position: relative; line-height: 15px;'
						. ' text-align: center; cursor: pointer; z-index: 10000; '
						. $side . ':' . $offset . '; float: ' . $side . '; margin-' . $opposite . ': -15px;',
		];

		return '<a ' . HTML::build_attributes( $attributes ) . '>×</a>';
	}


	/**
	 * Add content after the ad wrapper
	 *
	 * @since 1.0.0
	 *
	 * @param string $content Existing ad content.
	 * @param Ad     $ad      Ad instance.
	 *
	 * @return string
	 */
	public function add_content_after( $content, Ad $ad ) {
		$placement = $ad->get_root_placement();

		if ( ! $placement ) {
			return $content;
		}

		$wrapper    = $ad->create_wrapper();
		$wrapper_id = $wrapper['id'] ?? $ad->create_wrapper_id();

		$content .= $this->close_script( $wrapper_id, $ad );

		return $content;
	}

	/**
	 * Add content after the group wrapper.
	 *
	 * @param string $content Existing group content.
	 * @param Group  $group   Group instance.
	 *
	 * @return string
	 */
	public function add_content_after_group( $content, Group $group ) {
		$wrapper = $group->create_wrapper();
		if ( isset( $wrapper['id'] ) ) {
			$content .= $this->close_script( $wrapper['id'], $group );
		}

		return $content;
	}

	/**
	 * Add content after the group wrapper (passive cache-busting).
	 *
	 * @param array $group_data Data to inject after the group.
	 * @param Group $group      Group instance.
	 *
	 * @return array $group_data Modified data to inject after the group.
	 */
	public function after_group_output_passive( $group_data, Group $group ) {
		$wrapper = $group->create_wrapper();
		if ( isset( $wrapper['id'] ) ) {
			$close_script = $this->close_script( $wrapper['id'], $group );

			if ( $close_script ) {
				$group_data['group_wrap'][] = [ 'after' => $close_script ];
			}
		}

		return $group_data;
	}


	/**
	 * Add the javascript for close and timeout feature
	 *
	 * @since 1.2.4
	 *
	 * @param string   $wrapper_id Id of the wrapper.
	 * @param Ad|Group $ad         Ad or Group instance.
	 */
	public function close_script( $wrapper_id, $ad ) {
		$content   = '';
		$placement = $ad->get_root_placement();

		if ( ! $placement || ! $placement->is_type( 'layer' ) ) {
			return $content;
		}

		$options              = $placement->get_prop( 'layer_placement' );
		$set_cookie_string    = '';
		$close_button_enabled = isset( $options['close']['enabled'] ) && $options['close']['enabled'];

		// Check if value exists; also 0 works, since it sets the cookie for the current session.
		if ( isset( $options['close']['timeout_enabled'] ) ) {
			$timeout = isset( $options['close']['timeout'] ) ? absint( $options['close']['timeout'] ) : 0;
			if ( ! $timeout ) {
				$timeout = 'null';
			}

			$set_cookie_string .= 'advads.set_cookie("timeout_placement_' . $placement->get_slug() . '", 1, ' . $timeout . '); ';
		}

		$content .= $this->build_close_popup_js( $set_cookie_string, $wrapper_id, $close_button_enabled );

		return $content;
	}

	/**
	 * Build js for popup close handling
	 *
	 * @param string $set_cookie_string    For setup timeout cookie.
	 * @param string $wrapper_id           Id of the wrapper.
	 * @param bool   $close_button_enabled Whether the close button is enabled.
	 *
	 * @return string js for popup close handling
	 */
	private function build_close_popup_js( $set_cookie_string, $wrapper_id, $close_button_enabled ) {
		$script = '<script>( window.advanced_ads_ready || jQuery( document ).ready ).call( null, function() {';

		if ( $this->fancybox_is_enabled ) {
			$script .= "advads_items.close_functions[ '{$wrapper_id}' ] = function() {"
			. "advads.close( '#{$wrapper_id}' );";

			if ( $set_cookie_string ) {
				$script .= $set_cookie_string;
			}

			$script .= '};';
		} else {
			$script .= "advads_items.close_functions[ '{$wrapper_id}' ] = function() {"
				. "advads.close( '#{$wrapper_id}' ); "
				. "if ( can_remove_background ( '{$wrapper_id}' ) ) { "
				. 'jQuery( ".advads-background" ).remove(); '
				. '}; ';

			if ( $set_cookie_string ) {
				$script .= $set_cookie_string;
			}
			$script .= '};';

			if ( $close_button_enabled ) {
				$prefix  = wp_advads()->get_frontend_prefix();
				$script .= "jQuery( '#{$wrapper_id}' ).on( 'click', '.{$prefix}close-button', function() { "
					. "var close_function = advads_items.close_functions[ '{$wrapper_id}' ];"
					. "if ( typeof close_function === 'function' ) {"
					. 'close_function(); '
					. '}';
				$script .= '});';
			}
		}
		$script .= '});</script>';
		return $script;
	}

	/**
	 * Check if placement was closed with a cookie before
	 *
	 * @since 1.2.4
	 *
	 * @param bool $check Whether placement can be displayed or not.
	 * @param int  $id    Placement id.
	 *
	 * @return bool false if placement was closed for this user
	 */
	public function placement_can_display( $check, $id = 0 ) {
		// Early bail!!
		if ( ! $id ) {
			return $check;
		}

		$placement = wp_advads_get_placement( $id );
		$options   = $placement->get_prop( 'layer_placement' );

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
	 * Check if the current ad can be displayed based on minimal and maximum browser width
	 *
	 * @since 1.2.4
	 *
	 * @param bool $can_display Value as set so far.
	 * @param Ad   $ad          Ad instance.
	 *
	 * @return bool false if can’t be displayed, else return $can_display
	 */
	public function can_display( $can_display, $ad = 0 ) {

		$ad_options = $ad->get_data();

		if ( ! isset( $ad_options['layer']['close']['enabled'] ) || ! $ad_options['layer']['close']['enabled'] ) {
			return $can_display;
		}

		if ( isset( $ad_options['layer']['close']['timeout_enabled'] ) && $ad_options['layer']['close']['timeout_enabled'] ) {
			if ( isset( $_COOKIE[ 'timeout_' . $ad->get_id() ] ) ) {
				return false;
			}
		}

		return $can_display;
	}

	/**
	 * Returns the (css) class name for layer ads
	 *
	 * @return string
	 */
	public static function get_layer_class() {
		return wp_advads()->get_frontend_prefix() . 'layer';
	}
}
