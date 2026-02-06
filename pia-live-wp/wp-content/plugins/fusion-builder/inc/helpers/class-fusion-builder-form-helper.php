<?php
/**
 * Avada Builder Form Helper class.
 *
 * @package Avada-Builder
 * @since 3.1
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Avada Builder Form Helper class.
 *
 * @since 3.1
 */
class Fusion_Builder_Form_Helper {

	/**
	 * Class constructor.
	 *
	 * @since 3.1
	 * @access public
	 */
	public function __construct() {
	}

	/**
	 * Returns the array of forms with their id.
	 *
	 * @since 3.1
	 * @param string $type The type of data to fetch.
	 * @return array
	 */
	public static function fusion_form_creator_form_list( $type = 'titles' ) {
		global $post;

		$form_list = [];
		$args      = [
			'post_type'      => 'fusion_form',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		];

		$original_post = $post;
		$forms_query   = fusion_cached_query( $args );
		$forms         = $forms_query->posts;

		// Check if there are forms available.
		if ( is_array( $forms ) ) {
			foreach ( $forms as $form ) {
				$value            = 'permalinks' === $type ? get_the_permalink( $form->ID ) : get_the_title( $form->ID );
				$id               = $form->ID;
				$form_list[ $id ] = $value;
			}
		}

		wp_reset_postdata();
		$post = $original_post;

		return $form_list;
	}

