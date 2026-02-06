<?php
/**
 * Options.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking;

use Advanced_Ads;
use AdvancedAds\Framework\Utilities\Arr;
use AdvancedAds\Framework\Interfaces\Initializer_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Options.
 */
class Options implements Initializer_Interface {
	/**
	 * Default options for the tracking plugin.
	 *
	 * @var array
	 */
	private $default_options = [
		'method'               => 'frontend',
		'everything'           => 'true',
		'linkbase'             => 'linkout',
		'nofollow'             => false,
		'sponsored'            => false,
		'sum-timeout'          => '60',
		'public-stats-slug'    => 'ad-stats',
		'email-addresses'      => '',
		'email-sched'          => 'daily',
		'email-stats-period'   => 'last30days',
		'email-sender-name'    => 'Advanced Ads',
		'email-sender-address' => 'noreply@_',
		'email-subject'        => 'Ads Statistics',
	];

	/**
	 * Hold plugin options
	 *
	 * @var array
	 */
	private $options = null;

	/**
	 * Runs this initializer.
	 *
	 * @return void
	 */
	public function initialize(): void {}

	/**
	 * Retrieves the value of a specific option.
	 *
	 * @param string $id      The ID of the option to retrieve.
	 * @param mixed  $default Optional. The default value to return if the option is not found. Default is false.
	 *
	 * @return mixed The value of the option if found, or the default value if not found.
	 */
	public function get( $id, $default = false ) {
		$options = $this->get_all();

		return Arr::get( $options, $id, $default );
	}

	/**
	 * Load advanced ads settings.
	 * If options are empty or in old format, convert to new options.
	 *
	 * @return array
	 */
	public function get_all(): array {
		// Early bail!!
		if ( null !== $this->options ) {
			return $this->options;
		}

		$this->options = get_option( ADVADS_SLUG . '-tracking', [] );

		// Get "old" options.
		if ( empty( $this->options ) ) {
			$old_options   = Advanced_Ads::get_instance()->options();
			$this->options = $this->default_options;

			if ( isset( $old_options['tracking'] ) ) {
				$this->options = array_merge( $this->options, $old_options['tracking'] );
			}

			// Save as new options.
			$this->update( $this->options );
		} else {
			$this->options = wp_parse_args( $this->options, $this->default_options );
		}

		return $this->options;
	}

	/**
	 * Update plugin options
	 *
	 * @param array $options Options array.
	 *
	 * @return void
	 */
	public function update( array $options ): void {
		// don’t allow to clear options.
		if ( [] === $options ) {
			return;
		}

		$this->options = $options;
		update_option( ADVADS_SLUG . '-tracking', $options );
	}
}
