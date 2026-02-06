<?php
/**
 * Add an element to fusion-builder.
 *
 * @package fusion-builder
 * @since 1.0
 */

if ( ! class_exists( 'FusionSC_RowInner' ) ) {
	/**
	 * Shortcode class.
	 *
	 * @since 3.0
	 */
	class FusionSC_RowInner extends Fusion_Row_Element {

		/**
		 * Constructor.
		 *
		 * @access public
		 * @since 3.0
		 */
		public function __construct() {
			$shortcode         = 'fusion_builder_row_inner';
			$shortcode_attr_id = 'row-inner';
			$classname         = 'fusion-builder-row-inner fusion-row';
			$content_filter    = 'fusion_builder_row_inner';
			parent::__construct( $shortcode, $shortcode_attr_id, $classname, $content_filter );
		}

		/**
		 * Gets the default values.
		 *
		 * @static
		 * @access public
		 * @since 3.14.0
		 * @return array
		 */
		public static function get_element_defaults() {
			return [
				'id'                   => '',
				'class'                => '',
				'min_height'           => '',
				'min_height_medium'    => '',
				'min_height_small'     => '',
				'max_height'           => '',
				'max_height_medium'    => '',
				'max_height_small'     => '',
				'align_content'        => '',
				'flex_align_items'     => '',
				'flex_justify_content' => '',
				'flex_wrap'            => '',
				'flex_wrap_medium'     => '',
				'flex_wrap_small'      => '',
				'overflow'             => '',
			];
		}

		/**
		 * Get the styling vars.
		 *
		 * @since 3.14.0
		 * @return string
		 */
		public function get_style_vars() {
			$css_vars = [
				'min_height',
				'min_height_medium',
				'min_height_small',
				'max_height',
				'max_height_medium',
				'max_height_small',
				'flex_wrap',
				'flex_wrap_medium',
				'flex_wrap_small',
				'overflow',
			];

			return $this->get_css_vars_for_options( $css_vars );
		}		
	}
}

new FusionSC_RowInner();

/**
 * Map Row shortcode to Avada Builder
 */
