<?php
/**
 * Upgrades.
 *
 * @package AdvancedAds\Pro
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.26.0
 */

namespace AdvancedAds\Pro;

use AdvancedAds\Framework\Updates;
use AdvancedAds\Framework\Interfaces\Initializer_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Upgrades.
 */
class Upgrades extends Updates implements Initializer_Interface {

	const DB_VERSION = '1.1';

	/**
	 * Get updates that need to run.
	 *
	 * @since 2.26.0
	 *
	 * @return array
	 */
	public function get_updates(): array {
		return [
			'1.1' => 'upgrade-1-1.php',
		];
	}

	/**
	 * Get folder path
	 *
	 * @since 2.26.0
	 *
	 * @return string
	 */
	public function get_folder(): string {
		return AA_PRO_ABSPATH . 'upgrades/';
	}

	/**
	 * Get plugin version number
	 *
	 * @since 2.26.0
	 *
	 * @return string
	 */
	public function get_version(): string {
		return self::DB_VERSION;
	}

	/**
	 * Get plugin option name.
	 *
	 * @since 2.26.0
	 *
	 * @return string
	 */
	public function get_option_name(): string {
		return 'advanced_ads_pro_db_version';
	}

	/**
	 * Runs this initializer.
	 *
	 * @since 2.26.0
	 *
	 * @return void
	 */
	public function initialize(): void {
		// Force run the upgrades.
		$is_first_time = empty( $this->get_installed_version() );
		$this->hooks();

		if ( $is_first_time ) {
			update_option( $this->get_option_name(), '1.0.0' );
			add_action( 'admin_init', [ $this, 'perform_updates' ] );
		}
	}
}
