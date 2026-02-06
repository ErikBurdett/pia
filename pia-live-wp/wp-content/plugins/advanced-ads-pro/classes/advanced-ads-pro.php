<?php // phpcs:ignoreFile

use AdvancedAds\Abstracts\Ad;
use AdvancedAds\Frontend\Stats;
use AdvancedAds\Utilities\Conditional;

/**
 * Class Advanced_Ads_Pro
 */
class Advanced_Ads_Pro {

	/**
	 * Pro options
	 *
	 * @var array
	 */
	protected $options;

	/**
	 * Interal plugin options – set by the plugin
	 *
	 * @var     array (if loaded)
	 */
	protected $internal_options;

	/**
	 * Option name shared by child modules.
	 *
	 * @var string
	 *
	 * @deprecated AdvancedAds\Pro\Constants::OPTION_KEY
	 */
	const OPTION_KEY = 'advanced-ads-pro';

	/**
	 * Name of the frontend script.
	 *
	 * @var string
	 */
	const FRONTEND_SCRIPT_HANDLE = 'advanced-ads-pro/front';

	/**
	 * Instance of Advanced_Ads_Pro
	 *
	 * @var Advanced_Ads_Pro
	 */
	private static $instance;

	/**
	 * Advanced_Ads_Pro constructor.
	 */
	private function __construct() {
		// Setup plugin once base plugin that is initialized at priority `20` is available.
		add_action( 'plugins_loaded', [ $this, 'init' ], 30 );
	}

	/**
	 * Instance of Advanced_Ads_Pro
	 *
	 * @return Advanced_Ads_Pro
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Must not be called before `plugins_loaded` hook.
	 */
	public function init() {
		// TODO: Update routines will be handled by the Advanced Ads Framework. Move this function then accordingly.
		$this->plugin_updates();

		// Load config and modules.
		$options = $this->get_options();
		Advanced_Ads_ModuleLoader::loadModules( AAP_PATH . '/modules/', isset( $options['modules'] ) ? $options['modules'] : [] );

		// Load admin on demand.
		if ( is_admin() ) {
			new Advanced_Ads_Pro_Admin();
			// Run after the internal Advanced Ads version has been updated by the `Advanced_Ads_Upgrades`, because.
			// The `Admin_Notices` can update this version, and the `Advanced_Ads_Upgrades` will not be called.
			add_action( 'init', [ $this, 'maybe_update_capabilities' ] );

			add_filter( 'advanced-ads-notices', [ $this, 'add_notices' ] );
		} else {
			// Force advanced js file to be attached.
			add_filter( 'advanced-ads-activate-advanced-js', '__return_true' );
			// Check autoptimize.
			if ( method_exists( 'Advanced_Ads_Checks', 'requires_noptimize_wrapping' ) && Advanced_Ads_Checks::requires_noptimize_wrapping() && ! isset( $options['autoptimize-support-disabled'] ) ) {
				add_filter( 'advanced-ads-output-inside-wrapper', [ $this, 'autoptimize_support' ] );
			}
		}
		new Advanced_Ads_Pro_Compatibility();

		add_filter( 'advanced-ads-can-display-ad', [ $this, 'can_display_by_display_limit' ], 10, 3 );
		add_filter( 'advanced-ads-ad-output', [ $this, 'add_custom_code' ], 30, 2 );
		add_filter( 'advanced-ads-ad-output', [ $this, 'encode_ad_custom_code' ], 20, 2 );
		add_filter( 'advanced-ads-placement-content-offsets', [ $this, 'placement_content_offsets' ], 10, 6 );
	}

	/**
	 * Return Advanced Ads Pro options
	 *
	 * @return array
	 */
	public function get_options() {
		if ( ! isset( $this->options ) ) {
			$default_options = [];
			$this->options   = get_option( self::OPTION_KEY, $default_options );
			// Handle previous option key.
			if ( $this->options === [] ) {
				$old_options = get_option( self::OPTION_KEY . '-modules', false );
				if ( $old_options ) {
					// Update old options.
					$this->update_options( $old_options );
					delete_option( self::OPTION_KEY . '-modules' );
				}
			}
			if ( ! isset( $this->options['placement-positioning'] ) ) {
				$this->options['placement-positioning'] = 'php';
			}
		}

		return $this->options;
	}

