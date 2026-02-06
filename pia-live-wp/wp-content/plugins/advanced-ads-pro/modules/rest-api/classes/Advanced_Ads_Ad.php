<?php // phpcs:ignoreFile

namespace Advanced_Ads_Pro\Rest_Api;

use AdvancedAds\Abstracts\Ad as AbstractsAd;

/**
 * REST API extension of the Ad class.
 */
class Ad {

	private $ad = null;

	/**
	 * Get the ad if ID is passed, otherwise the ad is new and empty.
	 *
	 * @throws Rest_Exception Throw an exception if the provided id is not an ad.
	 *
	 * @param Ad|WP_Post|int $ad Ad to init.
	 */
	public function __construct( $ad = 0 ) {
		$this->ad = wp_advads_get_ad( $ad );
		add_filter( 'advanced-ads-tracking-link-attributes', [ $this, 'filter_tracking_attributes' ], 10, 2 );
	}

	/**
	 * Return ad details for API response.
	 *
	 * @return array
	 */
	public function get_rest_response() {
		return [
			'ID'              => $this->ad->get_id(),
			'title'           => $this->ad->get_title(),
			'type'            => $this->ad->get_type(),
			'start_date'      => get_post_datetime( $this->ad->get_id() )->getTimestamp(),
			'expiration_date' => $this->ad->get_expiry_date(),
			'content'         => $this->prepare_rest_output(),
		];
	}

	/**
	 * Parse the ad content according to ad type, but without adding any wrappers.
	 *
	 * @return string
	 */
	private function prepare_rest_output() {
		$user_supplied_content = $this->ad->get_prop( 'change-ad.content', false );
		if ( $user_supplied_content ) {
			// output was provided by the user.
			return $user_supplied_content;
		}

		// load ad type specific content filter.
		$output = $this->ad->prepare_output();

		// Remove superfluous whitespace
		$output = str_replace( [ "\n", "\r", "\t" ], ' ', $output );
		$output = preg_replace( '/\s+/', ' ', $output );

		/**
		 * Allow filtering of the API ad markup.
		 *
		 * @var string $output The ad content.
		 * @var AD     $this   The current ad object.
		 */
		$output = (string) apply_filters( 'advanced-ads-rest-ad-content', $output, $this->ad );

		return $output;
	}

	/**
	 * If tracking is active, filter the attributes to remove tracking-specific frontend attributes.
	 *
	 * @param array $attributes Keys are attribute names, values their respective values.
	 * @param AbstractsAd    $ad         Ad instance.
	 *
	 * @return array
	 */
	public function filter_tracking_attributes( array $attributes, AbstractsAd $ad ) {
		if ( $this->ad->get_id() !== $ad->get_id() ) {
			return $attributes;
		}

		unset(
			$attributes['data-bid'],
			$attributes['data-id'],
			$attributes['class']
		);

		return $attributes;
	}
}
