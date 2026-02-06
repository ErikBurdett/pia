<?php // phpcs:disable WordPress.Files.FileName.InvalidClassFileName

use AdvancedAds\Modal;
use AdvancedAds\Options;
use AdvancedAds\Utilities\Conditional;
use AdvancedAds\Framework\Utilities\Params;

/**
 * Extended Adblocker Module
 *
 * @package AdvancedAds\Pro
 * @since   3.0.0
 */
class Advanced_Ads_Pro_Module_Extended_Adblocker {

	/**
	 * Plugin options array
	 *
	 * @var array
	 */
	private $options = [];

	/**
	 * Overlay display frequency setting
	 *
	 * @var string
	 */
	private $show_frequency;

	/**
	 * Cookie name for tracking last overlay display time
	 *
	 * @var string
	 */
	private const COOKIE_NAME = AA_PRO_SLUG . '-overlay-last-display';

	/**
	 * Initializes the module with options and hooks.
	 */
	public function __construct() {
		$this->options           = Options::instance()->get( 'adblocker', [] );
		$this->show_frequency    = $this->options['overlay']['time_frequency'] ?? 'everytime';
		$this->options['method'] = $this->options['method'] ?? 'nothing';

		add_action( 'init', [ $this, 'print_extended_adblocker' ], 99 );
	}

	/**
	 * Determines which method to use (overlay/redirect) and enqueues the appropriate action.
	 */
	public function print_extended_adblocker(): void {
		if ( ! $this->prechecks() ) {
			return;
		}

		$method_name = $this->get_method_name();

		if ( ! empty( $method_name ) && method_exists( $this, $method_name ) ) {
			add_action( 'wp_footer', [ $this, $method_name ], 99 );
		}
	}


	/**
	 * Render overlay modal and detection script.
	 */
	public function print_overlay(): void {
		$button_text = $this->get_dismiss_button_text();

		Modal::create(
			[
				'modal_slug'             => 'extended-adblocker',
				'modal_content'          => $this->options['overlay']['content'] ?? '',
				'close_action'           => $button_text,
				'template'               => plugin_dir_path( __FILE__ ) . 'views/modal.php',
				'dismiss_button_styling' => $this->options['overlay']['dismiss_style'] ?? '',
				'container_styling'      => $this->options['overlay']['container_style'] ?? '',
				'background_styling'     => $this->options['overlay']['background_style'] ?? '',
			]
		);

		$this->print_overlay_script();
	}

	/**
	 * Render redirect script for adblocker detection.
	 *
	 * Redirects to configured URL when adblocker is detected.
	 */
	public function print_redirect(): void {
		$redirect_url = $this->options['redirect']['url'] ?? '';

		if ( empty( $redirect_url ) ) {
			return;
		}

		$current_url = $this->get_current_url();

		if ( $this->compare_urls( $redirect_url, $current_url ) ) {
			return; // Don't redirect to the same URL.
		}

		$this->print_redirect_script( $redirect_url );
	}

