<?php
/**
 * Add an element to fusion-builder.
 *
 * @package fusion-builder
 * @since 3.13.3
 */

if ( fusion_is_element_enabled( 'fusion_form_turnstile' ) ) {

	if ( ! class_exists( 'FusionForm_Turnstile' ) ) {
		/**
		 * Shortcode class.
		 *
		 * @since 3.13.3
		 */
		class FusionForm_Turnstile extends Fusion_Form_Component {

			/**
			 * Array of forms that use Turnstile.
			 *
			 * @static
			 * @access private
			 * @since 3.13.3
			 * @var array
			 */
			private static $forms = [];

			/**
			 * Constructor.
			 *
			 * @access public
			 * @since 3.13.3
			 */
			public function __construct() {
				add_filter( 'fusion_attr_turnstile-shortcode', [ $this, 'attr' ] );

				parent::__construct( 'fusion_form_turnstile' );
			}

			/**
			 * Gets the default values.
			 *
			 * @static
			 * @access public
			 * @since 3.13.3
			 * @return array
			 */
			public static function get_element_defaults() {
				$fusion_settings = awb_get_fusion_settings();
				return [
					'appearance' => $fusion_settings->get( 'turnstile_appearance' ),
					'theme'      => $fusion_settings->get( 'turnstile_theme' ),
					'size'       => $fusion_settings->get( 'turnstile_size' ),
					'language'   => $fusion_settings->get( 'turnstile_language' ),
					'tab_index'  => '',
					'class'      => '',
					'id'         => '',
				];
			}

			/**
			 * Render form field html.
			 *
			 * @access public
			 * @since 3.13.3
			 * @param string $content The content.
			 * @return string
			 */
			public function render_input_field( $content ) {
				self::$forms[ $this->params['form_number'] ] = isset( self::$forms[ $this->params['form_number'] ] ) ? self::$forms[ $this->params['form_number'] ] + 1 : 1;
				$counter                                     = 1 < self::$forms[ $this->params['form_number'] ] ? $this->params['form_number'] . '-' . self::$forms[ $this->params['form_number'] ] : $this->params['form_number'];

				$html = '<div ' . FusionBuilder::attributes( 'turnstile-shortcode' ) . '></div>';

				if ( 1 === $this->counter ) {
					$this->enqueue_scripts();
				}

				$this->counter++;

				return $html;
			}

			/**
			* Sets the necessary scripts.
			*
			* @access public
			* @since 3.13.3
			* @return void
			*/
			public function enqueue_scripts() {
				if ( fusion_library()->get_option( 'turnstile_site_key' ) && fusion_library()->get_option( 'turnstile_secret_key' ) ) {
					$turnstile_api_url = 'https://challenges.cloudflare.com/turnstile/v0/api.js';

					wp_enqueue_script( 'cloudflare-turnstile-api', $turnstile_api_url, [], FUSION_BUILDER_VERSION, false );
				}
			}


			/**
			 * Builds the attributes array.
			 *
			 * @access public
			 * @since 3.13.3
			 * @return array
			 */
			public function attr() {
				$attr = [
					'class' => 'awb-forms-turnstile cf-turnstile',
				];

				$attr['data-response-field-name'] = 'cf-turnstile-response-' . $this->counter;
				$attr['data-sitekey']             = fusion_library()->get_option( 'turnstile_site_key' );
				$attr['data-appearance']          = $this->args['appearance'];
				$attr['data-theme']               = $this->args['theme'];
				$attr['data-size']                = $this->args['size'];
				$attr['data-language']            = $this->args['language'];

				if ( $this->args['class'] ) {
					$attr['class'] .= ' ' . $this->args['class'];
				}

				if ( $this->args['id'] ) {
					$attr['id'] = $this->args['id'];
				}

				return $attr;
			}

			/**
			 * Used to set any other variables for use on front-end editor template.
			 *
			 * @static
			 * @access public
			 * @since 3.13.3
			 * @return array
			 */
			public static function get_element_extras() {
				$fusion_settings = awb_get_fusion_settings();
				return [
					'turnstile_site_key'   => $fusion_settings->get( 'turnstile_site_key' ),
					'turnstile_secret_key' => $fusion_settings->get( 'turnstile_secret_key' ),
					'turnstile_appearance' => $fusion_settings->get( 'turnstile_appearance' ),
					'turnstile_theme'      => $fusion_settings->get( 'turnstile_theme' ),
					'turnstile_size'       => $fusion_settings->get( 'turnstile_size' ),
					'turnstile_language'   => $fusion_settings->get( 'turnstile_language' ),					
				];
			}

			/**
			 * Maps settings to extra variables.
			 *
			 * @static
			 * @access public
			 * @since 3.14.0
			 * @return array
			 */
			public static function settings_to_extras() {
				return [
					'turnstile_appearance' => 'turnstile_appearance',
					'turnstile_theme'      => 'turnstile_theme',
					'turnstile_size'       => 'turnstile_size',
					'turnstile_language'   => 'turnstile_language',
				];
			}

			/**
			 * Maps settings to param variables.
			 *
			 * @static
			 * @access public
			 * @since 3..13.4
			 * @return array
			 */
			public static function settings_to_params() {
				return [
					'turnstile_appearance' => 'appearance',
					'turnstile_theme'      => 'theme',
					'turnstile_size'       => 'size',
					'turnstile_language'   => 'language',
				];
			}			
		}
	}

	new FusionForm_Turnstile();
}

