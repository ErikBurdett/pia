<?php // phpcs:disable WordPress.Files.FileName.InvalidClassFileName
/**
 * Admin functionality for the adblocker module.
 *
 * @package AdvancedAds\Pro
 * @author  Advanced Ads <info@wpadvancedads.com>
 */

use AdvancedAds\Utilities\Conditional;
/**
 * Class definition.
 */
class Advanced_Ads_Pro_Module_Extended_Adblocker_Admin {

	/**
	 * Setting options
	 *
	 * @var array
	 */
	private $options = [];

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'advanced-ads-settings-init', [ $this, 'settings_init' ], 9 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
	}

	/**
	 * Initializes the settings for the adblocker module.
	 *
	 * @return void
	 */
	public function settings_init(): void {
		$this->options = Advanced_Ads::get_instance()->get_adblocker_options();

		add_settings_section(
			'advanced_ads_adblocker_extended_setting_section',
			'',
			null,
			ADVADS_SETTINGS_ADBLOCKER
		);

		add_settings_field(
			'extended_ab',
			__( 'Ad blocker countermeasures', 'advanced-ads-pro' ),
			[ $this, 'render_settings_activate_extended' ],
			ADVADS_SETTINGS_ADBLOCKER,
			'advanced_ads_adblocker_extended_setting_section'
		);
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts(): void {
		if ( ! Conditional::is_screen_advanced_ads() ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! isset( $screen->id ) || 'advanced-ads_page_advanced-ads-settings' !== $screen->id ) {
			return;
		}

		$admin_js = AA_PRO_BASE_URL . 'assets/dist/extended-adblocker-admin.js';
		wp_enqueue_script( 'eab-admin', $admin_js, [ 'jquery' ], AAP_VERSION, true );
	}

	/**
	 * Render 'adblocker checkbox'
	 *
	 * @return void
	 */
	public function render_settings_activate_extended(): void {
		$method       = empty( $this->options['method'] ) ? 'nothing' : $this->options['method'];
		$redirect_url = $this->options['redirect']['url'] ?? '';
		$abf_enabled  = $this->options['use-adblocker'] ?? null;

		include_once __DIR__ . '/views/setting-activate-extended.php';
	}

	/**
	 * Render content box
	 *
	 * @return void
	 */
	public function render_settings_overlay_content(): void {
		$content = $this->options['overlay']['content'] ?? '';
		$args    = [
			'textarea_name'    => esc_attr( ADVADS_SETTINGS_ADBLOCKER ) . '[overlay][content]',
			'textarea_rows'    => 16,
			'drag_drop_upload' => true,
		];

		include_once __DIR__ . '/views/setting-overlay-content.php';
	}

	/**
	 * Render 'overlay again and dismiss button'
	 *
	 * @return void
	 */
	public function render_settings_dismissible(): void {
		$hide_checked         = $this->options['overlay']['hide_dismiss'] ?? 0;
		$button_text          = $this->options['overlay']['dismiss_text'] ?? '';
		$option_time_freq     = 'time_frequency';
		$time_freq            = $this->options['overlay'][ $option_time_freq ] ?? 'everytime';
		$option_dismiss_style = 'dismiss_style';
		$dismiss_style        = $this->options['overlay'][ $option_dismiss_style ] ?? '';

		include_once __DIR__ . '/views/setting-dismissible.php';
	}

	/**
	 * Render 'styling for dismiss button, container, background'
	 *
	 * @return void
	 */
	public function render_settings_styling(): void {
		$option_container  = 'container_style';
		$option_background = 'background_style';
		$container_style   = $this->options['overlay'][ $option_container ] ?? '';
		$background_style  = $this->options['overlay'][ $option_background ] ?? '';

		include_once __DIR__ . '/views/setting-styling.php';
	}

	/**
	 * Render 'exclude settings'
	 *
	 * @return void
	 */
	public function render_settings_exclude(): void {
		global $wp_roles;
		$roles          = $wp_roles->get_names();
		$option_exclude = 'exclude';
		$exclude        = isset( $this->options[ $option_exclude ] ) ? Advanced_Ads_Utils::maybe_translate_cap_to_role( $this->options[ $option_exclude ] ) : [];

		include_once __DIR__ . '/views/setting-exclude.php';
	}
}
