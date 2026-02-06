<?php // phpcs:ignore WordPress.Files.FileName

use AdvancedAds\Abstracts\Placement;
use AdvancedAds\Utilities\WordPress;

/**
 * Admin UI for the parallax option.
 */
class Advanced_Ads_Pro_Module_Parallax_Admin_UI {
	/**
	 * The injected parallax class.
	 *
	 * @var Advanced_Ads_Pro_Module_Parallax
	 */
	private $parallax;

	/**
	 * Constructor.
	 *
	 * @param Advanced_Ads_Pro_Module_Parallax $parallax The injected parallax class.
	 */
	public function __construct( Advanced_Ads_Pro_Module_Parallax $parallax ) {
		add_action( 'advanced-ads-placement-options-after-advanced', [ $this, 'render_option' ], 9, 2 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		$this->parallax = $parallax;
	}

	/**
	 * Enqueue admin stylesheet.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		wp_enqueue_style( 'advads-pro-parallax-admin-style', plugins_url( 'assets/css/admin-parallax-ads.css', __DIR__ ), [ 'advanced-ads-pro-admin-styles' ], AAP_VERSION );
		wp_enqueue_script( 'advads-pro-parallax-admin-script', plugins_url( 'assets/js/admin-parallax-ads.js', __DIR__ ), [], AAP_VERSION, true );
	}

	/**
	 * Render parallax option in modal.
	 *
	 * @param string    $placement_slug Placement ID.
	 * @param Placement $placement      Placement array.
	 */
	public function render_option( string $placement_slug, $placement ): void {
		if ( empty( $placement->get_type_object()->get_options()['show_parallax'] ) ) {
			return;
		}

		// Options are not defined on a new placement.
		$parallax_options    = wp_parse_args(
			$placement->get_prop( 'parallax' ) ?? [],
			$this->parallax->get_default_option_values()
		);
		$option_prefix       = 'advads[placements][options][parallax]';
		$parallax_enabled    = isset( $parallax_options['enabled'] );
		$parallax_enabled_id = 'advads-option-placement-parallax-enabled-' . $placement_slug;
		$height_value        = $parallax_options['height']['value'];
		$height_unit         = $parallax_options['height']['unit'];
		$height_units        = [
			'px' => 'px',
			'vh' => '%',
		];

		ob_start();
		require __DIR__ . '/../views/placement-options-after.php';
		$option_content = ob_get_clean();

		WordPress::render_option(
			'placement-parallax',
			__( 'Parallax Ads', 'advanced-ads-pro' ),
			$option_content
		);
	}
}
