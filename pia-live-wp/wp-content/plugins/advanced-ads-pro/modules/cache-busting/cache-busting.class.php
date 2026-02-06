<?php // phpcs:ignoreFile

use AdvancedAds\Abstracts\Ad;
use AdvancedAds\Abstracts\Group;
use AdvancedAds\Abstracts\Placement;
use AdvancedAds\Compatibility\Compatibility;
use AdvancedAds\Constants;
use AdvancedAds\Framework\Utilities\Params;
use AdvancedAds\Frontend\Stats;
use AdvancedAds\Slider\Frontend\Frontend;
use AdvancedAds\Tracking\Utilities\Data;
use AdvancedAds\Utilities\Conditional;
use AdvancedAds\Utilities\WordPress;

/**
 * Cache Busting class.
 *
 * TODO should use a constant for option key as it is shared at multiple positions.
 */
class Advanced_Ads_Pro_Module_Cache_Busting {
    /** @#+
     * Cache-busting option values.
     *
     * @var string
     */
    const OPTION_ON = 'on';
    const OPTION_OFF = 'off';
    const OPTION_AUTO = 'auto';
    // Ignore any cache-busting, even for no placement.
    const OPTION_IGNORE = 'ignore';
    /** @#- */

	/**
	 * Instance of this class.
	 *
	 * @var Advanced_Ads_Pro_Module_Cache_Busting
	 */
	private static $instance = null;

    /**
     * Internal global ad block count.
     *
     * @var integer
     */
    protected static $adOffset = 0;

    /**
     * Module options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Context-switch used for ad override.
     *
     * @var boolean
     */
    protected $isHead = true;

    /**
     * True if ajax, false otherwise.
     *
     * @var boolean
     */
    public $is_ajax;

    /**
     * Ads, Groups, Placements for JavaScript.
     *
     * @var arrays
     */
    protected $passive_cache_busting_ads = [];
    protected $passive_cache_busting_groups = [];
    protected $passive_cache_busting_placements = [];

    /**
     * Simple js items injected using js.
     * Their conditions are not checked for every visitor of a cached page.
     *
     * @var arrays
     */
    protected $js_items = [];

    /**
     * Whether we are collecting simple js items.
     *
     * @var bool
     */
    protected $collecting_js_items = false;

    /**
     * Info about simple items for tracking purpose.
     *
     * @var array
     */
    protected $has_js_items = [];

    /**
     * Ads loaded without cache-busting
     *
     * @var array
     */
    protected $has_ads = [];

    /**
     *  Queries for ads, that need to be loaded with AJAX
     *
     * @var array
     */
    protected static $ajax_queries = [];

	/**
	 * Each AJAX query is merged into this array. A query may replace but not remove an item in this array.
	 *
	 * @var array
	 */
	private $ajax_default_args =  [
		'lazy_load' => 'disabled',
		'cache-busting' => 'on',
		'ad_label' => 'default',
		'placement_position' => '',
		'item_adblocker' => '',
		'pro_minimum_length' => '0',
		'words_between_repeats' => '0',
		'previous_method' => null,
		'previous_id' => null,
		'wp_the_query' =>  [
			'term_id' => '',
			'taxonomy' => '',
			'is_main_query' => true,
			'is_rest_api' => false,
			'page' => 1,
			'numpages' => 1,
			'is_archive' => false,
			'is_search' => false,
			'is_home' => false,
			'is_404' => false,
			'is_attachment' => false,
			'is_singular' => true,
			'is_front_page' => false,
			'is_feed' => false,
		],
		'global_output' => true,
	];

	/**
	 * One argument in this array may belong to several AJAX requests.
	 *
	 * @var array
	 */
	private $ajax_queries_args = [];

	/**
	 * Is module enabled.
	 *
	 * @var bool
	 */
	private $is_enabled = false;

	/**
	 * Lazy load enabled.
	 *
	 * @var bool
	 */
	private $lazy_load_enabled = false;

	/**
	 * The fallback method.
	 *
	 * @var string
	 */
	public $fallback_method = 'ajax';

	/**
	 * Lazy load offset.
	 *
	 * @var integer
	 */
	public $lazy_load_offset = 0;

	/**
	 * Holds the server info class.
	 *
	 * @var Advanced_Ads_Pro_Cache_Busting_Server_Info
	 */
	public $server_info = null;

	/**
	 * Constructor
	 */
    private function __construct() {
		if ( is_admin() ) {
			new Advanced_Ads_Pro_Module_Cache_Busting_Admin_UI();
		}
		add_action( 'init', [ $this, 'init' ], 30 );
    }

	/**
	 * Cache busting initialization
	 *
	 * @return void
	 */
	public function init() {
		$options = Advanced_Ads_Pro::get_instance()->get_options();

		if ( isset( $options['cache-busting'] ) ) {
			$this->options = $options['cache-busting'];
		}

		$this->is_enabled = $this->options['enabled'] ?? false;

		if ( ! $this->should_init_cb() ) {
			// CB not needed, abort.
			add_action( 'wp_enqueue_scripts', [ $this, 'check_for_tcf_privacy' ] );

			return;
		}

		$this->lazy_load_enabled = $options['lazy-load']['enabled'] ?? false;
		$this->lazy_load_offset  = absint( $options['lazy-load']['offset'] ?? 0 );

		// An AJAX request but not necessarily to `/admin-ajax.php`.
		$this->is_ajax = wp_doing_ajax() || 'XMLHttpRequest' === Params::server( 'HTTP_X_REQUESTED_WITH' );

		if ( ! $this->is_ajax && ! is_admin() ) {
			add_action( 'wp', [ $this, 'init_frontend' ] );
			// Load Advads Tracking header scripts.
			add_filter( 'advanced-ads-tracking-load-header-scripts', [ $this, 'load_tracking_scripts' ], 10, 1 );
		}

		$this->fallback_method = ( ! isset( $this->options['default_fallback_method'] ) || $this->options['default_fallback_method'] === 'ajax' ) ? 'ajax' : 'off';

		if ( 'ajax' === $this->fallback_method ) {
			$this->server_info = new Advanced_Ads_Pro_Cache_Busting_Server_Info( $this, $this->options );
		}

		add_filter( 'advanced-ads-ad-output-debug-content', [ $this, 'add_debug_content' ], 10, 2 );
		add_filter( 'advanced-ads-ajax-ad-select-arguments', [ $this, 'add_default_ajax_arguments' ], 10, 2 );
		add_action( 'advanced-ads-placement-options-after-advanced', [ $this, 'placement_options' ], 10, 2 );
	}

	/**
	 * Append empty cache busting options to placement advanced options list
	 *
	 * @param string    $slug      current placement slug.
	 * @param Placement $placement current placement data.
	 *
	 * @return void
	 */
	public function placement_options( $slug, $placement ) {
		WordPress::render_option(
			'placement-empty-cache-busting',
			__( 'Hide when empty', 'advanced-ads-pro' ),
			sprintf(
				'<label><input type="hidden" name="advads[placements][options][cache_busting_empty]" value="0"/><input type="checkbox" name="advads[placements][options][cache_busting_empty]" value="1" %s/>%s</label>',
				checked( $placement->get_prop( 'cache_busting_empty' ) ? true : false, true, false ),
				esc_html__( 'Remove the placeholder if unfilled.', 'advanced-ads-pro' )
			),
			sprintf(
				'%s %s%s%s',
				__( 'Deleting an empty placement might lead to a layout shift.', 'advanced-ads-pro' ),
				'<a href="https://wpadvancedads.com/cumulative-layout-shift-cls-and-ads/?utm_source=advanced-ads&utm_medium=link&utm_campaign=ad-modal-placements-cb" target="_blank" class="advads-manual-link">',
				__( 'Manual', 'advanced-ads-pro' ),
				'</a>'
			)
		);
	}