	/**
	 * Set a specific option.
	 *
	 * @param string $key key to identify the option.
	 * @param mixed  $value value of the option.
	 */
	public function set_option( $key, $value ) {
		$options = $this->get_options();

		$options[ $key ] = $value;
		$this->update_options( $options );
	}

	/**
	 * Update all Advanced Ads Pro options.
	 *
	 * @param array $options
	 */
	public function update_options( array $options ) {
		$updated = update_option( self::OPTION_KEY, $options );

		if ( $updated ) {
			$this->options = $options;
		}
	}

	/**
	 * Add autoptimize support
	 *
	 * @param string          $ad_content ad content.
	 * @return string output that should not be changed by Autoptimize.
	 */
	public function autoptimize_support( $ad_content = '' ) {
		return '<!--noptimize-->' . $ad_content . '<!--/noptimize-->';
	}

	/**
	 * Return internal plugin options, these are options set by the plugin
	 *
	 * @param bool $set_defaults true if we set default options.
	 * @return array $options
	 */
	public function internal_options( $set_defaults = true ) {
		if ( ! $set_defaults ) {
			return get_option( 'Advanced Ads Pro' . '-internal', [] );
		}

		if ( ! isset( $this->internal_options ) ) {
			$defaults               = [
				'version' => AAP_VERSION,
			];
			$this->internal_options = get_option( 'Advanced Ads Pro' . '-internal', [] );
			// Save defaults.
			if ( $this->internal_options === [] ) {
				$this->internal_options = $defaults;
				$this->update_internal_options( $this->internal_options );
			}
		}

		return $this->internal_options;
	}

	/**
	 * Update internal plugin options
	 *
	 * @param array $options new internal options.
	 */
	public function update_internal_options( array $options ) {
		$this->internal_options = $options;
		update_option( 'Advanced Ads Pro' . '-internal', $options );
	}

	/**
	 * Update capabilities and warn user if needed
	 */
	public function maybe_update_capabilities() {
		$internal_options = $this->internal_options( false );
		if ( ! isset( $internal_options['version'] ) ) {
			$roles = [ 'advanced_ads_admin', 'advanced_ads_manager' ];
			// Add notice if there is at least 1 user with that role.
			foreach ( $roles as $role ) {
				$users_query = new WP_User_Query(
					[
						'fields' => 'ID',
						'number' => 1,
						'role'   => $role,
					]
				);
				if ( count( $users_query->get_results() ) ) {
					Advanced_Ads_Admin_Notices::get_instance()->add_to_queue( 'pro_changed_caps' );
					break;
				}
			}

			$admin_role = get_role( 'advanced_ads_admin' );
			if ( $admin_role ) {
				$admin_role->add_cap( 'upload_files' );
				$admin_role->add_cap( 'unfiltered_html' );
			}
			$manager_role = get_role( 'advanced_ads_manager' );
			if ( $manager_role ) {
				$manager_role->add_cap( 'upload_files' );
				$manager_role->add_cap( 'unfiltered_html' );
			}

			// Save new version.
			$this->internal_options();
		}
	}

