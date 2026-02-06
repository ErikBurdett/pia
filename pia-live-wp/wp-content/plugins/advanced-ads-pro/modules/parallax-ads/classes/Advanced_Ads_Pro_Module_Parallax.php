<?php //phpcs:ignoreFile

/**
 * Parallax class.
 */
class Advanced_Ads_Pro_Module_Parallax {
	/**
	 * Default values for options.
	 *
	 * @const array
	 */
	private const DEFAULT_VALUES = [
		'enabled' => null,
		'height'  => [
			'value' => 200,
			'unit'  => 'px',
		],
	];

	/**
	 * Allow parallax ads on placement types.
	 * Filterable, and then cached in this property.
	 *
	 * @var string[]
	 */
	private $allowed_placement_types;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'set_show_options_on_placement' ] );
		add_action( 'init', [ $this, 'start_module' ], 30 );
	}

	public function start_module() {
		if ( ! $this->enabled_placement_exists() ) {
			return;
		}

		if ( is_admin() && ! wp_doing_ajax() ) {
			new Advanced_Ads_Pro_Module_Parallax_Admin_UI( $this );

			return;
		}

		new Advanced_Ads_Pro_Module_Parallax_Frontend( $this );
	}

	/**
	 * Add filter to set placement option for parallax
	 *
	 * @return void
	 */
	public function set_show_options_on_placement() {
		foreach ( $this->get_allowed_placement_types() as $type_id ) {
			add_filter( 'advanced-ads-placement-' . $type_id . '-options', [ $this, 'set_show_parallax_option' ] );
		}
	}

	/**
	 * Add parallax options to allowed placements.
	 *
	 * @param array $options Array of placement options.
	 *
	 * @return array
	 */
	public function set_show_parallax_option( $options ): array {
		$options['show_parallax'] = true;

		return $options;
	}

	/**
	 * Iterate through all placements to see if there is one that can have the parallax effect.
	 * If found return as early as possible.
	 *
	 * @return bool
	 */
	public function enabled_placement_exists(): bool {
		foreach ( wp_advads_get_placements() as $placement ) {
			if ( $placement->is_type( $this->get_allowed_placement_types() ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether parallax is allowed on a specific placement type.
	 *
	 * @param string $placement_type The placement type to check against.
	 *
	 * @return bool
	 */
	public function allowed_on_placement( string $placement_type ): bool {
		return in_array( $placement_type, $this->get_allowed_placement_types(), true );
	}

	/**
	 * Return the default values for the parallax option.
	 *
	 * @return array
	 */
	public function get_default_option_values(): array {
		return self::DEFAULT_VALUES;
	}

	/**
	 * Get and filter the allowed placement types for the parallax option.
	 *
	 * @return array
	 */
	public function get_allowed_placement_types(): array {
		if ( ! isset( $this->allowed_placement_types ) ) {
			$allowed_placement_types = [
				'post_content',
			];

			/**
			 * Filter the allowed placement types, to allow the parallax option there.
			 *
			 * @param string[] $allowed_placement_types Array of placement type identifiers.
			 */
			$this->allowed_placement_types = apply_filters( 'advanced-ads-pro-parallax-allowed-placement-types', $allowed_placement_types );
			if ( ! is_array( $this->allowed_placement_types ) ) {
				$this->allowed_placement_types = [];
			}
		}

		return $this->allowed_placement_types;
	}
}
