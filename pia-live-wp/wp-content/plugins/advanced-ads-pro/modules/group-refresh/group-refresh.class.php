<?php // phpcs:ignore WordPress.Files.FileName

use AdvancedAds\Constants;
use AdvancedAds\Abstracts\Ad;
use AdvancedAds\Abstracts\Group;
use AdvancedAds\Framework\Utilities\Arr;

/**
 * The Group refresh class.
 */
class Advanced_Ads_Pro_Group_Refresh {
	/**
	 * Data related to each group.
	 *
	 * @var array
	 */
	private $state_groups = [];

	/**
	 * Caches shown group ids.
	 *
	 * @var array
	 */
	private $shown_group_ids = [];

	/**
	 * If we are in a current AJAX call.
	 *
	 * @var bool
	 */
	public $is_ajax = false;

	/**
	 * Group IDs with all ads already shown using AJAX.
	 *
	 * @var array
	 */
	private $all_ads_shown = [];

	/**
	 * The Constructor.
	 */
	public function __construct() {
		$options = Advanced_Ads_Pro::get_instance()->get_options();

		if ( empty( $options['cache-busting']['enabled'] ) ) {
			return;
		}

		$this->is_ajax = wp_doing_ajax();

		if ( $this->is_ajax ) {
			$this->init_group_refresh();
		}
	}

	/**
	 * Init group refresh.
	 */
	private function init_group_refresh() {
		add_filter( 'advanced-ads-ad-select-args', [ $this, 'additional_ad_select_args' ], 10, 3 );
		add_filter( 'advanced-ads-group-output-ad-ids', [ $this, 'group_output_ad_ids' ], 10, 5 );
		add_filter( 'advanced-ads-group-output', [ $this, 'group_output' ], 10, 2 );
		add_filter( 'advanced-ads-can-display-ad', [ $this, 'can_display' ], 10, 2 );
		add_action( 'advanced-ads-ad-output', [ $this, 'ad_output' ], 10, 2 );

		add_action( 'advanced-ads-group-before-output', [ $this, 'before_group_output' ] );
	}

	/**
	 * Add group information to the ad_args prop
	 *
	 * @param Group $group the group.
	 *
	 * @return null output process is interrupted if anything beside `null` is returned.
	 */
	public function before_group_output( $group ) {
		$args = $group->get_prop( 'ad_args' );

		if ( ! empty( $args['group_info'] ) ) {
			return null;
		}

		$args['group_info'] = [
			'id'              => $group->get_id(),
			'name'            => $group->get_title(),
			'type'            => $group->get_type(),
			'refresh_enabled' => (bool) $group->get_prop( 'options.refresh.enabled' ),
			'ads_displayed'   => (int) $group->get_ad_count(),
		];

		$group->set_prop_temp( 'ad_args', $args );

		return null;
	}

	/**
	 * Update state with info about the currently displayed ads for the next AJAX call
	 *
	 * @param string $output Output string.
	 * @param Ad     $ad     Ad instance.
	 *
	 * @return string $output
	 */
	public function ad_output( string $output, Ad $ad ) { // phpcs:ignore
		$element_id = $ad->get_prop( 'ad_args.cache_busting_elementid' );
		$group_id   = $ad->get_prop( 'ad_args.group_info.id' );

		if ( empty( $element_id ) || empty( $group_id ) ) {
			return $output;
		}

		$el_group_id = $element_id . '_' . $group_id;

		if ( empty( $this->state_groups[ $el_group_id ] ) ) {
			return $output;
		}

		// Save current ad id so that this ad will not be added to next AJAX response.
		$this->state_groups[ $el_group_id ]['prev_ad_id'] = $ad->get_id();
		// Do not track the same ad twice.
		$this->state_groups[ $el_group_id ]['shown_ad_ids'][ $ad->get_id() ] = true;
		// Allow to show only 1 ad.
		$this->state_groups[ $el_group_id ]['limit_exceeded'] = 1;

		// Get the position of the placement or the ad.
		if ( ! isset( $this->state_groups[ $el_group_id ]['position'] ) ) {
			if ( ! empty( $ad->get_prop( 'placement_position' ) ) ) {
				$this->state_groups[ $el_group_id ]['position'] = $ad->get_prop( 'placement_position' );
			} elseif ( ! empty( $options['output']['position'] ) ) {
				$this->state_groups[ $el_group_id ]['position'] = $options['output']['position'];
			}
		}

		if ( ! wp_doing_ajax() ) {
			return $output;
		}

		$group           = $ad->get_parent();
		$displayable_ads = [];

		foreach ( $group->get_ads() as $ad ) {
			if ( $ad->can_display() && 0.0 !== floatval( $ad->get_prop( 'weight' ) ) ) {
				$displayable_ads[] = $ad->get_id();
			}
		}

		if ( count( $displayable_ads ) === count( (array) $this->state_groups[ $el_group_id ]['shown_ad_ids'] ) ) {
			$this->all_ads_shown[] = $group->get_id();
		}

		return $output;
	}

