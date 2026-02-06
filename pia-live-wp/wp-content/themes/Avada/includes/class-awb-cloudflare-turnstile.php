<?php
/**
 * Handles GCloudflare Turnstile Avada.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://avada.com
 * @package    Avada
 * @subpackage Core
 * @since      7.14.0
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Implements the AWB Cloudflare Turnstile object.
 */
final class AWB_Cloudflare_Turnstile {

	/**
	 * The class instance.
	 *
	 * @since 7.14.0
	 * @static
	 * @access private
	 * @var null|object
	 */
	private static $instance = null;

	/**
	 * The class instance.
	 *
	 * @since 7.14.0
	 * @access private
	 * @var int
	 */
	private $counter = 0;

	/**
	 * The class constructor.
	 *
	 * @since 7.14.0
	 * @access public
	 */
	public function __construct() {
		$this->counter = 1;

		if ( ! is_user_logged_in() && fusion_library()->get_option( 'turnstile_comment_form' ) ) {
			add_action( 'comment_form_after_fields', [ $this, 'render_comment_form_turnstile' ] );

			add_action( 'pre_comment_on_post', [ $this, 'check_turnstile_comment_form' ] );
		}
	}

	/**
	 * Render Turnstile HTML on comment forms.
	 *
	 * @access public
	 * @since 7.14.0
	 * @return void
	 */
	public function render_comment_form_turnstile() {
		$attr = [
			'class'                    => 'awb-commentform-turnstile cf-turnstile',
			'data-response-field-name' => 'cf-turnstile-response-' . $this->counter,
			'data-sitekey'             => fusion_library()->get_option( 'turnstile_site_key' ),
			'data-appearance'          => fusion_library()->get_option( 'turnstile_appearance' ),
			'data-theme'               => fusion_library()->get_option( 'turnstile_theme' ),
			'data-size'                => fusion_library()->get_option( 'turnstile_size' ),
			'data-language'            => fusion_library()->get_option( 'turnstile_language' ),
		];
		?>
		<div
			<?php foreach ( $attr as $key => $value ) {
				echo esc_html( $key ) . '="' . esc_attr( $value ) . '" ';
			} ?>
		></div>

		<?php
		if ( 1 === $this->counter ) {
			$this->enqueue_scripts();
		}

		$this->counter++;
	}

	/**
	* Sets the necessary scripts.
	*
	* @access public
	* @since 7.11.6
	* @return void
	*/
	public function enqueue_scripts() {
		if ( fusion_library()->get_option( 'turnstile_site_key' ) && fusion_library()->get_option( 'turnstile_secret_key' ) ) {
			$turnstile_api_url = 'https://challenges.cloudflare.com/turnstile/v0/api.js';

			wp_enqueue_script( 'cloudflare-turnstile-api', $turnstile_api_url, [], FUSION_BUILDER_VERSION, false );
		}
	}

	/**
	 * Check reCAPTCHA on comment forms.
	 *
	 * @since 7.14.0
	 * @access private
	 * @param string $post_id current post id.
	 * @return void
	 */
	public function check_turnstile_comment_form( $post_id ) {
		if ( empty( $_POST['cf-turnstile-response'] ) ) {
			wp_safe_redirect(
				add_query_arg(
					[
						'type'            => 'error',
						'turnstile_error' => __( 'Sorry, Turnstile could not verify that you are a human. Please try again.', 'Avada' ),
					],
					esc_url( get_permalink( $post_id ) )
				)
			);
			exit;
		}

		if ( ! empty( $_POST['cf-turnstile-response'] ) ) {
			$response = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', [
				'body' => [
					'secret'   => fusion_library()->get_option( 'turnstile_secret_key' ),
					'response' => sanitize_text_field( $_POST['cf-turnstile-response'] ),
					'remoteip' => $_SERVER['REMOTE_ADDR'],
				],
			] );

			if ( is_wp_error( $response ) ) {
				wp_safe_redirect(
					add_query_arg(
						[
							'type'            => 'error',
							'turnstile_error' =>  __( 'The Cloudflare server did not respond.', 'Avada' ),
						],
						esc_url( get_permalink( $post_id ) )
					)
				);
				exit;
			}