	/**
	 * Check if adblocker functionality should run.
	 *
	 * @return bool True if functionality should run, false otherwise.
	 */
	private function prechecks(): bool {
		// Don't run on AMP or when method is disabled.
		if ( Conditional::is_amp() || 'nothing' === $this->options['method'] ) {
			return false;
		}

		// Check if current user should be excluded.
		if ( $this->is_user_excluded() ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the method name to execute based on settings.
	 *
	 * @return string Method name or empty string if none should be executed.
	 */
	private function get_method_name(): string {
		if ( 'overlay' === $this->options['method'] ) {
			wp_enqueue_style( 'eab-modal', plugin_dir_url( __FILE__ ) . 'assets/css/modal.css', [], AAP_VERSION );

			return $this->should_show_overlay() ? 'print_overlay' : '';
		}

		if ( 'redirect' === $this->options['method'] ) {
			return 'print_redirect';
		}

		return '';
	}

	/**
	 * Get dismiss button text for overlay.
	 *
	 * @return string|null Button text or null if dismiss should be hidden.
	 */
	private function get_dismiss_button_text(): ?string {
		if ( isset( $this->options['overlay']['hide_dismiss'] ) ) {
			return null;
		}

		$default_text = __( 'Dismiss', 'advanced-ads-pro' );
		$custom_text  = $this->options['overlay']['dismiss_text'] ?? '';

		return ! empty( $custom_text ) ? $custom_text : $default_text;
	}

	/**
	 * Print JavaScript for overlay detection and cookie management.
	 */
	private function print_overlay_script(): void {
		$cookie_name = wp_json_encode( self::COOKIE_NAME );
		$max_age     = (int) $this->get_interval_from_frequency();
		?>
		<script>
			jQuery(function() {
				var cookieName = <?php echo wp_json_encode( self::COOKIE_NAME ); ?>;
				var maxAge = <?php echo (int) $this->get_interval_from_frequency(); ?>;

				function setAdblockCookie() {
					if (maxAge <= 0) { return; }
					var expires = new Date(Date.now() + (maxAge * 1000)).toUTCString();
					var value = Math.floor(Date.now()/1000);
					document.cookie = cookieName + '=' + value + '; expires=' + expires + '; path=/;';
				}

				if (typeof window.advanced_ads_check_adblocker === 'function') {
					window.advanced_ads_check_adblocker( function ( is_enabled ) {
						if ( is_enabled ) {
							setAdblockCookie();
							var dlg = document.getElementById('modal-extended-adblocker');
							if (dlg && typeof dlg.showModal === 'function') {
								dlg.showModal();
							}
						}
					} );
				}
			});
		</script>
		<?php
	}

	/**
	 * Check if current user should be excluded from adblocker functionality.
	 *
	 * @return bool True if user should be excluded, false otherwise.
	 */
	private function is_user_excluded(): bool {
		$hide_for_roles = $this->options['exclude'] ?? [];

		if ( empty( $hide_for_roles ) ) {
			return false;
		}

		$hide_for_roles = Advanced_Ads_Utils::maybe_translate_cap_to_role( $hide_for_roles );
		$user           = wp_get_current_user();

		return is_user_logged_in()
			&& is_array( $user->roles )
			&& ! empty( array_intersect( $hide_for_roles, $user->roles ) );
	}

	/**
	 * Get the last time the overlay was displayed from cookie.
	 *
	 * @return int Unix timestamp of last display, 0 if never displayed.
	 */
	private function get_last_display_time(): int {
		return (int) Params::cookie( self::COOKIE_NAME, 0 );
	}

	/**
	 * Check if overlay should be shown based on frequency settings.
	 *
	 * @return bool True if overlay should be shown, false otherwise.
	 */
	private function should_show_overlay(): bool {
		switch ( $this->show_frequency ) {
			case 'everytime':
				return true;
			case 'never':
				return false;
			default:
				return $this->is_overlay_due();
		}
	}

	/**
	 * Check if overlay is due based on last display time and interval.
	 *
	 * @return bool True if overlay is due, false otherwise.
	 */
	private function is_overlay_due(): bool {
		$last_display_time = $this->get_last_display_time();
		$interval          = $this->get_interval_from_frequency();

		return time() >= ( $last_display_time + $interval );
	}

	/**
	 * Get interval in seconds based on frequency setting.
	 *
	 * @return int Interval in seconds.
	 */
	private function get_interval_from_frequency(): int {
		$intervals = [
			'everytime' => 0,
			'hour'      => HOUR_IN_SECONDS,
			'day'       => DAY_IN_SECONDS,
			'week'      => WEEK_IN_SECONDS,
			'month'     => MONTH_IN_SECONDS,
		];

		return $intervals[ $this->show_frequency ] ?? 0;
	}

	/**
	 * Get current page URL.
	 *
	 * @return string Current page URL.
	 */
	private function get_current_url(): string {
		$protocol = is_ssl() ? 'https://' : 'http://';
		$host     = Params::server( 'HTTP_HOST' );
		$uri      = Params::server( 'REQUEST_URI' );

		return $protocol . $host . $uri;
	}

	/**
	 * Print JavaScript for redirect detection.
	 *
	 * @param string $redirect_url URL to redirect to.
	 */
	private function print_redirect_script( string $redirect_url ): void {
		$escaped_url = esc_js( $redirect_url );
		?>
		<script>
			jQuery(function() {
				if (typeof window.advanced_ads_check_adblocker === 'function') {
					window.advanced_ads_check_adblocker( function ( is_enabled ) {
						if ( is_enabled ) {
							window.location.href = <?php echo wp_json_encode( $redirect_url ); ?>;
						}
					} );
				}
			});
		</script>
		<?php
	}

	/**
	 * Compare two URLs to determine if they are the same.
	 *
	 * @param string $url1 URL in database.
	 * @param string $url2 current page url.
	 *
	 * @return bool True if URLs are the same, false otherwise.
	 */
	private function compare_urls( string $url1, string $url2 ): bool {
		$parsed_url1 = wp_parse_url( $url1 );
		$parsed_url2 = wp_parse_url( $url2 );

		if ( false === $parsed_url1 || false === $parsed_url2 ) {
			return false;
		}

		$host1 = $parsed_url1['host'] ?? '';
		$host2 = $parsed_url2['host'] ?? '';
		$path1 = $parsed_url1['path'] ?? '/';
		$path2 = $parsed_url2['path'] ?? '/';

		return $host1 === $host2 && $path1 === $path2;
	}
}