	/**
	 * Save JS query that loads group using AJAX cache-busting.
	 *
	 * @param array  $args   Arguments.
	 * @param string $method Method.
	 * @param int    $id     ID.
	 *
	 * @return array $args
	 */
	public function additional_ad_select_args( $args, $method, $id ) {
		if ( ! isset( $args['cache_busting_elementid'] ) || empty( $args['group_refresh'] ) ) {
			return $args;
		}

		if (
			// Allow to track each ad of a group with refresh interval enabled only once.
			(
				Constants::ENTITY_AD === $method
				&& ! empty( $args['group_refresh']['shown_ad_ids'][ $id ] )
				&& isset( $args['group_info']['id'] )
				&& isset( $args['group_refresh']['group_id'] )
				&& absint( $args['group_info']['id'] ) === absint( $args['group_refresh']['group_id'] )
			)
			// Display the same group only once in the "Ads" menu of Admin Bar.
			|| ( Constants::ENTITY_GROUP === $method && ! empty( $args['group_refresh']['shown_group_ids'][ $id ] ) )
		) {
			$args['global_output'] = false;
			return $args;
		}

		$args['global_output'] = true;
		return $args;
	}

	/**
	 * Change ordered ids of ads that belong to the group.
	 *
	 * @param array  $ordered_ad_ids Array of ad ids.
	 * @param string $type           Type of the group.
	 * @param array  $ads            Array of Ad objects.
	 * @param array  $weights        Array of weights.
	 * @param Group  $group          Group instance.
	 *
	 * @return array $ordered_ad_ids
	 */
	public function group_output_ad_ids( $ordered_ad_ids, $type, $ads, $weights, Group $group ) {
		if ( ! is_array( $ordered_ad_ids ) || count( $ordered_ad_ids ) < 2 ) {
			return $ordered_ad_ids;
		}

		if ( ! self::is_enabled( $group ) ) {
			return $ordered_ad_ids;
		}

		if ( ! $group->get_prop( 'ad_args.cache_busting_elementid' ) ) {
			return $ordered_ad_ids;
		}

		// TODO: check nested group.

		$el_group_id = $group->get_prop( 'ad_args.cache_busting_elementid' ) . '_' . $group->get_id();

		$this->state_groups[ $el_group_id ]['shown_ad_ids'] = $group->get_prop( 'ad_args.group_refresh.shown_ad_ids' ) ?? [];
		$this->state_groups[ $el_group_id ]['ad_label']     = $group->get_prop( 'ad_args.ad_label' ) ?? 'default';

		$prev_ad_id = absint( $group->get_prop( 'ad_args.group_refresh.prev_ad_id' ) );

		if ( empty( $prev_ad_id ) ) {
			// Show the first ad.
			return $ordered_ad_ids;
		}

		// Do not show previously visible ad.
		switch ( $type ) {
			case 'ordered':
				// At this point ads with the same weight will not be shuffled anymore.
				// We support the order that was formed before the first ad was shown.
				arsort( $weights );
				$ordered_ad_ids = array_keys( $weights );
				$pos            = array_search( $prev_ad_id, $ordered_ad_ids, true );
				if ( false === $pos ) {
					return $ordered_ad_ids;
				}

				$start          = array_slice( $ordered_ad_ids, 0, $pos );
				$end            = array_slice( $ordered_ad_ids, $pos + 1 );
				$ordered_ad_ids = array_merge( $end, $start );
				break;
			default:
				$pos = array_search( $prev_ad_id, $ordered_ad_ids, true );
				if ( false === ( $pos ) ) {
					return $ordered_ad_ids;
				}
				unset( $ordered_ad_ids[ $pos ] );
		}

		return $ordered_ad_ids;
	}

