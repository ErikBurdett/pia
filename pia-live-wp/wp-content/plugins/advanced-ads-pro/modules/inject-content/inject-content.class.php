<?php // phpcs:ignoreFile

use AdvancedAds\Constants;
use AdvancedAds\Abstracts\Ad;
use AdvancedAds\Abstracts\Group;
use AdvancedAds\Abstracts\Placement;
use AdvancedAds\Utilities\Conditional;

/**
 * Inject Content module
 */
class Advanced_Ads_Pro_Module_Inject_Content {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// TODO: load options.
		add_filter( 'the_content', [ $this, 'inject_content' ], 100 );
		// action after ad output is created; used for js injection.
		add_filter( 'advanced-ads-ad-output', [ $this, 'after_ad_output' ], 10, 2 );
		// action after group output is created; used for js injection.
		add_filter( 'advanced-ads-group-output', [ $this, 'after_group_output' ], 10, 2 );
		// check if content injection is limited for longer texts only.
		add_filter( 'advanced-ads-can-inject-into-content', [ $this, 'check_content_length' ], 10, 3 );
		// Allow to prevent injection inside `the_content`.
		add_action( 'advanced-ads-can-inject-into-content', [ $this, 'prevent_injection_the_content' ], 10, 3 );
		add_action( 'wp_footer', [ $this, 'inject_footer' ], 20 );
		add_action( 'the_post', [ $this, 'inject_loop_post' ], 20, 2 );
		// Add ads into AMP archive pages created by the AMP for WP plugin.
		add_action( 'ampforwp_between_loop', [ $this, 'inject_loop_post_amp_for_wp' ] );

		// Support custom hook for content injections.
		if ( defined( 'ADVANCED_ADS_PRO_CUSTOM_CONTENT_FILTER' ) ) {
			add_filter( ADVANCED_ADS_PRO_CUSTOM_CONTENT_FILTER, [ Advanced_Ads::get_instance(), 'inject_content' ] );
			add_filter( ADVANCED_ADS_PRO_CUSTOM_CONTENT_FILTER, [ $this, 'inject_content' ] );
		}

		add_filter( 'advanced-ads-cache-busting-item', [ $this, 'inject_js_before_cache_busting_output' ], 10, 2 );
		add_action( 'init', [ $this, 'add_skip_paragraph_filters' ], 30 );

		// Check if ads can be displayed by post type.
		add_filter( 'advanced-ads-can-display-ad', [ $this, 'can_display_by_post_type' ], 10, 2 );
		// Check if Verification code & Auto ads ads can be displayed by post type.
		add_filter( 'advanced-ads-can-display-ads-in-header', [ $this, 'can_display_in_header_by_post_type' ], 10 );
		add_action( 'advanced-ads-body-classes', [ $this, 'body_class' ] );

