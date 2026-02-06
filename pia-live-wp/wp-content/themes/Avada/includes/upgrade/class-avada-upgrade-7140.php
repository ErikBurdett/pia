<?php
/**
 * Upgrades Handler.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://avada.com
 * @package    Avada
 * @subpackage Core
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Handle migrations for Avada 7.14.0
 *
 * @since 7.14.0
 */
class Avada_Upgrade_7140 extends Avada_Upgrade_Abstract {

	/**
	 * The version.
	 *
	 * @access protected
	 * @since 7.14.0
	 * @var string
	 */
	protected $version = '7.14.0';

	/**
	 * An array of all available languages.
	 *
	 * @static
	 * @access private
	 * @since 7.14.0
	 * @var array
	 */
	private static $available_languages = [];

	/**
	 * The actual migration process.
	 *
	 * @access protected
	 * @since 7.14.0
	 * @return void
	 */
	protected function migration_process() {
		$available_languages       = Fusion_Multilingual::get_available_languages();
		self::$available_languages = ( ! empty( $available_languages ) ) ? $available_languages : [ '' ];

		$this->migrate_options();
	}

	/**
	 * Migrate options.
	 *
	 * @since 7.14.0
	 * @access protected
	 */
	protected function migrate_options() {
		$available_langs = self::$available_languages;

		$options = get_option( $this->option_name, [] );
		$options = $this->update_third_party_css_combination( $options );

		update_option( $this->option_name, $options );

		foreach ( $available_langs as $language ) {

			// Skip langs that are already done.
			if ( '' === $language ) {
				continue;
			}

			$options = get_option( $this->option_name . '_' . $language, [] );
			$options = $this->update_third_party_css_combination( $options );

			update_option( $this->option_name . '_' . $language, $options );
		}
	}

	/**
	 * Update "Combine Third Party CSS Files" option to a multi select.
	 *
	 * @access private
	 * @since 7.14.0
	 * @param array $options The Global Options array.
	 * @return array         The updated Global Options array.
	 */
	private function update_third_party_css_combination( $options ) {
		if ( isset( $options['css_combine_third_party_assets'] ) ) {
			if ( '1' === $options['css_combine_third_party_assets'] ) {
				$options['css_combine_third_party_assets'] = [ 'tec', 'slider_rev', 'convert_plus', 'contact_form_7', 'bbpress' ];
			} else {
				$options['css_combine_third_party_assets'] = [];
			}
		}

		return $options;
	}
}