	/**
	 * Add JS code that reloads the group using AJAX request.
	 *
	 * @param string $output_string Output string.
	 * @param Group  $group         Group instance.
	 */
	public function group_output( $output_string, Group $group ) {
		if ( empty( $output_string ) ) {
			return $output_string;
		}

		$element_id = $group->get_prop( 'ad_args.cache_busting_elementid' );

		if ( ! $element_id ) {
			return $output_string;
		}

		if ( ! empty( $group->get_prop( 'options.refresh' ) ) ) {
			$this->shown_group_ids[ $group->get_id() ] = true;
		}

		if ( ! self::is_enabled( $group ) ) {
			return $output_string;
		}

		$this->shown_group_ids[ $group->get_id() ] = true;

		$el_group_id = $element_id . '_' . $group->get_id();

		if ( ! isset( $this->state_groups[ $el_group_id ] ) ) {
			return $output_string;
		}

		if ( ! isset( $this->state_groups[ $el_group_id ]['query']['id'] ) ) {
			$is_first_impression = $this->is_first_impression( $element_id );

			if ( $is_first_impression ) {
				static $count = 0;
				$element_id  .= '-' . ( ++$count ) . '-group-refresh';
			}

			$prev_ad_id            = ! empty( $this->state_groups[ $el_group_id ]['prev_ad_id'] ) ? $this->state_groups[ $el_group_id ]['prev_ad_id'] : '';
			$shown_ad_ids          = ! empty( $this->state_groups[ $el_group_id ]['shown_ad_ids'] ) ? $this->state_groups[ $el_group_id ]['shown_ad_ids'] : [];
			$shown_group_ids       = ! empty( $this->state_groups[ $el_group_id ]['shown_group_ids'] ) ? array_merge( $this->state_groups[ $el_group_id ]['shown_group_ids'], $this->shown_group_ids ) : $this->shown_group_ids;
			$this->shown_group_ids = [];

			$query = Advanced_Ads_Pro_Module_Cache_Busting::build_js_query( $group->get_prop( 'ad_args' ) );
			$query = Advanced_Ads_Pro_Module_Cache_Busting::get_instance()->get_ajax_query( $query, false );

			$query['elementid']                                  = $element_id;
			$query['params']['group_refresh']['prev_ad_id']      = $prev_ad_id;
			$query['params']['group_refresh']['shown_ad_ids']    = $shown_ad_ids;
			$query['params']['group_refresh']['shown_group_ids'] = $shown_group_ids;
			$query['params']['group_refresh']['group_id']        = $group->get_id();

			if ( $group->get_prop( 'ad_args.group_refresh.is_top_level' ) ) {
				$is_top_level = $group->get_prop( 'ad_args.group_refresh.is_top_level' );
			} else {
				$is_top_level = $is_first_impression && ! empty( $group->get_prop( 'ad_args.is_top_level' ) );
			}
			$query['params']['group_refresh']['is_top_level'] = $is_top_level;

			// If it is top level, make it top level again for the next request.
			// This allows to deprecate `group_refresh > is_top_level` key from above in the future.
			if ( ! empty( $group->get_prop( 'ad_args.is_top_level' ) ) ) {
				unset( $query['params']['is_top_level'] );
			}

			if ( isset( $this->state_groups[ $el_group_id ]['ad_label'] ) ) {
				$query['params']['ad_label'] = $this->state_groups[ $el_group_id ]['ad_label'];
			}

			$this->state_groups[ $el_group_id ]['query'] = $query;

			// If the first ad was shown, do not use Lazy Load anymore.
			unset( $query['params']['lazy_load'] );
			$position  = ! empty( $this->state_groups[ $el_group_id ]['position'] ) ? $this->state_groups[ $el_group_id ]['position'] : false;
			$intervals = self::get_ad_intervals( $group );
			$interval  = ! empty( $prev_ad_id ) ? $intervals[ $prev_ad_id ] : $group->get_prop( 'options.refresh.interval' );

			$js  = '<script>(function() {';
			$js .= 'var query_id = ' . wp_rand() . ';'
				. 'if ( advanced_ads_group_refresh.element_ids[ "' . $element_id . '" ] === query_id ) {'
				. '    return;'
				. '}'
				. 'advanced_ads_group_refresh.element_ids[ "' . $element_id . '" ] = query_id;';
			$js .= sprintf( 'advanced_ads_group_refresh.prepare_wrapper( jQuery(".%s"), "%s", %d );', $element_id, $position, $is_first_impression );
			$js .= 'advanced_ads_group_refresh.add_query( ' . wp_json_encode( $query ) . ', ' . $interval . ' );';
			$js .= '})()</script>';

			if ( $is_first_impression ) {
				$style = in_array( $position, [ 'left', 'right' ], true ) ? 'float:' . $position . ';' : '';
				// Create wrapper around group. The following AJAX requests will insert group content into this wrapper.
				$output_string  = $js . '<div style="' . $style . '" class="' . $element_id . '" id="' . $element_id . '">' . $output_string . '</div>';
				$output_string .= '<script>window.advanced_ads_group_refresh.collectPassiveRefreshData(' . wp_json_encode( $this->collect_passive_cb_data( $group, $element_id ) ) . ')</script>';
			} elseif ( in_array( $group->get_id(), $this->all_ads_shown, true ) ) {
				$output_string  = '<script>window.advanced_ads_group_refresh.prepare_wrapper( jQuery(".' . $element_id . '"), "' . $position . '", false )</script>' . $output_string;
				$output_string .= '<script>window.advanced_ads_group_refresh.switchToPassive("' .  Arr::get( $group->get_prop( 'ad_args' ), 'cache_busting_elementid' ) . '")</script>';
			} else {
				$output_string = $js . $output_string;
			}
		}

		return $output_string;
	}