	/**
	 * Retrieve the form post content and return shortcode content.
	 *
	 * @since 3.1
	 * @param integer $form_post_id Form post id to retrieve content from form CPT.
	 * @return string Form shortcode content from the post content.
	 */
	public static function fusion_get_form_post_content( $form_post_id ) {
		$shortcode_content = '';
		$args              = [];
		$form_post         = get_post( $form_post_id );

		if ( $form_post ) {
			$form_content = $form_post->post_content;
			if ( isset( $_GET['form_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$args['recaptcha'] = 'no';
			}

			if ( false !== strpos( $form_post->post_content, '[fusion_builder_form_step' ) ) {
				$steps        = preg_split( '/\[fusion_builder_form_step(.*?)\/\]/', $form_post->post_content );
				$form_content = '';

				// Clean out empty steps, so we know the length accurately.
				foreach ( $steps as $key => $step ) {
					if ( empty( $step ) ) {
						unset( $steps[ $key ] );
					}
				}

				// Loop through steps.
				$count = count( $steps );
				foreach ( array_values( $steps ) as $key => $step ) {
					$form_content .= '<div class="awb-form-step' . ( 0 === $key ? ' active' : '' ) . '" data-final="' . ( $key + 1 === $count ? 1 : 0 ) . '" data-step="' . ( $key + 1 ) . '">' . $step . '</div>';
				}
			}
			return [
				'args'    => $args,
				'content' => $form_content,
				'css'     => get_post_meta( $form_post_id, '_fusion_builder_custom_css', true ),
			];
		}

		return false;
	}

	/**
	 * Returns all countries with their country code.
	 *
	 * @since 3.1
	 * @param string $country_code Country code to get states for.
	 * @return array Countries with country code.
	 */
	public static function fusion_form_get_states_for_country( $country_code ) {
		$states = include FUSION_BUILDER_PLUGIN_DIR . 'inc/i18n/states.php';
		if ( isset( $states[ $country_code ] ) ) {
			return apply_filters( 'form_creator_country_state_' . strtolower( $country_code ), $states[ $country_code ] );
		} else {
			return [
				'none' => __( 'No States Found', 'fusion-builder' ),
			];
		}
	}

	/**
	 * Returns all states.
	 *
	 * @since 3.1
	 * @return array states.
	 */
	public static function fusion_form_get_all_states() {
		return include FUSION_BUILDER_PLUGIN_DIR . 'inc/i18n/states.php';
	}

	/**
	 * Check if the provided user role can view the form.
	 *
	 * @access public
	 * @since 3.1
	 * @param array $user_roles User roles to check.
	 * @return bool
	 */
	public static function user_can_see_fusion_form( $user_roles ) {
		// Get current logged in user.
		$current_user = wp_get_current_user();

		return (
			empty( $user_roles ) ||
			( is_array( $user_roles ) && ! empty( array_intersect( $user_roles, $current_user->roles ) ) )
		);
	}

	/**
	 * Returns all countris with their country code.
	 *
	 * @since 3.1
	 * @return array Countries with country code.
	 */
	public static function fusion_form_get_all_countries() {
		return apply_filters( 'fusion_form_countries', include FUSION_BUILDER_PLUGIN_DIR . 'inc/i18n/countries.php' );
	}

	/**
	 * Get fusion form post meta.
	 *
	 * @access public
	 * @since 3.1
	 * @param string $id post id.
	 * @return array
	 */
	public static function fusion_form_get_form_meta( $id ) {
		$form_meta = wp_parse_args(
			(array) fusion_data()->post_meta( $id )->get_all_meta(),
			[
				'form_type'                   => 'ajax',
				'form_actions'                => [],
				'method'                      => 'method',
				'tooltip_text_color'          => '#ffffff',
				'tooltip_background_color'    => '#333333',
				'field_margin'                => [
					'top'    => '15px',
					'bottom' => '15px',
				],
				'label_position'              => 'above',
				'form_input_height'           => '',
				'form_border_width'           => [
					'top'    => '',
					'right'  => '',
					'bottom' => '',
					'left'   => '',
				],
				'form_border_radius'          => '',
				'form_border_color'           => '',
				'form_bg_color'               => '',
				'form_text_color'             => '',
				'form_label_color'            => '',
				'form_placeholder_color'      => '',
				'member_only_form'            => 'no',
				'user_roles'                  => '',
				'recaptcha'                   => 'no',
				'privacy_store_ip_ua'         => 'no',
				'privacy_expiration_interval' => 48,
				'privacy_expiration_action'   => 'anonymize',
				'email'                       => get_option( 'admin_email' ),
				'email_from'                  => 'WordPress',
				'email_from_id'               => 'wordpress@' . preg_replace( '#^www\.#', '', wp_parse_url( network_home_url(), PHP_URL_HOST ) ),

				/* translators: The title. */
				'email_subject'               => sprintf( esc_html__( '%s - Form Submission Notification', 'fusion-builder' ), get_the_title( $id ) ),
				'email_subject_encode'        => 0,
				'nonce_method'                => 'ajax',
				'form_views'                  => fusion_library()->get_option( 'form_views' ),
				'form_views_counting'         => fusion_library()->get_option( 'form_views_counting' ),
			]
		);

		return $form_meta;
	}

	/**
	 * Sets the global $fusion_form var and returns it.
	 *
	 * @static
	 * @access public
	 * @since 3.1
	 * @param int $id The form ID.
	 * @return array
	 */
	public static function fusion_form_set_form_data( $id ) {
		global $fusion_form;

		$fusion_form = [
			'id'           => false,
			'form_meta'    => [],
			'field_labels' => [],
			'field_logics' => [],
			'field_types'  => [],
			'form_number'  => false,
			'text_config'  => [],
		];

		$id = apply_filters( 'wpml_object_id', $id, 'fusion_form', true );

		// If form post exists.
		if ( false !== get_post_status( $id ) ) {
			$fusion_form = [
				'id'           => $id,
				'form_meta'    => self::fusion_form_get_form_meta( $id ),
				'field_labels' => [],
				'field_logics' => [],
				'field_types'  => [],
				'form_number'  => $id,
				'text_config'  => [],
				'steps'        => [],
			];

			// Get the form step data.
			$form_post = get_post( $id );
			if ( $form_post ) {
				$first_form_step = strpos( $form_post->post_content, '[fusion_builder_form_step' );
				if ( false !== $first_form_step ) {
					$first_container = strpos( $form_post->post_content, '[fusion_builder_container' );
					$step_count      = 1;

					if ( $first_container < $first_form_step ) { // If we don't have a step before a container, then add an empty step.
						$fusion_form['steps'][1] = [
							'title' => '',
							'icon'  => '',
						];
						$step_count              = 2;
					}

					preg_match_all( '/\[fusion_builder_form_step(.*?)\/\]/', $form_post->post_content, $steps );
					if ( ! empty( $steps ) ) {
						foreach ( $steps[0] as $step ) {
							$step       = str_replace( '/]', ' /]', $step );
							$attributes = shortcode_parse_atts( $step );

							$fusion_form['steps'][ $step_count ] = [
								'title' => isset( $attributes['title'] ) ? $attributes['title'] : '',
								'icon'  => isset( $attributes['icon'] ) ? $attributes['icon'] : '',
							];
							$step_count++;
						}
					}
				}
			}
		}

		return $fusion_form;
	}

	/**
	 * Exception for Name <me@mail.com> from sanitize.
	 *
	 * @static
	 * @access public
	 * @since 3.6
	 * @param String $str String to sanitize.
	 * @return String
	 */
	public static function fusion_form_sanitize( $str ) {
		if ( is_object( $str ) || is_array( $str ) ) {
			return '';
		}

		$str = (string) $str;

		$filtered = wp_check_invalid_utf8( $str );

		if ( false !== strpos( $filtered, '<' ) ) {
			// check if it contains email.
			preg_match( '/<(.*?)>/', $filtered, $matches );
			$tag_content = isset( $matches[1] ) ? $matches[1] : '';
			if ( ! is_email( $tag_content ) ) {
				$filtered = sanitize_textarea_field( $filtered );
			}
		} else {
			$filtered = sanitize_textarea_field( $filtered );
		}

		return $filtered;
	}

	/**
	 * Convert filed names to labels.
	 *
	 * @static
	 * @access public
	 * @since 3.6
	 * @param String $str filed name.
	 * @return String
	 */
	public static function fusion_name_to_label( $str ) {
		if ( is_object( $str ) || is_array( $str ) ) {
			return '';
		}

		$str = (string) $str;

		$str = preg_replace( '/_|-/', ' ', $str );
		return ucwords( $str );
	}

	/**
	 * Get autocomplete params.
	 *
	 * @static
	 * @access public
	 * @since 3.12
	 * @param  array $args The placeholder arguments.
	 * @return array
	 */
	public static function get_autocomplete_params( $args ) {

		$params = [
			[
				'type'        => 'select',
				'heading'     => esc_attr__( 'Autocomplete Tokens', 'fusion-builder' ),
				/* translators: Autocomplete token link. */
				'description' => sprintf( __( 'Select an autocomplete detail token. Select custom to add your own token combination. For more info and examples, you can check %s.', 'fusion-builder' ), '<a href="https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/autocomplete#token_list_tokens" target="_blank">' . esc_attr__( 'MDN Web Docs', 'fusion-builder' ) . '</a>' ),
				'param_name'  => 'autocomplete',
				'value'       => [
					'on'                   => esc_attr__( 'On', 'fusion-builder' ),
					'off'                  => esc_attr__( 'Off', 'fusion-builder' ),
					'custom'               => esc_attr__( 'Custom', 'fusion-builder' ),
					'home'                 => esc_attr__( 'Home', 'fusion-builder' ),
					'work'                 => esc_attr__( 'Work', 'fusion-builder' ),
					'mobile'               => esc_attr__( 'Mobile', 'fusion-builder' ),
					'fax'                  => esc_attr__( 'Fax', 'fusion-builder' ),
					'page'                 => esc_attr__( 'Page', 'fusion-builder' ),
					'tel'                  => esc_attr__( 'Tel', 'fusion-builder' ),
					'tel-country-code'     => esc_attr__( 'Tel Country Code', 'fusion-builder' ),
					'tel-national'         => esc_attr__( 'Tel National', 'fusion-builder' ),
					'tel-area-code'        => esc_attr__( 'Tel Area Code', 'fusion-builder' ),
					'tel-local'            => esc_attr__( 'Tel Local', 'fusion-builder' ),
					'tel-extension'        => esc_attr__( 'Tel Extension', 'fusion-builder' ),
					'email'                => esc_attr__( 'Email', 'fusion-builder' ),
					'impp'                 => esc_attr__( 'Instant Messaging Protocol', 'fusion-builder' ),
					'name'                 => esc_attr__( 'Name', 'fusion-builder' ),
					'honorific-prefix'     => esc_attr__( 'Honorific Prefix', 'fusion-builder' ),
					'additional-name'      => esc_attr__( 'Middle Name', 'fusion-builder' ),
					'family-name'          => esc_attr__( 'Family Name', 'fusion-builder' ),
					'honorific-suffix'     => esc_attr__( 'Honorific Suffix', 'fusion-builder' ),
					'nickname'             => esc_attr__( 'Nickname', 'fusion-builder' ),
					'username'             => esc_attr__( 'Username', 'fusion-builder' ),
					'new-password'         => esc_attr__( 'New Password', 'fusion-builder' ),
					'current-password'     => esc_attr__( 'Current Password', 'fusion-builder' ),
					'one-time-code'        => esc_attr__( 'One Time Code', 'fusion-builder' ),
					'organization-title'   => esc_attr__( 'Organization Title', 'fusion-builder' ),
					'organization'         => esc_attr__( 'Organization', 'fusion-builder' ),
					'street-address'       => esc_attr__( 'Street Address', 'fusion-builder' ),
					'address-line1'        => esc_attr__( 'Address Line 1', 'fusion-builder' ),
					'address-line2'        => esc_attr__( 'Address Line 2', 'fusion-builder' ),
					'address-line3'        => esc_attr__( 'Address Line 3', 'fusion-builder' ),
					'address-level1'       => esc_attr__( 'Address Level 1', 'fusion-builder' ),
					'address-level2'       => esc_attr__( 'Address Level 2', 'fusion-builder' ),
					'address-level3'       => esc_attr__( 'Address Level 3', 'fusion-builder' ),
					'address-level4'       => esc_attr__( 'Address Level 4', 'fusion-builder' ),
					'country'              => esc_attr__( 'Country', 'fusion-builder' ),
					'country-name'         => esc_attr__( 'Country Name', 'fusion-builder' ),
					'postal-code'          => esc_attr__( 'Postal Code', 'fusion-builder' ),
					'cc-name'              => esc_attr__( 'CC Name', 'fusion-builder' ),
					'cc-given-name'        => esc_attr__( 'CC First Name', 'fusion-builder' ),
					'cc-additional-name'   => esc_attr__( 'CC Middle Name', 'fusion-builder' ),
					'cc-family-name'       => esc_attr__( 'CC Family Name', 'fusion-builder' ),
					'cc-number'            => esc_attr__( 'CC Number', 'fusion-builder' ),
					'cc-exp'               => esc_attr__( 'CC Expiration Date', 'fusion-builder' ),
					'cc-exp-month'         => esc_attr__( 'CC Expiration Month', 'fusion-builder' ),
					'cc-exp-year'          => esc_attr__( 'CC Expiration Year', 'fusion-builder' ),
					'cc-csc'               => esc_attr__( 'CC Security Code', 'fusion-builder' ),
					'cc-type'              => esc_attr__( 'CC Type', 'fusion-builder' ),
					'transaction-currency' => esc_attr__( 'Transaction Currency', 'fusion-builder' ),
					'transaction-amount'   => esc_attr__( 'Transaction Amount', 'fusion-builder' ),
					'language'             => esc_attr__( 'Language', 'fusion-builder' ),
					'bday'                 => esc_attr__( 'Birthday', 'fusion-builder' ),
					'bday-day'             => esc_attr__( 'Birthday Day', 'fusion-builder' ),
					'bday-month'           => esc_attr__( 'Birthday Month', 'fusion-builder' ),
					'bday-year'            => esc_attr__( 'Birthday Year', 'fusion-builder' ),
					'sex'                  => esc_attr__( 'Sex / Gender', 'fusion-builder' ),
					'url'                  => esc_attr__( 'URL', 'fusion-builder' ),
					'photo'                => esc_attr__( 'Photo', 'fusion-builder' ),
					'webauthn'             => esc_attr__( 'Web Authentication Passkey', 'fusion-builder' ),
				],
				'default'     => 'off',
			],
			[
				'type'        => 'textfield',
				'heading'     => esc_attr__( 'Autocomplete Custom Tokens', 'fusion-builder' ),
				'param_name'  => 'autocomplete_custom',
				'value'       => '',
				/* translators: Autocomplete token link. */
				'description' => sprintf( __( 'Enter your custom autocomplete tokens. For more info and examples, you can check %s.', 'fusion-builder' ), '<a href="https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/autocomplete#token_list_tokens" target="_blank">' . esc_attr__( 'MDN Web Docs', 'fusion-builder' ) . '</a>' ),
				'dependency'  => [
					[
						'element'  => 'autocomplete',
						'value'    => 'custom',
						'operator' => '==',
					],
				],
			],
		];

		return $params;
	}
}
