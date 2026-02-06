<?php
/**
 * Bootstrap.
 *
 * @package AdvancedAds\StickyAds
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   1.9.0
 */

namespace AdvancedAds\StickyAds;

defined( 'ABSPATH' ) || exit;

/**
 * Bootstrap.
 */
class Bootstrap {

	/**
	 * Whether the plugin has been started.
	 *
	 * @var bool
	 */
	private $done = false;

	/**
	 * Get singleton instance.
	 *
	 * @return Bootstrap
	 */
	public static function get() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Start the plugin.
	 *
	 * @return void
	 */
	public function start(): void {
		// Early bail!!
		if ( $this->done ) {
			return;
		}

		add_action( 'plugins_loaded', [ $this, 'halt_code' ], -10 );

		$this->done = true;
	}

	/**
	 * Halt code for new release.
	 *
	 * @return void
	 */
	public function halt_code(): void {
		global $advads_halt_notices;

		if ( ! isset( $advads_halt_notices ) ) {
			$advads_halt_notices = [];
		}

		// Early bail!!
		if ( ! defined( 'ADVADS_VERSION' ) ) {
			$advads_halt_notices[] = __( 'Advanced Ads – Sticky Ads', 'advanced-ads-sticky' );
			add_action( 'all_admin_notices', [ $this, 'print_missing_notices' ] );
			add_action( 'after_plugin_row_' . plugin_basename( AA_STICKY_ADS_FILE ), [ $this, 'missing_row' ] );
			return;
		}

		if ( version_compare( ADVADS_VERSION, '2.0.0', '<' ) ) {
			$advads_halt_notices[] = __( 'Advanced Ads – Sticky Ads', 'advanced-ads-sticky' );
			add_action( 'all_admin_notices', [ $this, 'print_halt_notices' ] );
			add_action( 'after_plugin_row_' . plugin_basename( AA_STICKY_ADS_FILE ), [ $this, 'compatible_row' ] );
			return;
		}

		// Start it.
		add_action( 'advanced-ads-loaded', 'wp_advads_stickyads' );
	}

	/**
	 * Display missing notice row.
	 *
	 * @return void
	 */
	public function missing_row(): void {
		$this->print_row(
			__( '<strong>Advanced Ads – Sticky Ads</strong> requires the <strong>Advanced Ads free</strong> plugin to be installed and activated on your site.', 'advanced-ads-sticky' )
			. '&nbsp;' . $this->get_button( 'button-link' )
		);
	}

	/**
	 * Display compatible notice row.
	 *
	 * @return void
	 */
	public function compatible_row(): void {
		$this->print_row(
			sprintf(
				/* translators: %s: Plugin name */
				__( 'Your version of <strong>Advanced Ads – Sticky Ads</strong> is incompatible with <strong>Advanced Ads %s</strong> and has been deactivated. Please update the plugin to the latest version.', 'advanced-ads-sticky' ),
				ADVADS_VERSION
			)
		);
	}

	/**
	 * Display missing notices.
	 *
	 * @return void
	 */
	public function print_missing_notices(): void {
		global $advads_halt_notices;

		// Early bail!!
		if ( 'plugins' === get_current_screen()->base || empty( $advads_halt_notices ) ) {
			return;
		}

		$this->print_notices(
			__( 'Important Notice', 'advanced-ads-sticky' ),
			__( 'Addons listed below requires the <strong><a href="https://wpadvancedads.com/?utm_source=advanced-ads&utm_medium=link&utm_campaign=activate-sticky" target="_blank">Advanced Ads</a></strong> plugin to be installed and activated on your site.', 'advanced-ads-sticky' )
			. '&nbsp;' . $this->get_button(),
			$advads_halt_notices
		);

		$advads_halt_notices = [];
	}

	/**
	 * Display halt notices.
	 *
	 * @return void
	 */
	public function print_halt_notices(): void {
		global $advads_halt_notices;

		// Early bail!!
		if ( 'plugins' === get_current_screen()->base || empty( $advads_halt_notices ) ) {
			return;
		}

		$this->print_notices(
			__( 'Important Notice', 'advanced-ads-sticky' ),
			sprintf(
				/* translators: %s: Plugin name */
				__( 'Your versions of the Advanced Ads addons listed below are incompatible with <strong>Advanced Ads %s</strong> and have been deactivated. Please update the plugin to the latest version.', 'advanced-ads-sticky' ),
				ADVADS_VERSION
			),
			$advads_halt_notices
		);

		$advads_halt_notices = [];
	}

	/**
	 * Display notices.
	 *
	 * @param string $title       Title.
	 * @param string $description Description.
	 * @param array  $notices     Notices.
	 *
	 * @return void
	 */
	private function print_notices( $title, $description, $notices ): void {
		?>
		<div class="notice notice-error">
			<h2><?php echo esc_html( $title ); ?></h2>
			<p>
				<?php echo wp_kses_post( $description ); ?>
			</p>
			<h3><?php esc_html_e( 'The following addons are affected:', 'advanced-ads-sticky' ); ?></h3>
			<ul>
				<?php foreach ( $notices as $notice ) : ?>
					<li><strong><?php echo esc_html( $notice ); ?></strong></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Print row.
	 *
	 * @param string $message Message to print.
	 *
	 * @return void
	 */
	private function print_row( $message ): void {
		?>
		<tr class="active">
			<td colspan="5" class="plugin-update colspanchange">
				<div class="notice notice-error notice-alt inline update-message">
					<p>
						<?php echo wp_kses_post( $message ); ?>
					</p>
				</div>
			</td>
		</tr>
		<?php
	}

	/**
	 * Get button.
	 *
	 * @param string $type Button type.
	 *
	 * @return string
	 */
	private function get_button( $type = 'button-primary' ): string {
		$plugins = get_plugins();

		$link = wp_nonce_url(
			self_admin_url( 'update.php?action=install-plugin&plugin=advanced-ads' ),
			'install-plugin_advanced-ads'
		);
		$text = __( 'Install Now', 'advanced-ads-sticky' );

		// Check if Advanced Ads is already installed.
		if ( isset( $plugins['advanced-ads/advanced-ads.php'] ) ) {
			$link = wp_nonce_url(
				self_admin_url( 'plugins.php?action=activate&plugin=advanced-ads/advanced-ads.php' ),
				'activate-plugin_advanced-ads/advanced-ads.php'
			);
			$text = __( 'Activate Now', 'advanced-ads-sticky' );
		}

		return sprintf(
			'<a class="button %s" href="%s">%s</a>',
			$type,
			$link,
			$text
		);
	}
}