	/**
	 * Check if cache-busting should be initialized.
	 *
	 * Even when the module is disabled, we partially (i.e .conditions are not checked for every visitor of a cached page)
	 * use cache-busting functionality to deliver Custom Position placements so that they do not appear in the footer when
	 * selectors do not exist.
	 *
	 * @see self::add_simple_js_item
	 */
	public function should_init_cb() {
		if ( $this->is_enabled ) {
			return true;
		}

		foreach ( wp_advads_get_placements() as $placement ) {
			if (
				$placement->is_type( 'custom_position' )
				&& ! empty( $placement->get_item() )
				&& Advanced_Ads_Pro::get_instance()->get_options()['placement-positioning'] !== 'php'
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return obj A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

    /**
     *  Init cache-busting frontend after the `parse_query` hook.
     *  Not ajax, not admin.
     */
    public function init_frontend() {
        global $wp_the_query;

        if (
			apply_filters( 'advanced-ads-pro-cb-frontend-disable', false )
            || Conditional::is_amp()
            || $wp_the_query->is_feed()
        ) {
			return;
		}

        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'wp_head', [ $this, 'watch_wp_head'], PHP_INT_MAX );
        add_filter( 'advanced-ads-ad-output', [ $this, 'watch_ad_output' ], 100, 2 );
        add_filter( 'advanced-ads-group-output', [ $this, 'watch_group_output' ], 100, 2 );
        add_filter( 'advanced-ads-ad-select-override-by-ad', [ $this, 'override_ad_select_by_ad' ], 10, 3 );
        add_filter( 'advanced-ads-ad-select-override-by-group', [ $this, 'override_ad_select_by_group' ], 10, 4 );
        add_action( 'wp_footer', [ $this, 'passive_cache_busting_output' ], 21 );
        add_filter( 'advanced-ads-can-display-ad', [ $this, 'can_display_by_display_limit' ], 10, 3 );

        if ( ! $this->is_enabled ) {
            return;
        }

        add_filter( 'advanced-ads-ad-select-args', [ $this, 'override_ad_select' ], 100 );
        add_filter( 'advanced-ads-ad-select-args', [ $this, 'disable_global_output' ], 101 );
        add_action( 'advanced-ads-can-display-placement', [ $this, 'placement_can_display' ], 12, 2 );
    }

    /**
     * Output passive cache-busting array
     */
    public function passive_cache_busting_output() {
		$arrays = [
            'window.advads_placement_tests' => Advanced_Ads_Pro_Placement_Tests::get_instance()->get_placement_tests_js( false ),
            'window.advads_passive_ads' => $this->passive_cache_busting_ads,
            'window.advads_passive_groups' => $this->passive_cache_busting_groups,
            'window.advads_passive_placements' => $this->passive_cache_busting_placements,
            'window.advads_ajax_queries' => self::$ajax_queries,
            'window.advads_has_ads' => $this->has_ads,
            'window.advads_js_items' => $this->js_items,
            'window.advads_ajax_queries_args' => $this->ajax_queries_args,
        ];

		$content = '';
        foreach ( $arrays as $name => $array ) {
            if ( $array ) {
                $has_data = true;
                $content .= $name . ' = ' . json_encode( $array ) . ";\n";
            }
        }

        if ( ! $content ) {
            return;
        }

        $content = '<script>'
        . $content
        . '( window.advanced_ads_ready || jQuery( document ).ready ).call( null, function() {'
        .     'if ( !window.advanced_ads_pro ) {'
        .         'console.log("Advanced Ads Pro: cache-busting can not be initialized");'
        .     '} '
        . '});'
        . '</script>';

        if ( class_exists( 'Advanced_Ads_Utils' ) && method_exists( 'Advanced_Ads_Utils', 'get_inline_asset' ) ) {
            $content = Advanced_Ads_Utils::get_inline_asset( $content );
        }
        echo $content;
    }

	/**
	 * Enqueue frontend scripts
	 *
	 * @return void
	 */
    public function enqueue_scripts() {
	    wp_advads()->json->add( [ 'frontendPrefix' => wp_advads()->get_frontend_prefix() ] );
		wp_enqueue_script( 'advanced-ads-pro/postscribe', AA_PRO_BASE_URL . 'assets/js/postscribe.js', [], AAP_VERSION, true );
	    $dependencies = [ 'advanced-ads-pro/postscribe', 'jquery' ];

	    // If the privacy module is active, add advanced-js as a dependency.
	    if ( ! empty( Advanced_Ads_Privacy::get_instance()->options()['enabled'] ) ) {
		    $dependencies[] = ADVADS_SLUG . '-advanced-js';
	    }

		// Include in footer to prevent conflict when Autoptimize and NextGen Gallery are used at the same time.
		wp_register_script( 'advanced-ads-pro/cache_busting', AA_PRO_BASE_URL . 'assets/dist/front.js', $dependencies, AAP_VERSION, true );

		$info = [
			'ajax_url'                 => admin_url( 'admin-ajax.php' ),
			'lazy_load_module_enabled' => $this->lazy_load_enabled,
			'lazy_load'                => [
				'default_offset' => $this->lazy_load_offset,
				'offsets'        => apply_filters( 'advanced-ads-lazy-load-placement-offsets', [] ),
			],
			'moveintohidden'           => defined( 'ADVANCED_ADS_PRO_CUSTOM_POSITION_MOVE_INTO_HIDDEN' ),
			'wp_timezone_offset'       => Advanced_Ads_Utils::get_wp_timezone()->getOffset( date_create() ),
			'the_id'                   => get_the_ID(),
			'is_singular'              => is_singular(),
		];

        if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
            $current_lang = apply_filters( 'wpml_current_language', null );
            $info['ajax_url'] = add_query_arg( 'wpml_lang', $current_lang, $info['ajax_url'] );
        }

        wp_localize_script( 'advanced-ads-pro/cache_busting', 'advanced_ads_pro_ajax_object', $info );

        wp_enqueue_script( 'advanced-ads-pro/cache_busting' );
    }

    /**
     * Provide current_ad propery to client.
     *
     * @param string          $content
     * @param Ad $ad
     *
     * @return string
     */
    public function watch_ad_output( $content, $ad = null ) {
        if ( isset( $ad ) && is_an_ad( $ad ) ) {
            // build content (arguments are: id, method, title)
            if ( ! empty( $ad->get_prop( 'ad_args.global_output' ) ) ) {
                $this->has_ads[] = [ "{$ad->get_id()}", 'ad', $ad->get_title(), 'off' ];
            }
            if ( $this->collecting_js_items ) {
                $this->has_js_items[] = [ 'id' => $ad->get_id(), 'type' => 'ad', 'title' => $ad->get_title(), 'blog_id' => get_current_blog_id() ];
            }
        }

        return $content;
    }

    /**
     * Provide current group propery to client.
     *
     * @param string $content
     * @param Group $group
     *
     * @return string
     */
    public function watch_group_output( $content, Group $group ) {
        if ( $this->collecting_js_items ) {
            $this->has_js_items[] = [ 'id' => $group->get_id(), 'type' => 'group', 'title' => $group->get_id() ];
        }

        return $content;
    }

    /**
     * Turn off head optimisation.
     */
    public function watch_wp_head() {
        $this->isHead = false;
    }

    /**
     * Replace ad content with placeholder.
     *
     * @param array $arguments
     *
     * @return array
     */
    public function override_ad_select( $arguments ) {
        // placements and not Feed only
        $not_feed = empty( $arguments['wp_the_query']['is_feed'] );
        if ( $arguments['method'] === Constants::ENTITY_PLACEMENT && $not_feed ) {
			$placement = wp_advads_get_placement( $arguments['id'] );

			if ( empty( $placement) ) {
				return $arguments;
			}

            if ( empty( $placement->get_item() ) ) {
                // placement was created but no item was selected in dropdown
                unset( $arguments['override'] );
                return $arguments;
            }

            $arguments['placement_type'] = $placement->get_type();
            $options = $placement->get_data();

            foreach ( $options as $_k => $_v ) {
                if ( ! isset( $arguments[ $_k ] ) ) {
                    $arguments[ $_k ] = $_v;
                }
            }

            $query = self::build_js_query( $arguments );

            // allow to disable feature
            if ( $this->can_override( $query ) ) {
                $arguments['override'] = $this->get_override_content( $query );
            }
        }

        return $arguments;
    }

    /**
     * Disable global output for cache-busting.
	 * We neither track ads nor show them in the "Ads" section of Admin Bar until we show them to the user.
     *
	 * @param array $arguments Arguments passed to ads and groups from top level placements/ads/groups.
	 * @return array
     */
    public function disable_global_output( $arguments ) {
		if ( isset( $arguments['global_output'] ) ) {
			return $arguments;
		}

		if (
			// Custom position.
			(
				isset( $arguments['placement_type'] )
				&& $arguments['placement_type'] === 'custom_position'
				&& Advanced_Ads_Pro::get_instance()->get_options()['placement-positioning'] !== 'php'
			)
			// Cache Busting "ajax" or "auto".
			|| ( isset( $arguments['placement_type'] ) && ( ! isset( $arguments['cache-busting'] ) || $arguments['cache-busting'] !== self::OPTION_OFF ) )
			// Force passive cache-busting.
			|| ( ! isset( $arguments['placement_type'] ) && ! empty( $this->options['passive_all'] ) )
		) {
			$arguments['global_output'] = false;
			return $arguments;
		}

		$arguments['global_output'] = true;
		return $arguments;
    }

	/**
	 * Return ad, prepared for js handler if the conditions are met.
	 *
	 * @param bool|string     $overriden_ad Ad content to override.
	 * @param Ad $ad Ad object.
	 * @param array           $args Arguments passed to ads and groups from top level placements/ads/groups.
	 * @return bool|string Ad content prepared for js handler if the conditions are met.
	 */
	public function override_ad_select_by_ad( $overriden_ad, Ad $ad, $args ) {
		if ( ! $this->can_override_passive( $args ) ) {
			return $overriden_ad;
		}

		if ( $this->is_enabled ) {
			// Cache busting 'auto'.
			$overriden_ad = $this->cache_busting_auto_for_ad( $overriden_ad, $ad, $args );
		}

		if ( false === $overriden_ad ) {
			// The cache-busting module is disabled or the 'off' fallback has been aplied.
			$overriden_ad = $this->get_simple_js_ad( $overriden_ad, $ad, $args );
		}

		return $overriden_ad;
    }

    /**
     * return group, prepared for js handler if the conditions are met
     *
     * @param string $overriden_group group content to override
     * @param obj $group Group
     * @param array/null $ordered_ad_ids ordered ids of the ads that belong to the group
     * @param array $args argument passed to the 'get_ad_by_group' function
     * @return string/false group content prepared for js handler if the conditions are met
     */
    public function override_ad_select_by_group( $overriden_group, Group $group, $ordered_ad_ids, $args ) {
        if ( ! $this->can_override_passive( $args ) ) {
            return $overriden_group;
        }

        if ( $this->is_enabled ) {
            // Cache busting 'auto'.
            $overriden_group = $this->cache_busting_auto_for_group( $overriden_group, $group, $ordered_ad_ids, $args );
        }

        if ( false === $overriden_group ) {
            // The cache-busting module is disabled or the 'off' fallback has been aplied.
            $overriden_group = $this->get_simple_js_group( $overriden_group, $group, $ordered_ad_ids, $args );
        }

        return $overriden_group;
    }

	/**
	 * Prevents serverside check of visitor conditions for passive ads
	 *
	 * @param array $check_options can display check options.
	 *
	 * @return array
	 */
	public function bypass_can_display_check( array $check_options ) {
		return array_merge(
			$check_options,
			[
				'passive_cache_busting' => true
			]
		);
	}

	/**
	 * Return passive ad, prepared for js handler if the conditions are met.
	 *
	 * @param bool|string     $overriden_ad Ad content to override.
	 * @param Ad $ad Ad object.
	 * @param array           $args Arguments passed to ads and groups from top level placements/ads/groups.
	 * @return bool|string
	 */
    public function cache_busting_auto_for_ad( $overriden_ad, Ad $ad, $args ) {
        // If it was requested by placement; if cache-busting option does not exist yet, or exist and = 'auto'.
        $cache_busting_auto = isset( $args['placement_type'] ) && ( ! isset( $args['cache-busting'] ) || $args['cache-busting'] === self::OPTION_AUTO );
        $cache_busting_off = isset( $args['cache-busting'] ) && $args['cache-busting'] === self::OPTION_OFF;
        $prev_is_placement = isset( $args['previous_method'] ) && $args['previous_method'] === 'placement' && isset( $args['previous_id'] );
        $test_id = isset( $args['test_id'] ) ? $args['test_id'] : null;
        $is_passive_all = ! empty( $this->options['passive_all'] );

        if ( $cache_busting_auto  && ! $this->is_passive_method_used() ) { // ajax method
            // ad was requested by group `placement->group->ad` or `group->ad`
            if ( isset( $args['previous_method'] ) && $args['previous_method'] === 'group' && isset( $args['previous_id'] ) ) {
                return $ad;
            }
			$ad_args = $ad->get_prop( 'ad_args' );
			$ad->set_prop_temp(
				'ad_args',
				array_merge(
					$ad_args,
					[
						'cache-busting'      => self::OPTION_ON,
						'cache-busting-orig' => self::OPTION_AUTO,
					]
				)
			);
            $overriden_ad = $this->get_overridden_ajax_ad( $ad, $args );

            if ( false === $overriden_ad ) {
                // static and not test
				return $this->return_ad_with_cb_off( $overriden_ad, $ad, $args );
            }
            return $overriden_ad;
        }
        elseif ( ! $cache_busting_off && ( $cache_busting_auto || $is_passive_all ) ) { // passive method
            // Ad was requested by group `placement->group->ad` or `group->ad`.
            if ( isset( $args['previous_method'] ) && $args['previous_method'] === 'group' && isset( $args['previous_id'] ) ) {
                return $ad;
            }

		    $needs_backend = $this->ad_needs_backend_request( $ad );

            // ad was requested by placement `placement->ad` or `ad`
            // check if ad can be delivered without any cache-busting
            if ( 'static' === $needs_backend && ! $is_passive_all && ! $test_id ) {
				return $this->return_ad_with_cb_off( $overriden_ad, $ad, $args );
            }
            // check if ad cannot be delivered with passive cache-busting
            if ( 'off' === $needs_backend || 'ajax' === $needs_backend ) {
                $is_ajax_fallbback = 'ajax' === $needs_backend;

                if ( isset( $args['output']['placement_id'] ) && ! $this->placement_can_display_not_passive( $args['output']['placement_id'] ) ) {
                    // prevent selection of this placement using JavaScript
                    if ( $test_id ){
                        Advanced_Ads_Pro_Placement_Tests::get_instance()->no_cb_fallbacks[] = $args['previous_id'];
                    }
                    return '';
                }

                if ( $is_ajax_fallbback && $cache_busting_auto ) {
					$ad->set_prop( 'cache-busting', self::OPTION_ON );
					$ad->set_prop( 'cache-busting-orig', self::OPTION_AUTO );
                    return $this->get_overridden_ajax_ad( $ad, $args );
                }

                // `No cache-busting` fallback
                if ( $test_id ) {
                    if ( in_array( $args['previous_id'], Advanced_Ads_Pro_Placement_Tests::get_instance()->get_random_placements() ) ) {
                        Advanced_Ads_Pro_Placement_Tests::get_instance()->delivered_tests[ $args['previous_id'] ] = $test_id;
                    } else {
                        // prevent selection of this placement using JavaScript
                        Advanced_Ads_Pro_Placement_Tests::get_instance()->no_cb_fallbacks[] = $args['previous_id'];
                        return '';
                    }
                }
				return $this->return_ad_with_cb_off( $overriden_ad, $ad, $args );
            }

            if ( ! $ad->can_display( [ 'passive_cache_busting' => true ] ) ) {
                if ( $test_id && array_key_exists( $args['previous_id'], Advanced_Ads_Pro_Placement_Tests::get_instance()->delivered_tests ) ) {
                    Advanced_Ads_Pro_Placement_Tests::get_instance()->delivered_tests[ $args['previous_id'] ] = $test_id;
                }

                return '';
            }

            // deliver ad using passive cache-busting
            // add new info to the passive cache-busting array
            $overriden_ad = $this->get_passive_overriden_ad( $ad, $args );
        }

        if ( $prev_is_placement && false === $overriden_ad && $test_id ) {
            Advanced_Ads_Pro_Placement_Tests::get_instance()->delivered_tests[ $args['previous_id'] ] = $test_id ;
        }

        return $overriden_ad;
    }

	/**
	 * Return ad with cache-busting "off" when it is not needed.
	 *
	 * @param bool|string     $overriden_ad Ad content to override.
	 * @param Ad $ad           Ad object.
	 * @param array           $args         Arguments passed to ads and groups from top level placements/ads/groups.
	 *
	 * @return bool|string
	 */
	private function return_ad_with_cb_off( $overriden_ad, Ad $ad, $args ) {
		$ad_args = $ad->get_prop( 'ad_args' );
		$ad->set_prop_temp(
			'ad_args',
			array_merge(
				$ad_args,
				[
					'cache-busting'      => self::OPTION_OFF,
					'cache-busting-orig' => self::OPTION_AUTO,
					'global_output'      => true,
				]
			)
		);

		if ( isset( $args['output']['placement_id'] ) ) {
			if ( ! $this->placement_can_display_not_passive( $args['output']['placement_id'] ) ) {
				return '';
			}
		}

		return $overriden_ad;
	}

    public function cache_busting_auto_for_group( $overriden_group, Group $group, $ordered_ad_ids, $args ) {
        $prev_is_placement = isset( $args['previous_method'] ) && $args['previous_method'] === 'placement' && isset( $args['previous_id'] );
        $cache_busting_auto = isset( $args['placement_type'] ) && ( ! isset( $args['cache-busting'] ) || $args['cache-busting'] === self::OPTION_AUTO );
        $test_id = isset( $args['test_id'] ) ? $args['test_id'] : null;
        $is_passive_all = ! empty( $this->options['passive_all'] );
        $cache_busting_off = isset( $args['cache-busting'] ) && $args['cache-busting'] === self::OPTION_OFF;

        if ( $cache_busting_auto && ! $this->is_passive_method_used() ) { // ajax method
	        $group_ads = $this->request_passive_ads_of_group( $group, $ordered_ad_ids, $args );
	        $ad_args   = $group->get_prop( 'ad_args' );
            if ( $test_id || ! $this->group_ads_static( $group_ads, $group ) ) {
	            $group->set_prop_temp(
		            'ad_args',
		            array_merge(
			            $ad_args,
			            [
				            'cache-busting'      => self::OPTION_ON,
				            'cache-busting-orig' => self::OPTION_AUTO,
			            ]
		            )
	            );
	            $query           = self::build_js_query( $args );
	            $overriden_group = $this->get_override_content( $query );
            }

            if ( false === $overriden_group ) {
                // Static and does not belong to a test.
				unset( $ad_args['cache_busting_elementid'] );
				$group->set_prop_temp(
					'ad_args',
					array_merge(
						$ad_args,
						[
							'cache-busting'      => self::OPTION_OFF,
							'cache-busting-orig' => self::OPTION_AUTO,
							'global_output'      => true,
						]
					)
				);
				unset( $args['cache_busting_elementid'] );
                if ( isset( $args['output']['placement_id'] ) ) {
                    if ( ! $this->placement_can_display_not_passive( $args['output']['placement_id'] ) ) { return ''; }
                }
            }
            return $overriden_group;
        }
        elseif ( ! $cache_busting_off && ( $cache_busting_auto || $is_passive_all ) ) { // passive method
            if ( is_array( $ordered_ad_ids ) && count( $ordered_ad_ids ) > 0 ) {
                // add info about the group to the passive cache-busting array
                $uniq_key = ++self::$adOffset;

                $group_ads = $this->request_passive_ads_of_group( $group, $ordered_ad_ids, $args );

                foreach ( $group_ads as $ad ) {
                    $needs_backend = $this->ad_needs_backend_request( $ad );

                    if ( 'off' === $needs_backend || 'ajax' === $needs_backend ) {
                        $is_ajax_fallbback = 'ajax' === $needs_backend;

                        // delete info from the passive cache-busting array
                        $this->delete_passive_group( $group, $args, $uniq_key );

                        if ( isset( $args['output']['placement_id'] ) && ! $this->placement_can_display_not_passive( $args['output']['placement_id'] ) ) {
                            // prevent selection of this placement using JavaScript
                            if ( $test_id ){
                                Advanced_Ads_Pro_Placement_Tests::get_instance()->no_cb_fallbacks[] = $args['previous_id'];
                            }
                            return '';
                        }

                        if ( $is_ajax_fallbback && $cache_busting_auto ) {
                            $group->set_prop( 'cache-busting', self::OPTION_ON );
                            $group->set_prop( 'cache-busting-orig', self::OPTION_AUTO );
                            $query = self::build_js_query( $args);
                            return $this->get_override_content( $query );
                        } else {
                            // `No cache-busting` fallback
                            if ( $test_id ) {
                                if ( in_array( $args['previous_id'], Advanced_Ads_Pro_Placement_Tests::get_instance()->get_random_placements() ) ) {
                                    Advanced_Ads_Pro_Placement_Tests::get_instance()->delivered_tests[ $args['previous_id'] ] = $test_id;
                                } else {
                                    // prevent selection of this placement using JavaScript
                                    Advanced_Ads_Pro_Placement_Tests::get_instance()->no_cb_fallbacks[] = $args['previous_id'];
                                    return '';
                                }
                            }

							$ad_args = $group->get_prop( 'ad_args' );
							unset( $ad_args['cache_busting_elementid'] );
							$group->set_prop_temp(
								'ad_args',
								array_merge(
									$ad_args,
									[
										'cache-busting'      => self::OPTION_OFF,
										'cache-busting-orig' => self::OPTION_AUTO,
										'global_output'      => true,
									]
								)
							);

                            unset( $args['cache_busting_elementid'] );
                            if ( isset( $args['output']['placement_id'] ) ) {
                                if ( ! $this->placement_can_display_not_passive( $args['output']['placement_id'] ) ) { return ''; }
                            }

                            return $overriden_group;
                        }
                    }
                }

                if ( $this->group_ads_static( $group_ads, $group ) && ! $is_passive_all && ! $test_id ) {
					$ad_args = $group->get_prop( 'ad_args' );
					unset( $ad_args['cache_busting_elementid'] );
					$group->set_prop(
						'ad_args',
						array_merge(
							$ad_args,
							[
								'cache-busting'      => self::OPTION_OFF,
								'cache-busting-orig' => self::OPTION_AUTO,
								'global_output'      => true,
							]
						)
					);
                    unset( $args['cache_busting_elementid'] );
                    if ( isset( $args['output']['placement_id'] ) ) {
                        if ( ! $this->placement_can_display_not_passive( $args['output']['placement_id'] ) ) { return ''; }
                    }
                    return $overriden_group;
                }

                $output_string = $this->get_passive_overriden_group( $group, $ordered_ad_ids, $args, $uniq_key, $group_ads );
                $overriden_group = $output_string;
            }
        }

        if ( $prev_is_placement && false === $overriden_group && $test_id ) {
            Advanced_Ads_Pro_Placement_Tests::get_instance()->delivered_tests[ $args['previous_id'] ] = $test_id;
        }

        return $overriden_group;

    }

	/**
	 * Request passive ads of a group.
	 *
	 * @param Group      $group          Group
	 * @param array|null $ordered_ad_ids ordered ids of the ads that belong to the group
	 * @param array      $args           argument passed to the 'get_ad_by_group' function
	 */
	private function request_passive_ads_of_group( $group, $ordered_ad_ids, $args ) {
		$args['global_output'] = false;
		$args['is_top_level'] = false;
		$args['ad_label'] = 'disabled';
		$args['group_info'] =  [
			'passive_cb'      => true,
			'id'              => $group->get_id(),
			'name'            => $group->get_title(),
			'type'            => $group->get_type(),
			'refresh_enabled' => Advanced_Ads_Pro_Group_Refresh::is_enabled( $group ),
		];

		$ordered_ad_ids = is_array( $ordered_ad_ids ) ? $ordered_ad_ids : [];
		$group_ads = [];
		foreach ( $ordered_ad_ids as $_ad_id ) {
			// get result from the 'override_ad_select_by_ad' method
			$ad = get_the_ad( $_ad_id, '', $args );

			// Ignore ads that are hidden for all users.
			if ( ! is_an_ad( $ad ) || ! $ad->can_display( [ 'passive_cache_busting' => true ] ) ) {
				continue;
			}

			$ad->set_parent( $group );
			$group_ads[] = $ad;
		}
		return $group_ads;
	}

    /**
     * Get simple js ad.
     * Conditions are not checked for every visitor of a cached page.
	 *
	 * @param bool|string     $overriden_ad Ad content to override.
	 * @param Ad $ad Ad object.
	 * @param array           $args Arguments passed to ads and groups from top level placements/ads/groups.
	 * @return string         Ad content prepared for js handler if the conditions are met
     */
    public function get_simple_js_ad( $overriden_ad, Ad $ad, $args ) {
        $cp_placement = isset( $args['placement_type'] ) && $args['placement_type'] === 'custom_position';

        if (
			! $cp_placement
			// Check if collecting of simple ads has been started.
			|| $this->collecting_js_items
			|| Advanced_Ads_Pro::get_instance()->get_options()['placement-positioning'] === 'php'
		) {
            return $overriden_ad;
        }

        $this->collecting_js_items = true;
        $elementid = $this->generate_elementid();
        $args['cache_busting_elementid'] = $elementid;
		$ad->set_prop( 'cache_busting_elementid', $elementid );
        $overriden_ad = '';

        if ( $ad->can_display() ) {
            // Disable global output because the ads will be tracked using an AJAX request.
			$ad_args = $ad->get_prop( 'ad_args' );
			$ad_args['global_output'] = false;
            $ad->set_prop_temp( 'ad_args', $ad_args );

            $l = count( $this->has_js_items );
            $overriden_ad = $this->add_simple_js_item( $elementid, $ad->output(), $l, $args );

			$ad_args = $ad->get_prop( 'ad_args' );
			$ad_args['global_output'] = true;
			$ad->set_prop_temp( 'ad_args', $ad_args );
        }

        $this->collecting_js_items = false;
        return $overriden_ad;
    }

    /**
     * Get simple js group.
     * Conditions are not checked for every visitor of a cached page.
	 *
	 * @param bool|string        $overriden_group Group content to override.
	 * @param Group $group Group object.
	 * @param int[]              $ordered_ad_ids ids of the ads that belong to the group ordered by their injection order.
	 * @param array              $args Arguments passed to ads and groups from top level placements/ads/groups.
	 * @return bool|string       $overriden_group Overriden group content if conditions are met.
     */
    public function get_simple_js_group( $overriden_group, Group $group, $ordered_ad_ids, $args ) {
        $cp_placement = isset( $args['placement_type'] ) && $args['placement_type'] === 'custom_position';

        if ( ! $cp_placement
			// Check if collecting of simple ads has been started.
			|| $this->collecting_js_items
			|| Advanced_Ads_Pro::get_instance()->get_options()['placement-positioning'] === 'php'
		) {
            return $overriden_group;
        }

        $this->collecting_js_items = true;
        $elementid = $this->generate_elementid();
        $args['cache_busting_elementid'] = $elementid;

		$ad_args = $group->get_prop( 'ad_args' );
		// Disable global output because the ads will be tracked using an AJAX request.
		$ad_args['global_output']           = false;
		$ad_args['cache_busting_elementid'] = $elementid;
		$group->set_prop_temp( 'ad_args', $ad_args );

        $l = count( $this->has_js_items );
        $overriden_group = $this->add_simple_js_item( $elementid, $group->output( $ordered_ad_ids ), $l, $args );

		$ad_args                  = $group->get_prop( 'ad_args' );
		$ad_args['global_output'] = true;
		$group->set_prop_temp( 'ad_args', $ad_args );

        $this->collecting_js_items = false;

        return $overriden_group;
    }

    /**
     * Add simple js item.
     *
     * @param string $elementid Wrapper id.
     * @param string $output Ad/Group output.
     * @param int $l Number of existing simple js items.
     * @param array $args Placement options.
     * @return string Wrapper id.
     */
    function add_simple_js_item( $elementid, $output, $l, $args ) {
		if ( isset( $args['output']["placement_id"] ) ) {
			$placement = wp_advads_get_placement( $args['output']['placement_id'] );

			$this->has_js_items[] = [
				'id' => $args['output']["placement_id"],
				'type' => 'placement',
				'title' => $placement->get_title() ?? '',
				'blog_id' => get_current_blog_id()
			];
		}

        $js_item = [
            'output' => $output,
            'elementid' => $elementid,
            'args' => $args,
            'has_js_items' => array_slice( $this->has_js_items, $l ),
        ];

        $js_item = apply_filters(
            'advanced-ads-cache-busting-item',
            $js_item,
            [
                'method' => 'placement',
                'args' => $args
            ]
        );

        $this->js_items[] = $js_item;


        /**
         * Collect blog data before `restore_current_blog` is called.
         */
        if ( class_exists( Data::class, false ) ) {
            Data::collect_blog_data();
        }

        $placement_id = ! empty( $args['output']['placement_id'] ) ? $args['output']['placement_id'] : '';

		return $this->create_wrapper( $elementid, $placement_id, $args );
    }

    /**
     * add data related to ad and ad placement to js array
     *
     * @param obj $ad Ad
     * @param array $args argument passed to the 'get_ad_by_id' function
     * @return string
     */
    private function get_passive_overriden_ad( Ad $ad, $args ) {
        $cache_busting_auto = isset( $args['placement_type'] ) && ( ! isset( $args['cache-busting'] ) || $args['cache-busting'] === self::OPTION_AUTO );

        if ( $cache_busting_auto ) {
            $js_array = & $this->passive_cache_busting_placements;
            $id = $args['previous_id'];
        } else {
            $js_array = & $this->passive_cache_busting_ads;
            $id = $args['id'];
        }
        $uniq_key = $id . '_' . ++self::$adOffset;

		$not_head                        = ! $this->isHead || ( isset( $args['placement_type'] ) && $args['placement_type'] !== 'header' );
		$elementid                       = $not_head ? $this->generate_elementid() : null;
		$args['cache_busting_elementid'] = $elementid;
		$ad->set_prop( 'cache_busting_elementid', $elementid );
		$placement_id                    = ! empty( $args['output']['placement_id'] ) ? $args['output']['placement_id'] : '';
		$output_string                   = $not_head ? $this->create_wrapper( $elementid, $placement_id, $args ) : '';

        $js_array[ $uniq_key ] = [
            'elementid' => [ $elementid ],
            'ads' => [ $ad->get_id() => $this->get_passive_cb_for_ad( $ad ) ], // only 1 ad
        ];


        if ( $cache_busting_auto ) {
            $test_id = isset( $args['test_id'] ) ? $args['test_id'] : null;

            $js_array[ $uniq_key ]['type'] = 'ad';
            $js_array[ $uniq_key ]['id'] = $ad->get_id();
            $js_array[ $uniq_key ]['placement_info']  = $this->get_placement_info( $id );
            $js_array[ $uniq_key ]['test_id'] = $test_id;
			$item_for_ab = Advanced_Ads_Pro_Module_Ads_For_Adblockers::get_item_for_adblocker( $ad );

            if ( is_an_ad( $item_for_ab ) ) {
                $js_array[ $uniq_key ]['ads_for_ab'] = [ $item_for_ab->get_id() => $this->get_passive_cb_for_ad( $item_for_ab ) ];
            }

			if ( is_a_group( $item_for_ab ) ) {
				$js_array[ $uniq_key ]['groups_for_ab'] = [
					'id'             => $item_for_ab->get_id(),
					'name'           => $item_for_ab->get_title(),
					'weights'        => $item_for_ab->get_ad_weights(),
					'type'           => $item_for_ab->get_type(),
					'ordered_ad_ids' => $item_for_ab->get_ordered_ad_ids(),
					'ad_count'       => $item_for_ab->get_ad_count(),
				];
				$ads_for_ab                             = $item_for_ab->get_ads();
				$js_array[ $uniq_key ]['groups_for_ab']['ads']    = [];
				foreach ( $ads_for_ab as $item ) {
					$js_array[ $uniq_key ]['groups_for_ab']['ads'][ $item->get_id() ] = $this->get_passive_cb_for_ad( $item );
				}
			}

            if ( 'ajax' === $this->fallback_method ) {
                $ajax_info = $this->server_info->get_ajax_for_passive_placement( $ad, $args, $elementid );
                if ( $ajax_info ) {
                    $js_array[ $uniq_key ] = array_merge( $js_array[ $uniq_key ], $ajax_info );
                }
            }
        }

        $js_array[ $uniq_key ] = apply_filters(
            'advanced-ads-cache-busting-item',
            $js_array[ $uniq_key ],
            [
                'method' => $cache_busting_auto ? 'placement' : 'ad',
                'args' => $args
            ]
        );

        return $output_string;
    }

	/**
	 * Add data related to group and group placement to js array
	 *
	 * @param Group      $group          the group.
	 * @param array|null $ordered_ad_ids ordered ids of the ads that belong to the group.
	 * @param array      $args           argument passed to the 'get_ad_by_group' function.
	 * @param string     $uniq_key       Property name in JS array.
	 * @param array      $group_ads      Group ads.
	 *
	 * @return string
	 */
    private function get_passive_overriden_group( Group $group, $ordered_ad_ids, $args, $uniq_key, $group_ads ) {
        $cache_busting_auto = isset( $args['placement_type'] ) && ( ! isset( $args['cache-busting'] ) || $args['cache-busting'] === self::OPTION_AUTO );

        if ( $cache_busting_auto ) {
            $js_array = & $this->passive_cache_busting_placements;
            $id = $args['previous_id'];
        } else {
            $js_array = & $this->passive_cache_busting_groups;
            $id = $args['id'];
        }
        $uniq_key = $id . '_' . $uniq_key;

		$not_head                        = ! $this->isHead || ( isset( $args['placement_type'] ) && $args['placement_type'] !== 'header' );
		$elementid                       = $not_head ? $this->generate_elementid() : null;
		$args['cache_busting_elementid'] = $elementid;
		$group->set_prop( 'cache_busting_elementid', $elementid );
		$placement_id                    = ! empty( $args['output']['placement_id'] ) ? $args['output']['placement_id'] : '';
		$output_string                   = $not_head ? $this->create_wrapper( $elementid, $placement_id, $args ) : '';

        if ( ( $ad_count = apply_filters( 'advanced-ads-group-ad-count', $group->get_ad_count(), $group ) ) === 'all' ) {
            $ad_count = 999;
        }

        $passive_ads = [];
        foreach ( $group_ads as $group_ad ) {
            $passive_ads[ $group_ad->get_id() ] = $this->get_passive_cb_for_ad( $group_ad );
        }

        $js_array[ $uniq_key ] =  [
            'type'=> 'group',
            'id' => $group->get_id(),
            'elementid' => [ $elementid ],
            'ads' => $passive_ads,
            'group_info' => [
                'id' => $group->get_id(),
                'name' => $group->get_title(),
				'weights' => $group->get_ad_weights( $ordered_ad_ids ),
                'type' => $group->get_type(),
                'ordered_ad_ids' => $ordered_ad_ids,
                'ad_count' => $ad_count,
            ],
        ];

        // deprecated after Advaned Ads Slider > 1.3.1
        if ( $group->is_type( 'slider' ) && defined( 'AAS_VERSION' ) && version_compare( AAS_VERSION, '1.3.1', '<=' ) ) {
            $slider_options = Frontend::get_slider_options( $group );
            $js_array[ $uniq_key ]['group_info']['slider_options'] = $slider_options;
        }



        if ( Advanced_Ads_Pro_Group_Refresh::is_enabled( $group ) ) {
            $js_array[ $uniq_key ]['group_info']['refresh_enabled'] = true;
            $js_array[ $uniq_key ]['group_info']['refresh_interval_for_ads'] = Advanced_Ads_Pro_Group_Refresh::get_ad_intervals( $group );
        }

        $label = '';
        if ( method_exists( Advanced_Ads::get_instance(), 'get_label' ) ) {
            $placement_state = isset( $args['ad_label'] ) ? $args['ad_label'] : 'default';
            $label = Advanced_Ads::get_instance()->get_label( $group, $placement_state );
        }

        if ( $cache_busting_auto ) {
			$js_array[ $uniq_key ]['placement_info'] = $this->get_placement_info( $id );
			$js_array[ $uniq_key ]['test_id']        = isset( $args['test_id'] ) ? $args['test_id'] : null;
			$placement                               = $group->get_root_placement();
			$item_for_ab                             = false;

			if ( $placement ) {
				$placement->set_prop_temp( 'ad_label', false );
				$item_for_ab = Advanced_Ads_Pro_Module_Ads_For_Adblockers::get_item_for_adblocker( $placement );
			}

			if ( is_an_ad( $item_for_ab ) ) {
				$js_array[ $uniq_key ]['ads_for_ab'] = [ $item_for_ab->get_id() => $this->get_passive_cb_for_ad( $item_for_ab ) ];
			}

			if ( is_a_group( $item_for_ab ) ) {
				$js_array[ $uniq_key ]['groups_for_ab'] = [
					'id'             => $item_for_ab->get_id(),
					'name'           => $item_for_ab->get_title(),
					'weights'        => $item_for_ab->get_ad_weights(),
					'type'           => $item_for_ab->get_type(),
					'ordered_ad_ids' => $item_for_ab->get_ordered_ad_ids(),
					'ad_count'       => $item_for_ab->get_ad_count(),
				];
				$ads_for_ab                             = $item_for_ab->get_ads();
				$js_array[ $uniq_key ]['groups_for_ab']['ads']    = [];
				foreach ( $ads_for_ab as $item ) {
					$js_array[ $uniq_key ]['groups_for_ab']['ads'][ $item->get_id() ] = $this->get_passive_cb_for_ad( $item );
				}
			}

            if ( 'ajax' === $this->fallback_method ) {
                $ajax_info = $this->server_info->get_ajax_for_passive_placement( $group_ads, $args, $elementid );
                if ( $ajax_info ) {
                    $js_array[ $uniq_key ] = array_merge( $js_array[ $uniq_key ], $ajax_info );
                }
            }
        }

        $js_array[ $uniq_key ] = apply_filters( 'advanced-ads-pro-passive-cb-group-data', $js_array[ $uniq_key ], $group, $elementid );

        // Add wrapper around group.
		$wrapper = $group->create_wrapper();
        if ( ( ! empty( $wrapper ) || $label )
            && is_array( $wrapper )
            && class_exists( 'Advanced_Ads_Utils' ) && method_exists( 'Advanced_Ads_Utils' , 'build_html_attributes' )
        ) {
			$before = '<div' . Advanced_Ads_Utils::build_html_attributes( $wrapper ) . '>'
                . $label
                . apply_filters( 'advanced-ads-output-wrapper-before-content-group', '', $group );

            $after = apply_filters( 'advanced-ads-output-wrapper-after-content-group', '', $group )
                . '</div>';
            if ( ! empty( $group->get_prop( 'placement_clearfix' ) ) ) {
                $after .= '<br style="clear: both; display: block; float: none; "/>';
            }

            $js_array[ $uniq_key ]['group_wrap'][] = [
                'before' => $before,
                'after' => $after,
            ];

        }
        $js_array[ $uniq_key ] = apply_filters(
            'advanced-ads-cache-busting-item',
            $js_array[ $uniq_key ],
            [
                'method' => $cache_busting_auto ? 'placement' : 'group',
                'args' => $args
            ]
        );

        return $output_string;
    }

	/**
	 * Get placement information
	 *
	 * @param string $id Placement id.
	 */
	private function get_placement_info( $id ) {
		// The information which passive cache-busting (`base.js`) can read.
		// When a new placement option is added and passive cache-busting needs to access it, it should be added to the array.
		$allowed_keys = [ 'id', 'lazy_load', 'test_id', 'layer_placement', 'close', 'inject_by', 'placement_position', 'pro_custom_element', 'container_id', 'cache_busting_empty' ];

		$placement_info = wp_advads_get_placement( $id );
		if ( ! $placement_info ) {
			return [];
		}

		$placement_info = $placement_info->get_data();
		$placement_info['id'] = (string) $id;

		if ( ! empty( $placement_info['options'] ) && is_array( $placement_info['options'] ) ) {
			foreach ( $placement_info['options'] as $k => $option ) {
				if ( ! in_array( $k, $allowed_keys, true ) ) {
					unset( $placement_info['options'][ $k ] );
				}
			}
		}

		return $placement_info;
	}

    /**
     * add new passive ad to passive cb js array
     *
     * @param obj $ad Ad
     * @param array $args argument passed to the 'get_ad_by_id' function
     * @param str $uniq_key Property name in JS array.
     */
    private function add_passive_ad_to_group( Ad $ad, $args, $uniq_key ) {
        $cache_busting_auto = isset( $args['placement_type'] ) && ( ! isset( $args['cache-busting'] ) || $args['cache-busting'] === self::OPTION_AUTO );

        if ( $cache_busting_auto ) {
            $uniq_key = $args['previous_id'] . '_' . $uniq_key;
            $this->passive_cache_busting_placements[ $uniq_key ]['ads'][ $ad->get_id() ] = $this->get_passive_cb_for_ad( $ad );
        } else {
            $uniq_key = $args['id'] . '_' . $uniq_key;
            $this->passive_cache_busting_groups[ $uniq_key ]['ads'][ $ad->get_id() ] = $this->get_passive_cb_for_ad( $ad );
        }
    }

    /**
     * delete an ad from passive cb js array
     *
     * @param $group Group
     * @param array $args argument passed to the 'get_ad_by_id' function
     * @param str $uniq_key Property name in JS array.
     */
    private function delete_passive_group( Group $group, $args, $uniq_key ) {
        $cache_busting_auto = isset( $args['placement_type'] ) && ( ! isset( $args['cache-busting'] ) || $args['cache-busting'] === self::OPTION_AUTO );

        if ( $cache_busting_auto ) {
            $uniq_key = $args['previous_id'] . '_' . $uniq_key;
            unset( $this->passive_cache_busting_placements[ $uniq_key ] );
        } else {
            $uniq_key = $args['id'] . '_' . $uniq_key;
            unset( $this->passive_cache_busting_groups[ $uniq_key ] );
        }
    }

    /**
     * get ad info for passive cache-busting
     *
	 * @param Ad $ad ad object.
     * @return array
     */
    public function get_passive_cb_for_ad( Ad $ad ) {
		$ad_options               = $ad->get_data();
		$ad_args                  = $ad->get_prop( 'ad_args' );
		$ad_args['cache-busting'] = self::OPTION_AUTO;
		$ad_args['global_output'] = false;
		$ad->set_prop_temp( 'ad_args', $ad_args );

		add_filter( 'advanced-ads-can-display-ad-check-options', [ $this, 'bypass_can_display_check' ] );

		$passive_cb_for_ad = apply_filters( 'advanced-ads-pro-passive-cb-for-ad', [
			'id'            => $ad->get_id(),
			'title'         => $ad->get_title(),
			'expiry_date'   => $ad->get_expiry_date(),
			'visitors'      => array_values( $ad->get_visitor_conditions() ),
			'content'       => $ad->output(),
			'once_per_page' => $ad->get_prop( 'once_per_page' ) ? 1 : 0,
			'debugmode'     => $ad->is_debug_mode(),
			'blog_id'       => get_current_blog_id(),
			'type'          => $ad->get_type(),
			'position'      => $ad->get_position(),
		], $ad );

		remove_filter( 'advanced-ads-can-display-ad-check-options', [ $this, 'bypass_can_display_check' ] );

		// Consent overridden for this ad.
		$passive_cb_for_ad['privacy']['ignore'] = ! empty( $ad_options['privacy']['ignore-consent'] );
		// This ad has custom code and therefore needs consent (if not overridden above).
		$passive_cb_for_ad['privacy']['needs_consent'] = ! empty( Advanced_Ads_Pro::get_instance()->get_custom_code( $ad ) );

		/**
		 * Collect blog data before `restore_current_blog` is called.
		 */
		if ( class_exists( Data::class, false ) ) {
            Data::collect_blog_data();
        }

        return $passive_cb_for_ad;
    }

    /**
     * return wrapper and js code to load the ad
     *
     * @param Ad $ad Ad
     * @param array $args argument passed to the 'get_ad_by_id' function
     * @return string/bool $overridden_ad
     */
    public function get_overridden_ajax_ad( $ad, $args ) {
        $overridden_ad = false;
        $test_id = isset( $args['test_id'] ) ? $args['test_id'] : null;
        $needs_backend = $this->ad_needs_backend_request( $ad );

        if ( 'static' !== $needs_backend || $test_id ) {
            $query = self::build_js_query( $args);
            $overridden_ad = $this->get_override_content( $query );
        }

        return $overridden_ad;
    }

	/**
	 * Determine if backend request is needed.
	 *
	 * @param Ad $ad Ad object.
	 * @return string
	 *     'static'   Do not use cache-busting. There are no dynamic conditions, all users will see the same.
	 *     'off'      Do not use cache-busting (fallback).
	 *     'ajax'     Use AJAX request (fallback).
	 *     'passive'  Use passive cache-busting.
	 */
    public function ad_needs_backend_request( Ad $ad ) {
        $ad_options = $ad->get_data();
		$visitors = $ad->get_visitor_conditions();

		// code is evaluated as php if setting was never saved or php is allowed
        $allow_php = ( $ad->is_type( 'plain' ) && $ad->is_php_allowed() );
        // if there is at least one visitor condition (check old "visitor" and new "visitors" conditions)
		$is_visitor_conditions = ! empty( $visitors );
        $is_group = $ad->is_type( 'group' );
        $has_shortcode = ! empty( $ad_options['output']['has_shortcode'] )
            // The Rich Content ad type saved long time ago.
            || ( ! isset( $ad_options['output']['has_shortcode'] ) && $ad->is_type( 'content' ) );

		$placement    = $ad->get_root_placement();
		$is_lazy_load = $this->lazy_load_enabled && $placement && 'enabled' === $placement->get_prop( 'lazy_load' );

        // Check if there is conditions that need backend request.
        $has_not_js_conditions = false;

		if ( ! empty( $visitors ) ) {
            // Conditions that can be checked using js.
            $js_visitor_conditions = [
                'mobile',
                'referrer_url',
                'user_agent',
                'request_uri',
                'browser_lang',
                'cookie',
                'page_impressions',
                'ad_impressions',
                'new_visitor',
                'device_width',
                'tablet',
                'adblocker'
            ];

			if ( $this->fallback_method === 'ajax' && $ad->get_root_placement() ) {
                // Conditions that can be checked by passive cache-busting only if cookies exist.
                // If not, ajax cache-busting will not be used.
                $all_server_conditions = $this->server_info->get_all_server_conditions();
                $js_visitor_conditions = array_merge( $js_visitor_conditions, array_keys( $all_server_conditions ) );
            }


            $js_visitor_conditions = apply_filters( 'advanced-ads-js-visitor-conditions', $js_visitor_conditions );

            foreach ( $visitors as $visitor ) {
                if ( ! in_array( $visitor['type'], $js_visitor_conditions ) && 'unknown' !== $visitor['type'] ) {
                    // Use AJAX cache-busting, or disable cache-busting.
                    $has_not_js_conditions = true;
                }
            }
        }

        $has_tracking = false;
        if ( function_exists( 'wp_advads_tracking' ) &&
            ( ( isset( $ad_options['tracking']['impression_limit'] ) && $ad_options['tracking']['impression_limit'] ) ||
            ( isset( $ad_options['tracking']['click_limit'] ) && $ad_options['tracking']['click_limit'] ) )
        ) {
            // Use AJAX cache-busting, or disable cache-busting.
            $has_tracking = true;
        }

        $hidden_without_consent = false;

		if ( empty( $ad_options['privacy']['ignore-consent'] ) && class_exists( 'Advanced_Ads_Privacy' ) ) {
			$privacy_options = Advanced_Ads_Privacy::get_instance()->options();

			if (
				// Cookie method enabled.
				! empty( $privacy_options['enabled'] ) && 'custom' === $privacy_options['consent-method']
				// Do not show non-personalized AdSense ads until consent (i.e. do not show AdSense ads at all until consent).
				&& empty( $privacy_options['show-non-personalized-adsense'] ) && $ad->is_type( 'adsense' )
			) {
				$hidden_without_consent = true;
			}
		}

		$specific_days            = $ad->has_weekdays();
		$placement                = $ad->get_root_placement();
		$close                    = $placement ? $placement->get_prop( 'close' ) : false;

		if ( $placement && $placement->get_prop( 'layer_placement' ) ) {
			$close = $placement->get_prop( 'layer_placement.close' ) ?? false;
		}

		$random_paragraph         = $placement && $placement->is_type( 'post_content_random' );
		$checks_placement_cookies = $close && isset( $close['enabled'], $close['timeout_enabled'] );

        if ( $allow_php || $is_group || $has_shortcode || $has_not_js_conditions || $has_tracking ) {
            // Use AJAX cache-busting, or disable cache-busting.
            $return = $this->fallback_method;
		} elseif ( $is_visitor_conditions || $is_lazy_load || $hidden_without_consent || $specific_days || $checks_placement_cookies || $random_paragraph ) {
            // Passive cache-busting.
            $return = 'passive';
        } else {
            $return = 'static';
        }

        $return = apply_filters( 'advanced-ads-pro-ad-needs-backend-request', $return, $ad, $this->fallback_method );
        return $return;
    }

    /**
     * Determine if all ads of a group are static.
     *
     * @param Ad[] $group_ads An array of ad objects.
     * @param Group $group Group object.
     * @return bool
     */
    private function group_ads_static( $group_ads, $group ) {
        if ( 0 === count( $group_ads )  ) {
            return true;
        }
        if ( 1 === count( $group_ads ) ) {
			$cb_method =  $this->ad_needs_backend_request( $group_ads[0] );
			if ( 'static' === $cb_method ) {
				$ad_args                  = $group->get_prop( 'ad_args' );
				$ad_args['global_output'] = true;
				$group->set_prop_temp( 'ad_args', $ad_args );
			}
            return 'static' === $cb_method;
        }

        return false;
    }

	/**
	 * Prepare query for js handler
	 *
	 * @param array $arguments
	 * @return array query
	 */
	public static function build_js_query( $arguments ) {
		// base query (required keys)
		$query = [
			'id' => (int) $arguments['id'],
			'method' => (string) $arguments['method'],
		];
		$arguments['global_output'] = true;

		// process further arguments (optional keys)
		$params = array_diff_key( $arguments, [ 'id' => false, 'method' => false ] );

		if ( $params !== [] ) {
			$query['params'] = $params;
		}
		return $query;
	}

    /**
     * Determine override option for query.
     *
     * @param array $query
     *
     * @return boolean
     */
    protected function can_override( $query ) {
        $params = isset( $query['params'] ) ? $query['params'] : [];

        // allow disable cache-busting according to placement settings
        if ( $query['method'] === 'placement' && ! isset( $params['cache-busting'] ) ) {
			$placement = wp_advads_get_placement( intval( $query['id'] ) );

            if ( $placement && null !== $placement->get_prop( 'cache-busting' ) ) {
                $params['cache-busting'] = $placement->get_prop( 'cache-busting' );
            }
        }

        return isset( $params['cache-busting'] ) && $params['cache-busting'] === self::OPTION_ON;
    }

    /**
     * Check if passive cache-busting can be used.
     *
     * @param array $args argument passed to ads.
     * @return bool
     */
    private function can_override_passive( $args ) {
        if ( ! empty( $args['wp_the_query']['is_feed'] ) || ! array_key_exists( 'previous_method', $args ) || ! array_key_exists( 'previous_id', $args ) ) {
            return false;
        }

        // Prevent non-header placement from being collected through wp_head.
        if ( doing_action( 'wp_head' ) && isset( $args['placement_type'] ) && 'header' !== $args['placement_type']
            && ! $this->can_inject_during_wp_head() ) {
            return false;
        }

        if ( isset( $args['cache-busting'] ) && $args['cache-busting'] === self::OPTION_IGNORE ) {
            return false;
        }

        return true;
    }

    /**
     * Prepare ad for js handler.
     *
     * @param array $query
     * @return string
     */
    protected function get_override_content( $query ) {
        $content = '';

        // Prevent non-header placement from being collected through wp_head.
        if ( doing_action( 'wp_head' ) && isset( $query['params']['placement_type'] ) && 'header' !== $query['params']['placement_type']
            && ! $this->can_inject_during_wp_head() ) {
            return $content;
        }

        // <head> scripts require no wrapper
        if ( ! $this->isHead
            || ( isset( $query['params']['placement_type'] ) && $query['params']['placement_type'] !== 'header' )
        ) {
            $query['elementid'] = $this->generate_elementid();

            // Get placement id
            if ( ! empty( $query['method'] ) && 'placement' === $query['method'] && ! empty( $query['id'] )  ) {
                // Cache-busting: "ajax"
                $placement_id = $query['id'];
            } elseif( ! empty( $query['params']['output']['placement_id'] )  ) {
                // AJAX fallback
                $placement_id = $query['params']['output']['placement_id'];
            } else {
                $placement_id = '';
            }

			$content .= $this->create_wrapper( $query['elementid'], $placement_id, $query['params'] );
        }

        $query = $this->get_ajax_query( $query );
        self::$ajax_queries[] = $query;
        return $content;
    }

	/**
	 * Get ajax query.
	 *
	 * @param array $query
	 * @param bool $request_placement Whether or not to request top level placement.
	 * @return array
	 */
    public function get_ajax_query( $query, $request_placement = true ) {
        // Request placement.
        if ( $request_placement && isset( $query['params']['output']['placement_id'] ) ) {
            $query['method'] = 'placement';
            $query['id'] = $query['params']['output']['placement_id'];
        }
        $query['blog_id'] = get_current_blog_id();

        /**
         * Collect blog data before `restore_current_blog` is called.
         */
		if ( class_exists( Data::class, false ) ) {
            Data::collect_blog_data();
        }

        // Check if the `advanced-ads-ajax-ad-select-arguments` filter exists.
        if ( ! empty( $query['params'] ) && version_compare( ADVADS_VERSION, '1.24.0', '>' ) ) {
			$query['params'] = $this->remove_default_ajax_args( $this->ajax_default_args, $query['params'] );
			$query['params'] = $this->extract_general_ajax_args( $query['params'] );

        }

		return $query;
    }

	/**
	 * Remove default AJAX arguments to reduce the size of the array printed in footer.
	 *
	 * @param array $default Default arguments.
	 * @param array $source A full list of arguments that we need to be minifed.
	 * @return array Minified array (source array that does not contain default arguments).
	 */
	private function remove_default_ajax_args( $default, $source ) {
		$result = [];

		foreach ( $source as $key => $f ) {
			if ( ! array_key_exists( $key, $default ) ) {
				$result[ $key ] = $source[ $key ];
				continue;
			}

			if ( $source[ $key ] === $default[ $key ] ) {
				continue;
			}

			if (
				! is_array( $default[ $key ] )
				|| ! is_array( $source[ $key ] )
			) {
				$result[ $key ] = $source[ $key ];
				continue;
			}

			$key_result = $this->remove_default_ajax_args( $default[ $key ], $source[ $key ] );
			if ( $key_result !== [] ) {
				$result[ $key ] = $key_result;
			}
		}

		return $result;
	}

	/**
	 * Extract general AJAX arguments into separate array to reduce the size of the array printed in footer.
	 *
	 * @param array $source A full list of arguments to extract general arguments from.
	 * @return array A list of arguments with general arguments removed.
	 */
	private function extract_general_ajax_args( $source ) {
		if ( wp_doing_ajax() ) {
			// Do nothing because we are not able to add data to the footer array.
			return $source;
		}
		if ( isset( $source['post'] ) ) {
			$ref = array_search( $source['post'], $this->ajax_queries_args, true );
			if ( $ref === false ) {
				$ref = 'r' . count( $this->ajax_queries_args );
				$this->ajax_queries_args[ $ref ] = $source['post'];
			}
			$source['post'] = $ref;
		}
		return $source;
	}

	/**
	 * Add default AJAX arguments that were removed to reduce the size of the array printed in footer.
	 *
	 * @see self::remove_default_ajax_args
	 *
	 * When the item in the default array is not an array, it will be replaced by the item in the minified array.
	 * When an item exists in either associative array, it will be added. Numeric keys are overridden.
	 *
	 * @param array $arguments Minified arguments.
	 * @param array $request Current ad request.
	 * @return array New arguments.
	 */
	public function add_default_ajax_arguments( $arguments, $request ) {
		if ( ! empty( $request['elementId'] ) ) {
			$arguments['cache_busting_elementid'] = $request['elementId'];
		}

		return array_replace_recursive( $this->ajax_default_args, $arguments );
	}

	/**
	 * Create wrapper for cache-busting.
	 *
	 * @param string $element_id   Id of the wrapper.
	 * @param string $placement_id Id of the placement.
	 * @param array  $args         Custom arguments of ad or group.
	 *
	 * @return string Cache-busting wrapper.
	 */
	private function create_wrapper( $element_id, $placement_id = '', $args = [] ) {
		$class     = $element_id;
		$placement = wp_advads_get_placement( is_numeric( $placement_id ) ? (int) $placement_id : $placement_id );
		if ( $placement ) {
			$prefix = wp_advads()->get_frontend_prefix();
			$class .= ' ' . $prefix . $placement->get_slug();
		}
		$style           = ! empty( $args['inline-css'] ) ? 'style="' . $args['inline-css'] . '"' : '';
		$wrapper_element = ! empty( $args['inline_wrapper_element'] ) ? 'span' : 'div';

		// TODO: `id` is deprecated.
		return '<' . $wrapper_element . ' ' . $style . ' class="' . $class . '" id="' . $element_id . '"></' . $wrapper_element . '>';
	}




    /**
     * Generate unique element id
     *
     * @return string
     */
    public function generate_elementid() {
        $prefix = wp_advads()->get_frontend_prefix();
        return $prefix . md5( 'advanced-ads-pro-ad-' . uniqid( ++self::$adOffset, true ) );
    }

    /**
     * Check if placement can be displayed without passive cache-busting.
     *
     * @param string $id Placement id.
     * @see placement_can_display()
     * @return bool
     */
    private function placement_can_display_not_passive( $id ) {
        // We force this filter to return true when collecting placements for passive cache-busting.
        // For now revoke this behavior
        return apply_filters( 'advanced-ads-can-display-placement', true, $id );
    }

    /**
     * check if placement was closed before
     *
     * @param int $id placement id
     * @return bool whether placement can be displayed or not
     */
    public function placement_can_display( $return, $id = 0 ){
        static $checked_passive = [];

        if ( in_array( $id, $checked_passive ) ) {
            // Ignore current filter when the placement is delivered without passive cache-busting.
            return $return;
        }

		$options = wp_advads_get_placement( $id )->get_data();

        $cache_busting_auto = ! isset( $options['cache-busting'] ) || $options['cache-busting'] === self::OPTION_AUTO;

        if ( $cache_busting_auto && $this->is_passive_method_used() ) {
            $checked_passive[] = $id;
            return true;
        }

        return $return;
    }

    /**
     * determines, whether the "passive"  method is used or not
     *
     * @return bool true if the "passive" method is used, false otherwise
     */
    public function is_passive_method_used() {
        return isset( $this->options['default_auto_method'] ) && $this->options['default_auto_method'] === 'passive';
    }

    /**
     * determines, whether or not to load tracking scripts
     *
     * @param bool  $need_load_header_scripts
     * @return bool true if tracking scripts should be loaded, $need_load_header_scripts otherwise
     */
    public function load_tracking_scripts( $need_load_header_scripts ) {
        //the script is used by: passive cache-busting, 'group refresh' feature
        return true;
    }

    /**
     * Add ad debug content
     *
     * @param arr $content
     * @param obj $ad Ad
     * @return arr $content
     */
    public function add_debug_content( $content, Ad $ad ) {
        $needs_backend = $this->ad_needs_backend_request( $ad );
        if ( 'off' === $needs_backend || 'ajax' === $needs_backend ) {
            $info = __( 'The ad can not work with passive cache-busting', 'advanced-ads-pro' );
        } else {
            $info = __( 'The ad can work with passive cache-busting', 'advanced-ads-pro' );
        }

        if ( $this->is_ajax ) {
            $name = _x( 'ajax', 'setting label', 'advanced-ads-pro' );
        } elseif ( self::OPTION_AUTO === $ad->get_prop( 'ad_args.cache-busting' ) ) {
            $name =  __( 'passive', 'advanced-ads-pro' );
            $info .= '<br />##advanced_ads_passive_cb_debug##'
            . sprintf( '<div class="advads-passive-cb-debug" style="display:none;" data-displayed="%s" data-hidden="%s"></div>',
                __( 'The ad is displayed on the page', 'advanced-ads-pro' ),
                __( 'The ad is not displayed on the page', 'advanced-ads-pro' )
            );
        } else {
            $name = _x( 'off', 'setting label', 'advanced-ads-pro' );
        }

        $content[] = sprintf( '%s <strong>%s</strong><br />%s', _x( 'Cache-busting:', 'placement admin label', 'advanced-ads-pro' ), $name, $info );


        return $content;
    }

    /**
     * Check if the ad can be displayed based on display limit.
     * Handle "Custom position" placements that have cache-busting disabled.
     *
     * @param bool $can_display Existing value.
     * @param obj $ad Ad object
     * @param array $check_options
     * @return bool true if limit is not reached, false otherwise
     */
    public function can_display_by_display_limit( $can_display, Ad $ad, $check_options ) {
        if ( ! $can_display ) {
            return false;
        }

        if ( ! $this->collecting_js_items ) {
            return $can_display;
        }

        if ( $ad->get_prop( 'once_per_page' ) ) {
            foreach ( $this->has_js_items as $item ) {
                if ( $item['type'] === 'ad' && absint( $item['id'] ) === $ad->get_id() ) {
                    return false;
                }
            }
        }
        return true;
    }

	/**
	 * Check whether the module is enabled.
	 *
	 * @return bool.
	 */
	public static function is_enabled() {
		$options = Advanced_Ads_Pro::get_instance()->get_options();
		return ! empty( $options['cache-busting']['enabled'] );
	}

    /**
     * Check if placements of type other than `header` can be injected during `wp_head` action.
     */
    private function can_inject_during_wp_head() {
        return Compatibility::can_inject_during_wp_head();
    }

	/**
	 * Check if TCF privacy is active; only do this when cache-busting is turned off.
	 * If yes, add a script to handle decoded ads due to TCF privacy settings.
	 */
	public function check_for_tcf_privacy() {
		$options = Advanced_Ads_Privacy::get_instance()->options();
		if ( ! isset( $options['enabled'] ) || $options['consent-method'] !== 'iab_tcf_20' ) {
			return;
		}

		wp_enqueue_script(
			// we need the same handle as with cache-busting so tracking still works.
			'advanced-ads-pro/cache_busting',
			AAP_BASE_URL . 'assets/dist/privacy.js',
			[ ADVADS_SLUG . '-advanced-js', 'jquery'],
			AAP_VERSION,
			true
		);
	}
}
