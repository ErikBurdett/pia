<?php
/**
 * Public Ad.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking;

use AdvancedAds\Abstracts\Ad;

defined( 'ABSPATH' ) || exit;

/**
 * Public Ad.
 */
class Public_Ad {

	/**
	 * Ad object.
	 *
	 * @var Ad
	 */
	private $ad = null;

	/**
	 * Constructor.
	 *
	 * @param Ad|WP_Post|int|bool $ad_id Ad instance, post instance, numeric or false to use global $post.
	 */
	public function __construct( $ad_id ) {
		$this->ad = wp_advads_get_ad( $ad_id );
	}

	/**
	 * Check if the current ad has tracking enabled.
	 *
	 * @return bool True if tracking is enabled.
	 */
	public function has_tracking(): bool {
		$tracking = $this->ad->get_prop( 'tracking.enabled' );

		return ! empty( $tracking );
	}

	/**
	 * Get the public ad name as set by the user.
	 *
	 * @param bool $fallback Whether to return the ad title.
	 *
	 * @return string
	 */
	public function get_name( $fallback = false ): string {
		$name = $this->ad->get_prop( 'tracking.public-name' );
		if ( $fallback && empty( $name ) ) {
			$name = $this->ad->get_title();
		}

		return $name ?? '';
	}

	/**
	 * Gets public ID from ad_id
	 *
	 * @return string The public ID.
	 */
	public function get_id(): string {
		$public_id = $this->ad->get_prop( 'tracking.public-id' );
		if ( ! empty( $public_id ) ) {
			return $public_id;
		}

		return $this->set_id();
	}

	/**
	 * Get the URL for the public ad statistics.
	 *
	 * @return string The URL for the public ad statistics.
	 */
	public function get_url(): string {
		$public_stats_slug = Helpers::get_public_stats_slug();
		$public_id         = $this->get_id();

		$url = sprintf( '/%1$s/%2$s/', $public_stats_slug, $public_id );

		return site_url( $url );
	}

	/**
	 * Sets public ID from ad ID
	 *
	 * @return string
	 */
	private function set_id(): string {
		$public_id = wp_generate_password( 48, false );
		$this->ad->set_prop( 'tracking.public-id', $public_id );
		$this->ad->save();

		return $public_id;
	}
}
