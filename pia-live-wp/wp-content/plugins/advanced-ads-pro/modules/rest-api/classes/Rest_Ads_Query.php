<?php // phpcs:ignoreFile

namespace Advanced_Ads_Pro\Rest_Api;

use AdvancedAds\Constants;

/**
 * Extend the WP_Query to get ads suitable for REST API request.
 */
class Rest_Ads_Query extends \WP_Query {
	/**
	 * Parse the user supplied query params and merge with defaults.
	 *
	 * @param array $query_params The $_GET query parameters.
	 */
	public function __construct( array $query_params ) {
		parent::__construct( array_filter( array_merge(
			Rest_Query_Params_Helper::setup_query_params( $query_params ),
			[
				'post_type' => Constants::POST_TYPE_AD,
				'fields'    => 'ids',
			]
		) ) );

		$this->check_invalid_paging();
	}

	/**
	 * Check if the requested page offset exists.
	 *
	 * @return void
	 * @throws Rest_Exception Throw an exception if the provided page number is larger than the available pages.
	 */
	private function check_invalid_paging() {
		if ( $this->found_posts > 0 || $this->query_vars['paged'] === 1 ) {
			return;
		}
		// Out-of-bounds, run the query again without LIMIT for total count.
		$page = $this->query_vars['paged'];
		unset( $this->query['paged'] );
		$this->query( $this->query );
		if ( $page > $this->max_num_pages && $this->found_posts > 0 ) {
			throw new Rest_Exception( serialize( new \WP_Error(
				'rest_post_invalid_page_number',
				// phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- we're re-using a core translation.
				__( 'The page number requested is larger than the number of pages available.' ),
				[ 'status' => 400 ]
			) ) );
		}
	}

	/**
	 * Map array of ad ids into array of \Advanced_Ads_Pro\Rest_Api\Ad arrays.
	 *
	 * @return array[]
	 */
	public function get_ads() {
		return array_map(
			function ( $ad_id ) {
				return ( new Ad( $ad_id ) )->get_rest_response();
			},
			$this->posts
		);
	}
}
