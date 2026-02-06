<?php
/**
 * The class provides tracking installation routines.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking\Installation;

use RuntimeException;
use AdvancedAds\Utilities\Data;
use AdvancedAds\Tracking\Helpers;
use AdvancedAds\Tracking\Debugger;
use AdvancedAds\Tracking\Utilities\Tracking;

/**
 * Class Tracking Installer
 *
 * Install the custom ajax-handler into WP_CONTENT and add database information
 */
class Tracking_Installer {

	const SOURCE_HANDLER = 'nowp-ajax-handler.php';
	const DEST_HANDLER   = 'ajax-handler.php';
	const VERSION_OPTION = 'ajax_dropin_version';

	/**
	 * The destination to write the handler to
	 *
	 * @var string
	 */
	private $dest_file;

	/**
	 * The source file for the handler
	 *
	 * @var string
	 */
	private $source_file;

	/**
	 * Last written drop-in version
	 *
	 * @var string
	 */
	private $ajax_dropin_version = '2.1.0';

	/**
	 * The destination directory for the ajax-handler.php file.
	 *
	 * @var string
	 */
	private $dest_dir;

	/**
	 * The destination URL for the ajax-handler.php file.
	 *
	 * @var string
	 */
	private $handler_url;

	/**
	 * The constructor.
	 */
	public function __construct() {
		$this->source_file = trailingslashit( dirname( AAT_FILE ) ) . self::SOURCE_HANDLER;

		/**
		 * Allow filtering of the destination directory for the ajax handler file.
		 *
		 * @param string WP_CONTENT_DIR by default.
		 */
		$this->dest_dir  = trailingslashit( apply_filters( 'advanced-ads-tracking-ajax-dropin-path', WP_CONTENT_DIR ) );
		$this->dest_file = $this->dest_dir . self::DEST_HANDLER;

		/**
		 * If the destination dir gets filtered, the resulting URL must also be filtered.
		 *
		 * @param string content_url() by default.
		 */
		$this->handler_url = trailingslashit( apply_filters( 'advanced-ads-tracking-ajax-dropin-url', content_url() ) ) . self::DEST_HANDLER;

		// Only try the following in wp-admin.
		if ( ! is_admin() ) {
			return;
		}

		$options = self::get_options();

		// Delete ajax handler if not needed. Keep it on multisite installations.
		if (
			( defined( 'ADVANCED_ADS_TRACKING_LEGACY_AJAX' ) && ADVANCED_ADS_TRACKING_LEGACY_AJAX )
			|| ( isset( $options['method'] ) && 'ga' === $options['method'] && ! is_multisite() )
		) {
			$this->uninstall();
		}
	}

	/**
	 * Return the add-on options
	 *
	 * @return array
	 */
	public static function get_options() {
		return get_option( 'advanced-ads-tracking', [] );
	}

	/**
	 * Trigger the ajax-handler.php installer to account for changed options.
	 *
	 * @return void
	 */
	public static function trigger_installer_update(): void {
		$options = self::get_options();
		if ( array_key_exists( self::VERSION_OPTION, $options ) ) {
			unset( $options[ self::VERSION_OPTION ] );
			update_option( 'advanced-ads-tracking', $options );
		}
	}

	/**
	 * Check if AJAX handler exists.
	 *
	 * @return bool
	 */
	public function handler_exists(): bool {
		try {
			return $this->get_filesystem()->exists( $this->dest_file );
		} catch ( RuntimeException $e ) {
			return file_exists( $this->dest_file );
		}
	}

	/**
	 * Print error message from the installer
	 *
	 * @param string $message The error message string.
	 *
	 * @return void
	 */
	public function installer_notice( $message ): void {
		add_action(
			'advanced-ads-notices',
			function () use ( $message ) {
				?>
				<div class="notice notice-error">
					<p><?php echo $message; // phpcs:ignore ?></p>
				</div>
				<?php
			}
		);
	}