	/**
	 * Create passive CB data for a group
	 *
	 * @param Group  $group the group.
	 * @param string $cb_id CB wrapper ID.
	 *
	 * @return array
	 */
	private function collect_passive_cb_data( $group, $cb_id ) {
		$passive_ads = [];
		foreach ( $group->get_ads() as $id => $ad ) {
			if ( $ad->can_display() && 0.0 !== floatval( $ad->get_prop( 'weight' ) ) ) {
				$passive_ads[ $id ] = Advanced_Ads_Pro_Module_Cache_Busting::get_instance()->get_passive_cb_for_ad( $ad );
				if ( defined( 'AAT_VERSION' ) ) {
					$passive_ads[ $id ]['tracking_enabled'] = false;
				}
			}
		}

		$placement = $group->get_parent();

		if ( ! $placement ) {
			$placement = wp_advads_get_placement( (int) $group->get_prop( 'ad_args' )['previous_id'] );
		}

		$placement_data                  = $placement->get_data();
		$placement_data['cache-busting'] = 'auto';
		$clone                           = clone $group;
		$wrapper                         = $clone->create_wrapper();
		$label                           = Advanced_Ads::get_instance()->get_label( $group, Arr::get( $group->get_prop( 'ad_args' ), 'ad_label', 'default' ) );
		$before                          = '<div' . Advanced_Ads_Utils::build_html_attributes( $wrapper ) . '>' . $label
										   . apply_filters( 'advanced-ads-output-wrapper-before-content-group', '', $clone );
		$after                           = apply_filters( 'advanced-ads-group-output', '', $clone ) . '</div>';

		if ( ! empty( $group->get_prop( 'placement_clearfix' ) ) ) {
			$after .= '<br style="clear: both; display: block; float: none; "/>';
		}

		return [
			'group_id'         => $group->get_id(),
			'type'             => 'group',
			'ads'              => $passive_ads,
			'cb_id'            => $cb_id,
			'placement_info'   => $placement_data,
			'default_interval' => $group->get_prop( 'options.refresh.interval' ),
			'group_wrap'       => [
				[
					'after' => $after,
				],
				[
					'before' => $before,
				],
			],
			'group_info'       => [
				'id'                       => $group->get_id(),
				'name'                     => $group->get_title(),
				'weights'                  => $group->get_ad_weights(),
				'type'                     => $group->get_type(),
				'ordered_ad_ids'           => $group->get_ordered_ad_ids(),
				'ad_count'                 => $group->get_ad_count(),
				'refresh_enabled'          => true,
				'refresh_interval_for_ads' => $this->get_ad_intervals( $group ),
			],
		];
	}