/**
 * Map shortcode to Fusion Builder
 *
 * @since 3.13.3
 */
function fusion_form_turnstile() {
	$info = [];
	if ( ! fusion_library()->get_option( 'turnstile_site_key' ) || ! fusion_library()->get_option( 'turnstile_secret_key' ) ) {
		if ( ( function_exists( 'fusion_is_preview_frame' ) && fusion_is_preview_frame() ) || ( function_exists( 'fusion_is_builder_frame' ) && fusion_is_builder_frame() ) ) {
			$to_link = '<span class="fusion-panel-shortcut" data-fusion-option="turnstile_site_key">' . esc_html__( 'Turnstile Section', 'fusion-builder' ) . '</span>';
		} else {
			$to_link = '<a href="' . esc_url( awb_get_fusion_settings()->get_setting_link( 'turnstile_site_key' ) ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Turnstile Section', 'fusion-builder' ) . '</a>';
		}
		
		$info = [
			'heading'     => esc_attr__( 'Set Up Needed Keys In Global Options', 'fusion-builder' ),
			'description' =>  sprintf( esc_html__( 'Please make sure to set up the needed keys in %s of Global Options.', 'fusion-builder' ), $to_link ),
			'param_name'  => 'turnstile_important_note_info',
			'type'        => 'custom',
		];
	}

	fusion_builder_map(
		fusion_builder_frontend_data(
			'FusionForm_Turnstile',
			[
				'name'           => esc_attr__( 'Turnstile Field', 'fusion-builder' ),
				'shortcode'      => 'fusion_form_turnstile',
				'icon'           => 'fusiona-cloudflare',
				'form_component' => true,
				'preview'        => FUSION_BUILDER_PLUGIN_DIR . 'inc/templates/previews/fusion-form-element-preview.php',
				'preview_id'     => 'fusion-builder-block-module-form-element-preview-template',
				'params'         => [
					$info,
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Appearance Mode', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose the Turnstile appearance mode. "Always" will display the widget on page load in any case, while "Interaction Only" will make it visible only when visitor interaction is required.', 'fusion-builder' ),
						'param_name'  => 'appearance',
						'default'     => '',
						'value'       => [
							''                 => esc_attr__( 'Default', 'fusion-builder' ),
							'always'           => esc_attr__( 'Always', 'fusion-builder' ),
							'interaction-only' => esc_attr__( 'Interaction Only', 'fusion-builder' ),
						],
					],					
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Color Scheme', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose the Turnstile color scheme.', 'fusion-builder' ),
						'param_name'  => 'theme',
						'default'     => '',
						'value'       => [
							''      => esc_attr__( 'Default', 'fusion-builder' ),
							'auto'  => esc_attr__( 'Auto', 'fusion-builder' ),
							'light' => esc_attr__( 'Light', 'fusion-builder' ),
							'dark'  => esc_attr__( 'Dark', 'fusion-builder' ),
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Widget Size', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose the Turnstile widget size.', 'fusion-builder' ),
						'param_name'  => 'size',
						'default'     => '',
						'value'       => [
							''         => esc_attr__( 'Default', 'fusion-builder' ),
							'normal'   => esc_attr__( 'Normal (300px)', 'fusion-builder' ),
							'flexible' => esc_attr__( 'Flexible (100%)', 'fusion-builder' ),
							'compact'  => esc_attr__( 'Compact (150px)', 'fusion-builder' ),
						],
					],
					[
						'type'        => 'select',
						'heading'     => esc_attr__( 'Language', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose the Turnstile widget language.', 'fusion-builder' ),
						'param_name'  => 'language',
						'default'     => '',
						'value'       => class_exists( 'AWB_Cloudflare_Turnstile' ) ? array_merge( [ '' => esc_attr__( 'Default', 'fusion-builder' ) ], AWB_Cloudflare_Turnstile::get_language_array() ) : [],
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Tab Index', 'fusion-builder' ),
						'param_name'  => 'tab_index',
						'value'       => '',
						'description' => esc_attr__( 'Tab index for the form field.', 'fusion-builder' ),
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'CSS Class', 'fusion-builder' ),
						'param_name'  => 'class',
						'value'       => '',
						'description' => esc_attr__( 'Add a class for the form field.', 'fusion-builder' ),
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'CSS ID', 'fusion-builder' ),
						'param_name'  => 'id',
						'value'       => '',
						'description' => esc_attr__( 'Add an ID for the form field.', 'fusion-builder' ),
					],
				],
			]
		)
	);
}
add_action( 'fusion_builder_before_init', 'fusion_form_turnstile' );
