<?php
/**
 * Tracking settings.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking\Admin;

use AdvancedAds\Tracking\Helpers;
use AdvancedAds\Tracking\Constants;
use AdvancedAds\Utilities\Sanitize;
use AdvancedAds\Framework\Interfaces\Integration_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Tracking settings.
 */
class Settings implements Integration_Interface {

	/**
	 * Page hook.
	 *
	 * @var string
	 */
	private $page_hook = 'advanced-ads-tracking-settings-page';

	/**
	 * Options slug.
	 *
	 * @var string
	 */
	private $options_slug = 'advanced-ads-tracking';

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		$this->options_slug = Constants::OPTIONS_SLUG;
		add_filter( 'advanced-ads-setting-tabs', [ $this, 'setting_tabs' ] );
		add_action( 'advanced-ads-settings-init', [ $this, 'register_settings' ] );
		add_action( 'advanced-ads-settings-init', [ $this, 'tracking_section' ] );
		add_action( 'advanced-ads-settings-init', [ $this, 'email_reports_section' ] );
		add_action( 'advanced-ads-settings-init', [ $this, 'advance_section' ] );
		add_filter( 'advanced-ads-ad-admin-options', [ $this, 'save_options' ] );
		$this->flush_rewrite_rules();
	}

	/**
	 * Flush rewrite rules if the public stats slug has changed.
	 *
	 * @return void
	 */
	public function flush_rewrite_rules(): void {
		if ( get_option( 'advanced-ads-flush-permalinks', false ) ) {
			flush_rewrite_rules();
			delete_option( 'advanced-ads-flush-permalinks' );
		}
	}

	/**
	 * Add tracking settings tab
	 *
	 * @since 1.2.0
	 *
	 * @param array $tabs existing setting tabs.
	 *
	 * @return array $tabs setting tabs with AdSense tab attached
	 */
	public function setting_tabs( array $tabs ): array {
		$tabs['tracking'] = [
			'page'  => $this->page_hook,
			'group' => $this->options_slug,
			'tabid' => 'tracking',
			'title' => __( 'Tracking', 'advanced-ads-tracking' ),
		];

		return $tabs;
	}

	/**
	 * Allow Ad Admin to save tracking options.
	 *
	 * @param array $options Array with allowed options.
	 *
	 * @return array
	 */
	public function save_options( $options ): array {
		$options[] = $this->options_slug;

		return $options;
	}

	/**
	 * Register settings
	 *
	 * @since 1.0.0
	 */
	public function register_settings(): void {
		register_setting( $this->options_slug, $this->options_slug, [ $this, 'sanitize_settings' ] );
	}

	/**
	 * Add tracking section and settings
	 *
	 * @return void
	 */
	public function tracking_section(): void {
		$tracking_section = 'advanced_ads_tracking_setting_section';

		add_settings_section(
			$tracking_section,
			__( 'Tracking', 'advanced-ads-tracking' ),
			[ $this, 'render_tracking_section' ],
			$this->page_hook
		);

		// Settings.
		add_settings_field(
			'tracking-method',
			__( 'Choose tracking method', 'advanced-ads-tracking' ),
			[ $this, 'render_setting_tracking_method' ],
			$this->page_hook,
			$tracking_section
		);

		add_settings_field(
			'ga-settings',
			__( 'Google Analytics', 'advanced-ads-tracking' ),
			[ $this, 'render_setting_google_analytics' ],
			$this->page_hook,
			$tracking_section,
			[ 'class' => $this->get_ga_classes() ]
		);

		add_settings_field(
			'tracking-everything',
			__( 'What to track by default', 'advanced-ads-tracking' ),
			[ $this, 'render_setting_tracking_everything' ],
			$this->page_hook,
			$tracking_section
		);

		add_settings_field(
			'tracking-user-role',
			__( 'Disable tracking for user roles', 'advanced-ads-tracking' ),
			[ $this, 'render_setting_user_role' ],
			$this->page_hook,
			$tracking_section
		);

		add_settings_field(
			'link-nofollow',
			__( 'Add “nofollow”', 'advanced-ads-tracking' ),
			[ $this, 'render_setting_link_nofollow' ],
			$this->page_hook,
			$tracking_section
		);

		add_settings_field(
			'link-sponsored',
			__( 'Add “sponsored”', 'advanced-ads-tracking' ),
			[ $this, 'render_setting_link_sponsored' ],
			$this->page_hook,
			$tracking_section
		);

		add_settings_field(
			'public-stat',
			__( 'Link base for public reports', 'advanced-ads-tracking' ),
			[ $this, 'render_setting_public_stats' ],
			$this->page_hook,
			$tracking_section
		);
	}

	/**
	 * Add email reports section and settings
	 *
	 * @return void
	 */
	public function email_reports_section(): void {
		$reports_section = 'advanced_ads_tracking_reports_setting_section';

		add_settings_section(
			$reports_section,
			__( 'Email Reports', 'advanced-ads-tracking' ),
			[ $this, 'render_email_reports_section' ],
			$this->page_hook
		);

		$reports_args = [ 'class' => Helpers::is_tracking_method( 'ga' ) ? 'hidden advads-is-hidden' : '' ];

		// Settings.
		add_settings_field(
			'email-report-recipient',
			__( 'Recipients', 'advanced-ads-tracking' ),
			[ $this, 'render_setting_email_report_recipients' ],
			$this->page_hook,
			$reports_section,
			$reports_args
		);

		add_settings_field(
			'email-report-frequency',
			__( 'Frequency', 'advanced-ads-tracking' ),
			[ $this, 'render_setting_email_frequency' ],
			$this->page_hook,
			$reports_section,
			$reports_args
		);

		add_settings_field(
			'email-report-period',
			__( 'Statistics period', 'advanced-ads-tracking' ),
			[ $this, 'render_setting_email_stats_period' ],
			$this->page_hook,
			$reports_section,
			$reports_args
		);

		add_settings_field(
			'email-report-sender-name',
			__( 'From name', 'advanced-ads-tracking' ),
			[ $this, 'render_setting_email_sender_name' ],
			$this->page_hook,
			$reports_section,
			$reports_args
		);

		add_settings_field(
			'email-report-sender-address',
			__( 'From address', 'advanced-ads-tracking' ),
			[ $this, 'render_setting_email_sender_address' ],
			$this->page_hook,
			$reports_section,
			$reports_args
		);

		add_settings_field(
			'email-report-subject',
			__( 'Email subject', 'advanced-ads-tracking' ),
			[ $this, 'render_setting_email_subject' ],
			$this->page_hook,
			$reports_section,
			$reports_args
		);

		add_settings_field(
			'email-report-test-email',
			__( 'Send test email', 'advanced-ads-tracking' ),
			[ $this, 'render_setting_email_test_email' ],
			$this->page_hook,
			$reports_section,
			$reports_args
		);
	}

	/**
	 * Advance section
	 *
	 * @return void
	 */
	public function advance_section(): void {
		$advanced_section = 'advanced_ads_tracking_advanced_setting_section';

		add_settings_section(
			$advanced_section,
			__( 'Advanced', 'advanced-ads-tracking' ),
			'__return_empty_string',
			$this->page_hook
		);

		$reports_args = [ 'class' => Helpers::is_tracking_method( 'ga' ) ? 'hidden advads-is-hidden' : '' ];

		// Settings.
		if ( Helpers::can_show_stats() ) {
			add_settings_field(
				'tracking-db-mgmt',
				__( 'Database Management', 'advanced-ads-tracking' ),
				[ $this, 'render_setting_db_mgmt' ],
				$this->page_hook,
				$advanced_section
			);
		}

		add_settings_field(
			'link-base',
			__( 'Click-link base', 'advanced-ads-tracking' ),
			[ $this, 'render_setting_link_base' ],
			$this->page_hook,
			$advanced_section
		);

		add_settings_field(
			'tracking-bots',
			__( 'Track bots', 'advanced-ads-tracking' ),
			[ $this, 'render_setting_track_bots' ],
			$this->page_hook,
			$advanced_section,
			$reports_args
		);

		add_settings_field(
			'tracking-uninstall',
			__( 'Delete data on uninstall', 'advanced-ads-tracking' ),
			[ $this, 'render_setting_tracking_uninstall' ],
			$this->page_hook,
			$advanced_section
		);
	}

	/**
	 * Sanitize plugin settings
	 *
	 * @since 1.2.6
	 *
	 * @param array $options All options.
	 *
	 * @return array
	 */
	public function sanitize_settings( $options ): array {
		if ( isset( $options['linkbase'] ) ) {
			$options['linkbase'] = sanitize_title( $options['linkbase'] );
		}

		if ( ! empty( $options['public-stats-slug'] ) ) {
			$options['public-stats-slug'] = stripslashes( $options['public-stats-slug'] );
			update_option( 'advanced-ads-flush-permalinks', true );
		}

		// Email reports addresses.
		$options['email-addresses'] = ! empty( $options['email-addresses'] )
			? Sanitize::email_addresses( $options['email-addresses'] )
			: '';

		// Email sender address.
		if ( ! empty( $options['email-sender-address'] ) ) {
			$options['email-sender-address'] = sanitize_email( stripslashes( $options['email-sender-address'] ) );
		}

		if ( empty( $options['email-sender-address'] ) ) {
			$options['email-sender-address'] = 'noreply@' . wp_parse_url( get_bloginfo( 'url' ), PHP_URL_HOST );
		}

		// Email sender name.
		$options['email-sender-name'] = ! empty( $options['email-sender-name'] )
			? stripslashes( $options['email-sender-name'] )
			: get_bloginfo( 'name' );

		// Email subject.
		$options['email-subject'] = ! empty( $options['email-subject'] )
			? stripslashes( $options['email-subject'] )
			: __( 'Ads Statistics', 'advanced-ads-tracking' );

		// Sanitize Analytics UID.
		if ( isset( $options['ga-UID'] ) ) {
			$options['ga-UID'] = Sanitize::analytics_uid( $options['ga-UID'] );
		}

		// Remove options on uninstall.
		if ( isset( $options['uninstall'] ) ) {
			$options['uninstall'] = '1';
		}

		return $options;
	}

	/**
	 * Render tracking settings section
	 *
	 * @return void
	 */
	public function render_tracking_section(): void {
		// Add hidden field to also save db version and not to override it.
		$dbversion = wp_advads_tracking()->options->get( 'dbversion', 0 );
		?>
		<input type="hidden" name="<?php echo esc_attr( $this->options_slug ); ?>[dbversion]" value="<?php echo esc_attr( $dbversion ); ?>"/>
		<?php
	}

	/**
	 * Render tracking method setting
	 *
	 * @return void
	 */
	public function render_setting_tracking_method(): void {
		$method                  = Helpers::get_tracking_method();
		$show_tcf_warning        = Helpers::has_tcf_conflict();
		$missing_scripts_warning = ! apply_filters( 'advanced-ads-tracking-load-header-scripts', true );
		$is_amp_nossl            = ! is_ssl() && ( function_exists( 'is_amp_endpoint' ) || function_exists( 'is_wp_amp' ) || function_exists( 'ampforwp_is_amp_endpoint' ) || function_exists( 'is_penci_amp' ) );

		include_once $this->get_view( 'method' );
	}

	/**
	 *  Render Google Analytics settings
	 *
	 * @return void
	 */
	public function render_setting_google_analytics(): void {
		$uid   = wp_advads_tracking()->options->get( 'ga-UID', '' );
		$is_ga = Helpers::is_forced_analytics() || Helpers::is_tracking_method( 'ga' );

		include_once $this->get_view( 'ga' );
	}

	/**
	 * Render tracking-everything setting
	 *
	 * @return void
	 */
	public function render_setting_tracking_everything(): void {
		$method = wp_advads_tracking()->options->get( 'everything', 'true' );

		include_once $this->get_view( 'everything' );
	}

	/**
	 * Render disabled-roles setting
	 *
	 * @return void
	 */
	public function render_setting_user_role(): void {
		$roles          = wp_roles();
		$disabled_roles = wp_advads_tracking()->options->get( 'disabled-roles', [] );

		include_once $this->get_view( 'user-role' );
	}

	/**
	 * Render link-nofollow setting
	 *
	 * @return void
	 */
	public function render_setting_link_nofollow(): void {
		$nofollow = ! empty( wp_advads_tracking()->options->get( 'nofollow' ) );

		include_once $this->get_view( 'nofollow' );
	}

	/**
	 * Render rel="sponsored" setting.
	 *
	 * @return void
	 */
	public function render_setting_link_sponsored(): void {
		$sponsored = ! empty( wp_advads_tracking()->options->get( 'sponsored' ) );

		include_once $this->get_view( 'sponsored' );
	}

	/**
	 *  Render public stats setting
	 *
	 * @return void
	 */
	public function render_setting_public_stats(): void {
		$public_stats_slug = Helpers::get_public_stats_slug();
		$nonce             = wp_create_nonce( 'advads-tracking-public-stats' );

		include_once $this->get_view( 'public-stats' );
	}

	/**
	 * Render tracking settings section for email reports
	 *
	 * @return void
	 */
	public function render_email_reports_section(): void {
		if ( Helpers::is_tracking_method( 'ga' ) ) :
			?>
			<p class="advads-notice-inline advads-idea">
				<?php _e( ' <a href="https://wpadvancedads.com/share-custom-reports-google-analytics/?utm_source=advanced-ads&utm_medium=link&utm_campaign=settings-reports-google-analytics" target="_blank">How to share Google Analytics ad reports with your customers.</a>', 'advanced-ads-tracking' ); // phpcs:ignore ?>
			</p>
			<?php
		endif;
	}

	/**
	 *  Render settings email recipient
	 *
	 * @return void
	 */
	public function render_setting_email_report_recipients(): void {
		$recipients = wp_advads_tracking()->options->get( 'email-addresses', '' );

		include_once $this->get_view( 'email-report-recipients' );
	}

	/**
	 *  Render settings email frequency
	 *
	 * @return void
	 */
	public function render_setting_email_frequency(): void {
		$frequency = wp_advads_tracking()->options->get( 'email-sched', 'daily' );

		include_once $this->get_view( 'email-report-frequency' );
	}

	/**
	 *  Render settings email stats period
	 *
	 * @return void
	 */
	public function render_setting_email_stats_period(): void {
		$period = wp_advads_tracking()->options->get( 'email-stats-period', 'last30days' );

		include_once $this->get_view( 'email-report-stats-period' );
	}

	/**
	 *  Render settings email sender name
	 *
	 * @return void
	 */
	public function render_setting_email_sender_name(): void {
		$sender_name = stripslashes( wp_advads_tracking()->options->get( 'email-sender-name', 'Advanced Ads' ) );

		include_once $this->get_view( 'email-report-sender-name' );
	}

	/**
	 *  Render settings email sender address
	 *
	 * @return void
	 */
	public function render_setting_email_sender_address(): void {
		$sender_address = wp_advads_tracking()->options->get( 'email-sender-address', false );
		$sender_address = sanitize_email( $sender_address );
		if ( ! $sender_address ) {
			$sender_address = 'noreply@' . wp_parse_url( get_bloginfo( 'url' ), PHP_URL_HOST );
		}

		include_once $this->get_view( 'email-report-sender-address' );
	}

	/**
	 *  Render settings email subject
	 *
	 * @return void
	 */
	public function render_setting_email_subject(): void {
		$subject = stripslashes( wp_advads_tracking()->options->get( 'email-subject', __( 'Ads Statistics', 'advanced-ads-tracking' ) ) );

		include $this->get_view( 'email-report-subject' );
	}

	/**
	 *  Render settings email subject
	 *
	 * @return void
	 */
	public function render_setting_email_test_email(): void {
		$hash = [
			'daily'   => __( 'every day', 'advanced-ads-tracking' ),
			'weekly'  => __( 'every Monday', 'advanced-ads-tracking' ),
			'monthly' => __( 'first day of the month', 'advanced-ads-tracking' ),
		];

		$recipients     = wp_advads_tracking()->options->get( 'email-addresses', '' );
		$schedule       = wp_advads_tracking()->options->get( 'email-sched', 'daily' );
		$email_schedule = $hash[ $schedule ] ?? $hash['daily'];

		include_once $this->get_view( 'email-test-email' );
	}

	/**
	 * Render advanced tracking settings section.
	 *
	 * @return void
	 */
	public function render_setting_db_mgmt() {
		$page_url = Helpers::get_database_tool_link();

		include_once $this->get_view( 'advanced-db-mgmt' );
	}

	/**
	 * Render link-nofollow setting
	 *
	 * @return void
	 */
	public function render_setting_link_base() {
		$linkbase = wp_advads_tracking()->options->get( 'linkbase', 'linkout' );

		include_once $this->get_view( 'advanced-linkbase' );
	}

	/**
	 *  Render settings tracking bot
	 *
	 * @return void
	 */
	public function render_setting_track_bots() {
		$track_bots = wp_advads_tracking()->options->get( 'track-bots', '0' );

		include_once $this->get_view( 'advanced-tracking-bots' );
	}

	/**
	 *  Render tracking uninstall settings
	 *
	 * @return void
	 */
	public function render_setting_tracking_uninstall() {
		$uninstall = wp_advads_tracking()->options->get( 'uninstall' ) ? '1' : '0';

		include_once $this->get_view( 'advanced-uninstall' );
	}

	/**
	 * Generate classes string for GA UID field.
	 *
	 * @return string
	 */
	private function get_ga_classes() {
		$is_ga_forced = Helpers::is_forced_analytics();
		$ga_classes   = [
			'advads-ga-uid'       => true,
			'advads-is-ga-forced' => $is_ga_forced,
			'advads-is-visible'   => $is_ga_forced || Helpers::is_tracking_method( 'ga' ),
		];

		return implode( ' ', array_keys( array_filter( $ga_classes ) ) );
	}

	/**
	 * Retrieves the view file path based on the provided file name.
	 *
	 * @param string $file The name of the file.
	 *
	 * @return string The absolute file path of the view file.
	 */
	private function get_view( $file ): string {
		return AA_TRACKING_ABSPATH . 'views/admin/settings/setting-' . $file . '.php';
	}
}