	/**
	 * Check if ad can be displayed.
	 *
	 * @param bool $check Return value.
	 * @param Ad   $ad     Ad instance.
	 */
	public function can_display( $check, Ad $ad ) {
		if ( empty( $ad->get_prop( 'cache_busting_elementid' ) ) || empty( $ad->get_prop( 'ad_args.group_info.id' ) ) ) {
			return $check;
		}

		// Check again if the placement should be displayed.
		if (
			! $this->is_first_impression( $ad->get_prop( 'cache_busting_elementid' ) )
			&& $ad->get_parent()
			&& ! apply_filters( 'advanced-ads-can-display-placement', true, $ad->get_parent()->get_id() ) ) {
			return false;
		}

		$el_group_id = $ad->get_prop( 'cache_busting_elementid' ) . '_' . $ad->get_prop( 'ad_args.group_info.id' );

		if ( ! empty( $this->state_groups[ $el_group_id ]['limit_exceeded'] ) ) {
			return false;
		}

		return $check;
	}

	/**
	 * Adjust the ad group number for group refresh.
	 *
	 * @param int|string $ad_count The number of ads, is an integer or string 'all'.
	 * @param Group      $group    Group instance.
	 *
	 * @return int|string The number of ads, either an integer or string 'all'.
	 */
	public function adjust_ad_group_number( $ad_count, Group $group ) {
		if ( self::is_enabled( $group ) ) {
			return 'all';
		}

		return $ad_count;
	}

	/**
	 * Check if group refresh is enabled.
	 *
	 * @param Group $group Group instance.
	 *
	 * @return bool
	 */
	public static function is_enabled( $group ) {
		$result = $group->is_type( [ 'default', 'ordered' ] ) &&
			! empty( $group->get_prop( 'options.refresh.enabled' ) ) &&
			empty( $group->get_prop( 'ad_args.adblocker_active' ) );

		/**
		 * Filter to disable refresh for a group.
		 *
		 * @param bool $enabled Whether refresh is enabled.
		 * @param Group $group  Group instance.
		 */
		return (bool) apply_filters( 'advanced-ads-group-refresh-enabled', $result, $group );
	}

	/**
	 * Get durations (in ms) of the ads that belong to the group.
	 *
	 * @param Group $group Group instance.
	 *
	 * @return array
	 */
	public static function get_ad_intervals( Group $group ) {
		$interval       = $group->get_prop( 'options.refresh.interval' );
		$group_interval = ! empty( $interval ) ? absint( $interval ) : 2000;

		// An array with ad ids as keys, duration (in ms) as values.
		$ad_intervals = apply_filters( 'advanced-ads-group-refresh-intervals', [] );

		$group_ad_intervals = [];
		$group_ad_ids       = $group->get_ordered_ad_ids();
		foreach ( $group_ad_ids as $ad_id ) {
			$group_ad_intervals[ $ad_id ] = ! empty( $ad_intervals[ $ad_id ] ) ? absint( $ad_intervals[ $ad_id ] ) : $group_interval;
		}

		return $group_ad_intervals;
	}

	/**
	 * Check if no ads of the group has been shown to the user yet.
	 *
	 * @param string $element_id Element Id.
	 * @return bool
	 */
	private function is_first_impression( $element_id ) {
		return '-group-refresh' !== substr( $element_id, -14 );
	}
}
