<?php // phpcs:ignoreFile
use AdvancedAds\Abstracts\Ad;
use AdvancedAds\Tracking\Helpers;
use AdvancedAds\Utilities\Conditional;

/**
 * Background ads class.
 */
class Advanced_Ads_Pro_Module_Background_Ads {

	/**
	 * List of placements.
	 *
	 * @var array
	 */
	private $placements = [];

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_footer', [ $this, 'footer_injection' ], 20 );
		add_action( 'wp_head', [ $this, 'initialise_rotating_click_listener' ] );

		// Register output change hook.
		add_filter( 'advanced-ads-ad-output', [ $this, 'ad_output' ], 20, 2 );
	}

	/**
	 * Creates abort controller to reset the click event listeners for rotating ads.
	 *
	 * @return void
	 */
	public function initialise_rotating_click_listener() {
		if ( ! $this->contains_background_placement() ) {
			return;
		}

		wp_register_script( 'advanced-ads-pro/background-ads', '', [], AAP_VERSION, false );
		wp_enqueue_script( 'advanced-ads-pro/background-ads' );
		wp_add_inline_script( 'advanced-ads-pro/background-ads', 'let abort_controller = new AbortController();' );
	}

	/**
	 * Echo the placement output into the footer.
	 *
	 * @return void
	 */
	public function footer_injection() {
		foreach ( wp_advads_get_placements() as $placement ) {
			if ( $placement->is_type( 'background' ) ) {
				the_ad_placement( $placement );
			}
		}
	}

	/**
	 * Check if a background placement exists.
	 *
	 * @return bool
	 */
	protected function contains_background_placement(): bool {
		foreach ( wp_advads_get_placements() as $placement ) {
			if ( $placement->is_type( 'background' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Change ad output.
	 *
	 * @param string $output Ad output.
	 * @param Ad     $ad     Ad instance.
	 *
	 * @return string
	 */
	public function ad_output( $output, $ad ) {
		$parent = $ad->get_root_placement();

		if ( ! $parent || ! $parent->is_type( 'background' ) || ! $ad->is_type( 'image' ) ) {
			return $output;
		}

		$background_color = sanitize_text_field( $parent->get_prop( 'bg_color' ) ) ?: false;

		// Get prefix and generate new body class.
		$class  = wp_advads()->get_frontend_prefix() . 'body-background';

		// Get the ad image.
		$image = wp_get_attachment_image_src( $ad->get_image_id(), 'full' );
		if ( ! $image || empty ( $image[0] ) ) {
			return $output;
		}

		[ $image_url, $image_width, $image_height ] = $image;

		$selector = apply_filters( 'advanced-ads-pro-background-selector', 'body' );
		$is_amp   = Conditional::is_amp();

		/**
		 * Filter the background placement URL.
		 *
		 * @param string $link The URL.
		 * @param Ad     $ad   The current ad object.
		 */
		$link = (string) apply_filters( 'advanced-ads-pro-background-url', $ad->get_url(), $ad );

		if ( class_exists( Helpers::class ) ) {
			$target = Helpers::get_ad_target( $ad, true );
		} else {
			$options = Advanced_Ads::get_instance()->options();
			$target  = isset( $options['target-blank'] ) ? '_blank' : '';
		}
		$target = '' !== $target ? $target : '_self';

		ob_start();
		?>
		<style>
			<?php echo $selector; // phpcs:ignore ?> {
				background: url(<?php echo $image_url; // phpcs:ignore ?>) no-repeat fixed;
				background-size: 100% auto;
			<?php echo $background_color ? "background-color: {$background_color};" : ''; ?>
			}
			<?php if ( $link && ! $is_amp ) : ?>
				<?php
					/**
					 * We should not use links and other tags that should have cursor: pointer as direct children of the $selector.
					 * That is, we need a nested container (e.g. body > div > a) to make it work correctly.
					 */
				?>
				<?php echo $selector; // phpcs:ignore ?> { cursor: pointer; }
				<?php echo $selector; // phpcs:ignore ?> > * { cursor: default; }
			<?php endif; ?>
		</style>
		<?php
		/**
		 * Don't load any javascript on amp.
		 * Javascript output can be prevented by disabling click tracking and empty url field on ad level.
		 */
		if ( ! $is_amp ) :
			?>
			<script>
				( window.advanced_ads_ready || document.readyState === 'complete' ).call( null, function () {
					// Remove all existing click event listeners and recreate the controller.
					abort_controller.abort();
					abort_controller = new AbortController();
					document.querySelector( '<?php echo esc_attr( $selector ); ?>' ).classList.add( '<?php echo esc_attr( $class ); ?>' );
					<?php if ( $link ) : ?>
					// Use event delegation because $selector may be not in the DOM yet.
					document.addEventListener( 'click', function ( e ) {
						if ( e.target.matches( '<?php echo $selector; // phpcs:ignore ?>' ) ) {
							<?php
							$script = '';
							/**
							 * Add additional script output.
							 *
							 * @param string          $script The URL.
							 * @param Ad $ad     The current ad object.
							 */
							$script = (string) apply_filters( 'advanced-ads-pro-background-click-matches-script', $script, $ad );
							// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- this is our own JS code, escaping will break it
							echo $script;
							?>
							// Open url in new tab.
							window.open( '<?php echo esc_url( $link ); ?>', '<?php echo esc_attr( $target ); ?>' );
						}
					}, { signal: abort_controller.signal } );
					<?php endif; ?>
				} );
			</script>
		<?php endif; ?>
		<?php
		$custom_code = $ad->get_prop( 'custom-code' );
		if ( ! empty( $custom_code ) ) {
			echo $custom_code;
		}

		return ob_get_clean();
	}
}
