<?php
/**
 * Main module class
 *
 * @package AdvancedAds\Pro
 * @author  Advanced Ads <info@wpadvancedads.com>
 */

use AdvancedAds\Constants;
use AdvancedAds\Abstracts\Ad;
use AdvancedAds\Framework\Utilities\Formatting;
use AdvancedAds\Framework\Utilities\Params;
use AdvancedAds\Utilities\Conditional;

/**
 * Ads by specific days class
 */
class Advanced_Ads_Pro_Weekdays {

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( ! is_admin() ) {
			add_filter( 'advanced-ads-pro-passive-cb-for-ad', [ $this, 'add_passive_cb_for_ad' ], 10, 2 );
		} elseif ( ! wp_doing_ajax() ) {
			add_action( 'post_submitbox_misc_actions', [ $this, 'add_weekday_options' ] );
			add_filter( 'advanced-ads-ad-pre-save', [ $this, 'save_weekday_options' ], 10, 2 );
			add_filter( 'advanced-ads-ad-list-column-filter', [ $this, 'ad_list_column_filter' ], 10, 3 );
			add_filter( 'advanced-ads-ad-list-filter', [ $this, 'ad_list_filter' ], 10, 2 );
			add_action( 'advanced-ads-ad-list-timing-column-after', [ $this, 'render_ad_planning_column' ], 10, 2 );
		}

