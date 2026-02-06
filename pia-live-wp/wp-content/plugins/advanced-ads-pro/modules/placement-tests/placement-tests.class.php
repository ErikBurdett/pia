<?php // phpcs:ignoreFile
use AdvancedAds\Constants;
use AdvancedAds\Framework\Utilities\Params;
use AdvancedAds\Utilities\Conditional;

/**
 * Takes care of the placement tests.
 */
class Advanced_Ads_Pro_Placement_Tests {
	/**
	 * Instance of this class.
	 *
	 * @var      object
	 */
	private static $instance = null;

	/**
	 * @var array
	 */
	protected $placement_tests;

	/**
	 * delivered placement tests when cache-busting is not used
	 * contains pairs placement_id => test_id
	 *
	 * @var array
	 */
	public $delivered_tests = [];

	/**
	 * contains placement IDs, that can not be delivered using cache-busting
	 * they can not be randomly selected using JavaScript
	 *
	 * @var array
	 */
	public $no_cb_fallbacks = [];

	protected $random_placements;

	private function __construct() {
		if ( is_admin() ) {
			add_action( 'manage_posts_extra_tablenav', [ $this, 'render_tests' ] );
			add_filter( 'manage_' . Constants::POST_TYPE_PLACEMENT . '_posts_columns', [ $this, 'add_column' ], 100 );
			add_action( 'manage_' . Constants::POST_TYPE_PLACEMENT . '_posts_custom_column', [ $this, 'column_content' ], 10, 2 );
			add_action( 'wp_trash_post', [ $this, 'before_trashing_placement' ] );

			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
			add_action( 'admin_footer', [ $this, 'admin_footer' ] );
			add_action( 'wp_ajax_advads_new_placement_test', [ $this, 'ajax_save_new_test' ] );
			add_action( 'wp_ajax_advads_update_placement_tests', [ $this, 'ajax_update_tests' ] );
			add_action( 'advanced-ads-export', [ $this, 'export' ], 10, 2 );
			add_action( 'advanced-ads-import', [ $this, 'import' ], 10, 3 );
		}
		// check if placement can be displayed
		add_action( 'advanced-ads-can-display-placement', [ $this, 'placement_can_display' ], 13, 2 );
		// add ad select arguments: inject test_id
		add_filter( 'advanced-ads-ad-select-args', [ $this, 'additional_ad_select_args' ], 9, 3 );
		// send emails using CRON
		add_action( 'advanced-ads-placement-tests-emails', [ $this, 'send_emails' ] );
	}

	/**
	 * Before a post is trashed
	 *
	 * @param int $id the post ID.
	 *
	 * @return void
	 */
	public function before_trashing_placement( $id ) {
		$placement = wp_advads_get_placement( $id );

		if ( ! $placement ) {
			// Not a placement - abort.
			return;
		}

		$placement_tests = $this->get_placement_tests_array();

		if ( empty( $placement_tests ) ) {
			// No test found - abort.
			return;
		}

		$placement_slug = $placement->get_slug();
		$affected_placements = [];
		$affected_test_id = false;

		foreach ( $placement_tests as $test_id => $test ) {
			foreach ( $test['placements'] as $slug => $value ) {
				if ( $slug === $placement_slug ) {
					$affected_test_id = $test_id;
					break 2;
				}
			}
		}

		if ( ! $affected_test_id ) {
			// The placement that is about to be trashed is not part of any test.
			return;
		}

		foreach( $placement_tests[$affected_test_id]['placements'] as $slug => $weight ) {
			$affected_placements[] = $slug;
		}

		// Remove the "test_id" prop off all involved placements.
		foreach( $affected_placements as $slug ) {
			$placement = wp_advads_get_placement( $slug );
			if ( $placement ) {
				$placement->unset_prop( 'test_id' );
				$placement->save();
			}
		}

		// Delete the test.
		unset( $placement_tests[ $affected_test_id ] );
		$this->update_placement_tests_array( $placement_tests );
	}

