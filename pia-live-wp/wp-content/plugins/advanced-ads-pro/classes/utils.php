<?php // phpcs:ignore WordPress.Files.FileName

use AdvancedAds\Framework\Utilities\Params;

/**
 * Utils class for Advanced Ads Pro.
 */
class Advanced_Ads_Pro_Utils {
	/**
	 * Generate unique wrapper id
	 *
	 * @return string
	 */
	public static function generate_wrapper_id() {
		static $count = 0;
		return wp_advads()->get_frontend_prefix() . ( ++$count ) . wp_rand();
	}

	/**
	 * Checks if a blog exists and is not marked as deleted.
	 *
	 * @link http://wordpress.stackexchange.com/q/138300/73
	 *
	 * @param int $blog_id Blog ID.
	 * @param int $site_id Site ID.
	 *
	 * @return bool
	 */
	public static function blog_exists( $blog_id, $site_id = 0 ) {
		global $wpdb;
		static $cache = [];

		$site_id = absint( $site_id );

		if ( 0 === $site_id ) {
			$site_id = get_current_site()->id;
		}

		if ( empty( $cache[ $site_id ] ) ) {
			// we do not test large sites.
			if ( wp_is_large_network() ) {
				return true;
			}

			$query  = $wpdb->prepare( "SELECT `blog_id` FROM $wpdb->blogs WHERE site_id = %d AND deleted = 0", $site_id );
			$result = $wpdb->get_col( $query ); // phpcs:ignore

			// Make sure the array is always filled with something.
			$cache[ $site_id ] = empty( $result ) ? [ 'checked' ] : $result;
		}

		return in_array( (string) $blog_id, $cache[ $site_id ], true );
	}

	/**
	 * Convert a value to non-negative integer.
	 *
	 * @param mixed $maybeint Data you wish to have converted to a non-negative integer.
	 * @param int   $min      A minimum.
	 * @param int   $max      A maximum.
	 *
	 * @return int A non-negative integer.
	 */
	public static function absint( $maybeint, $min = null, $max = null ) {
		$int = abs( (int) $maybeint );

		if ( null !== $min && $int < $min ) {
			return $min;
		}
		if ( null !== $max && $int > $max ) {
			return $max;
		}
		return $int;
	}

	/**
	 * Retrieve a post given a post ID
	 *
	 * Used for display conditions during `advads_ad_select` (ajax ads).
	 *
	 * @return array|WP_Post|null
	 */
	public static function get_post() {
		$post_object = get_post();

		if (
			! $post_object
			&& wp_doing_ajax()
			&& isset( $_REQUEST['action'], $_REQUEST['theId'], $_REQUEST['isSingular'] )
			&& Params::request( 'action' ) === 'advads_ad_select'
			&& Params::request( 'isSingular' )
		) {
			$post_object = get_post( Params::request( 'theId', 0, FILTER_VALIDATE_INT ) );
		}

		return $post_object;
	}
}
