<?php // phpcs:ignoreFile

namespace Advanced_Ads_Pro\Rest_Api;

use WP_Error;
use AdvancedAds\Abstracts\Group as BaseGroup;

/**
 * REST API extension for the base Group.
 */
class Group extends BaseGroup {
	/**
	 * Get the group if ID is passed, otherwise the group is new and empty.
	 *
	 * @throws Rest_Exception Throw an exception if the provided id is not a group.
	 *
	 * @param Group|WP_Term|int $group Group to init.
	 */
	public function __construct( $group = 0 ) {
		parent::__construct( $group, [] );

		if ( 0 === $this->get_id() ) {
			throw new Rest_Exception(
				serialize(
					new WP_Error(
						'rest_post_invalid_id',
						__( 'Invalid group ID.', 'advanced-ads-pro' ),
						[ 'status' => 404 ]
					)
				)
			);
		}
	}

	/**
	 * Return group details for API response.
	 *
	 * @return array
	 */
	public function get_rest_response() {
		$ad_ids = $this->get_ordered_ad_ids();

		return [
			'ID'         => $this->get_id(),
			'name'       => $this->get_title(),
			'type'       => $this->get_type(),
			'ads'        => $ad_ids,
			'ad_weights' => $this->get_ad_weights(),
		];
	}
}