	/**
	 * Update existing tests
	 *
	 * @return void
	 */
	public function ajax_update_tests() {
		if ( ! Conditional::user_can( 'advanced_ads_manage_placements' ) || ! wp_verify_nonce( Params::post( 'nonce', '' ), 'advads-placement-tests' ) ) {
			wp_send_json_error( 'Unauthorized', 401 );
		}

		$placement_tests = $this->get_placement_tests_array();
		$advads          = Params::post( 'advads', false, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		foreach ( $advads['placement_tests'] as $placement_test_id => $placement_test ) {
			if ( isset( $placement_tests[ $placement_test_id ] ) && is_array( $placement_tests[ $placement_test_id ] ) ) {
				// delete test
				if ( isset( $placement_test['delete'] ) ) {
					if ( isset( $placement_tests[ $placement_test_id ]['placements'] ) && is_array( $placement_tests[ $placement_test_id ]['placements'] ) ) {
						foreach ( $placement_tests[ $placement_test_id ]['placements'] as $placement_slug => $placement_name ) {
							// detach placements from this test
							$placement = wp_advads_get_placement( $placement_slug );
							$placement->set_prop( 'test_id', null );
							$placement->save();
						}
					}

					unset( $placement_tests[ $placement_test_id ] );
					continue;
				}

				$placement_tests[ $placement_test_id ]['expiry_date'] = $this->extract_expiry_date( $placement_test );
			}
		}

		$this->update_placement_tests_array( $placement_tests );
	}

	/**
	 * Save new test
	 *
	 * @return void
	 */
	public function ajax_save_new_test() {
		if ( ! Conditional::user_can( 'advanced_ads_manage_placements' ) || ! wp_verify_nonce( Params::post( 'nonce', '' ), 'advads-placement-tests' ) ) {
			wp_send_json_error( 'Unauthorized', 401 );
		}

		$placement_tests = $this->get_placement_tests_array();
		$new_test        = Params::post( 'candidates', false, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		// Sort by weights.
		arsort( $new_test );
		$new_placements = [];
		$test_id        = 'pt_' . md5( uniqid( time(), true ) );

		foreach ( $new_test as $slug => $placement_weight ) {
			$placement = wp_advads_get_placement( $slug );
			if ( $placement ) {
				$new_placements[ $slug ] = $placement_weight;
				$placement->set_prop( 'test_id', $test_id );
				$placement->save();
			}
		}

		$placement_tests[ $test_id ] = [
			'user_id'    => get_current_user_id(),
			'placements' => $new_placements,
		];

		$this->update_placement_tests_array( $placement_tests );
		wp_send_json_success();
	}

	/**
	 * Print data on admin page footer
	 *
	 * @return void
	 */
	public function admin_footer() {
		$screen = get_current_screen();
		if ( 'edit-' . Constants::POST_TYPE_PLACEMENT !== $screen->id || ! Conditional::user_can( 'advanced_ads_manage_placements' ) ) {
			return;
		}
		echo '<script type="text/html" id="placement-ajax-nonce">' . esc_html( wp_create_nonce( 'advads-placement-tests' ) ) . '</script>';
	}

	/**
	 * Enqueue scripts on the placement screen
	 *
	 * @param string $hooks current page hook.
	 *
	 * @return void
	 */
	public function enqueue_scripts( $hooks ) {
		global $post_type;

		if ( 'edit.php' !== $hooks || Constants::POST_TYPE_PLACEMENT !== $post_type ) {
			return;
		}

		wp_enqueue_script(
			'advanced-ads-pro/placement-test',
			plugin_dir_url( __FILE__ ) . 'assets/placement-tests.js',
			[ 'jquery' ],
			AAP_VERSION,
			true
		);
	}

	/**
	 * Add a custom column for test weight
	 *
	 * @param array $columns column slug.
	 *
	 * @return array
	 */
	public function add_column( array $columns ): array {
		$columns['placement_weight'] = __( 'Test weight', 'advanced-ads-pro' );

		return $columns;
	}

	/**
	 * Print test weight column content
	 *
	 * @param string $column
	 * @param int    $post_id
	 *
	 * @return void
	 */
	public function column_content( $column, $post_id ) {
		if ( 'placement_weight' !== $column ) {
			return;
		}

		$this->display_placement_weight_selector( wp_advads_get_placement( $post_id ) );
	}

	/**
	 * Prints the placement tests list
	 *
	 * @param string $which whether we're in the top or bottom nav section
	 *
	 * @return void
	 */
	public function render_tests( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		$screen = get_current_screen();

		if ( Constants::POST_TYPE_PLACEMENT !== $screen->post_type ) {
			return;
		}

		$placement_tests = $this->get_placement_tests_array();

		include plugin_dir_path( __FILE__ ) . '/views/placement-tests.php';
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return Advanced_Ads_Pro_Placement_Tests a single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * display weight selector in placement table
	 */
	public function display_placement_weight_selector( $placement ) {
		// TODO Some placement types might not support cache busting, hence will not work with placement test. A "placement type option" named 'placement-cache-busting' was used in the past
		// but I didn't find any current occurrence where it's `FALSE` anywhere.
		include plugin_dir_path( __FILE__ ) . '/views/setting_placement_test_weight.php';
	}

	/**
	 * add ad select arguments: inject test_id
	 *
	 * @param array $args
	 *
	 * @return array $args
	 */
	public function additional_ad_select_args( $args, $method = null, $id = null ) {
		if ( 'placement' === $method ) {
			$placement = wp_advads_get_placement( $id );
			if ( $placement && null !== $placement->get_prop( 'test_id' ) ) {
				$args['test_id'] = $placement->get_prop( 'test_id' );
			}
		}

		return $args;
	}

	/**
	 * check if placement can be displayed
	 *
	 * @param bool $return
	 * @param int  $placement_id placement id
	 *
	 * @return bool false, if
	 * - cache-busting is not used and the placement belongs to a test, and was not randomly selected by weight
	 * - 1 placement was already delivered when 'no cache-busting' fallback is used
	 */
	public function placement_can_display( $return, $placement_id = 0 ) {
		$placement = wp_advads_get_placement( $placement_id );

		if ( $placement->get_prop( 'test_id' ) ) {
			$pro_options           = Advanced_Ads_Pro::get_instance()->get_options();
			$cache_busting_enabled = ! empty( $pro_options['cache-busting']['enabled'] );

			$test_id = $placement->get_prop( 'test_id' );
			$cb_off  = ! $cache_busting_enabled || $placement->get_prop( 'cache-busting' ) === Advanced_Ads_Pro_Module_Cache_Busting::OPTION_OFF;

			if (
				( $cb_off && ! in_array( $placement->get_slug(), $this->get_random_placements() ) ) ||
				( in_array( $test_id, $this->delivered_tests ) && ! array_key_exists( $placement_id, $this->delivered_tests ) )
			) {
				return false;
			}
		}

		return $return;
	}

	/**
	 * update the array with placement tests
	 *
	 * @param array
	 */
	public function update_placement_tests_array( $placement_tests ) {
		if ( is_array( $placement_tests ) ) {
			$this->placement_tests = $placement_tests;
			update_option( 'advads-ads-placement-tests', $placement_tests );
		}
	}

	/**
	 * get the array with placement tests
	 *
	 * @return array
	 */
	public function get_placement_tests_array() {
		if ( ! isset( $this->placement_tests ) ) {
			$this->placement_tests = get_option( 'advads-ads-placement-tests', [] );

			// load default array if not saved yet
			if ( ! is_array( $this->placement_tests ) ) {
				$this->placement_tests = [];
			}
		}

		return $this->placement_tests;
	}

	/**
	 * get random placements from tests based on placement weight in a test (used without cache-busting)
	 *
	 * @return array
	 */
	public function get_random_placements() {
		if ( ! isset( $this->random_placements ) ) {
			$placement_tests         = $this->get_placement_tests_array();
			$this->random_placements = [];

			foreach ( $placement_tests as $placement_test_id => $placement_test ) {
				if ( isset( $placement_test['placements'] ) && is_array( $placement_test['placements'] ) ) {
					if ( $random_placement_id = $this->get_random_placement_from_test( $placement_test['placements'] ) ) {
						$this->random_placements[] = $random_placement_id;
					};
				}
			}
		}

		return $this->random_placements;
	}

	/**
	 * get random placement by placement weight
	 *
	 * @param array $placement_weights e.g. array(A => 2, B => 3, C => 5)
	 *
	 * @source applied with fix for order http://stackoverflow.com/a/11872928/904614
	 */
	private function get_random_placement_from_test( array $placement_weights ) {
		// placements might have a weight of zero (0); to avoid mt_rand fail assume that at least 1 is set.
		$max = array_sum( $placement_weights );
		if ( $max < 1 ) {
			return;
		}

		$rand = mt_rand( 1, $max );

		foreach ( $placement_weights as $placement_id => $_weight ) {
			$rand -= $_weight;
			if ( $rand <= 0 ) {
				return $placement_id;
			}
		}
	}

	/**
	 * get names of placements for the test
	 *
	 * @param array $placement_test
	 *
	 * @return array $placements_names
	 */
	public function get_placement_names( $placement_test ) {
		$placement_names = [];

		if ( ! isset( $placement_test['placements'] ) || ! is_array( $placement_test['placements'] ) ) {
			return [];
		}

		foreach ( $placement_test['placements'] as $placement_id => $placement_weight ) {
			$title = wp_advads_get_placement( $placement_id )->get_title();
			if ( ! empty ( $title ) ) {
				$placement_names[] = sprintf( '%s <em>(%d)</em>', $title, $placement_weight );
			}
		}

		return $placement_names;
	}

	/**
	 * return DateTime for timestamp or current time
	 *
	 * @return object DateTime
	 */
	public static function get_exp_time( $timestamp = null ) {
		$utc_ts    = $timestamp ?: time();
		$utc_time  = date_create( '@' . $utc_ts );
		$tz_option = get_option( 'timezone_string' );
		$exp_time  = clone $utc_time;

		if ( $tz_option ) {
			$exp_time->setTimezone( Advanced_Ads_Utils::get_wp_timezone() );
		} else {
			$off_time      = date_create( $utc_time->format( 'Y-m-d\TH:i:s' ), Advanced_Ads_Utils::get_wp_timezone() );
			$offset_in_sec = date_offset_get( $off_time );
			$exp_time      = date_create( '@' . ( $utc_ts + $offset_in_sec ) );
		}

		return $exp_time;
	}

	/**
	 * output expiry date form on placement page
	 */
	public function output_expiry_date_form( $slug, $timestamp = null ) {
		if ( method_exists( 'Advanced_Ads_Utils', 'get_timezone_name' ) && method_exists( 'Advanced_Ads_Utils', 'get_wp_timezone' ) ) {
			$enabled  = (bool) $timestamp;
			$exp_time = $this->get_exp_time( $timestamp );

			[ $curr_year, $curr_month, $curr_day, $curr_hour, $curr_minute ] = explode( '-', $exp_time->format( 'Y-m-d-H-i' ) );
			$TZ = Advanced_Ads_Utils::get_wp_timezone();

			include plugin_dir_path( __FILE__ ) . '/views/settings_test_expiry_date.php';
		}
	}

	/**
	 * Extract expire date from array ($_POST)
	 *
	 * @param array $test_data
	 *
	 * @return int Unix timestamp for the date, 0 otherwise
	 */
	public function extract_expiry_date( $test_data ): int {
		// prepare expiry date
		if ( isset( $test_data['expiry_date']['enabled'] ) ) {
			$year            = absint( $test_data['expiry_date']['year'] );
			$month           = absint( $test_data['expiry_date']['month'] );
			$day             = absint( $test_data['expiry_date']['day'] );
			$hour            = absint( $test_data['expiry_date']['hour'] );
			$minute          = absint( $test_data['expiry_date']['minute'] );
			$expiration_date = sprintf( "%04d-%02d-%02d %02d:%02d:%02d", $year, $month, $day, $hour, $minute, '00' );

			return wp_checkdate( $month, $day, $year, $expiration_date )
				? ( date_create( $expiration_date, Advanced_Ads_Utils::get_wp_timezone() ) )->getTimestamp()
				: 0;
		}

		return 0;
	}

	/**
	 * send email to user if at least 1 placement test is expired
	 */
	public function send_emails() {
		$placement_tests    = $this->get_placement_tests_array();
		$expiry_date_format = get_option( 'date_format' ) . ', ' . get_option( 'time_format' );
		$combined_tests     = [];

		$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

		$from_email = $this->get_default_sender_email();

		$from            = "From: \"$blogname\" <$from_email>";
		$message_headers = "$from\n"
						   . "Content-Type: text/html; charset=\"" . get_option( 'blog_charset' ) . "\"\n";
		$message_subject = _x( 'Expired placement tests', 'placement tests', 'advanced-ads-pro' );

		foreach ( $placement_tests as $placement_test_id => $placement_test ) {
			if (
				! empty( $placement_test['user_id'] ) &&
				! empty( $placement_test['placements'] ) && is_array( $placement_test['placements'] ) && count( $placement_test['placements'] ) > 1 &&
				! empty ( $placement_test['expiry_date'] )
			) {
				$expiry_date = (int) $placement_test['expiry_date'];
				if ( $expiry_date <= 0 || $expiry_date > time() ) {
					continue;
				}

				if ( ! ( $user = get_user_by( 'ID', $placement_test['user_id'] ) ) || ! is_email( $user->user_email ) ) {
					continue;
				}
				// combine tests, that belong to given user id
				$combined_tests [ $placement_test['user_id'] ][ $placement_test_id ] = $placement_test;
			}
		}

		foreach ( $combined_tests as $user_id => $tests ) {
			$message_body = '';

			foreach ( $tests as $test_id => $test ) {
				$expiry_date_formatted = $this->get_exp_time( $test['expiry_date'] );
				$expiry_date_formatted = $expiry_date_formatted->format( $expiry_date_format );

				$message_body .= implode( _x( ' vs ', 'placement tests', 'advanced-ads-pro' ), $this->get_placement_names( $test ) ) .
								 ' - ' . $expiry_date_formatted . "<br />";
			}

			$message_body .= '<br />'
							 . sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=advanced-ads-placements' ) ),
					_x( 'Placement page', 'placement tests', 'advanced-ads-pro' ) );

			$user = get_user_by( 'ID', $user_id );

			if ( wp_mail( $user->user_email, $message_subject, $message_body, $message_headers ) ) {
				foreach ( $tests as $test_id => $test ) {
					unset( $placement_tests[ $test_id ]['expiry_date'] );
				}
				// do not send this email after
				$this->update_placement_tests_array( $placement_tests );
			}
		}
	}

	/**
	 * Generate default sender email.
	 */
	private function get_default_sender_email() {
		if ( isset( $_SERVER['SERVER_NAME'] ) && $_SERVER['SERVER_NAME'] ) {
			return 'noreply@' . preg_replace( '#^www\.#', '', strtolower( $_SERVER['SERVER_NAME'] ) );
		} else {
			return get_bloginfo( 'admin_email' );
		}
	}

	/**
	 * return placements tests that can be randomly selected by JavaScript
	 *
	 * @return string
	 */
	public function get_placement_tests_js( $json_encode = true ) {
		// exclude tests, that was already delivered without cache-busting (cb: off, 'no cache-busting' fallback).
		$js_tests = array_diff_key( $this->get_placement_tests_array(), array_flip( $this->delivered_tests ) );
		$cb_off   = Advanced_Ads_Pro_Module_Cache_Busting::OPTION_OFF;

		// Exclude placements without cache-busting, so that JavaScript can not randomly select it based on weight.
		foreach ( $js_tests as &$js_test ) {
			if ( isset( $js_test['placements'] ) && is_array( $js_test['placements'] ) ) {

				foreach ( $js_test['placements'] as $placement_id => $placement_weight ) {
					$placement = wp_advads_get_placement( $placement_id );
					$cb        = $placement->get_prop( 'cache-busting' );
					if ( $cb_off === $cb || in_array( $placement->get_id(), $this->no_cb_fallbacks ) ) {
						unset( $js_test['placements'][ $placement->get_id() ] );
					}
				}
			}
		}

		return $json_encode ? wp_json_encode( $js_tests ) : $js_tests;
	}

	/**
	 * export tests
	 *
	 * @param $items  array requested items (ads, groups, etc.)
	 * @param $export array array to encode to XML
	 */
	public function export( $items, &$export ) {
		if ( in_array( 'placements', $items ) ) {
			$placement_tests = $this->get_placement_tests_array();

			foreach ( $placement_tests as &$placement_test ) {
				if ( empty( $placement_test['user_id'] )
					 || ! isset( $placement_test['placements'] )
					 || ! is_array( $placement_test['placements'] )
					 || count( $placement_test['placements'] ) < 2
				) {
					continue;
				}

				// prevent nodes starting with number
				$placement_array = [];
				foreach ( $placement_test['placements'] as $placement_id => $placement_weight ) {
					$placement_array[] = [ 'placement_id' => $placement_id, 'weight' => $placement_weight ];
				}
				$placement_test['placements'] = $placement_array;
			}

			if ( $placement_tests ) {
				$export['placement_tests'] = $placement_tests;
			}
		}
	}

	/**
	 * import tests
	 *
	 * @param $decoded       array decoded XML
	 * @param $imported_data array imported data mapped with previous data, e.g. ids [ $old_ad_id => $new_ad_id ]
	 * @param $messages      array status messages
	 */
	public function import( $decoded, $imported_data, $messages ) {
		if ( isset( $decoded['placement_tests'] ) && is_array( $decoded['placement_tests'] ) ) {
			$existing_tests = $updated_tests = $this->get_placement_tests_array();

			foreach ( $decoded['placement_tests'] as $placement_test_id => $placement_test ) {
				if ( empty( $placement_test['user_id'] )
					 || ! isset( $placement_test['placements'] )
					 || ! is_array( $placement_test['placements'] )
					 || count( $placement_test['placements'] ) < 2
				) {
					continue;
				}

				if ( isset( $existing_tests[ $placement_test_id ] ) ) {
					$count = 1;

					while ( isset( $existing_tests[ $placement_test_id . '_' . $count ] ) ) {
						$count++;
					}

					$new_test_id = $placement_test_id . '_' . $count;
				} else {
					$new_test_id = $placement_test_id;
				}

				$new_test = array_diff_key( $placement_test, [ 'placements' => true ] );

				foreach ( $placement_test['placements'] as $placements_of_test ) {
					if ( empty( $placements_of_test['placement_id'] ) || empty( $placements_of_test['weight'] ) ) {
						continue;
					}

					$placement_id       = $placements_of_test['placement_id'];
					$placement_key_uniq = $placement_id;

					if ( isset( $imported_data['placements'][ $placement_id ] ) && $imported_data['placements'][ $placement_id ] !== $placement_id ) {
						$placement_key_uniq = $imported_data['placements'][ $placement_id ];
					}

					$new_test['placements'][ $placement_key_uniq ] = $placements_of_test['weight'];
				}

				if ( count( $new_test['placements'] ) > 1 ) {
					$placement_names = $this->get_placement_names( $new_test );
					$placement_names = implode( _x( ' vs ', 'placement tests', 'advanced-ads-pro' ), $placement_names );
					/* translators: %s: placement test name */
					$messages[]      = [ 'update', sprintf( __( 'Placement test <em>%s</em> created', 'advanced-ads-pro' ), $placement_names ) ];

					$updated_tests[ $new_test_id ] = $new_test;
				}
			}

			if ( $existing_tests !== $updated_tests ) {
				$this->update_placement_tests_array( $updated_tests );
			}
		}
	}
}
