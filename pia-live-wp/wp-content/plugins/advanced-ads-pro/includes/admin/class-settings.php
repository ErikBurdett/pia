<?php
/**
 * Settings template file.
 * Brief description of the styles in this file
 *
 * @since   3.0.4
 * @package AdvancedAds\Pro
 * @author  Advanced Ads <info@wpadvancedads.com>
 */

namespace AdvancedAds\Pro\Admin;

use Advanced_Ads_Checks;
use Advanced_Ads_Pro;
use AdvancedAds\Pro\Constants;
use AdvancedAds\Framework\Interfaces\Integration_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Settings class.
 */
class Settings implements Integration_Interface {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_filter( 'advanced-ads-setting-tabs', [ $this, 'setting_tabs' ] );
		add_action( 'advanced-ads-settings-init', [ $this, 'settings_init' ], 9 );
		add_filter( 'advanced-ads-ad-admin-options', [ $this, 'add_option_key' ] );
	}

	/**
	 * Add tracking settings tab
	 *
	 * @since 1.2.0
	 *
	 * @param array $tabs existing setting tabs.
	 *
	 * @return array $tabs setting tabs with AdSense tab attached.
	 */
	public function setting_tabs( array $tabs ) {
		$tabs['pro'] = [
			'page'  => Constants::OPTION_KEY . '-settings',
			'group' => Constants::OPTION_KEY,
			'tabid' => 'pro',
			'title' => 'Pro',
		];

		return $tabs;
	}

	/**
	 * Allow Ad Admin to save pro options.
	 *
	 * @param array $options Array with allowed options.
	 *
	 * @return array
	 */
	public function add_option_key( $options ): array {
		$options[] = Constants::OPTION_KEY;

		return $options;
	}

	/**
	 * Add settings to settings page
	 *
	 * @param string $hook settings page hook.
	 *
	 * @return void
	 */
	public function settings_init( $hook ): void {
		register_setting( Constants::OPTION_KEY, Constants::OPTION_KEY );

		add_settings_section(
			Constants::OPTION_KEY . '_modules-enable',
			'',
			'__return_empty_string',
			Constants::OPTION_KEY . '-settings'
		);

		add_settings_section(
			'advanced_ads_pro_settings_section',
			'',
			[ $this, 'render_other_settings' ],
			Constants::OPTION_KEY . '-settings'
		);

		$has_optimizer_installed = Advanced_Ads_Checks::active_autoptimize();
		if ( ! $has_optimizer_installed && method_exists( 'Advanced_Ads_Checks', 'active_wp_rocket' ) ) {
			$has_optimizer_installed = Advanced_Ads_Checks::active_wp_rocket();
		}

		if ( $has_optimizer_installed ) {
			add_settings_field(
				'autoptimize-support',
				__( 'Allow optimizers to modify ad codes', 'advanced-ads-pro' ),
				[ $this, 'render_settings_autoptimize' ],
				Constants::OPTION_KEY . '-settings',
				'advanced_ads_pro_settings_section'
			);
		}

		add_settings_field(
			'placement-positioning',
			__( 'Placement positioning', 'advanced-ads-pro' ),
			[ $this, 'render_settings_output_buffering' ],
			Constants::OPTION_KEY . '-settings',
			'advanced_ads_pro_settings_section'
		);

		add_settings_field(
			'disable-by-post-types',
			__( 'Disable ads for post types', 'advanced-ads-pro' ),
			[ $this, 'render_settings_disable_post_types' ],
			$hook,
			'advanced_ads_setting_section_disable_ads'
		);
	}

	/**
	 * Render additional pro settings
	 *
	 * @return void
	 */
	public function render_other_settings(): void {
		// Save options when the user is on the "Pro" tab.
		$selected = $this->get_disable_by_post_type_options();
		foreach ( $selected as $item ) { ?>
			<input type="hidden" name="<?php echo esc_attr( AA_PRO_SLUG ); ?>[general][disable-by-post-types][]" value="<?php echo esc_html( $item ); ?>">
			<?php
		}
	}

	/**
	 * Render Autoptimise settings field.
	 *
	 * @return void
	 */
	public function render_settings_autoptimize(): void {
		$options                      = Advanced_Ads_Pro::get_instance()->get_options();
		$autoptimize_support_disabled = $options['autoptimize-support-disabled'] ?? false;
		require AA_PRO_ABSPATH . '/views/setting_autoptimize.php';
	}

	/**
	 * Render output buffering settings field.
	 *
	 * @return void
	 */
	public function render_settings_output_buffering(): void {
		$placement_positioning = 'js' === Advanced_Ads_Pro::get_instance()->get_options()['placement-positioning']
			? 'js' : 'php';
		$allowed_types         = [
			'post_above_headline',
			'custom_position',
		];
		$allowed_types_names   = [];

		foreach ( $allowed_types as $allowed_type ) {
			$allowed_type = wp_advads_get_placement_type( $allowed_type );
			if ( $allowed_type && '' !== $allowed_type->get_title() ) {
				$allowed_types_names[] = $allowed_type->get_title();
			}
		}

		require AA_PRO_ABSPATH . '/views/setting-placement-positioning.php';
	}

	/**
	 * Render settings to disable ads by post types.
	 *
	 * @return void
	 */
	public function render_settings_disable_post_types(): void {
		$selected = $this->get_disable_by_post_type_options();

		$post_types        = get_post_types(
			[
				'public'             => true,
				'publicly_queryable' => true,
			],
			'objects',
			'or'
		);
		$type_label_counts = array_count_values( wp_list_pluck( $post_types, 'label' ) );

		require AA_PRO_ABSPATH . '/views/setting_disable_post_types.php';
	}

	/**
	 * Get "Disabled by post type" Pro options.
	 *
	 * @return array
	 */
	private function get_disable_by_post_type_options(): array {
		$selected = [];
		$options  = Advanced_Ads_Pro::get_instance()->get_options();
		if ( isset( $options['general']['disable-by-post-types'] ) && is_array( $options['general']['disable-by-post-types'] ) ) {
			$selected = $options['general']['disable-by-post-types'];
		}

		return $selected;
	}
}