function fusion_element_row_inner() {
	fusion_builder_map(
		fusion_builder_frontend_data(
			'FusionSC_Row',
			[
				'name'              => esc_attr__( 'Nested Columns', 'fusion-builder' ),
				'shortcode'         => 'fusion_builder_row_inner',
				'hide_from_builder' => true,
				'params'            => [
					[
						'type'        => 'info',
						'content'     => esc_attr__( 'When using "default" values for the flex options here, that means they will inherit from the parent container.', 'fusion-builder' ),
						'param_name'  => 'row_info',
						'group'       => esc_attr__( 'General', 'fusion-builder' ),
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Row Minimum Height', 'fusion-builder' ),
						'description' => esc_attr__( 'Set a minimum height for the Nested Columns row. Set to 100vh to have it full viewport height. Leave empty or set to "auto" for automatic sizing.', 'fusion-builder' ),
						'param_name'  => 'min_height',
						'value'       => [
							'no'  => esc_attr__( 'Auto', 'fusion-builder' ),
							'yes' => esc_attr__( 'Full Height', 'fusion-builder' ),
							'min' => esc_attr__( 'Minimum Height', 'fusion-builder' ),
						],
						'group'       => esc_attr__( 'General', 'fusion-builder' ),
						'default'     => 'no',
						'responsive'  => [
							'state'         => 'large',
						],
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Row Maximum Height', 'fusion-builder' ),
						'description' => esc_attr__( 'Set a maximum height for the Nested Columns row. Leave empty or set to "none" for automatic sizing.', 'fusion-builder' ),
						'param_name'  => 'max_height',
						'value'       => '',
						'group'       => esc_attr__( 'General', 'fusion-builder' ),
						'responsive'  => [
							'state' => 'large',
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Column Row Alignment', 'fusion-builder' ),
						'description' => __( 'Defines how column rows should be aligned vertically within the Nested Columns row. <strong>IMPORTANT:</strong> These settings will only take full effect when multiple column rows are present.', 'fusion-builder' ),
						'param_name'  => 'align_content',
						'default'     => '',
						'group'       => esc_attr__( 'General', 'fusion-builder' ),
						'value'       => [
							''              => esc_attr__( 'Default', 'fusion-builder' ),
							'stretch'       => esc_attr__( 'Stretch', 'fusion-builder' ),
							'flex-start'    => esc_attr__( 'Flex Start', 'fusion-builder' ),
							'center'        => esc_attr__( 'Center', 'fusion-builder' ),
							'flex-end'      => esc_attr__( 'Flex End', 'fusion-builder' ),
							'space-between' => esc_attr__( 'Space Between', 'fusion-builder' ),
							'space-around'  => esc_attr__( 'Space Around', 'fusion-builder' ),
							'space-evenly'  => esc_attr__( 'Space Evenly', 'fusion-builder' ),
						],
						'icons'       => [
							''              => '<span class="fusiona-cog"></span>',
							'stretch'       => '<span class="fusiona-stretch"></span>',
							'flex-start'    => '<span class="fusiona-align-top-vert"></span>',
							'center'        => '<span class="fusiona-align-center-vert"></span>',
							'flex-end'      => '<span class="fusiona-align-bottom-vert"></span>',
							'space-between' => '<span class="fusiona-space-between"></span>',
							'space-around'  => '<span class="fusiona-space-around"></span>',
							'space-evenly'  => '<span class="fusiona-space-evenly"></span>',
						],
						'grid_layout' => true,
						'back_icons'  => true,
						'dependency'  => [
							[
								'element'  => 'row_height',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Column Alignment', 'fusion-builder' ),
						'description' => esc_attr__( 'Select how you want columns to align within rows.', 'fusion-builder' ),
						'param_name'  => 'flex_align_items',
						'back_icons'  => true,
						'grid_layout' => true,
						'value'       => [
							''           => esc_attr__( 'Default', 'fusion-builder' ),
							'flex-start' => esc_attr__( 'Flex Start', 'fusion-builder' ),
							'center'     => esc_attr__( 'Center', 'fusion-builder' ),
							'flex-end'   => esc_attr__( 'Flex End', 'fusion-builder' ),
							'stretch'    => esc_attr__( 'Stretch', 'fusion-builder' ),
						],
						'icons'       => [
							''           => '<span class="fusiona-cog"></span>',
							'flex-start' => '<span class="fusiona-align-top-columns"></span>',
							'center'     => '<span class="fusiona-align-center-columns"></span>',
							'flex-end'   => '<span class="fusiona-align-bottom-columns"></span>',
							'stretch'    => '<span class="fusiona-full-height"></span>',
						],
						'default'     => '',
						'group'       => esc_attr__( 'General', 'fusion-builder' ),
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_html__( 'Column Justification', 'fusion-builder' ),
						'description' => esc_html__( 'Select how the columns will be justified horizontally.', 'fusion-builder' ),
						'param_name'  => 'flex_justify_content',
						'default'     => '',
						'grid_layout' => true,
						'back_icons'  => true,
						'icons'       => [
							''              => '<span class="fusiona-cog"></span>',
							'flex-start'    => '<span class="fusiona-horizontal-flex-start"></span>',
							'center'        => '<span class="fusiona-horizontal-flex-center"></span>',
							'flex-end'      => '<span class="fusiona-horizontal-flex-end"></span>',
							'space-between' => '<span class="fusiona-horizontal-space-between"></span>',
							'space-around'  => '<span class="fusiona-horizontal-space-around"></span>',
							'space-evenly'  => '<span class="fusiona-horizontal-space-evenly"></span>',
						],
						'value'       => [
							''              => esc_attr__( 'Default', 'fusion-builder' ),
							'flex-start'    => esc_html__( 'Flex Start', 'fusion-builder' ),
							'center'        => esc_html__( 'Center', 'fusion-builder' ),
							'flex-end'      => esc_html__( 'Flex End', 'fusion-builder' ),
							'space-between' => esc_html__( 'Space Between', 'fusion-builder' ),
							'space-around'  => esc_html__( 'Space Around', 'fusion-builder' ),
							'space-evenly'  => esc_html__( 'Space Evenly', 'fusion-builder' ),
						],
						'group'       => esc_attr__( 'General', 'fusion-builder' ),
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Content Wrap', 'fusion-builder' ),
						'description' => __( 'Controls whether flex items are forced onto one line or can wrap onto multiple lines.', 'fusion-builder' ),
						'group'       => esc_attr__( 'General', 'fusion-builder' ),
						'param_name'  => 'flex_wrap',
						'default'     => '',
						'value'       => [
							''       => esc_attr__( 'Default', 'fusion-builder' ),
							'wrap'   => esc_attr__( 'Wrap', 'fusion-builder' ),
							'nowrap' => esc_attr__( 'No Wrap', 'fusion-builder' ),
						],
						'responsive'  => [
							'state'             => 'large',
							'additional_states' => [ 'medium', 'small' ],
							'defaults'          => [
								'small'  => '',
								'medium' => '',
							],
						],
					],
					[
						'type'        => 'select',
						'heading'     => esc_attr__( 'Overflow', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the Nested Columns row overflow property.', 'fusion-builder' ),
						'param_name'  => 'overflow',
						'value'       => [
							''               => esc_attr__( 'Default', 'fusion-builder' ),
							'visible'        => esc_attr__( 'Visible', 'fusion-builder' ),
							'hidden'         => esc_attr__( 'Hidden', 'fusion-builder' ),
							'scroll'         => esc_attr__( 'Scroll', 'fusion-builder' ),
							'auto'           => esc_attr__( 'Auto', 'fusion-builder' ),
							'clip'           => esc_attr__( 'Clip', 'fusion-builder' ),

							'visible hidden' => esc_attr__( 'Visible Hidden', 'fusion-builder' ),
							'visible scroll' => esc_attr__( 'Visible Scroll', 'fusion-builder' ),
							'visible auto'   => esc_attr__( 'Visible Auto', 'fusion-builder' ),
							'visible clip'   => esc_attr__( 'Visible Clip', 'fusion-builder' ),
							
							'hidden visible' => esc_attr__( 'Hidden Visible', 'fusion-builder' ),
							'hidden scroll'  => esc_attr__( 'Hidden Scroll', 'fusion-builder' ),
							'hidden auto'    => esc_attr__( 'Hidden Auto', 'fusion-builder' ),
							'hidden clip'    => esc_attr__( 'Hidden Clip', 'fusion-builder' ),
							
							'scroll visible' => esc_attr__( 'Scroll Visible', 'fusion-builder' ),
							'scroll hidden'  => esc_attr__( 'Scroll Hidden', 'fusion-builder' ),
							'scroll auto'    => esc_attr__( 'Scroll Auto', 'fusion-builder' ),
							'scroll clip'    => esc_attr__( 'Scroll Clip', 'fusion-builder' ),
							
							'auto visible'   => esc_attr__( 'Auto Visible', 'fusion-builder' ),
							'auto hidden'    => esc_attr__( 'Auto Hidden', 'fusion-builder' ),
							'auto scroll'    => esc_attr__( 'Auto Scroll', 'fusion-builder' ),
							'auto clip'      => esc_attr__( 'Auto Clip', 'fusion-builder' ),
							
							'clip visible'   => esc_attr__( 'Clip Visible', 'fusion-builder' ),
							'clip hidden'    => esc_attr__( 'Clip Hidden', 'fusion-builder' ),
							'clip scroll'    => esc_attr__( 'Clip Scroll', 'fusion-builder' ),
							'clip auto'      => esc_attr__( 'Clip Auto', 'fusion-builder' ),
						],
						'default'     => '',
						'group'       => esc_attr__( 'General', 'fusion-builder' ),
					],					
				]
			]
		)
	);
}
add_action( 'fusion_builder_before_init', 'fusion_element_row_inner' );
