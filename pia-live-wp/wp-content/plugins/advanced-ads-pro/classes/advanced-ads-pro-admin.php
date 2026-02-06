<?php // phpcs:ignoreFile

use AdvancedAds\Widget;
use AdvancedAds\Abstracts\Ad;
use AdvancedAds\Framework\Utilities\Params;

/**
 *
 * NOTE: can not rely actively on base plugin without prior test for existence (only after plugins_loaded hook)
 */
class Advanced_Ads_Pro_Admin {

	/**
	 * Field name of the user role
	 *
	 * @since 1.2.5
	 * @const
	 */
	const ROLE_FIELD_NAME = 'advanced-ads-role';

	/**
	 * Initialize the plugin
	 *
	 * @since   1.0.0
	 */
	public function __construct() {
		// Add user role selection to users page.
		add_action( 'show_user_profile', [ $this, 'add_user_role_fields' ] );
		add_action( 'edit_user_profile', [ $this, 'add_user_role_fields' ] );

		add_action( 'profile_update', [ $this, 'save_user_role' ] );

		// Display warning if advanced visitor conditions are not active.
		add_action( 'advanced-ads-visitor-conditions-after', [ $this, 'show_condition_notice' ], 10, 0 );
		// Display "once per page" field.
		add_action( 'advanced-ads-output-metabox-after', [ $this, 'render_ad_output_options' ] );

		// Render repeat option for Content placement.
		add_action( 'advanced-ads-placement-post-content-position', [ $this, 'render_placement_repeat_option' ], 10, 2 );
		add_filter( 'pre_update_option_advanced-ads', [ $this, 'pre_update_advanced_ads_options' ], 10, 2 );

		// Show/hide warnings for privacy module based on Pro state.
		add_filter( 'advanced-ads-privacy-custom-show-warning', [ $this, 'show_custom_privacy_warning' ] );
		add_filter( 'advanced-ads-privacy-tcf-show-warning', '__return_false' );
		add_filter( 'advanced-ads-privacy-custom-link-attributes', [ $this, 'privacy_link_attributes' ] );
		add_filter( 'advanced-ads-ad-privacy-hide-ignore-consent', [ $this, 'hide_ignore_consent_checkbox' ], 10, 3 );

		// Show a warning if cache-busting is enabled, but no placement is used for a widget.
		add_action( 'in_widget_form', [ $this, 'show_no_placement_in_widget_warning' ], 10, 3 );
		add_action( 'advanced-ads-export-options', [ $this, 'export_options' ] );

		// Suggest a text for the WP Privacy Policy
		add_action( 'admin_init', [ $this, 'add_privacy_policy_content' ] );

		// Trim custom code on save.
		add_action( 'advanced-ads-ad-pre-save', [$this, 'trim_custom_code_on_save'], 10, 2 );
	}

	/**
	 * Trim whitespaces in custom code when saving an ad
	 *
	 * @param Ad $ad the ad.
	 * @param array $post_data sanitized content of $_POST.
	 *
	 * @return void
	 */
	public function trim_custom_code_on_save( $ad, $post_data ) {
		if ( isset( $post_data['custom-code'] ) ) {
			$ad->set_prop_temp( 'custom-code', trim( (string) $post_data['custom-code'] ) );
		}
	}

	/**
	 * Copy settings from `general` tab in order to prevent it from being cleaned
	 * when Pro is deactivated.
	 *
	 * @param mixed $options Advanced Ads options.
	 * @return mixed options
	 */
	public function pre_update_advanced_ads_options( $options ) {
		$pro = Advanced_Ads_Pro::get_instance()->get_options();

		if ( isset( $options['pro']['general']['disable-by-post-types'] ) && is_array( $options['pro']['general']['disable-by-post-types'] ) ) {
			$pro['general']['disable-by-post-types'] = $options['pro']['general']['disable-by-post-types'];
		} else {
			$pro['general']['disable-by-post-types'] = [];
		}
		Advanced_Ads_Pro::get_instance()->update_options( $pro );
		return $options;
	}

