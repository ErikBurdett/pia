<?php
/**
 * The class manages various admin action, links, feedback submission and text overrides in footer.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking\Admin;

use WP_Screen;
use Advanced_Ads_Admin_Notices;
use Advanced_Ads_Ad_Health_Notices;
use AdvancedAds\Constants;
use AdvancedAds\Tracking\Helpers;
use AdvancedAds\Tracking\Ad_Limiter;
use AdvancedAds\Framework\Utilities\Params;
use AdvancedAds\Tracking\Installation\Tracking_Installer;
use AdvancedAds\Framework\Interfaces\Integration_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Class Admin.
 */
class Admin implements Integration_Interface {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'init', [ $this, 'migrate_deprecated_tracking_method' ] );
		add_action( 'admin_init', [ $this, 'activate_installer' ] );
		add_filter( 'plugin_action_links_' . AA_TRACKING_BASENAME, [ $this, 'add_links' ] );
		add_filter( 'advanced-ads-add-screen', [ $this, 'add_stats_page' ] );
		add_action( 'advanced-ads-dashboard-screens', [ $this, 'add_menu_page_to_array' ] );
		add_action( 'advanced-ads-screen-settings', [ $this, 'settings_page_assets' ] );
		add_action( 'advanced-ads-screen-ads-editing', [ $this, 'ad_editing_page_assets' ] );
		add_filter( 'advanced-ads-ad-notices', [ $this, 'ad_notices' ], 10, 3 );
		add_action( 'dp_duplicate_post', [ $this, 'on_duplicate_post' ], 20 );
		add_action( 'advanced-ads-export-options', [ $this, 'export_options' ] );

		// Check if UID is present when tracking with Google Analytics.
		$this->check_missing_gauid();
		add_filter( 'advanced-ads-ad-health-notices', [ $this, 'add_missing_gauid_notice' ] );

		// Register limiter on current ad.
		add_action( 'current_screen', [ $this, 'tracking_limiter_init' ] );

		add_filter( 'advanced-ads-notices', [ $this, 'ga_events_notice' ] );
	}

	/**
	 * If the current screen is an ad, get the ID and init the limiter.
	 *
	 * @param WP_Screen $screen current WP_Screen object.
	 *
	 * @return void
	 */
	public function tracking_limiter_init( WP_Screen $screen ): void {
		if ( wp_doing_ajax() || Constants::POST_TYPE_AD !== $screen->post_type ) {
			return;
		}

		$ad_id = get_the_ID();
		$ad_id = ! empty( $ad_id ) ? $ad_id : Params::post( 'post_ID', 0, FILTER_VALIDATE_INT );
		if ( ! $ad_id ) {
			return;
		}

		( new Ad_Limiter( $ad_id ) );
	}

	/**
	 * Add links to the plugins list
	 *
	 * @param array $links array of links for the plugins, adapted when the current plugin is found.
	 *
	 * @return array
	 */
	public function add_links( $links ): array {
		// Early bail!!
		if ( ! is_array( $links ) ) {
			return $links;
		}

		$links[] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( add_query_arg( 'page', 'advanced-ads-settings', get_admin_url() . 'admin.php' ) ) . '#top#tracking',
			__( 'Settings', 'advanced-ads-tracking' )
		);

		return $links;
	}

	/**
	 * Install/Initialize custom ajax handler.
	 *
	 * @return void
	 */
	public function activate_installer(): void {
		( new Tracking_Installer() )->install();

		$notices = get_option( 'advanced-ads-notices' );
		if ( ! array_key_exists( 'tracking_ga_events_change', $notices['closed'] ?? [] ) ) {
			Advanced_Ads_Admin_Notices::get_instance()->add_to_queue( 'tracking_ga_events_change' );
		}
	}

	/**
	 * Add stats page.
	 *
	 * @param object $manager Screen Manager.
	 *
	 * @return void
	 */
	public function add_stats_page( $manager ): void {
		if ( Helpers::can_show_stats() ) {
			$manager->add_screen( Page_Stats::class );
			$manager->add_screen( Page_Database::class );
		}
	}

	/**
	 * Add menu page to the array of pages that belong to Advanced Ads
	 *
	 * @since 1.2.4
	 *
	 * @param array $pages Screen ids that already belong to Advanced Ads.
	 *
	 * @return array
	 */
	public function add_menu_page_to_array( array $pages ): array {
		$pages[] = 'advanced-ads_page_advanced-ads-stats';
		$pages[] = 'admin_page_advads-tracking-db-page';

		return $pages;
	}

	/**
	 * Add assets to the settings page.
	 *
	 * @return void
	 */
	public function settings_page_assets(): void {
		wp_advads_tracking()->registry->enqueue_style( 'screen-settings' );
		wp_advads_tracking()->registry->enqueue_script( 'screen-settings' );
	}

	/**
	 * Add assets to the ad editing page.
	 *
	 * @return void
	 */
	public function ad_editing_page_assets(): void {
		wp_advads_tracking()->registry->enqueue_style( 'jqplot' );
		wp_advads_tracking()->registry->enqueue_script( 'ads-editing-stats' );
	}

	/**
	 * Show AdSense ad specific notices in parameters box
	 *
	 * @param array $notices Notices array.
	 * @param array $box     The box data.
	 * @param Ad    $ad      Ad instance.
	 *
	 * @return mixed
	 */
	public function ad_notices( $notices, $box, $ad ) {
		switch ( $box['id'] ) {
			case 'ad-parameters-box':
				// General check for following conditions.
				$content_contains_a = strpos( $ad->get_content(), 'href=' );
				$link               = Helpers::get_ad_link( $ad );

				// Add warning if %link% parameter is in editor, but url field is empty.
				$notices[] = [
					'text'  => __( 'Use the <code>URL field</code> or remove <code>%link%</code> parameter from your editor.', 'advanced-ads-tracking' ),
					'class' => 'advads-ad-notice-tracking-missing-url-field error hidden',
				];

				// Add warning if url exists, but %link% parameter is not in editor.
				$notices[] = [
					'text'  => __( 'The link found in the ad code is used as the target URL. If you replace it with the <code>%link%</code> placeholder, the ad will be linked to the address specified in the Target URL field.', 'advanced-ads-tracking' ),
					'class' => 'advads-ad-notice-tracking-link-placeholder-missing error hidden',
				];

				// Notice, if ad can not open in new window due to existing link attribute and does not have such code in it already.
				if (
					$content_contains_a &&
					! strpos( $ad->get_content(), '_blank' ) &&
					Helpers::get_ad_target( $ad )
				) {
					$notices[] = [
						'text'  => __( 'Add <code>target="_blank"</code> to the ad code in order to open it in a new window. E.g. <code>&lt;a href="%link%" target="_blank"&gt;</code>', 'advanced-ads-tracking' ),
						'class' => 'advads-ad-notice-tracking-new-window',
					];
				}

				break;
			case 'tracking-ads-box':
				break;
		}

		return $notices;
	}

	/**
	 *  Recreate public stats link on post duplication
	 *
	 * @param int $new_id New post id.
	 *
	 * @return void
	 */
	public function on_duplicate_post( $new_id ): void {
		$meta = get_post_meta( $new_id, 'advanced_ads_ad_options', true );
		if ( isset( $meta['tracking']['public-id'] ) ) {
			$meta['tracking']['public-id'] = wp_generate_password( 48, false );
			update_post_meta( $new_id, 'advanced_ads_ad_options', $meta );
		}
	}

	/**
	 * Add Tracking options to the list of options to be exported
	 *
	 * @param array $options of option data keyed by option keys.
	 *
	 * @return array of option data keyed by option keys.
	 */
	public function export_options( $options ): array {
		$options[ ADVADS_SLUG . '-tracking' ] = get_option( ADVADS_SLUG . '-tracking' );

		return $options;
	}

	/**
	 * Migrate deprecated tracking method.
	 *
	 * @return void
	 */
	public function migrate_deprecated_tracking_method(): void {
		$options = wp_advads_tracking()->options->get_all();
		if ( ! isset( $options['method'] ) ) {
			return;
		}

		// If tracking is shutdown, change it to onrequest in database.
		if ( 'shutdown' === $options['method'] ) {
			$options['method'] = 'onrequest';
		}

		wp_advads_tracking()->options->update( $options );
	}

	/**
	 * Add warning to Ad Health Notices if GA has been chosen as tracking method, but no GA UID present.
	 *
	 * @param array $notices Array of registered health notices.
	 *
	 * @return array
	 */
	public function add_missing_gauid_notice( $notices ): array {
		$notices['tracking_missing_gauid'] = [
			/* Translators: 1: opening a-tag with link to settings page 2: closing a-tag */
			'text' => sprintf( __( 'You have selected to track ads with Google Analytics but not provided a tracking ID. Please add the Google Analytics UID %1$shere%2$s', 'advanced-ads-tracking' ), sprintf( '<a href="%s">', admin_url( 'admin.php?page=advanced-ads-settings#top#tracking' ) ), '</a>' ),
			'type' => 'problem',
		];

		return $notices;
	}

	/**
	 * Add a notice about event names change for the Google Analytics tracking method
	 *
	 * @param array $notices existing notices.
	 *
	 * @return mixed
	 */
	public function ga_events_notice( $notices ) {
		$message = wp_kses(
			sprintf(
				/* translators: 1: opening a-tag with link to manual 2: closing a-tag */
				__( 'With the latest update, we adjusted the event names used by Google Analytics to track ad clicks and impressions. This change aims to reduce confusion and enhance the clarity of reports. "Clicks" and "Impressions" will now be referred to as "advanced-ads-click" and "advanced-ads-impression". If you prefer the original names or wish to customize them further, use a filter. %1$sManual%2$s', 'advanced-ads-tracking' ),
				'<a href="https://wpadvancedads.com/manual/ad-tracking-with-google-analytics/?utm_source=advanced-ads&utm_medium=link&utm_campaign=notice-tracking-GA-update#Customizing_the_event_names" class="advads-manual-link" target="_blank">',
				'</a>'
			),
			[
				'a' => [
					'href'   => true,
					'target' => true,
					'class'  => true,
				],
			]
		);

		$notices['tracking_ga_events_change'] = [
			'type'   => 'info',
			'text'   => $message,
			'global' => true,
		];

		return $notices;
	}

	/**
	 * If tracking method is Google Analytics, but there is no UID, show an Ad_Health_Notice.
	 *
	 * @return void
	 */
	private function check_missing_gauid(): void {
		$is_ga = Helpers::is_forced_analytics() || Helpers::is_tracking_method( 'ga' );
		if ( ! $is_ga || ! empty( wp_advads_tracking()->options->get_all()['ga-UID'] ) ) {
			Advanced_Ads_Ad_Health_Notices::get_instance()->remove( 'tracking_missing_gauid' );
			return;
		}

		Advanced_Ads_Ad_Health_Notices::get_instance()->add( 'tracking_missing_gauid' );
	}
}
