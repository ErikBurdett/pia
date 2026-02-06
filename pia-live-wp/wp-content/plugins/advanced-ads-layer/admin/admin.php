<?php // phpcs:ignoreFile

use AdvancedAds\Abstracts\Ad;
use AdvancedAds\Utilities\WordPress;
use AdvancedAds\Abstracts\Placement;
use AdvancedAds\Utilities\Conditional;

class Advanced_Ads_Layer_Admin {

	/**
	 * stores the settings page hook
	 *
	 * @var     string
	 */
	protected $settings_page_hook = '';

	/**
	 * holds base class
	 *
	 * @var Advanced_Ads_Layer_Plugin
	 * @since 1.2.0
	 */
	protected $plugin;

	/**
	 * @var bool
	 * @since 1.3
	 */
	protected $fancybox_is_enabled;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->plugin = Advanced_Ads_Layer_Plugin::get_instance();

		add_action( 'plugins_loaded', array( $this, 'wp_admin_plugins_loaded' ) );
	}

	/**
	 * load actions and filters
	 */
	public function wp_admin_plugins_loaded() {
		$advads_options = $this->plugin->options();
		$this->fancybox_is_enabled = isset( $advads_options['layer']['use-fancybox'] ) ? $advads_options['layer']['use-fancybox'] : 0;

		// add notice to Ad Health notices in backend.
		add_filter( 'advanced-ads-ad-health-notices', array( $this, 'add_ad_health_notices' ) );
		// add notice if more than one fancybox is used.
		add_action( 'advanced-ads-admin-notices', array( $this, 'more_than_one_fancyboxes_notice' ) );

		// register settings.
		add_action( 'advanced-ads-settings-init', array( $this, 'settings_init' ) );
		// add our new options using the options filter before saving.
		add_action( 'advanced-ads-ad-pre-save', array( $this, 'save_ad_options' ), 10, 2 );
		// add admin scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		// content of layer placement.
		add_action( 'advanced-ads-placement-options-after-advanced', array( $this, 'layer_placement_content' ), 10, 2 );
		// add AdSense warning.
		add_action( 'advanced-ads-placement-options-after', array( $this, 'add_adsense_warning' ), 5, 2 );
	}

	/**
	 * Register warning to Ad Health notices in the backend
	 *
	 * @param	array 	$notices
	 * @return	array	extended notices
	 */
	public function add_ad_health_notices( $notices ) {
		$notices[ 'popup_multiple_fancyboxes' ] = array(
				'text' => __( 'You shouldn’t have more than one Fancybox on the same page. Please, make sure that you set up the ads for the different layers with display conditions to prevent them from showing up on the same page', 'advanced-ads-layer' )
		);

		return $notices;
	}

	/**
	 * show warning if Fancybox is enabled and more than one layer placement exists
	 */
	public function more_than_one_fancyboxes_notice() {
		$layers_count = 0;
		foreach ( wp_advads_get_placements() as $placement ) {
			if ( $placement->is_type( 'layer' ) ) {
				$layers_count++;
			}
		}

		/**
		 * With Advanced Ads 1.12 we register a notice instead of showing it on all pages
		 * registering this with the new `advanced-ads-admin-notices` hooks makes sure that Advanced_Ads_Ad_Health_Notices is also available
		 */
		if ( $this->fancybox_is_enabled && $layers_count > 1 ) {
			Advanced_Ads_Ad_Health_Notices::get_instance()->add( 'popup_multiple_fancyboxes' );
		} else {
			Advanced_Ads_Ad_Health_Notices::get_instance()->remove( 'popup_multiple_fancyboxes' );
		}
	}

	/**
	 * add layer placement styles
	 *
	 * @since 1.2.4
	 * @param type $hook_suffix
	 */
	function admin_scripts( $hook_suffix ) {
		if ( Conditional::is_screen_advanced_ads() ) {
			wp_enqueue_style( 'advanced-ads-layer-admin-css', AA_LAYER_ADS_BASE_URL . 'admin/assets/css/admin.css', array(), AAPLDS_VERSION );
			wp_add_inline_style( 'advanced-ads-layer-admin-css', self::get_custom_css() );
		}
	}

	/**
	 * creates the css containing the layer placement styles
	 *
	 * @since 1.6.3
	 */
	static final function get_custom_css(){
		$layer_class = Advanced_Ads_Layer::get_layer_class();
		$css = ".$layer_class-aa-position div.clear { content: ' '; display: block; float: none; clear: both; }\n";
		$css.= ".advads-placements-table .$layer_class-aa-position .advads-sticky-assistant table tbody tr td { width: 3em; height: 2em; text-align: center; vertical-align: middle; padding: 0; }\n";
		return $css;
	}

	/**
	 * add settings to settings page
	 *
	 * @since 1.2.0
	 */
	public function settings_init( $hook ) {
		$this->settings_page_hook = $hook;

		// add new section
		add_settings_section(
			'advanced_ads_layer_setting_section',
			'PopUp and Layer Ads',
			array( $this, 'render_settings_section_callback' ),
			$hook
		);

		// add setting fields
		add_settings_field(
			'use-fancybox',
			__( 'Use Fancybox plugin', 'advanced-ads-layer' ),
			array( $this, 'render_settings_fancybox_callback' ),
			$hook,
			'advanced_ads_layer_setting_section'
		);

	}

	/**
	 * render fancybox setting
	 *
	 */
	public function render_settings_section_callback() {
	}

	/**
	 * render fancybox setting
	 *
	 */
	public function render_settings_fancybox_callback() {
		$checked = $this->fancybox_is_enabled;
		require AA_LAYER_ADS_ABSPATH . '/admin/views/settings/general/enable-fancybox.php';
	}

	/**
	 * Save ad options.
	 *
	 * @param Ad    $ad        Ad instance.
	 * @param array $post_data Post data array.
	 *
	 * @return void
	 */
	public function save_ad_options( Ad $ad, $post_data ): void {
		$options = [];

		$options['enabled']                  = absint( $post_data['layer']['enabled'] ?? 0 );
		$options['trigger']                  = $post_data['layer']['trigger'] ?? '';
		$options['offset']                   = absint( $post_data['layer']['offset'] ?? 0 );
		$options['background']               = absint( $post_data['layer']['background'] ?? 0 );
		$options['close']['enabled']         = absint( $post_data['layer']['close']['enabled'] ?? 0 );
		$options['close']['where']           = $post_data['layer']['close']['where'] ?? '';
		$options['close']['side']            = $post_data['layer']['close']['side'] ?? '';
		$options['close']['timeout_enabled'] = $post_data['layer']['close']['timeout_enabled'] ?? false;
		$options['close']['timeout']         = absint( $post_data['layer']['close']['timeout'] ?? 0 );
		$options['effect']                   = $post_data['layer']['effect'] ?? 'show';
		$options['duration']                 = absint( $post_data['layer']['duration'] ?? 0 );

		$ad->set_prop( 'layer', $options );
	}

	/**
	 * render layer placement content
	 *
	 * @since 1.2.4
	 *
	 * @param string    $placement_slug Placement id.
	 * @param Placement $placement      Placement instance.
	 *
	 */
	public function layer_placement_content( $placement_slug, $placement ) {
		$placement_options = $placement->get_data();
		switch ( $placement->get_type() ) {
			case 'layer' :

				$options = $placement_options['layer_placement'] ?? [];
				$option_name = "advads[placements][options][layer_placement]";

				// trigger
				$trigger    = isset( $options['trigger'] ) ? $options['trigger'] : '';
				$offset     = isset( $options['offset'] ) ? absint( $options['offset'] ) : 0;
				$delay_sec  = isset( $options['delay_sec'] ) ? absint( $options['delay_sec'] ) : 0;

				ob_start();
				include AA_LAYER_ADS_ABSPATH . '/admin/views/trigger.php';
				$option_content = ob_get_clean();

				WordPress::render_option(
					'placement-layer-trigger',
					__( 'show the ad', 'advanced-ads-layer' ),
					$option_content );

				// effect
				$effect     = isset( $options['effect'] ) ? $options['effect'] : 'show';
				$duration   = isset( $options['duration'] ) ? absint( $options['duration'] ) : 0;

				ob_start();
				include AA_LAYER_ADS_ABSPATH . '/admin/views/effects.php';
				$option_content = ob_get_clean();

				WordPress::render_option(
					'placement-layer-effect',
					__( 'effect', 'advanced-ads-layer' ),
					$option_content );

				// background
				$background = isset( $options['background'] ) ? absint( $options['background'] ) : 0;
				$background_click_close = $background && ! empty( $options['background_click_close'] );

				ob_start();
				include AA_LAYER_ADS_ABSPATH . '/admin/views/background.php';
				$option_content = ob_get_clean();

				WordPress::render_option(
					'placement-layer-background',
					__( 'background', 'advanced-ads-layer' ),
					$option_content );

				// auto close
				ob_start();
				include AA_LAYER_ADS_ABSPATH . '/admin/views/auto_close.php';
				$option_content = ob_get_clean();

				WordPress::render_option(
					'placement-layer-auto-close',
					__( 'auto close', 'advanced-ads-layer' ),
					$option_content );

				// close button
				ob_start();
				include AA_LAYER_ADS_ABSPATH . '/admin/views/close-button.php';
				$option_content = ob_get_clean();

				WordPress::render_option(
					'placement-layer-trigger',
					__( 'close button', 'advanced-ads-layer' ),
					$option_content );

				// position on the screen
				ob_start();
				include AA_LAYER_ADS_ABSPATH . '/admin/views/position.php';
				$option_content = ob_get_clean();

				WordPress::render_option(
					'placement-layer-trigger',
					__( 'Position', 'advanced-ads-layer' ),
					$option_content );

				// dimension of the layer
				$width  = absint( $placement_options['placement_width'] ?? 0 );
				$height = absint( $placement_options['placement_height'] ?? 0 );

				ob_start();
				include AA_LAYER_ADS_ABSPATH . '/admin/views/size.php';
				$option_content = ob_get_clean();

				WordPress::render_option(
					'placement-layer-dimensions',
					__( 'size', 'advanced-ads-layer' ),
					$option_content );
			break;
		}
	}

	/**
	 * Add a warning when an AdSense ad is assigned to the layer placement.
	 *
	 * @param string    $placement_slug Placement id.
	 * @param Placement $placement      Placement instance.
	 */
	public function add_adsense_warning( $placement_slug, $placement ) {
		if ( ! $placement->is_type( 'layer') || empty( $placement->get_item() ) ) {
			return;
		}

		if ( ! class_exists( 'Advanced_Ads_Utils' ) || ! method_exists( 'Advanced_Ads_Utils', 'get_nested_ads' ) ) {
			return;
		}

		foreach ( Advanced_Ads_Utils::get_nested_ads( $placement_slug, 'placement' ) as $ad ) {
			if ( $ad->type === 'adsense' ) { ?>
				<p class="advads-error-message"><?php
				_e( 'It is against the AdSense policy to use their ads in popups.', 'advanced-ads-layer' ); ?></p>
				<?php return;
			}
		}
	}
}