	/**
	 * Install the custom ajax handler if environment permits it.
	 * Override if installed version is too old.
	 *
	 * @return void
	 */
	public function install(): void {
		if ( ! is_admin() || wp_doing_ajax() || ! class_exists( 'Advanced_Ads', false ) ) {
			return;
		}

		$options = wp_advads_tracking()->options->get_all();

		if (
			( ! Helpers::is_tracking_method( 'ga' ) || is_multisite() )
			&& ! Helpers::is_legacy_ajax()
			&& ( ! $this->handler_exists() || $this->needs_update( $options ) )
		) {
			if ( $this->write_handler() && $this->needs_update( $options ) ) {
				$options[ self::VERSION_OPTION ] = $this->generate_version_hash();
				wp_advads_tracking()->options->update( $options );
			}
		}
	}

	/**
	 * Remove the installed ajax handler.
	 * Return early if the file does not exist.
	 */
	public function uninstall() {
		if ( ! $this->handler_exists() ) {
			return;
		}

		try {
			$this->get_filesystem()->delete( $this->dest_file );
		} catch ( RuntimeException $e ) { // phpcs:ignore
			// if we don't have a filesystem and can't delete the file, just ignore this.
		}
	}

	/**
	 * Try to write the ajax handler to wp-content dir
	 *
	 * @return bool Whether the dropin file was written.
	 */
	private function write_handler(): bool {
		if ( Helpers::is_legacy_ajax() ) {
			return false;
		}

		try {
			$filesystem = $this->get_filesystem();
		} catch ( RuntimeException $e ) {
			return false;
		}

		// Check if the handler either exists and is writable or if parent dir is writable.
		if ( ! $filesystem->is_writable( $this->dest_dir ) || ( $this->handler_exists() && ! $filesystem->is_writable( $this->dest_file ) ) ) {
			$message = __( 'The Advanced Ads AJAX tracking drop-in could not be written.' ); // phpcs:ignore
			/* translators: 1: WP_CONTENT_DIR 2: <code>wp-config.php</code> 3: <code>define( 'ADVANCED_ADS_TRACKING_LEGACY_AJAX', true )</code> */
			$message .= '<br>' . sprintf( __( 'Please make sure the directory %1$s is writable or add the following to your %2$s: %3$s.', 'advanced-ads-tracking' ), '<code>' . $this->dest_dir . '</code>', '<code>wp-config.php</code>', '<code>define( \'ADVANCED_ADS_TRACKING_LEGACY_AJAX\', true )</code>' );
			/* translators: %s is <code>wp-admin/admin-ajax.php</code> */
			$message .= '<br>' . sprintf( 'Falling back to %s', '<code>wp-admin/admin-ajax.php</code>' );
			$this->installer_notice( $message );

			return false;
		}

		if ( ! file_exists( $this->source_file ) ) {
			return false;
		}

		$written = $filesystem->put_contents(
			$this->dest_file,
			vsprintf(
				$filesystem->get_contents( $this->source_file ),
				$this->gather_variables()
			)
		);
		if ( ! $written ) {
			return $written;
		}

		return $this->check_integrity();
	}

	/**
	 * Check if the installed ajax handler needs an update.
	 *
	 * @param array $options Options for add-on.
	 *
	 * @return bool
	 */
	private function needs_update( $options ): bool {
		return ! isset( $options[ self::VERSION_OPTION ] ) || $options[ self::VERSION_OPTION ] !== $this->generate_version_hash();
	}

	/**
	 * Get the bots from the main plugin and this add-on.
	 *
	 * @return string regex to match bots, empty if tracking bots.
	 */
	private function get_bots() {
		$options = wp_advads_tracking()->options->get_all();
		if ( array_key_exists( 'track-bots', $options ) && $options['track-bots'] ) {
			return '';
		}

		$bots = Data::get_bots();
		$bots = Tracking::add_bots_triggering_ajax( $bots );
		$bots = implode( '|', $bots );
		// Make sure delimiters in regex are escaped.
		$bots = preg_replace( '/(.*?)(?<!\\\)' . preg_quote( '/', '/' ) . '(.*?)/', '$1\\/$2', $bots );

		return $bots;
	}