		add_filter( 'advanced-ads-can-display-ad', [ $this, 'can_display_by_weekday' ], 10, 3 );
	}

	/**
	 * Pass day indexes to passive cache-busting.
	 *
	 * @param array $passive_cb_for_ad Data to be passed to passive CB.
	 * @param Ad    $ad                Ad instance.
	 */
	public function add_passive_cb_for_ad( $passive_cb_for_ad, Ad $ad ) {
		$passive_cb_for_ad['day_indexes'] = $ad->has_weekdays()
			? $this->sanitize_day_indexes( $ad->get_weekdays() )
			: false;

		return $passive_cb_for_ad;
	}

	/**
	 * Add options above the 'Publish' button.
	 */
	public function add_weekday_options() {
		global $post, $wp_locale;

		if ( Constants::POST_TYPE_AD !== $post->post_type ) {
			return;
		}

		$ad          = wp_advads_get_ad( $post->ID );
		$enabled     = $ad->has_weekdays();
		$day_indexes = $ad->has_weekdays() ? $this->sanitize_day_indexes( $ad->get_weekdays() ) : [];
		$time_zone   = Advanced_Ads_Utils::get_timezone_name();

		include __DIR__ . '/views/ad-submitbox-meta.php';
	}

	/**
	 * Save options above the 'Publish' button.
	 *
	 * @param Ad    $ad        Ad instance.
	 * @param array $post_data Post data array.
	 *
	 * @return void
	 */
	public function save_weekday_options( Ad $ad, $post_data ): void {
		$ad->set_has_weekdays( $post_data['weekdays']['enabled'] ?? false );
		$ad->set_weekdays( $this->sanitize_day_indexes( $post_data['weekdays']['day_indexes'] ?? [] ) );
	}

	/**
	 * Add new item to the filter above the ad list.
	 *
	 * @param array $timing_filter list of current filers.
	 *
	 * @return array $timing_filter
	 */
	public function add_item_to_frontend_filter( $timing_filter ) {
		$timing_filter['advads-pro-filter-specific-days'] = __( 'specific days', 'advanced-ads-pro' );

		return $timing_filter;
	}

	/**
	 * Add new item to the filter above the ad list.
	 *
	 * @param array  $all_filters Existing filters.
	 * @param object $post        WP_Post.
	 * @param array  $options     Ad options.
	 *
	 * @return array $all_filters New filters.
	 */
	public function ad_list_column_filter( $all_filters, $post, $options ) {
		if ( ! empty( $options['weekdays']['enabled'] ) && Formatting::string_to_bool( $options['weekdays']['enabled'] ) ) {
			if ( ! array_key_exists( 'advads-pro-filter-specific-days', $all_filters['all_dates'] ) ) {
				$all_filters['all_dates']['advads-pro-filter-specific-days'] = __( 'specific days', 'advanced-ads-pro' );
			}
		}

		return $all_filters;
	}

	/**
	 * Filter the ad list.
	 *
	 * @param array $posts           Post list.
	 * @param array $all_ads_options Ad options.
	 *
	 * @return array
	 */
	public function ad_list_filter( $posts, $all_ads_options ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( 'advads-pro-filter-specific-days' === urldecode( Params::request( 'addate' ) ) ) {
			$new_posts = [];
			foreach ( $posts as $post ) {
				if ( ! empty( $all_ads_options[ $post->ID ]['weekdays']['enabled'] ) && Formatting::string_to_bool( $all_ads_options[ $post->ID ]['weekdays']['enabled'] ) ) {
					$new_posts[] = $post;
				}
			}
			$posts = $new_posts;
		}

		//phpcs:enable
		return $posts;
	}

	/**
	 * Show weekdays on the ad list page.
	 *
	 * @param Ad     $ad           Ad instance.
	 * @param string $html_classes Existing html classes.
	 *
	 * @return void
	 */
	public function render_ad_planning_column( Ad $ad, &$html_classes = '' ) {
		global $wp_locale;
		$weekdays_enabled     = $ad->has_weekdays();
		$ads_by_hours         = $ad->get_prop( 'ads_by_hours' );
		$ads_by_hours_enabled = ! empty( $ads_by_hours['enabled'] );

		if ( ! $weekdays_enabled && ! $ads_by_hours_enabled ) {
			return;
		}

		$html_classes .= ' advads-pro-filter-specific-days';

		$day_indexes           = $this->sanitize_day_indexes( $ad->get_weekdays() );
		$day_names             = array_map( [ $wp_locale, 'get_weekday' ], $day_indexes );
		$day_names_string      = implode( ', ', $day_names );
		$ads_by_hours          = Advanced_Ads_Pro\Ads_By_Hours\module::get_instance()->admin()->get_localized_intervals( $ad );
		$pro_options           = Advanced_Ads_Pro::get_instance()->get_options();
		$ads_by_hour_module    = Advanced_Ads_Pro\Ads_By_Hours\Module::get_instance();
		$cache_busting_enabled = isset( $pro_options['cache-busting']['enabled'] ) && $pro_options['cache-busting']['enabled'];
		$need_cb               = $ads_by_hour_module->use_browser_time() && ! $cache_busting_enabled;
		$cache_detected        = ! $ads_by_hour_module->use_browser_time() && Conditional::has_cache_plugins() && ! $cache_busting_enabled;

		echo '<p>';

		if ( $weekdays_enabled && empty( $day_names ) ) {
			esc_html_e( 'Never shows up', 'advanced-ads-pro' );
			echo '</p>';

			return;
		}

		echo esc_html__( 'Shows up', 'advanced-ads-pro' );

		if ( $weekdays_enabled ) {
			/* translators: comma separated list of days. */
			printf( esc_html__( ' on: %s', 'advanced-ads-pro' ), esc_html( $day_names_string ) );
		}

		if ( $ads_by_hours_enabled ) {
			printf(
				/* translators: 1. localized time 2. localized time 3. timezone name. */
				esc_html__( ' between %1$s and %2$s %3$s', 'advanced-ads-pro' ),
				esc_html( $ads_by_hours['start'] ),
				esc_html( $ads_by_hours['end'] ),
				esc_html( Advanced_Ads_Pro\Ads_By_Hours\Module::get_instance()->admin()->get_time_zone_string() )
			);
		}

		if ( $need_cb ) {
			printf(
				'<p class="notice advads-notice inline">%s</p>',
				esc_html( \Advanced_Ads_Pro\Ads_By_Hours\Module::get_instance()->admin()->get_cb_warning_message() )
			);
		}

		if ( $cache_detected ) {
			printf(
				'<p class="notice advads-notice inline">%s</p>',
				esc_html( \Advanced_Ads_Pro\Ads_By_Hours\Module::get_instance()->admin()->get_cache_plugin_warning() )
			);
		}

		echo '</p>';
	}

	/**
	 * Sanitize day indexes.
	 *
	 * @param array $day_indexes Array to sanitize.
	 *
	 * @return array
	 */
	public function sanitize_day_indexes( $day_indexes ) {
		if ( ! is_array( $day_indexes ) ) {
			return [];
		}

		foreach ( $day_indexes as $_key => &$_index ) {
			$_index = absint( $_index );

			if ( $_index > 6 ) {
				unset( $day_indexes[ $_key ] );
			}
		}

		return array_unique( array_values( $day_indexes ) );
	}

	/**
	 * Determine if ad can be displayed today based on weekday
	 *
	 * @param bool  $can_display   Value as set so far.
	 * @param Ad    $ad            Ad instance.
	 * @param array $check_options Check options.
	 *
	 * @return bool false if can’t be displayed, else return $can_display
	 */
	public function can_display_by_weekday( $can_display, Ad $ad, $check_options ) {
		if ( ! $can_display ) {
			return false;
		}

		if ( ! empty( $check_options['passive_cache_busting'] ) ) {
			return $can_display;
		}

		if ( ! $ad->has_weekdays() || empty( $ad->get_weekdays() ) ) {
			return $can_display;
		}

		// current_datetime is available since WP 5.3.0.
		$date = function_exists( 'current_datetime' ) ? current_datetime() : date_create_immutable( 'now', Advanced_Ads_Utils::get_wp_timezone() );

		return in_array(
			(int) $date->format( 'w' ),
			$this->sanitize_day_indexes( $ad->get_weekdays() ),
			true
		);
	}
}