		add_filter( 'advanced-ads-set-wrapper', [ $this, 'add_ad_wrapper_id' ], 10, 2 );
		add_filter( 'advanced-ads-ad-health-nodes', [ $this, 'add_ad_health_nodes' ] );
	}

	/**
	 * Injected ad randomly into post content.
	 *
	 * @since 1.0.0
	 * @param string $content Post content.
	 */
	public function inject_content( $content = '' ) {
		$options = Advanced_Ads::get_instance()->options();

		// do not inject in content when on a BuddyPress profile upload page (avatar & cover image).
		if ( ( function_exists( 'bp_is_user_change_avatar' ) && bp_is_user_change_avatar() ) || ( function_exists( 'bp_is_user_change_cover_image' ) && bp_is_user_change_cover_image() ) ) {
			return $content;
		}

		if ( $this->has_many_the_content() ) {
			return $content;
		}

		// Check if ads are disabled in secondary queries.
		if ( ! empty( $options['disabled-ads']['secondary'] ) ) {
			if ( wp_doing_ajax() ) {
				// This function was called by ajax (in secondary query).
				return $content;
			}
			// get out of wp_router_page post type if ads are disabled in secondary queries.
			if ( 'wp_router_page' === get_post_type() ) {
				return $content;
			}
		}

		// No need to inject ads because all tags are stripped from excepts.
		if ( doing_filter( 'get_the_excerpt' ) ) {
			return $content;
		}

		// run only within the loop on single pages of public post types.
		$public_post_types = get_post_types( [ 'public' => true, 'publicly_queryable' => true ], 'names', 'or' );

		// make sure that no ad is injected into another ad
		if ( get_post_type() == Constants::POST_TYPE_AD ) {
			return $content;
		}

		// Do not inject on admin pages.
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $content;
		}

		$is_amp = Conditional::is_amp();

		// check if admin allows injection in all places.
		if ( ! isset( $options['content-injection-everywhere'] ) ) {
			// check if this is a singular page within the loop or an amp page.
			if ( ( ! is_singular( $public_post_types ) && ! is_feed() ) || ( ! $is_amp && ! in_the_loop() ) ) {
				return $content;
			}
		}

		$placements = wp_advads_get_placements();

		if ( ! apply_filters( 'advanced-ads-can-inject-into-content', true, $content, $placements ) ) {
			return $content;
		}

		if ( is_array( $placements ) ) {
			foreach ( $placements as $placement ) {
				if ( empty( $placement->get_item() ) ) {
				    continue;
				}

				if ( $placement->is_type( ['post_content_random', 'post_above_headline', 'post_content_middle'] ) ) {

					// Don’t inject above headline on non-singular pages.
					if ( $placement->is_type( 'post_above_headline' ) && ( ! is_singular( $public_post_types ) ||( $is_amp && ! $this->use_output_buffering() ) ) ) {
						continue;
					}

					// Check if injection is ok for a specific placement id.
					if ( ! apply_filters( 'advanced-ads-can-inject-into-content-' . $placement->get_id(), true, $content, $placement ) ) {
						continue;
					}

					$placement_data                      = $placement->get_data();
					$placement_data['placement']['type'] = $placement->get_type();

					switch ( $placement->get_type() ) {
						case 'post_above_headline':
							if ( ! $this->use_output_buffering() ) {
								$placement_data['lazy_load'] = 'disabled';
								$content                     .= get_the_placement( $placement->get_id(), '', $placement_data );
							}
							break;
						case 'post_content_middle' :
							$content = Advanced_Ads_In_Content_Injector::inject_in_content( $placement->get_id(), $placement_data, $content );
							break;
						case 'post_content_random' :
							if ( $this->content_random_use_js( $placement ) ) {
								$content .= get_the_placement( $placement->get_id(), '', $placement_data );
							} else {
								$content = Advanced_Ads_In_Content_Injector::inject_in_content( $placement->get_id(), $placement_data, $content );
							}
							break;
					}
				}
			}
		}

		return $content;
	}

	/**
	 * Inject custom position placements
	 *
	 * @since 1.1.2
	 */
	public function inject_footer() {
		if ( $this->use_output_buffering() ) {
			return;
		}

		$placements = wp_advads_get_placements();

		foreach ( $placements as $placement ) {
			if ( ! $placement->is_type( 'custom_position' ) || Conditional::is_amp() ) {
				continue;
			}
			$placement_options = $placement->get_data();
			if ( isset( $placement_options['lazy_load'] ) ) {
				$placement_options['lazy_load'] = 'disabled';
			}
			echo get_the_placement( $placement->get_id(), '', $placement_options );
		}
	}

	/**
	 * Add ad wrapper ID if there is none. Needed for random paragraph placement.
	 *
	 * @param array $wrapper the ad wrapper array.
	 * @param Ad    $ad      the ad.
	 *
	 * @return array
	 */
	public function add_ad_wrapper_id( $wrapper, $ad ) {
		$placement = $ad->get_root_placement();

		// Don't mess with anything but top level ad in an xpath based content placement
		if ( ! $ad->is_top_level() || ! $placement || ! $placement->is_type( [ 'post_content_random', 'post_above_headline', 'custom_position' ] ) ) {
			return $wrapper;
		}

		if ( empty( $wrapper['id'] ) ) {
			$wrapper['id'] = $ad->create_wrapper_id();
		}

		return $wrapper;
	}

	/**
	 * Inject ad output and js code.
	 *
	 * @param string          $content The ad content.
	 * @param Ad $ad      The ad object.
	 *
	 * @return string
	 */
	public function after_ad_output( $content, Ad $ad ) {
		$previous_method = $ad->get_prop( 'previous_method' );
		if ( null !== $previous_method && Constants::ENTITY_GROUP === $previous_method ) {
			return $content;
		}

		$wrapper = $ad->create_wrapper();

		if ( empty( $wrapper['id'] ) ) {
			$wrapper['id'] = $ad->create_wrapper_id();
		}

		if ( null === $ad->get_prop( 'cache_busting_elementid' ) ) {
			$content .= $this->get_output_js( $wrapper['id'], $ad );
		}

		return $content;
	}

	/**
	 * inject js code after group output
	 *
	 * @param string $output_string Final group output.
	 * @param Group  $group         Group instance.
	 */
	public function after_group_output( $output_string, Group $group ) {
		if ( $output_string ) {

			if ( ! $group->get_prop( 'cache_busting_elementid' ) ) {
				$wrapper_id = Advanced_Ads_Pro_Utils::generate_wrapper_id();

				if ( $js_output = $this->get_output_js( $wrapper_id, $group ) ) {
					$output_string = '<div id="' . $wrapper_id . '">' . $output_string . '</div>' . $js_output;
				}
			}
		}

		return $output_string;
	}

	/**
	 * Get js to append after ad/group output.
	 *
	 * @param string   $wrapper_id wrapper ID.
	 * @param Ad|Group $entity     the entity.
	 *
	 * @return string
	 */
	private function get_output_js( $wrapper_id, $entity ) {
		$content = '';
		// Do not inject js on AMP pages.
		if ( Conditional::is_amp() ) {
			return $content;
		}

		$ad_args = $entity->get_prop( 'ad_args' );

		// Group refresh: do not move if the top level wrapper was moved earlier.
		if ( is_a_group( $entity ) && $entity->get_prop( 'group_refresh' ) && ! $entity->get_prop( 'group_refresh.is_top_level' ) ) {
			return $content;
		}

		$parent = $entity->get_parent();

		if ( ! $parent ) {
			return $content;
		}

		// Move only the most outer group wrapper.
		if ( $parent && ! is_a_placement( $parent ) ) {
			return $content;
		}

		switch( $parent->get_type() ) {
			case 'post_content_random' :
				if ( ! $this->content_random_use_js( $parent ) ) {
					return '';
				}
				$paragraphs_selector = $this->get_paragraph_selector( $ad_args );
				$content .= 'var advads_content_p = jQuery("#'. $wrapper_id .'")' . $paragraphs_selector . ';'
							. 'var advads_content_random_p = advads_content_p.eq( Math.round(Math.random() * ( advads_content_p.length - 1) ) );'
							. 'if ( advads_content_random_p.length ) { advads.move("#'. $wrapper_id .'", advads_content_random_p, { method: "insertAfter" }); }';
				break;
			case 'post_above_headline':
				if ( $this->use_output_buffering() ) {
					return '';
				}
				$content .= 'advads.move("#'. $wrapper_id .'", "h1", { method: "insertBefore" });';
				break;
			case 'custom_position':
				if ( $this->use_output_buffering() ) {
					return '';
				}
				// By element Selector.
				$inject_by = $parent->get_prop( 'inject_by' );
				if ( ! $inject_by || 'pro_custom_element' === $inject_by ) {
					$target   = $parent->get_prop( 'pro_custom_element' ) ?? '';
					$position = $parent->get_prop( 'pro_custom_position' ) ?? 'insertBefore';
					// By HTML container.
				} else {
					$target   = $parent->get_prop( 'container_id' ) ?? '';
					$position = 'appendTo';
				}
				$options[] = 'method: "' . $position . '"';
				// check if can be moved into hidden elements
				if ( defined( 'ADVANCED_ADS_PRO_CUSTOM_POSITION_MOVE_INTO_HIDDEN' ) ) {
					$options[] = 'moveintohidden: "true"';
				}
				$content .= 'advads.move("#' . $wrapper_id . '", "' . $target . '", { ' . implode( ', ', $options ) . ' });';
				break;
		}

		if ( $content ) {
			if ( ! empty( $parent->get_prop( 'cache_busting_elementid' ) ) ) {
				// Document is ready. Do not use another 'ready' block so that the wrapper is moved before executing js in ad content.
				$content = '<script>' . $content . '</script>';
			} else  {
				$content = '<script>( window.advanced_ads_ready || jQuery( document ).ready ).call( null, function() {' . $content . '});</script>';
			}
		}

		return $content;
	}

	/**
	 * get paragraph selector for js depending on cache busting settings
	 *
	 * @since 1.2.3
	 *
	 * @return string $paragraph_selector
	 */
	private function get_paragraph_selector( $args ) {
		$plugin_options = Advanced_Ads::get_instance()->options();
		$content_injection_level_disabled = isset( $plugin_options['content-injection-level-disabled'] );

		/**
		 * find paragraphs
		 *  which are not within tables
		 *  which are not within blockquotes
		 *  which are not empty
		 *  which are not within an image caption
		 *
		 * depending on "Disable injection limitation" setting,
		 *  either inject into all p tags, including subordinated
		 *  or only direct and preceding siblings
		 */
		if ( $content_injection_level_disabled ) {
			$paragraphs_selector = '.parent().find("p:not(table p):not(blockquote p):not(div.wp-caption p)").filter(function() {return this.innerHTML.trim()!==""})';
		} else {
			// Do not use 'prevAll' because it returns elements in reverse order.
			$paragraphs_selector = '.parent().children("p:not(table p):not(blockquote p):not(div.wp-caption p)").filter(function() {return this.innerHTML.trim()!==""})';
		}

		return apply_filters( 'advanced-ads-pro-inject-content-selector', $paragraphs_selector );
	}

	/**
	 * Check content length for injecting ads into the post content
	 *
	 * @param bool   $inject     whether to inject or not
	 * @param string $content    post content
	 * @param array  $placements array with all placements
	 *
	 * @return bool true, if injection is ok
	 */
	public function check_content_length( $inject = true, $content = '', $placements = [] ) {
		if ( ! $inject ) {
			return false;
		}

		if ( defined( 'ADVADS_CURRENT_CONTENT_LENGTH' ) ) {
			return $inject;
		}

	    // content injection placements
	    $cj_placements = [ 'post_top', 'post_bottom', 'post_content', 'post_content_random', 'post_content_middle' ];

	    // find out of content injection placements are defined at all
	    $has_content_placements = false;
	    foreach( $placements as $placement_id => $placement ) {
		    if ( false !== $placement && $placement->is_type( $cj_placements ) ) {
			    $has_content_placements = true;

			    // register filter for placement specific length check
			    add_filter( 'advanced-ads-can-inject-into-content-' . $placement_id, [ $this, 'check_placement_minimum_length' ], 10, 3 );
			}
		}

		if ( $has_content_placements ) {
			// Remove all HTML tags and comments and count spaces in content.
			$length = (int) preg_match_all( '/\s+/', wp_strip_all_tags( $content ) );
			define( 'ADVADS_CURRENT_CONTENT_LENGTH', $length );
		}

	    return $inject;
	}

	/**
	 * Allow to prevent injections inside `the_content`.
	 *
	 * @param bool   $inject     Whether to inject or not
	 * @param string $content    Post content
	 * @param array  $placements Array with all placements
	 *
	 * @return bool true, if injection is ok
	 */
	public function prevent_injection_the_content( $inject = true, $content = '', $placements = [] ) {
		if ( ! $inject ) {
			return false;
		}

		global $post;
		if ( empty( $post->ID ) ) {
		    return true;
		}

		$post_ad_options = get_post_meta( $post->ID, '_advads_ad_settings', true );

		return empty( $post_ad_options['disable_the_content'] );
	}

	/**
	 * Check if placement should be displayed by content length setting of content injection placements.
	 *
	 * @param bool      $return    Whether to inject or not.
	 * @param string    $content   Post content.
	 * @param Placement $placement Placement instance.
	 *
	 * @return bool
	 */
	public function check_placement_minimum_length( $return, $content, $placement ) {
		if ( ! $return ) {
			return false;
		}

	    $minimum_length = $placement->get_prop( 'pro_minimum_length' );
		if ( ! $minimum_length ) {
		    return $return;
	    }

	    if (
			defined('ADVADS_CURRENT_CONTENT_LENGTH') &&
			ADVADS_CURRENT_CONTENT_LENGTH < absint( $minimum_length ) ) {
		    return false;
	    }

	    return $return;
	}

	/**
	 * echo ad before/after posts in loops on archive pages
	 *
	 * @since 1.2.1
	 * @param array $post post object
	 * @param WP_Query $wp_query query object
	 */
	public function inject_loop_post( $post, $wp_query = null ) {
		$is_ajax = wp_doing_ajax();

		if ( ! $wp_query instanceof WP_Query || is_feed() || ( is_admin() && ! $is_ajax ) ) {
			return;
		}

		$plugin_options = Advanced_Ads::get_instance()->options();
		// only inject on AJAX requests when Secondary Query option is enabled
		if ( ! empty( $plugin_options['disabled-ads']['secondary'] ) && $is_ajax ) {
			return;
		}

		if ( ! isset( $wp_query->current_post )) {
			return;
		};

		// don’t inject into main query on single pages.
		if ( $wp_query->is_main_query() && is_single() ) {
			return;
		}

		$curr_index = $wp_query->current_post + 1; // normalize index

		// 'wp_reset_postdata()' does 'the_post' action.
		// handle the situation when wp_reset_postdata() is used after secondary query inside main query.
		static $handled_indexes = [];
		if ( $wp_query->is_main_query() ) {
			if ( in_array( $curr_index, $handled_indexes ) ) {
				return;
			}
			$handled_indexes[] = $curr_index;
		}

		$placements = wp_advads_get_placements();

		if ( ! empty( $placements ) ) {
			foreach ( $placements as $_placement_id => $placement ) {
				if ( empty( $placement->get_item() ) ) {
					continue;
				}

				if ( $placement->is_type( 'archive_pages' ) ) {
					$options = $placement->get_data();

					if ( empty( $options['in_any_loop'] )
						 && ( $wp_query->is_singular() || ! $wp_query->in_the_loop || ! $wp_query->is_main_query() ) ) {
						continue;
					}

					// Check if the loop is outside wp_head, but only on non-AJAX calls.
					if ( ! is_admin() && ! did_action( 'wp_head' ) ) {
						continue;
					}

					$injection_index = ! empty ( $options['pro_archive_pages_index'] ) ? absint( $options['pro_archive_pages_index'] ) : 1;

					if ( $injection_index === $curr_index ) {
						// todo: leave a comment about the use of the next line. Might be needed to submit placement information to options.
						$options['placement']['type'] = $placement->get_type();
						echo get_the_placement( (int) $_placement_id, '', $options );
					}
				}
			}
		}
	}


	/**
	 * Insert an ad in the loop for archive pages created by AMP for WP (https://wordpress.org/plugins/accelerated-mobile-pages/)
	 *
	 * We can ommit the checks in inject_loop_post() here because the ampforwp_between_loop hook should provide the right position.
	 *
	 * @param int $count index of the current position in the loop.
	 */
	public function inject_loop_post_amp_for_wp( $count ) {
		$placements = wp_advads_get_placements_by_types( 'archive_pages' );

		foreach ( $placements as $id => $placement ) {
			if ( ! $placement->get_item() ) {
				continue;
			}

			$options = $placement->get_data();

			if ( isset( $options['pro_archive_pages_index'] ) ) {
				$ad_index = absint( $options['pro_archive_pages_index'] );
				// We need to reduce our index by one to match how AMP for WP counts the index.
				if ( ( $ad_index - 1 ) === $count ) {
					// Todo: leave a comment about the use of the next line. Might be needed to submit placement information to options.
					$options['placement']['type'] = $placement->get_type();
					echo get_the_placement( $id, '', $options ); // phpcs:ignore
				}
			}
		}
	}

	/**
	 * Find the calls to `the_content` inside functions hooked to `the_content`.
	 *
	 * @return bool
	 */
	public function has_many_the_content() {
		global $wp_current_filter;
		if ( count( array_keys( $wp_current_filter, 'the_content', true ) ) > 1 ) {
			// More then one `the_content` in the stack.
			return true;
		}
		return false;
	}

	/**
	 * Check whether to use JS to position the placement.
	 *
	 * @param Placement $placement Placement options.
	 *
	 * @return bool
	 */
	private function content_random_use_js( $placement ) {
		if ( Conditional::is_amp() ) {
			return false;
		}

		if ( ! Advanced_Ads_Pro_Module_Cache_Busting::is_enabled() ) {
			return false;
		}

		$cb = $placement->get_prop( 'cache-busting' );

		// Check if we did not switch to `off` from `auto` (off as fallback method).
		if ( 'off' === $cb && ! $placement->get_prop( 'cache-busting-orig' ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Check whether or not to use PHP to position placements.
	 *
	 * @return bool
	 */
	private function use_output_buffering() {
		return Advanced_Ads_Pro::get_instance()->get_options()['placement-positioning'] === 'php';
	}

	/**
	 * Inject a script to output before cache busting output.
	 * The script moves the empty wrapper because some ad networks do not allow to move ads inserted to the DOM.
	 *
	 * @param array $response Cache busting item.
	 * @param array $request  Request info.
	 *
	 * @return array $r Cache busting item.
	 */
	function inject_js_before_cache_busting_output( $response, $request ) {
		if ( ! isset( $request['method'] ) || 'placement' !== $request['method']
			|| empty( $request['args']['cache_busting_elementid'] ) ) {
			return $response;
		}

		$placement = wp_advads_get_placement( (int) ( $request['args']['previous_id'] ?? $request['id'] ) );

		$placement->set_prop_temp( 'ad_args', $request['args'] );

		$el_id = $request['args']['cache_busting_elementid'];
		$response['inject_before'][] = $this->get_output_js( $el_id, $placement->get_item_object() );
		return $response;
	}

	/**
	 * Add filters to skip paragraph.
	 */
	public function add_skip_paragraph_filters() {
		foreach ( wp_advads_get_placements() as $placement_id => $placement ) {
			if ( ! empty( $placement->get_prop( 'words_between_repeats' ) ) ) {
				add_filter( 'advanced-ads-can-inject-into-content-' . $placement_id, [ $this, 'maybe_skip_content_placement' ], 10, 3 );
			}
		}
	}

	/**
	 * Check if the "Before/After Content" placement has enough words before.
	 *
	 * @param bool      $return    Whether to inject or not.
	 * @param string    $content   Post content.
	 * @param Placement $placement Placement instance.
	 *
	 * @return bool
	 */
	public function maybe_skip_content_placement( $return, $content, $placement ) {
		if ( ! $return ) {
			return false;
		}

		if ( $placement->is_type( [ 'post_top', 'post_bottom' ] ) ) {
			return $return;
		}

		$words = $placement->get_prop( 'words_between_repeats' );
		if ( ! empty( $words ) ) {
			$options['words_between_repeats'] = absint( $words );

			$offset_shifter = Advanced_Ads_Pro_Offset_Shifter::from_html( $content, $options );

			return $placement->is_type( 'post_top' )
				? $offset_shifter->can_inject_before_content_placement()
				: $offset_shifter->can_inject_after_content_placement();
		}

		return $return;
	}

	/**
	 * Check if the ad should be displayed based on post type.
	 *
	 * @param bool            $can_display True if the ad should be displayed, false otherwise.
	 * @param Ad $ad Ad object.
	 * @return bool True if the ad should be displayed, false otherwise.
	 */
	public function can_display_by_post_type( $can_display, Ad $ad ) {
		if ( ! $can_display ) {
			return false;
		}

		$post_type = $ad->get_prop( 'ad_args.post.post_type' );

		if ( ! empty( $post_type )
			&& $this->post_type_disabled( $post_type ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Return true if the ad can be displayed in the header of the page depending on the current post type.
	 *
	 * @param bool $can_display if the ad can already be displayed.
	 *
	 * @return bool
	 */
	public function can_display_in_header_by_post_type( $can_display ) {
		if ( ! $can_display ) {
			return false;
		}


		$post_type = $this->get_current_post_type();
		if ( $this->post_type_disabled( $post_type ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get current post type.
	 *
	 * @return bool|string False on failure or post type on success.
	 */
	private function get_current_post_type() {
		global $wp_the_query, $post;
		// If currently on a single site, use the main query information just in case a custom query is broken.
		if ( isset( $wp_the_query->post->post_type ) && $wp_the_query->is_single() ) {
			return $wp_the_query->post->post_type;
		} elseif ( isset( $post->post_type ) ) {
			return $post->post_type;
		}
		return false;
	}

	/**
	 * Check if post type disabled.
	 *
	 * @param string $post_type Post type.
	 * @return bool
	 */
	private function post_type_disabled( $post_type ) {
		$options = Advanced_Ads_Pro::get_instance()->get_options();

		if ( ! empty( $options['general']['disable-by-post-types'] )
			&& is_array( $options['general']['disable-by-post-types'] )
			&& in_array( $post_type, $options['general']['disable-by-post-types'], true ) ) {
			return true;
		}

		return false;
	}


	/**
	 * Add classes to the `body` tag.
	 *
	 * @param string[] $aa_classes Array of existing class names.
	 * @return string[] $aa_classes Array of existing and new class names.
	 */
	public function body_class( $aa_classes ) {
		$post_type = $this->get_current_post_type();

		if ( $this->post_type_disabled( $post_type ) ) {
			$aa_classes[] = 'aa-disabled-post-type';
		}

		return $aa_classes;
	}

	/**
	 * List health notifications on the page in the admin-bar.
	 *
	 * @param arr $nodes Admin bar nodes.
	 *
	 * @return arr $nodes Admin bar nodes.
	 */
	public function add_ad_health_nodes( $nodes ) {
		if ( $this->post_type_disabled( $this->get_current_post_type() ) ) {
			$nodes[] = [
				'type' => 2,
				'data' => [
					'parent' => 'advanced_ads_ad_health',
					'id'     => 'advanced_ads_ad_health_no_post',
					'title'  => __( 'Ads are disabled for this Post Type', 'advanced-ads' ),
					'href'  => admin_url( 'admin.php?page=advanced-ads-settings' ),
					'meta'   => [
						'class' => 'advanced_ads_ad_health_warning',
						'target' => '_blank',
					],
				],
			];
		}
		return $nodes;
	}
}