			$result = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( empty( $result['success'] ) ) {
				wp_safe_redirect(
					add_query_arg(
						[
							'type'            => 'error',
							'turnstile_error' =>  __( 'The token verification failed.', 'Avada' ),
						],
						esc_url( get_permalink( $post_id ) )
					)
				);
				exit;
			}
		}
	}

	/**
	 * Returns the available languages.
	 *
	 * @since 7.14.0
	 * @access public
	 * @return array
	 */
	public static function get_language_array() {
		return [
			'auto'   => esc_html__( 'Auto (User browser language)', 'fusion-builder' ),
			'ar-eg'  => esc_html__( 'Arabic (Egypt)', 'fusion-builder' ),
			'bg-bg'  => esc_html__( 'Bulgarian (Bulgaria)', 'fusion-builder' ),
			'zh-cn'  => esc_html__( 'Chinese (Simplified, China)', 'fusion-builder' ),
			'zh-tw'  => esc_html__( 'Chinese (Traditional, Taiwan)', 'fusion-builder' ),
			'hr-hr'  => esc_html__( 'Croatian (Croatia)', 'fusion-builder' ),
			'cs-cz'  => esc_html__( 'Czech (Czech Republic)', 'fusion-builder' ),
			'da-dk'  => esc_html__( 'Danish (Denmark)', 'fusion-builder' ),
			'nl-nl'  => esc_html__( 'Dutch (Netherlands)', 'fusion-builder' ),
			'en-us'  => esc_html__( 'English (United States)', 'fusion-builder' ),
			'fa-ir'  => esc_html__( 'Farsi (Iran)', 'fusion-builder' ),
			'fi-fi'  => esc_html__( 'Finnish (Finland)', 'fusion-builder' ),
			'fr-fr'  => esc_html__( 'French (France)', 'fusion-builder' ),
			'de-de'  => esc_html__( 'German (Germany)', 'fusion-builder' ),
			'el-gr'  => esc_html__( 'Greek (Greece)', 'fusion-builder' ),
			'he-il'  => esc_html__( 'Hebrew (Israel)', 'fusion-builder' ),
			'hi-in'  => esc_html__( 'Hindi (India)', 'fusion-builder' ),
			'hu-hu'  => esc_html__( 'Hungarian (Hungary)', 'fusion-builder' ),
			'id-id'  => esc_html__( 'Indonesian (Indonesia)', 'fusion-builder' ),
			'it-it'  => esc_html__( 'Italian (Italy)', 'fusion-builder' ),
			'ja-jp'  => esc_html__( 'Japanese (Japan)', 'fusion-builder' ),
			'tlh'    => esc_html__( 'Klingon (Qo’noS)', 'fusion-builder' ),
			'ko-kr'  => esc_html__( 'Korean (Korea)', 'fusion-builder' ),
			'lt-lt'  => esc_html__( 'Lithuanian (Lithuania)', 'fusion-builder' ),
			'ms-my'  => esc_html__( 'Malay (Malaysia)', 'fusion-builder' ),
			'nb-no'  => esc_html__( 'Norwegian Bokmål (Norway)', 'fusion-builder' ),
			'pl-pl'  => esc_html__( 'Polish (Poland)', 'fusion-builder' ),
			'pt-br'  => esc_html__( 'Portuguese (Brazil)', 'fusion-builder' ),
			'ro-ro'  => esc_html__( 'Romanian (Romania)', 'fusion-builder' ),
			'ru-ru'  => esc_html__( 'Russian (Russia)', 'fusion-builder' ),
			'sr-ba'  => esc_html__( 'Serbian (Bosnia and Herzegovina)', 'fusion-builder' ),
			'sk-sk'  => esc_html__( 'Slovak (Slovakia)', 'fusion-builder' ),
			'sl-si'  => esc_html__( 'Slovenian (Slovenia)', 'fusion-builder' ),
			'es-es'  => esc_html__( 'Spanish (Spain)', 'fusion-builder' ),
			'sv-se'  => esc_html__( 'Swedish (Sweden)', 'fusion-builder' ),
			'tl-ph'  => esc_html__( 'Tagalog (Philippines)', 'fusion-builder' ),
			'th-th'  => esc_html__( 'Thai (Thailand)', 'fusion-builder' ),
			'tr-tr'  => esc_html__( 'Turkish (Turkey)', 'fusion-builder' ),
			'uk-ua'  => esc_html__( 'Ukrainian (Ukraine)', 'fusion-builder' ),
			'vi-vn'  => esc_html__( 'Vietnamese (Vietnam)', 'fusion-builder' ),
		];
	}

	/**
	 * Returns a single instance of the object (singleton).
	 *
	 * @since 7.14.0
	 * @access public
	 * @return object
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new AWB_Cloudflare_Turnstile();
		}
		return self::$instance;
	}
}