	/**
	 * Form field for user role selection
	 *
	 * @param array $user user data.
	 */
	public function add_user_role_fields( $user ) {
		if ( ! current_user_can( 'edit_users' ) ) {
			return;
		}

		$role = get_user_meta( $user->ID, self::ROLE_FIELD_NAME, true );
		?>
	<h3><?php esc_html_e( 'Advanced Ads User Role', 'advanced-ads-pro' ); ?></h3>
	<table class="form-table">
		<tr>
		<th><label for="advads_pro_role"><?php esc_html_e( 'Ad User Role', 'advanced-ads-pro' ); ?></label></th>
		<td><select name="<?php echo esc_attr( self::ROLE_FIELD_NAME ); ?>" id="advads_pro_role">
			<?php
			foreach ( $this->get_roles() as $_slug => $_name ) :
				?>
				<option value="<?php echo esc_attr( $_slug ); ?>" <?php selected( $role, $_slug ); ?>><?php echo esc_html( $_name ); ?></option>
				<?php
			endforeach;
			?>
		</select>
		<p class="description"><?php esc_html_e( 'Please note, with the last update, the “Ad Admin“ and “Ad Manager“ roles have the “upload_files“ and the “unfiltered_html“ capabilities.', 'advanced-ads-pro' ); ?></p>
		</td>
		</tr>
	</table>
		<?php
	}

	/**
	 * Update the user role
	 *
	 * @param int $user_id ID of the user.
	 */
	public function save_user_role( $user_id) {
		if (
			! array_key_exists( self::ROLE_FIELD_NAME, $_POST )
			|| ! current_user_can( 'edit_users' )
			|| ! wp_verify_nonce( Params::post( '_wpnonce' ), 'update-user_' . $user_id)
		) {
			return;
		}

		// check if this is a valid user role.
		$user_role = sanitize_text_field( Params::post( self::ROLE_FIELD_NAME ) );
		if ( ! array_key_exists( $user_role, $this->get_roles() ) ) {
			return;
		}

		// Get user object.
		$user = new WP_User( $user_id );

		// Remove previous role.
		$prev_role = get_user_meta( $user_id, self::ROLE_FIELD_NAME, true );
		$user->remove_role( $prev_role );

		// Save new role as user meta.
		update_user_meta( $user_id, self::ROLE_FIELD_NAME, $user_role );

		if ( $user_role ) {
			// Add role.
			$user->add_role( $user_role );
		}
	}


	/**
	 * Show a notice if advanced visitor conditions are disabled. Maybe some users are looking for it
	 */
	public function show_condition_notice() {
		$options = Advanced_Ads_Pro::get_instance()->get_options();

		if ( ! isset( $options['advanced-visitor-conditions']['enabled'] ) ) {
			echo '<p>' . sprintf(
				wp_kses(
					/* translators: %s: URL to the settings page */
					__( 'Enable the Advanced Visitor Conditions <a href="%s" target="_blank">in the settings</a>.', 'advanced-ads-pro' ),
					[
						'a' => [
							'href'   => [],
							'target' => [],
						],
					]
				),
				esc_url( admin_url( 'admin.php?page=advanced-ads-settings#top#pro' ) )
			) . '</p>';
		}
	}

	/**
	 * Add output options to ad edit page
	 *
	 * @param Ad $ad Ad instance.
	 */
	public function render_ad_output_options( Ad $ad ) {
		$once_per_page  = $ad->get_prop( 'once_per_page' ) ? 1 : 0;

		require AA_PRO_ABSPATH . '/views/setting_output_once.php';

		// Get CodeMirror setting for Custom code textarea.
		$settings        = $this->get_code_editor_settings();
		$custom_code     = ! empty( $ad->get_prop( 'custom-code' ) ) ? esc_textarea( $ad->get_prop( 'custom-code' ) ) : '';
		$privacy_options = Advanced_Ads_Privacy::get_instance()->options();
		require AA_PRO_ABSPATH . '/views/setting_custom_code.php';
	}

	/**
	 * Render repeat option for Content placement.
	 *
	 * @param string    $placement_slug Placement id.
	 * @param Placement $placement      Placement instance.
	 */
	public function render_placement_repeat_option( $placement_slug, $placement ) {
		$data                  = $placement->get_data();
		$words_between_repeats = ! empty( $data['words_between_repeats'] ) ? absint( $data['words_between_repeats'] ) : 0;
		require AA_PRO_ABSPATH . '/views/setting_repeat.php';
	}

	/**
	 * Get CodeMirror settings.
	 */
	public function get_code_editor_settings() {
		global $wp_version;
		if ( 'advanced_ads' !== get_current_screen()->id
			|| defined( 'ADVANCED_ADS_DISABLE_CODE_HIGHLIGHTING' )
			|| -1 === version_compare( $wp_version, '4.9' ) ) {
			return false;
		}

		// Enqueue code editor and settings for manipulating HTML.
		$settings = wp_enqueue_code_editor( [ 'type' => 'text/html' ] );

		if ( ! $settings ) {
			$settings = false;
		}

		return $settings;
	}