	/**
	 * Add potential warning to global array of notices.
	 *
	 * @param array $notices existing notices.
	 *
	 * @return mixed
	 */
	public function add_notices( $notices ) {
		$notices['pro_changed_caps'] = [
			'type'   => 'update',
			'text'   => __( 'Please note, the “Ad Admin“ and the “Ad Manager“ roles have the “upload_files“ and the “unfiltered_html“ capabilities', 'advanced-ads-pro' ),
			'global' => true,
		];
		$message                     = wp_kses(
			sprintf(
			/* translators: 1 is the opening link to the Advanced Ads website, 2 the closing link */
				__(
					'We have renamed the Responsive Ads add-on to ‘Advanced Ads AMP Ads’. With this change, the Browser Width visitor condition moved from that add-on into Advanced Ads Pro. You can deactivate ‘Advanced Ads AMP Ads’ if you don’t utilize AMP ads or the custom sizes feature for responsive AdSense ad units. %1$sRead more%2$s.',
					'advanced-ads-pro'
				),
				'<a href="https://wpadvancedads.com/responsive-ads-add-on-becomes-amp-ads" target="_blank" class="advads-manual-link">',
				'</a>'
			),
			[
				'a' => [
					'href' => true,
					'target' => true,
					'class' => true,
				],
			]
		);
		$notices['pro_responsive_migration'] = [
			'type'   => 'info',
			'text'   => $message,
			'global' => true,
		];

		return $notices;
	}

