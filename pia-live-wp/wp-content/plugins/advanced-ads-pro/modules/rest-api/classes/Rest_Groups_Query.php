<?php // phpcs:ignoreFile

namespace Advanced_Ads_Pro\Rest_Api;

use WP_Term_Query;
use AdvancedAds\Constants;

/**
 * Extend \WP_Term_Query for REST API.
 */
class Rest_Groups_Query extends WP_Term_Query {
	/**
	 * The amount of terms found for the current request.
	 *
	 * @var int
	 */
	private $found_terms;

	/**
	 * The amount of max pages for the current request.
	 *
	 * @var int
	 */
	private $max_num_pages;

	/**
	 * Constructor.
	 * Parse user request parameters.
	 * Get the amout of total items and pages.
	 * Run actual query.
	 *
	 * @param array $query_params The current user request parameters.
	 */
	public function __construct( array $query_params ) {
		$query_params = array_merge(
			Rest_Query_Params_Helper::setup_query_params( $query_params ),
			[
				'taxonomy'     => Constants::TAXONOMY_GROUP,
				'hide_empty'   => false,
				'hierarchical' => false,
			]
		);

		$query_params['number'] = $query_params['posts_per_page'];
		if ( isset( $query_params['paged'] ) ) {
			$query_params['offset'] = ( $query_params['paged'] - 1 ) * $query_params['posts_per_page'];
		}

		parent::__construct();
		$this->found_terms = $this->get_count( $query_params );
		$this->query( $query_params );
		$this->max_num_pages = (int) ceil( $this->found_terms / $this->query_vars['number'] );
	}

	/**
	 * Getter for found_terms.
	 *
	 * @return int
	 */
	public function found_terms() {
		return $this->found_terms;
	}

	/**
	 * Getter for max_num_pages.
	 *
	 * @return int
	 */
	public function max_num_pages() {
		return $this->max_num_pages;
	}

	/**
	 * Remove all limits and run current query to get the amount of total rows.
	 *
	 * @param array $query_params The current request parameters.
	 *
	 * @return int
	 */
	public function get_count( array $query_params ) {
		$query_params['fields'] = 'count';
		$query_params['number'] = '';
		$query_params['offset'] = '';

		return (int) $this->query( $query_params );
	}

	/**
	 * Map array of group ids into array of \Advanced_Ads_Pro\Rest_Api\Group response arrays.
	 *
	 * @return array[]
	 */
	public function get_groups() {
		return array_map(
			function ( $group_id ) {
				return ( new Group( $group_id ) )->get_rest_response();
			},
			null !== $this->terms ? $this->terms : []
		);
	}
}