	/**
	 * Only show privacy warning if cache-busting module not enabled.
	 *
	 * @param bool $show Whether to show warning.
	 *
	 * @return bool
	 */
	public function show_custom_privacy_warning( $show ) {
		if ( ! $show ) {
			return $show;
		}

		$options = Advanced_Ads_Pro::get_instance()->get_options();

		return ! isset( $options['cache-busting']['enabled'] );
	}

	/**
	 * Update Link in Privacy settings ot settings page instead of external plugin page.
	 *
	 * @return array
	 */
	public function privacy_link_attributes() {
		return [
			'href' => esc_url( admin_url( 'admin.php?page=advanced-ads-settings#top#pro' ) ),
		];
	}

	/**
	 * Show the ignore-consent checkbox if this ad has custom code and type is image or dummy.
	 * The filter is called `advanced-ads-ad-privacy-hide-ignore-consent`, so the return needs to be !$hide to show.
	 *
	 * @param bool $hide Whether to show ignore-consent checkbox.
	 * @param Ad   $ad   Ad instance.
	 *
	 * @return bool
	 */
	public function hide_ignore_consent_checkbox( $hide, Ad $ad ) {
		if ( ! $hide || ! $ad->is_type( [ 'image', 'dummy' ] ) ) {
			return $hide;
		}

		return empty( Advanced_Ads_Pro::get_instance()->get_custom_code( $ad ) );
	}

	/**
	 * Show a warning below the form of Advanced Ads widgets if cache-busting is enabled
	 * but the widget does not use a placement or "Force passive cache-busting" is enabled
	 *
	 * Uses the in_widget_form action hook
	 *
	 * @param WP_Widget $widget   The widget instance (passed by reference).
	 * @param null      $return   Return null if new fields are added.
	 * @param array     $instance An array of the widget's settings.
	 */
	public function show_no_placement_in_widget_warning( $widget, $return, $instance ) {

		// bail if this is not the Advanced Ads widget
		if ( ! is_a( $widget, Widget::class ) ) {
			return;
		}

		// bail if cache-busting is not enabled or if Force passive cache-busting is enabled
		$options = Advanced_Ads_Pro::get_instance()->get_options();
		if ( empty( $options['cache-busting']['enabled'] ) || isset( $options['cache-busting']['passive_all'] ) ) {
			return;
		}

		// check item ID and show warning if it is given but does not contain a placement
		if ( ! empty( $instance['item_id'] ) && 0 !== strpos( $instance['item_id'], 'placement_' ) ) {
			?>
			<p class="advads-notice-inline advads-error">
			<?php esc_html_e( 'Select a Sidebar placement to enable cache-busting.', 'advanced-ads-pro' ); ?>
			<a href="https://wpadvancedads.com/manual/cache-busting/#Cache-Busting_in_Widgets" target="_blank">
				<?php esc_html_e( 'Learn more', 'advanced-ads-pro' ); ?>
			</a>
			</p>
			<?php
		}
	}

	/**
	 * Add Pro options to the list of options to be exported.
	 *
	 * @param $options Array of option data keyed by option keys.
	 * @return $options Array of option data keyed by option keys.
	 */
	public function export_options( $options ) {
		$options[ Advanced_Ads_Pro::OPTION_KEY ] = get_option( Advanced_Ads_Pro::OPTION_KEY );
		return $options;
	}

	/**
	 * Adds a privacy policy statement under Settings > Privacy > Policy Guide
	 * which customers can use as a basic templace.
	 */
	public function add_privacy_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		ob_start();
		include AA_PRO_ABSPATH . 'views/privacy-policy-content.php';

		wp_add_privacy_policy_content( 'Advanced Ads Pro', wp_kses_post( wpautop( ob_get_clean(), false ) ) );
	}

	/**
	 * Get the user roles.
	 *
	 * @return array
	 */
	private function get_roles(): array {
		return [
			'advanced_ads_admin'   => __( 'Ad Admin', 'advanced-ads-pro' ),
			'advanced_ads_manager' => __( 'Ad Manager', 'advanced-ads-pro' ),
			'advanced_ads_user'    => __( 'Ad User', 'advanced-ads-pro' ),
			''                     => __( '--no role--', 'advanced-ads-pro' ),
		];
	}
}