	/**
	 * Check if the ad can be displayed based on display limit
	 *
	 * @param bool  $can_display   Existing value.
	 * @param Ad    $ad            Ad instance.
	 * @param array $check_options Options to check.
	 *
	 * @return bool true if limit is not reached, false otherwise
	 */
	public function can_display_by_display_limit( $can_display, Ad $ad, $check_options ) {
		if ( ! $can_display ) {
			return false;
		}

		if ( empty( $check_options['passive_cache_busting'] ) && $ad->get_prop( 'once_per_page' ) ) {
			foreach ( Stats::get()->entities as $item ) {
				if ( $item['type'] === 'ad' && absint( $item['id'] ) === $ad->get_id() ) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Get offsets for Content placement.
	 *
	 * @param array  $offsets Existing Offsets.
	 * @param array  $options Injection options.
	 * @param array  $placement_opts Placement options.
	 * @param object $xpath DOMXpath object.
	 * @param array  $items Selected items.
	 * @param object $dom DOMDocument object.
	 * @return array $offsets New offsets.
	 */
	public function placement_content_offsets( $offsets, $options, $placement_opts, $xpath = null, $items = null, $dom = null ) {
		if ( ! isset( $options['paragraph_count'] ) ) {
			return $offsets;
		}

		if ( isset( $placement_opts['placement']['type'] ) ) {
			if ( 'post_content_random' === $placement_opts['placement']['type'] ) {
				$max = absint( $options['paragraph_count'] - 1 );
				// Skip if have only one paragraph since `wp_rand( 0, 0)` generates large number.
				if ( $max > 0 ) {
					$rand    = wp_rand( 0, $max );
					$offsets = [ $rand ];
				}
			}

			if ( 'post_content_middle' === $placement_opts['placement']['type'] ) {
				$middle  = absint( ( $options['paragraph_count'] - 1 ) / 2 );
				$offsets = [ $middle ];
			}
		}

		// "Content" placement, repeat position.
		if ( ! empty( $placement_opts['repeat'] ) || ! empty( $options['repeat'] )
			&& isset( $options['paragraph_id'] )
			&& isset( $options['paragraph_select_from_bottom'] ) ) {

			$offsets = [];
			for ( $i = $options['paragraph_id'] - 1; $i < $options['paragraph_count']; $i++ ) {
				// Select every X number.
				if ( ( $i + 1 ) % $options['paragraph_id'] === 0 ) {
					$offsets[] = $options['paragraph_select_from_bottom'] ? $options['paragraph_count'] - 1 - $i : $i;
				}
			}
		}

		if ( ! empty( $placement_opts['words_between_repeats'] )
			&& $xpath && $items && $dom ) {
			$options['words_between_repeats'] = absint( $placement_opts['words_between_repeats'] );

			$offset_shifter = new Advanced_Ads_Pro_Offset_Shifter( $dom, $xpath, $options );
			$offsets        = $offset_shifter->calc_offsets( $offsets, $items );
		}

		return $offsets;
	}

	/**
	 * Add custom code after the ad.
	 *
	 * Note: this won’t work for the Background ad placement. There is a custom solution for that in Advanced_Ads_Pro_Module_Background_Ads:ad_output
	 *
	 * @param string          $ad_content Ad content.
	 * @param Ad $ad Ad instance.
	 * @return string $ad_content Ad content.
	 */
	public function add_custom_code( $ad_content, Ad $ad ) {
		$custom_code = $this->get_custom_code($ad);

		if ( empty( $custom_code ) ) {
			return $ad_content;
		}

		$privacy = Advanced_Ads_Privacy::get_instance();

		if ( $privacy->is_ad_output_encoded( $ad_content ) ) {
			// If the ad_content is already encoded, do not append the custom code in plain text after it.
			return $privacy->encode_ad( $this->decode_output( trim( $ad_content ) ) . $custom_code, $ad );
		}

		return $ad_content . $custom_code;
	}

	/**
	 * Retrieve the original ad content from an encoded script tag
	 *
	 * @param string $output the encoded output.
	 *
	 * @return string
	 */
	private function decode_output( $output ) {
		// Strips the <script ...> and the </script> then base64_decode the remaining characters.
		return base64_decode( substr( $output, strpos( $output, '>' ) + 1, -9 ) );
	}

	/**
	 * If this ad has custom code, encode the output.
	 *
	 * @param string          $output The output string.
	 * @param Ad $ad     The ad object.
	 *
	 * @return string
	 */
	public function encode_ad_custom_code( $output, Ad $ad ) {
		$privacy = Advanced_Ads_Privacy::get_instance();
		if (
			// don't encode if AMP.
			Conditional::is_amp()
			// privacy module is either not enabled, or shows all ads without consent.
			|| ( empty( $privacy->options()['enabled'] ) )
			// Ad is already encoded.
			|| ( ! method_exists( $privacy, 'is_ad_output_encoded' ) || $privacy->is_ad_output_encoded( $output ) )
			// Consent is overridden, and this is not an AdSense ad, don't encode it.
			|| ( ! $ad->is_type( 'adsense' ) && $ad->get_prop( 'privacy.ignore-consent' ) )
		) {
			return $output;
		}

		// If we have custom code, encode the ad.
		if ( ! empty( $this->get_custom_code( $ad ) ) ) {
			$output = $privacy->encode_ad( $output, $ad );
		}

		return $output;
	}

	/**
	 * Get the custom code for this ad.
	 *
	 * @param Ad $ad The ad object.
	 *
	 * @return string
	 */
	public function get_custom_code( Ad $ad ) {
		$custom_code = $ad->get_prop( 'custom-code' ) ?? '';

		return (string) apply_filters( 'advanced_ads_pro_output_custom_code', $custom_code, $ad );
	}

	/**
	 * Enable placement test emails
	 */
	public static function enable_placement_test_emails() {
		$placement_tests = get_option( 'advads-ads-placement-tests', [] );
		if ( ! wp_next_scheduled( 'advanced-ads-placement-tests-emails' ) && count($placement_tests) > 0 ) {
			// Only schedule if not yet scheduled & tests exists.
			wp_schedule_event( time(), 'daily', 'advanced-ads-placement-tests-emails' );
		} elseif ( wp_next_scheduled( 'advanced-ads-placement-tests-emails' ) && count($placement_tests) <= 0 ) {
			// deactivate if running and tests empty.
			self::disable_placement_test_emails();
		}
	}

	/**
	 * Disable placement test emails
	 */
	public static function disable_placement_test_emails() {
		wp_clear_scheduled_hook( 'advanced-ads-placement-tests-emails' );
	}

	/**
	 * Plugin update.
	 *
	 * @return void
	 */
	private function plugin_updates(): void {
		$pro_options  = $this->get_options();
		$free_options = Advanced_Ads::get_instance()->options();

		if ( isset( $pro_options['responsive-ads'] ) || ! isset( $free_options['responsive-ads'] ) ) {
			return;
		}

		$this->set_option( 'responsive-ads', $free_options['responsive-ads'] );
	}
}
