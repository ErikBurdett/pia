<?php
/**
 * Add an element to fusion-builder.
 *
 * @package fusion-builder
 * @since 3.13.0
 */

if ( fusion_is_element_enabled( 'awb_dd' ) && ! class_exists( 'FusionSC_DynamicData' ) ) {
	/**
	 * Shortcode class.
	 *
	 * @since 3.13.0
	 */
	class FusionSC_DynamicData extends Fusion_Element {

		/**
		 * The internal container counter.
		 *
		 * @access private
		 * @since 3.13.0
		 * @var int
		 */
		private $counter = 1;

		/**
		 * Constructor.
		 *
		 * @access public
		 * @since 3.13.03.13.0
		 */
		public function __construct() {
			add_filter( 'fusion_attr_awb-dd', [ $this, 'attr' ] );
			add_shortcode( 'awb_dd', [ $this, 'render' ] );
		}

		/**
		 * Gets the default values.
		 *
		 * @static
		 * @since 3.13.0
		 * @return array
		 */
		public static function get_element_defaults() {
			return [
				'class'          => '',
				'hide_on_mobile' => fusion_builder_default_visibility( 'string' ),
				'id'             => '',
			];
		}

		/**
		 * Render the shortcode
		 *
		 * @access public
		 * @since 3.13.0
		 * @param  array  $args    Shortcode parameters.
		 * @param  string $content Content between shortcode.
		 * @return string          HTML output.
		 */
		public function render( $args, $content = '' ) {

			$this->args = array_merge( self::get_element_defaults(), $args );

			$html = '<span ' . FusionBuilder::attributes( 'awb-dd-params' ) . '>' . $this->parse_dynamic_object_string( $args ) . '</span>';
			$html = apply_filters( 'fusion_shortcode_content', $html, 'awb_dd', $args );
			$html = '<span ' . FusionBuilder::attributes( 'awb-dd' ) . '>' . $html . '</span>';

			$this->on_render();

			return apply_filters( 'fusion_element_dynamic_data_content', $html, $this->args );
		}

		/**
		 * Parses the dynamic data arguments into an object string.
		 *
		 * @access public
		 * @since 3.13.0
		 * @param array $args The dynamic data arguments.
		 * @return string The dynamic data object string, that can be parsed in the dynamic data class.
		 */
		public function parse_dynamic_object_string( $args ) {
			$non_dynamic_args = [ 'hide_on_mobile', 'class', 'id' ];

			$object_string = '{';

			foreach( $args as $arg => $value ) {
				if ( 'dynamic_data' === $arg ) {
					$object_string .= $value;
				} else if ( ! in_array( $arg, $non_dynamic_args ) ) {
					$object_string .= ',' . $arg . ':' . $value;
				}
			}

			$object_string .= '}';

			return $object_string;
		}

		/**
		 * Builds the attributes array.
		 *
		 * @access public
		 * @since 3.13.0
		 * @return array
		 */
		public function attr() {
			$attr = [
				'class' => 'awb-dd awb-dd-' . $this->counter,
				'style' => '',
			];

			$attr = fusion_builder_visibility_atts( $this->args['hide_on_mobile'], $attr );

			if ( $this->args['class'] ) {
				$attr['class'] .= ' ' . $this->args['class'];
			}

			if ( $this->args['id'] ) {
				$attr['id'] = $this->args['id'];
			}

			return $attr;
		}
	}

	new FusionSC_DynamicData();
}


/**
 * Map shortcode to Avada Builder
 *
 * @since 3.13.0
 */
function awb_element_dynamic_data() {
	$dynamic_params  = FusionBuilder()->dynamic_data->get_params();
	$dd_endpoints    = [];
	$group           = '';
	$params          = [];

	foreach( $dynamic_params as $id => $param ) {
		if ( isset( $param['group'] ) && $group !== $param['group'] ) {
			$dd_endpoints[ 'group__' . str_replace( ' ', '_', strtolower( $param['group'] ) ) ] = $param['group'];
			$group === $param['group'];
		}

		$dd_endpoints[ $id ] = isset( $param['label'] ) ? $param['label'] : ucwords (str_replace ('_', ' ', $id ) );

		if ( isset( $param['fields'] ) ) {
			foreach( $param['fields'] as $name => $option ) {
				$new_option = [
					'type'        => 'text' === $option['type'] ? 'textfield' : $option['type'],
					'heading'     => $option['heading'],
					'param_name'  => $id . '__' . $option['param_name'],
					'dependency'  => [
						[
							'element'  => 'dynamic_data',
							'value'    => $id,
							'operator' => '==',
						],
					],					
				];

				if ( isset( $option['description'] ) ) {
					$new_option['description'] = $option['description'];
				}				

				if ( isset( $option['default'] ) ) {
					$new_option['default'] = $option['default'];
				}

				if ( isset( $option['value'] ) ) {
					$new_option['value'] = $option['value'];
				}

				$params[] = $new_option;
			}
		}
	}

	$params['0'] = [
		'type'        => 'select',
		'heading'     => esc_html__( 'Dynamic Data', 'fusion-builder' ),
		'description' => esc_html__( 'Choose your dynamic data endpoint.', 'fusion-builder' ),
		'placeholder' => esc_html__( 'Dynamic Data', 'fusion-builder' ),
		'param_name'  => 'dynamic_data',
		'default'     => '',
		'value'       => $dd_endpoints,
	];

	$params[] = [
		'type'        => 'checkbox_button_set',
		'heading'     => esc_attr__( 'Element Visibility', 'fusion-builder' ),
		'param_name'  => 'hide_on_mobile',
		'value'       => fusion_builder_visibility_options( 'full' ),
		'default'     => fusion_builder_default_visibility( 'array' ),
		'description' => esc_attr__( 'Choose to show or hide the element on small, medium or large screens. You can choose more than one at a time.', 'fusion-builder' ),
	];
	$params[] = [
		'type'        => 'textfield',
		'heading'     => esc_attr__( 'CSS Class', 'fusion-builder' ),
		'param_name'  => 'class',
		'value'       => '',
		'description' => esc_attr__( 'Add a class to the wrapping HTML element.', 'fusion-builder' ),
	];
	$params[] = [
		'type'        => 'textfield',
		'heading'     => esc_attr__( 'CSS ID', 'fusion-builder' ),
		'param_name'  => 'id',
		'value'       => '',
		'description' => esc_attr__( 'Add an ID to the wrapping HTML element.', 'fusion-builder' ),
	];

	$params['fusion_conditional_render_placeholder'] = [];

	fusion_builder_map(
		fusion_builder_frontend_data(
			'FusionSC_DynamicData',
			[
				'name'                     => esc_html__( 'Dynamic Data', 'fusion-builder' ),
				'shortcode'                => 'awb_dd',
				'icon'                     => 'fusiona-dynamic-data',
				'generator_only'           => true,
				'help_url'                 => 'https://avada.com/documentation/how-to-use-dynamic-content-options-in-avada/',
				'params'                   => $params,			
			]
		)
	);
}
add_action( 'fusion_builder_wp_loaded', 'awb_element_dynamic_data' );