	/**
	 * Generate a version hash to check if the drop-in needs to be rewritten.
	 *
	 * @return string
	 */
	private function generate_version_hash(): string {
		static $hash;

		if ( ! is_null( $hash ) ) {
			return $hash;
		}

		$hash = wp_hash( implode( '', $this->gather_variables() ) . $this->ajax_dropin_version, self::VERSION_OPTION );

		return $hash;
	}

	/**
	 * Gather the variables needed for writing the drop-in
	 *
	 * @return array
	 */
	private function gather_variables(): array {
		static $vars;

		if ( ! is_null( $vars ) ) {
			return $vars;
		}

		$vars = [
			'debug_file'    => Debugger::get_debug_file_path(),
			'debug_enabled' => Debugger::debugging_enabled() ? 'true' : 'false',
			'debug_id'      => get_option( Debugger::DEBUG_OPT, [ 'id' => 0 ] )['id'],
			'abs_path'      => ABSPATH,
			'plugin_path'   => AA_TRACKING_ABSPATH . 'packages/autoload.php',
			'bots'          => $this->get_bots(),
			'timezone'      => wp_timezone_string(),
		];

		return $vars;
	}

	/**
	 * Make sure that the custom ajax handler does not expose database credentials to the public.
	 *
	 * @return bool
	 */
	private function check_integrity(): bool {
		$response = wp_remote_post( $this->handler_url );
		if ( is_wp_error( $response ) ) {
			$this->uninstall();

			return false;
		}

		$response = $response['http_response'];

		if ( $response->get_response_object()->body === 'no ads' ) {
			return true;
		}

		// If the response is anything other than 'no ads', there's an issue on the website.
		$message = sprintf(
			/* translators: 1: <code>wp-content/ajax-handler.php</code> 2: <code>wp-admin/admin-ajax.php</code> 3: <a> 4: </a> 5: <code>ajax-handler.php</code> */
			esc_html__( 'The frontend tracking uses a swift AJAX call to %1$s for fetching ad IDs after page load. The creation of this Advanced Ads AJAX drop-in caused an issue and the file was removed. Now, the plugin employs %2$s as a reliable alternative. Please %3$scheck the documentation%4$s for more information on resolving your issue with %5$s and the fallback method.', 'advanced-ads-tracking' ),
			'<code>wp-content/ajax-handler.php</code>',
			'<code>wp-admin/admin-ajax.php</code>',
			'<a href="https://wpadvancedads.com/manual/tracking-methods/?utm_source=advanced-ads&utm_medium=link&utm_campaign=tracking-ajax-handler#Frontend" target="_blank" rel="noopener">',
			'</a>',
			'<code>ajax-handler.php</code>'
		);
		$message .= sprintf(
			'<br><label for="advanced-ads-unexpected-output">%s</label><br><textarea id="advanced-ads-unexpected-output" readonly  style="max-width: 100%%" rows="8" cols="120" onclick="this.select()">%s</textarea>',
			__( 'Error message:', 'advanced-ads-tracking' ),
			$response->get_response_object()->body
		);

		$this->installer_notice( $message );
		$this->uninstall();

		return false;
	}

	/**
	 * Check for the WP_Filesystem and error if no credentials.
	 *
	 * @throws RuntimeException If we can't find a Filesystem (e.g. not in admin), throw a RuntimeException.
	 *
	 * @return WP_Filesystem_Base
	 */
	private function get_filesystem() {
		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		if (
			null === $wp_filesystem ||
			( is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->has_errors()) ) {
			throw new RuntimeException( 'Can\'t instantiate WP_Filesystem' );
		}

		return $wp_filesystem;
	}

	/**
	 * Get the filtered URL for the ajax-handler.php.
	 *
	 * @return string the handler URL.
	 */
	public function get_handler_url() {
		return $this->handler_url;
	}
}
