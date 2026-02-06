<?php
/**
 * Add an element to fusion-builder.
 *
 * @package fusion-builder
 * @since 13.13.0
 */

if ( fusion_is_element_enabled( 'awb_text_path' ) ) {

	if ( ! class_exists( 'FusionSC_TextPath' ) ) {
		/**
		 * Shortcode class.
		 *
		 * @since 3.13.0
		 */
		class FusionSC_TextPath extends Fusion_Element {

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
			 * @since 3.13.0
			 */
			public function __construct() {
				parent::__construct();
				add_filter( 'fusion_attr_text-path-shortcode', [ $this, 'attr' ] );
				add_filter( 'fusion_attr_text-path-shortcode-path-animation', [ $this, 'path_animation_attr' ] );
				add_filter( 'fusion_attr_text-path-shortcode-path', [ $this, 'path_attr' ] );
				add_filter( 'fusion_attr_text-path-shortcode-text', [ $this, 'text_attr' ] );
				add_filter( 'fusion_attr_text-path-shortcode-text-path', [ $this, 'text_path_attr' ] );
				add_filter( 'fusion_attr_text-path-shortcode-text-animation', [ $this, 'text_animation_attr' ] );
				add_filter( 'fusion_attr_text-path-shortcode-text-animation-font-size', [ $this, 'text_animation_font_size_attr' ] );
				add_filter( 'fusion_attr_text-path-shortcode-link', [ $this, 'link_attr' ] );

				add_shortcode( 'awb_text_path', [ $this, 'render' ] );
			}

			/**
			 * Gets the default values.
			 *
			 * @static
			 * @since 3.13.0
			 * @return array
			 */
			public static function get_element_defaults() {
				$fusion_settings = awb_get_fusion_settings();

				return [
					'path_type'                       => 'wave_1',
					'custom_svg'                      => '',
					'custom_svg_id'                   => '',
					'link'                            => '',
					'link_target'                     => '_self',
					'text_align'                      => '',
					'text_direction'                  => '',

					'path_width'                      => '250',
					'path_rotation'		              => '0',
					'start_offset'                    => '0',
					'lock_font_size'                  => 'yes',

					'fusion_font_family_text_font'    => '',
					'fusion_font_variant_text_font'   => '',
					'font_size'                       => '',
					'line_height'                     => '',
					'letter_spacing'                  => '',
					'text_transform'                  => '',
					'text_color'                      => $fusion_settings->get( 'body_typography', 'color' ),

					'text_shadow'                     => '',
					'text_shadow_blur'                => '',
					'text_shadow_color'               => '',
					'text_shadow_horizontal'          => '',
					'text_shadow_vertical'            => '',
					'text_transform'                  => '',

					'show_text_stroke'                => 'no',
					'text_stroke_width'               => '1',
					'text_stroke_color'               => 'var(--primary_color)',

					'word_spacing'                    => '',

					'show_path'                       => 'no',
					'path_stroke_width'               => '1',					
					'path_stroke_color'               => 'var(--primary_color)',
					'path_fill_color'                 => '',

					'text_animation'                  => 'no',
					'text_animation_direction'        => 'none',
					'text_animation_start_offset'     => '-100',
					'text_animation_end_offset'       => '100',
					'text_animation_font_size_factor' => '1.0',
					'text_animation_duration'         => '1.5',

					'path_animation'                  => 'no',
					'path_animation_type'             => 'scale',
					'path_animation_scale'            => '1.1',
					'path_animation_wiggle'           => '10',
					'path_animation_slide'            => '3',
					'path_animation_rotate'           => '180',
					'path_animation_duration'         => '1.5',

					'padding_top'                     => '',
					'padding_right'                   => '',
					'padding_bottom'                  => '',
					'padding_left'                    => '',
					'padding_top_medium'              => '',
					'padding_right_medium'            => '',
					'padding_bottom_medium'           => '',
					'padding_left_medium'             => '',
					'padding_top_small'               => '',
					'padding_right_small'             => '',
					'padding_bottom_small'            => '',
					'padding_left_small'              => '',					

					'margin_top'                      => '',
					'margin_right'                    => '',
					'margin_bottom'                   => '',
					'margin_left'                     => '',
					'margin_top_medium'               => '',
					'margin_right_medium'             => '',
					'margin_bottom_medium'            => '',
					'margin_left_medium'              => '',
					'margin_top_small'                => '',
					'margin_right_small'              => '',
					'margin_bottom_small'             => '',
					'margin_left_small'               => '',

					'animation_direction'             => 'left',
					'animation_offset'                => $fusion_settings->get( 'animation_offset' ),
					'animation_speed'                 => '',
					'animation_delay'                 => '',
					'animation_type'                  => '',
					'animation_color'                 => '',

					'hide_on_mobile'                  => fusion_builder_default_visibility( 'string' ),
					'class'                           => '',
					'id'                              => '',	
				];				
			}

			/**
			 * Render the shortcode
			 *
			 * @since 1.0
			 * @param  array  $args    Shortcode parameters.
			 * @param  string $content Content between shortcode.
			 * @return string          HTML output.
			 */
			public function render( $args, $content = '' ) {
				$this->args = FusionBuilder::set_shortcode_defaults( self::get_element_defaults(), $args, 'awb_text_path' );
				$content    = apply_filters( 'fusion_shortcode_content', $content, 'awb_text_path', $args );

				$html  = '<div ' . FusionBuilder::attributes( 'text-path-shortcode' ) . '>';
				$html .= $this->get_svg_path_with_text( $content );
				$html .= '</div>';

				$this->counter++;

				$this->on_render();

				return apply_filters( 'fusion_element_text_path_content', $html, $args );
			}

			/**
			 * Returns the SVG path including the set text.
			 *
			 * @access public
			 * @since 3.13.0
			 * @param string $content The content.
			 * @return string The SVG path with text.
			 */
			public function get_svg_path_with_text( $content ) {
				$svg_tag  = '';
				$path_tag = '';

				if ( $this->args['custom_svg'] ) {
					$svg = file_get_contents( $this->args['custom_svg'] );
					preg_match( '/<svg[^>]+>/', $svg, $svg_tag );
					preg_match( '/<path[^>]+>/', $svg, $path_tag );

					$svg_tag  = isset( $svg_tag[0] ) ? $svg_tag[0] : '';
					$path_tag = isset( $path_tag[0] ) ? $path_tag[0] : '';
				}

				$svg_data = $svg_tag && $path_tag ? [] : $this->get_svg_data( $this->args['path_type'] );

				$svg = $svg_tag ? $svg_tag : '<svg width="' . esc_attr( $svg_data['width'] ) . '" height="' . esc_attr( $svg_data['height'] ) . '" viewBox="' . esc_attr( $svg_data['viewbox'] ) . '">';

				if ( 'yes' === $this->args['path_animation'] ) {
					$svg .= '<g><animateTransform ' . FusionBuilder::attributes( 'text-path-shortcode-path-animation' )  . ' />';

					if ( 'wiggle' === $this->args['path_animation_type'] ) {
						$svg .= '<animateTransform ' . FusionBuilder::attributes( 'text-path-shortcode-path-animation', [ 'type' => 'skewY' ] )  . ' />';
					}
				}

				$svg .= $path_tag ? str_replace( '<path', '<path ' . FusionBuilder::attributes( 'text-path-shortcode-path', [] ), $path_tag ) . '</path>' : '<path ' . FusionBuilder::attributes( 'text-path-shortcode-path', [ 'd' => $svg_data['path'] ] ) . '></path>';					
				$svg .= '<text ' . FusionBuilder::attributes( 'text-path-shortcode-text', $svg_data )  . '>
					<textPath ' . FusionBuilder::attributes( 'text-path-shortcode-text-path' ) . '>'
						. ( 'yes' === $this->args['text_animation'] && 'none' !== $this->args['text_animation_direction'] ? '<animate ' . FusionBuilder::attributes( 'text-path-shortcode-text-animation' )  . '/>' : '' )
						. ( 'yes' === $this->args['text_animation'] && '1.0' !== $this->args['text_animation_font_size_factor'] ? '<animate ' . FusionBuilder::attributes( 'text-path-shortcode-text-animation-font-size' )  . '/>' : '' )
						. ( '' !== $this->args['link'] ? '<a ' . FusionBuilder::attributes( 'text-path-shortcode-link' ) . '>' . $content . '</a>' : $content ) .
					'</textPath>
				</text>';
				
				if ( 'yes' === $this->args['path_animation'] ) {
					$svg .= '</g>';
				}					
					
				$svg .= '</svg>';

				return $svg;
			}

			/**
			 * Returns the SVG path.
			 *
			 * @access public
			 * @since 3.13.0
			 * @param string $name Path name
			 * @return string The SVG path.
			 */
			public function get_svg_data( $name ) {
				$svg_data = [];

				switch( $name ) {
					case 'wave_1':
						$svg_data = [
							'width'   => '250',
							'height'  => '42.25',
							'viewbox' => '0 0 250 42.25',
							'path'    => 'M 0,42.25 C 62.5,42.25 62.5,0.25 125,0.25 S 187.5,42.25 250,42.25',
						];
						break;
					case 'wave_2':
						$svg_data = [
							'width'   => '250',
							'height'  => '75',
							'viewbox' => '0 0 250 75',
							'path'    => 'M0 38Q62.5-37 125 38T250 38',
						];
							break;
					case 'arc':
						$svg_data = [
							'width'   => '250',
							'height'  => '125',
							'viewbox' => '0 0 250 125',
							'path'    => 'M 0,125 A 125,125 0 0 1 250,125',
						];
						break;
					case 'arc_bottom':
						$svg_data = [
							'width'     => '250',
							'height'    => '125',
							'viewbox'   => '0 0 250 125',
							'path'      => 'M0 0A125 125 0 00250 0',
							'text_attr' => [ 'key' => 'dominant-baseline', 'value' => 'hanging' ],
						];
						break;
					case 'circle':
						$svg_data = [
							'width'   => '250',
							'height'  => '250',
							'viewbox' => '0 0 250 250',
							'path'    => 'M 0,125 A 125,125 0 1 1 250,125 A 125,125 0 1 1 0,125.01',
						];
						break;
					case 'oval':
						$svg_data = [
							'width'   => '250',
							'height'  => '150',
							'viewbox' => '0 0 250 150',
							'path'    => 'M 0,75 A 125,75 0 1 1 250,75 A 125,75 0 1 1 0,75.01',
						];
						break;
					case 'spiral_1':
						$svg_data = [
							'width'   => '250',
							'height'  => '250',
							'viewbox' => '0 0 250 250',
							'path'    => 'M 0 49.0219a149.3489 149.3489 0 01210.9824-9.8266 119.479 119.479 0 017.8613 168.786A95.5831 95.5831 0 0183.8152 214.27a76.4666 76.4666 0 01-5.0312-108.023',
						];
						break;
					case 'spiral_2':
						$svg_data = [
							'width'   => '330',
							'height'  => '360',
							'viewbox' => '0 0 330 360',
							'path'    => 'M140 1C242 0 330 83 330 175c0 94-76 175-171 175-86 0-161-71-160-158 1-79 65-147 145-146 71 1 132 60 131 132-2 64-54 119-119 117-56-1-104-48-102-105 2-49 42-91 92-88 41 2 76 37 73 79-2 34-31 63-66 59-26-3-48-26-44-53 3-19 21-35 40-30 11 3 21 16 15 27-3 5-14 9-14 1',
						];
						break;						
					case 'star':
						$svg_data = [
							'width'   => '192',
							'height'  => '192',
							'viewbox' => '0 0 192 192',
							'path'    => 'M0 71 72 67 96 0 120 67 192 71 134 117 154 191 96 152 38 191 58 117Z',
						];
						break;
					case 'heart':
						$svg_data = [
							'width'   => '210',
							'height'  => '165',
							'viewbox' => '0 0 210 160',
							'path'    => 'M105 160C0 80 0 0 55 0 90 0 105 30 105 30 105 30 120 0 155 0 210 0 210 80 105 160Z',
						];
						break;

				}

				return $svg_data;
			}

			/**
			 * Builds the main wrapper attributes array.
			 *
			 * @access public
			 * @since 3.13.0
			 * @return array
			 */
			public function attr() {
				$attr = [];

				$attr['class']        = 'awb-text-path';
				$attr['data-counter'] = $this->counter;

				$attr = fusion_builder_visibility_atts( $this->args['hide_on_mobile'], $attr );

				if ( $this->args['animation_type'] ) {
					$attr = Fusion_Builder_Animation_Helper::add_animation_attributes( $this->args, $attr );
				}
	
				if ( $this->args['class'] ) {
					$attr['class'] .= ' ' . $this->args['class'];
				}
	
				if ( $this->args['id'] ) {
					$attr['id'] = $this->args['id'];
				}
	
				$attr['style'] = $this->get_style_variables();

				return $attr;
			}

			/**
			 * Builds the animationTransform attributes array.
			 *
			 * @access public
			 * @since 3.13.0
			 * @param array $args Additional params.
			 * @return array
			 */
			public function path_animation_attr( $args ) {
				$attr = [];

				$attr['attributeName'] = 'transform';
				$attr['attributeType'] = 'XML';
				$attr['type']          = str_replace( [ '_x', '_y' ], '', $this->args['path_animation_type'] );
				$attr['begin']         = '0s';
				$attr['dur']           = fusion_library()->sanitize->get_value_with_unit( $this->args['path_animation_duration'], 's' );
				$attr['repeatCount']   = 'indefinite';

				switch( $this->args['path_animation_type'] ) {
					case 'scale':
						$attr['values'] = '1;' . $this->args['path_animation_scale'] . ';1';
						break;
					case 'wiggle':
						$attr['type']     = isset( $args['type'] ) ? $args['type'] : 'skewX';
						$attr['values']   = '-' . $this->args['path_animation_wiggle'] . ';' . $this->args['path_animation_wiggle'] . ';-' . $this->args['path_animation_wiggle'];
						$attr['additive'] = 'sum';
						break;
					case 'translate_x':
						$elongation = (int) $this->args['path_width'] * (int) $this->args['path_animation_slide'] / 100;
						$attr['values'] = '-' . $elongation . ',0;' . $elongation . ',0;-' . $elongation . ',0';
						break;
					case 'translate_y':
						$elongation = (int) $this->args['path_width'] * (int) $this->args['path_animation_slide'] / 100;
						$attr['values'] = '0,-' . $elongation . ';0,' . $elongation . ';0,-' . $elongation;
						break;
					case 'rotate':
						$values = '360' === $this->args['path_animation_rotate'] ? '0;' . $this->args['path_animation_rotate'] : '0;' . $this->args['path_animation_rotate'] . ';0';
						$attr['values'] = $values;
						break;
				}

				return $attr;
			}

			/**
			 * Builds the path attributes array.
			 *
			 * @access public
			 * @since 3.13.0
			 * @param array $args Additional params.
			 * @return array
			 */
			public function path_attr( $args ) {
				$attr = [];

				$attr['class'] = 'awb-text-path__path awb-text-path__path_' . $this->args['show_path'];
				$attr['id']    = $this->args['path_type'] . '-' . $this->counter;				
				$attr['style'] = 'transform-origin: center;';				

				if ( isset( $args['d'] ) ) {
					$attr['d'] = $args['d'];
				}

				return $attr;
			}

			/**
			 * Builds the text attributes array.
			 *
			 * @access public
			 * @since 3.13.0
			 * @param array $args Additional params.
			 * @return array
			 */
			public function text_attr( $args ) {
				$attr = [];

				if ( $this->args['text_direction'] ) {
					$attr['direction'] = $this->args['text_direction'];
				}

				if ( isset( $args['text_attr'] ) ) {
					$attr[ $args['text_attr']['key'] ] = $args['text_attr']['value'];
				}

				return $attr;
			}

			/**
			 * Builds the textPath attributes array.
			 *
			 * @access public
			 * @since 3.13.0
			 * @return array
			 */
			public function text_path_attr() {
				$attr = [];

				$attr['class'] = 'awb-text-path__text-path awb-text-path__text-path_' . $this->args['show_text_stroke'];
				$attr['href']  = '#' . esc_attr( $this->args['path_type'] ) . '-' . esc_attr(  $this->counter );

				if ( ( is_rtl() && 'ltr' !== $this->args['text_direction'] ) || 'rtl' === $this->args['text_direction'] ) {
					$attr['startOffset'] = ( 100 - (int) $this->args['start_offset'] ) . '%';
				} else {
					$attr['startOffset'] = esc_attr( $this->args['start_offset'] ) . '%';
				}

				$attr['style'] = '';
				if ( 'yes' === $this->args['text_shadow'] ) {
					$text_shadow_styles = Fusion_Builder_Text_Shadow_Helper::get_text_shadow_styles(
						[
							'text_shadow_horizontal' => $this->args['text_shadow_horizontal'],
							'text_shadow_vertical'   => $this->args['text_shadow_vertical'],
							'text_shadow_blur'       => $this->args['text_shadow_blur'],
							'text_shadow_color'      => $this->args['text_shadow_color'],
						]
					);

					$attr['style'] = 'text-shadow:' . esc_attr( trim( $text_shadow_styles ) ) . ';';			
				}

				if ( 'yes' === $this->args['lock_font_size'] ) {
					$factor = 250 / (int) $this->args['path_width'];
					$attr['style'] .= 'font-size:' . $factor . 'em;';
				}						

				return $attr;
			}

			/**
			 * Builds the animation attributes array.
			 *
			 * @access public
			 * @since 3.13.0
			 * @return array
			 */
			public function text_animation_attr() {
				$attr                  = [];
				$attr['attributeName'] = 'startOffset';
				$attr['dur']           = fusion_library()->sanitize->get_value_with_unit( $this->args['text_animation_duration'], 's' );
				$attr['repeatCount']   = 'indefinite';				

				$is_rtl = ( is_rtl() && 'ltr' !== $this->args['text_direction'] ) || 'rtl' === $this->args['text_direction'];
				
				switch( $this->args['text_animation_direction'] ) {
					case 'ticker':
						$attr['values'] = $is_rtl ? '200%;0%' : '-100%;100%;';
						break;
					case 'ltr':
						$attr['values'] = $is_rtl ? '0%;200%;0%' : '-100%;100%;-100%';
						break;
					case 'rtl':
						$attr['values'] = $is_rtl ? '200%;0%;200%' : '100%;-100%;100%';
						break;
					case 'custom':
						$start_offset = $is_rtl ? 100 - (float) $this->args['text_animation_start_offset'] : $this->args['text_animation_start_offset'];
						$start_offset = fusion_library()->sanitize->get_value_with_unit( $start_offset, '%' );

						$end_offset = $is_rtl ? 100 - (float) $this->args['text_animation_end_offset'] : $this->args['text_animation_end_offset'];
						$end_offset = fusion_library()->sanitize->get_value_with_unit( $end_offset, '%' );

						$attr['values'] = $start_offset . ';' . $end_offset . ';' . $start_offset;
				}

				return $attr;
			}
			
			/**
			 * Builds the animation attributes array.
			 *
			 * @access public
			 * @since 3.13.0
			 * @return array
			 */
			public function text_animation_font_size_attr() {			
				$attr = [];

				$base_factor = 1;
				if ( 'yes' === $this->args['lock_font_size'] ) {
					$base_factor = 250 / (int) $this->args['path_width'];
				}

				$attr['attributeName'] = 'font-size';
				$attr['dur']           = fusion_library()->sanitize->get_value_with_unit( $this->args['text_animation_duration'], 's' );
				$attr['repeatCount']   = 'indefinite';
				$attr['values']        = $base_factor . 'em;' . fusion_library()->sanitize->get_value_with_unit( $this->args['text_animation_font_size_factor'] * $base_factor, 'em' ) . ';' . $base_factor . 'em';

				return $attr;
			}
			

			/**
			 * Builds the link attributes array.
			 *
			 * @access public
			 * @since 3.13.0
			 * @return array
			 */
			public function link_attr() {
				$attr = [];

				$attr['class']  = 'awb-text-path__link';
				$attr['href']   = esc_url( $this->args['link'] );
				$attr['target'] = $this->args['link_target'];

				return $attr;
			}			

			/**
			 * Get the styling vars.
			 *
			 * @since 3.13.0
			 * @return string
			 */
			public function get_style_variables() {
				$css_vars = [
					'path_width'            => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'path_rotation'         => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] , 'args' => [ 'unit' => 'deg' ] ],
					'word_spacing',
					'text_align',
					'font_size'             => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'line_height',
					'letter_spacing'        => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'text_transform',
					'text_color'            => [ 'callback' => [ 'Fusion_Sanitize', 'color' ] ],
					'padding_top'           => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'padding_right'         => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'padding_bottom'        => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'padding_left'          => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'padding_top_medium'    => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'padding_right_medium'  => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'padding_bottom_medium' => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'padding_left_medium'   => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'padding_top_small'     => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'padding_right_small'   => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'padding_bottom_small'  => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'padding_left_small'    => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'margin_top'            => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'margin_right'          => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'margin_bottom'         => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'margin_left'           => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'margin_top_medium'     => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'margin_right_medium'   => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'margin_bottom_medium'  => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'margin_left_medium'    => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'margin_top_small'      => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'margin_right_small'    => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'margin_bottom_small'   => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'margin_left_small'     => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
				];

				if ( 'yes' === $this->args['show_text_stroke'] ) {
					$css_vars['text_stroke_width'] = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars['text_stroke_color'] = [ 'callback' => [ 'Fusion_Sanitize', 'color' ] ];
				}

				if ( 'yes' === $this->args['show_path'] ) {
					$css_vars['path_stroke_width'] = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars['path_stroke_color'] = [ 'callback' => [ 'Fusion_Sanitize', 'color' ] ];
					$css_vars['path_fill_color']   = [ 'callback' => [ 'Fusion_Sanitize', 'color' ] ];
				}

				return $this->get_css_vars_for_options( $css_vars ) . $this->get_font_styling_vars( 'text_font' );
			}


			/**
			 * Sets the necessary scripts.
			 *
			 * @access public
			 * @since 3.13.0
			 * @return void
			 */
			public function on_first_render() {
				Fusion_Dynamic_JS::enqueue_script( 'fusion-animations' );
				Fusion_Dynamic_JS::enqueue_script( 'fusion-alert' );
			}

			/**
			 * Load base CSS.
			 *
			 * @access public
			 * @since 3.13.0
			 * @return void
			 */
			public function add_css_files() {
				FusionBuilder()->add_element_css( FUSION_BUILDER_PLUGIN_DIR . 'assets/css/shortcodes/text-path.min.css' );

				if ( class_exists( 'Avada' ) ) {
					$version = Avada::get_theme_version();
					Fusion_Media_Query_Scripts::$media_query_assets[] = [
						'awb-text-path-md',
						FUSION_BUILDER_PLUGIN_DIR . 'assets/css/media/awb-text-path-md.min.css',
						[],
						$version,
						Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-medium' ),
					];
					Fusion_Media_Query_Scripts::$media_query_assets[] = [
						'awb-text-path-sm',
						FUSION_BUILDER_PLUGIN_DIR . 'assets/css/media/awb-text-path-sm.min.css',
						[],
						$version,
						Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-small' ),
					];
				}				
			}
		}
	}

	new FusionSC_TextPath();
}


/**
 * Map shortcode to Avada Builder
 *
 * @since 3.13.0
 */
function fusion_element_text_path() {
	$fusion_settings = awb_get_fusion_settings();

	fusion_builder_map(
		fusion_builder_frontend_data(
			'FusionSC_TextPath',
			[
				'name'                     => esc_attr__( 'Text Path', 'fusion-builder' ),
				'shortcode'                => 'awb_text_path',
				'icon'                     => 'fusiona-text-path',
				'preview'                  => FUSION_BUILDER_PLUGIN_DIR . 'inc/templates/previews/awb-text-path-preview.php',
				'preview_id'               => 'fusion-builder-block-module-text-path-preview-template',
				'inline_editor_shortcodes' => false,
				'help_url'                 => 'https://avada.com/documentation/text-path-element/',
				'subparam_map'             => [
					'font_size'                      => 'typography',
					'fusion_font_family_typography'  => 'typography',
					'fusion_font_variant_typography' => 'typography',
					'letter_spacing'                 => 'typography',
					'text_transform'                 => 'typography',
					'line_height'                    => 'typography',
				],
				'params'                   => [
					[
						'type'        => 'select',
						'heading'     => esc_attr__( 'Path Type', 'fusion-builder' ),
						'description' => esc_attr__( 'Select the path type.', 'fusion-builder' ),
						'param_name'  => 'path_type',
						'default'     => 'wave_1',
						'value'       => [
							'wave_1'     => esc_attr__( 'Wave 1', 'fusion-builder' ),
							'wave_2'     => esc_attr__( 'Wave 2', 'fusion-builder' ),
							'arc'        => esc_attr__( 'Arc', 'fusion-builder' ),
							'arc_bottom' => esc_attr__( 'Arc Bottom', 'fusion-builder' ),
							'circle'     => esc_attr__( 'Circle', 'fusion-builder' ),
							'oval'       => esc_attr__( 'Oval', 'fusion-builder' ),
							'spiral_1'   => esc_attr__( 'Spiral 1', 'fusion-builder' ),
							'spiral_2'   => esc_attr__( 'Spiral 2', 'fusion-builder' ),
							'star'       => esc_attr__( 'Star', 'fusion-builder' ),
							'heart'      => esc_attr__( 'Heart', 'fusion-builder' ),
							'custom'     => esc_attr__( 'Custom', 'fusion-builder' ),
						],
					],
					[
						'type'        => 'upload',
						'heading'     => esc_attr__( 'Custom SVG', 'fusion-builder' ),
						'description' => esc_attr__( 'Upload a custom SVG.', 'fusion-builder' ),
						'param_name'  => 'custom_svg',
						'dynamic_data' => true,
						'dependency'  => [
							[
								'element'  => 'path_type',
								'value'    => 'custom',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Custom SVG ID', 'fusion-builder' ),
						'description' => esc_attr__( 'SVG ID from Media Library.', 'fusion-builder' ),
						'param_name'  => 'custom_svg_id',
						'value'       => '',
						'hidden'      => true,
					],					
					[
						'type'         => 'textfield',
						'heading'      => esc_attr__( 'Text', 'fusion-builder' ),
						'description'  => esc_attr__( 'Add the text that should be aligned to the path.', 'fusion-builder' ),
						'param_name'   => 'element_content',
						'value'        => esc_html__( 'Your Content Goes Here', 'fusion-builder' ),
						'placeholder'  => true,
						'dynamic_data' => true,
					],
					[
						'type'         => 'link_selector',
						'heading'      => esc_attr__( 'Link', 'fusion-builder' ),
						'description'  => esc_attr__( 'Set the URL the text path will link to.', 'fusion-builder' ),
						'param_name'   => 'link',
						'value'        => '',
						'dynamic_data' => true,
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Link Target', 'fusion-builder' ),
						'description' => esc_html__( 'Set how the link will open.', 'fusion-builder' ),
						'param_name'  => 'link_target',
						'value'       => [
							'_self'  => esc_html__( 'Same Window/Tab', 'fusion-builder' ),
							'_blank' => esc_html__( 'New Window/Tab', 'fusion-builder' ),
						],
						'default'     => '_self',
						'dependency'  => [
							[
								'element'  => 'link',
								'value'    => '',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'select',
						'heading'     => esc_attr__( 'Alignment', 'fusion-builder' ),
						'description' => esc_attr__( 'Select the text alignment.', 'fusion-builder' ),
						'param_name'  => 'text_align',
						'default'     => '',
						'responsive'  => [
							'state'         => 'large',
							'default_value' => true,
						],
						'value'       => [
							''        => esc_attr__( 'Text Flow', 'fusion-builder' ),
							'left'    => esc_attr__( 'Left', 'fusion-builder' ),
							'center'  => esc_attr__( 'Center', 'fusion-builder' ),
							'right'   => esc_attr__( 'Right', 'fusion-builder' ),
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Text Direction', 'fusion-builder' ),
						'description' => esc_attr__( 'Select the text direction.', 'fusion-builder' ),
						'param_name'  => 'text_direction',
						'default'     => '',
						'value'       => [
							''    => esc_attr__( 'Default', 'fusion-builder' ),
							'ltr' => esc_attr__( 'LTR', 'fusion-builder' ),
							'rtl' => esc_attr__( 'RTL', 'fusion-builder' ),
						],
					],					
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Path Width', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the overall width of the path. In pixels.', 'fusion-builder' ),
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'param_name'  => 'path_width',
						'value'       => '250',
						'min'         => '0',
						'max'         => '2000',
						'step'        => '25',
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Rotate Path', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the rotation angle of the path. In degree.', 'fusion-builder' ),
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'param_name'  => 'path_rotation',
						'value'       => '0',
						'min'         => '0',
						'max'         => '359',
						'step'        => '1',
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Text Starting Point', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the starting point of the text on the path. In percentage.', 'fusion-builder' ),
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'param_name'  => 'start_offset',
						'value'       => '0',
						'min'         => '0',
						'max'         => '100',
						'step'        => '1',
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Lock Font Size', 'fusion-builder' ),
						'description' => esc_attr__( 'Set to yes, to lock the font size to the values set in Typography option. If set to no, the font size will also change with the Path Width (based on the 250px default width), because of the SVG coordinate system.', 'fusion-builder' ),
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'param_name'  => 'lock_font_size',
						'default'     => 'yes',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no' => esc_attr__( 'No', 'fusion-builder' ),
						],
					],										
					[
						'type'             => 'typography',
						'remove_from_atts' => true,
						'global'           => true,
						'heading'          => esc_attr__( 'Typography', 'fusion-builder' ),
						'description'      => esc_html__( 'Controls the text typography.', 'fusion-builder' ),
						'group'            => esc_attr__( 'Design', 'fusion-builder' ),
						'param_name'       => 'typography',
						'choices'          => [
							'font-family'    => 'text_font',
							'font-size'      => 'font_size',
							'line-height'    => 'line_height',
							'letter-spacing' => 'letter_spacing',
							'text-transform' => 'text_transform',
						],
						'default'          => [
							'font-family'    => '',
							'variant'        => '',
							'font-size'      => '',
							'line-height'    => '',
							'letter-spacing' => '',
							'text-transform' => '',
						],
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Font Color', 'fusion-builder' ),
						'description' => esc_html__( 'Controls the color of the text, ex: #000.', 'fusion-builder' ),
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'param_name'  => 'text_color',
						'value'       => '',
						'default'     => $fusion_settings->get( 'body_typography', 'color' ),
					],
					'fusion_text_shadow_placeholder'       => [],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Text Stroke', 'fusion-builder' ),
						'description' => esc_attr__( 'Set to "Yes" to enable text stroke.', 'fusion-builder' ),
						'group'       => esc_html__( 'Design', 'fusion-builder' ),
						'param_name'  => 'show_text_stroke',
						'default'     => 'no',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Text Stroke Size', 'fusion-builder' ),
						'description' => esc_attr__( 'Set text stroke size. In pixels.', 'fusion-builder' ),
						'group'       => esc_html__( 'Design', 'fusion-builder' ),
						'param_name'  => 'text_stroke_width',
						'value'       => '1',
						'min'         => '0',
						'max'         => '10',
						'step'        => '1',
						'dependency'  => [
							[
								'element'  => 'show_text_stroke',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Text Stroke Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the color of the text stroke.', 'fusion-builder' ),
						'group'       => esc_html__( 'Design', 'fusion-builder' ),
						'param_name'  => 'text_stroke_color',
						'value'       => '',
						'default'     => 'var(--primary_color)',
						'dependency'  => [
							[
								'element'  => 'show_text_stroke',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Word Spacing', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the spacing between the words. Leave empty for normal spacing.', 'fusion-builder' ),
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'param_name'  => 'word_spacing',
						'default'     => '',
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Show Path', 'fusion-builder' ),
						'description' => esc_attr__( 'Set to "Yes" to show the path.', 'fusion-builder' ),
						'group'       => esc_html__( 'Design', 'fusion-builder' ),
						'param_name'  => 'show_path',
						'default'     => 'no',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Path Stroke Width', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the width of the path stroke. In pixels.', 'fusion-builder' ),
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'param_name'  => 'path_stroke_width',
						'value'       => '1',
						'min'         => '0',
						'max'         => '25',
						'step'        => '1',
						'dependency'  => [
							[
								'element'  => 'show_path',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Path Stroke Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the color of the path stroke.', 'fusion-builder' ),
						'group'       => esc_html__( 'Design', 'fusion-builder' ),
						'param_name'  => 'path_stroke_color',
						'value'       => '',
						'default'     => 'var(--primary_color)',
						'dependency'  => [
							[
								'element'  => 'show_path',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Path Fill Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the fill color of the path.', 'fusion-builder' ),
						'group'       => esc_html__( 'Design', 'fusion-builder' ),
						'param_name'  => 'path_fill_color',
						'value'       => '',
						'default'     => '',
						'dependency'  => [
							[
								'element'  => 'show_path',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Text Animation', 'fusion-builder' ),
						'description' => esc_attr__( 'Set to "Yes" to enable text animation along the path.', 'fusion-builder' ),
						'group'       => esc_html__( 'Design', 'fusion-builder' ),
						'param_name'  => 'text_animation',
						'default'     => 'no',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
					],
					[
						'type'        => 'select',
						'heading'     => esc_attr__( 'Text Animation Along Path Direction', 'fusion-builder' ),
						'description' => esc_attr__( 'Select the text animation direction along the path.', 'fusion-builder' ),
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'param_name'  => 'text_animation_direction',
						'default'     => 'none',
						'value'       => [
							'none'   => esc_attr__( 'None', 'fusion-builder' ),
							'ticker' => esc_attr__( 'News Ticker', 'fusion-builder' ),
							'ltr'    => esc_attr__( 'Left To Right', 'fusion-builder' ),
							'rtl'    => esc_attr__( 'Right To Left', 'fusion-builder' ),
							'custom' => esc_attr__( 'Custom', 'fusion-builder' ),
						],
						'dependency'  => [
							[
								'element'  => 'text_animation',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Text Animation Start Offset', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the start offset of the text animation along the path. In percentage.', 'fusion-builder' ),
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'param_name'  => 'text_animation_start_offset',
						'value'       => '-100',
						'min'         => '-100',
						'max'         => '100',
						'step'        => '1',
						'dependency'  => [
							[
								'element'  => 'text_animation',
								'value'    => 'yes',
								'operator' => '==',
							],
							[
								'element'  => 'text_animation_direction',
								'value'    => 'custom',
								'operator' => '==',
							],
						],
					],					
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Text Animation End Offset', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the end offset of the text animation along the path. In percentage.', 'fusion-builder' ),
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'param_name'  => 'text_animation_end_offset',
						'value'       => '100',
						'min'         => '-100',
						'max'         => '100',
						'step'        => '1',
						'dependency'  => [
							[
								'element'  => 'text_animation',
								'value'    => 'yes',
								'operator' => '==',
							],
							[
								'element'  => 'text_animation_direction',
								'value'    => 'custom',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Text Animation Font Size Factor', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the text animation font-size factor. In em. Leave at 1, if you don\'t want to animate the font-size.', 'fusion-builder' ),
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'param_name'  => 'text_animation_font_size_factor',
						'value'       => '1',
						'min'         => '0.1',
						'max'         => '4',
						'step'        => '0.05',
						'dependency'  => [
							[
								'element'  => 'text_animation',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],					
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Text Animation Duration', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the text animation duration. In seconds', 'fusion-builder' ),
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'param_name'  => 'text_animation_duration',
						'value'       => '1.5',
						'min'         => '0.1',
						'max'         => '20',
						'step'        => '0.1',
						'dependency'  => [
							[
								'element'  => 'text_animation',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Path Animation', 'fusion-builder' ),
						'description' => esc_attr__( 'Set to "Yes" to enable path animations.', 'fusion-builder' ),
						'group'       => esc_html__( 'Design', 'fusion-builder' ),
						'param_name'  => 'path_animation',
						'default'     => 'no',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
					],
					[
						'type'        => 'select',
						'heading'     => esc_attr__( 'Path Animation Type', 'fusion-builder' ),
						'description' => esc_attr__( 'Select the text path animation type.', 'fusion-builder' ),
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'param_name'  => 'path_animation_type',
						'default'     => 'scale',
						'value'       => [
							'scale'       => esc_attr__( 'Scale', 'fusion-builder' ),
							'wiggle'      => esc_attr__( 'Wiggle', 'fusion-builder' ),
							'translate_x' => esc_attr__( 'Slide Horizontally', 'fusion-builder' ),
							'translate_y' => esc_attr__( 'Slide Vertically', 'fusion-builder' ),
							'rotate'      => esc_attr__( 'Rotate', 'fusion-builder' ),
						],
						'dependency'  => [
							[
								'element'  => 'path_animation',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Path Scale Factor', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the scale factor of the path animation.', 'fusion-builder' ),
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'param_name'  => 'path_animation_scale',
						'value'       => '1.1',
						'min'         => '0.1',
						'max'         => '2',
						'step'        => '0.1',
						'dependency'  => [
							[
								'element'  => 'path_animation',
								'value'    => 'yes',
								'operator' => '==',
							],
							[
								'element'  => 'path_animation_type',
								'value'    => 'scale',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Path Wiggle Factor', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the wiggle factor of the path animation. In degrees of skewing.', 'fusion-builder' ),
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'param_name'  => 'path_animation_wiggle',
						'value'       => '10',
						'min'         => '1',
						'max'         => '25',
						'step'        => '1',
						'dependency'  => [
							[
								'element'  => 'path_animation',
								'value'    => 'yes',
								'operator' => '==',
							],
							[
								'element'  => 'path_animation_type',
								'value'    => 'wiggle',
								'operator' => '==',
							],
						],
					],					
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Path Slide Elongation', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the path slide elgongation in percentage of the path width.', 'fusion-builder' ),
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'param_name'  => 'path_animation_slide',
						'value'       => '3',
						'min'         => '1',
						'max'         => '25',
						'step'        => '1',
						'dependency'  => [
							[
								'element'  => 'path_animation',
								'value'    => 'yes',
								'operator' => '==',
							],
							[
								'element'  => 'path_animation_type',
								'value'    => 'scale',
								'operator' => '!=',
							],
							[
								'element'  => 'path_animation_type',
								'value'    => 'wiggle',
								'operator' => '!=',
							],							
							[
								'element'  => 'path_animation_type',
								'value'    => 'rotate',
								'operator' => '!=',
							],							
						],
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Path Rotation Angle', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the path animation rotation angle. If 360deg are used, it will keep spinning, without ever going in the reverse direction.', 'fusion-builder' ),
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'param_name'  => 'path_animation_rotate',
						'value'       => '180',
						'min'         => '1',
						'max'         => '360',
						'step'        => '1',
						'dependency'  => [
							[
								'element'  => 'path_animation',
								'value'    => 'yes',
								'operator' => '==',
							],
							[
								'element'  => 'path_animation_type',
								'value'    => 'rotate',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Path Animation Duration', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the path animation duration. In seconds', 'fusion-builder' ),
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'param_name'  => 'path_animation_duration',
						'value'       => '1.5',
						'min'         => '0.1',
						'max'         => '20',
						'step'        => '0.1',
						'dependency'  => [
							[
								'element'  => 'path_animation',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_attr__( 'Padding', 'fusion-builder' ),
						'description'      => esc_attr__( 'Set the element padding.', 'fusion-builder' ),
						'group'            => esc_attr__( 'Design', 'fusion-builder' ),
						'param_name'       => 'padding',
						'value'            => [
							'padding_top'    => '',
							'padding_right'  => '',
							'padding_bottom' => '',
							'padding_left'   => '',
						],
						'responsive' => [
							'state' => 'large',
						],
					],					
					'fusion_margin_placeholder' => [
						'param_name' => 'margin',
						'value'      => [
							'margin_top'    => '',
							'margin_right'  => '',
							'margin_bottom' => '',
							'margin_left'   => '',
						],
						'responsive' => [
							'state' => 'large',
						],
					],
					'fusion_conditional_render_placeholder' => [],
					'fusion_animation_placeholder' => [
						'preview_selector' => '.awb-text-path',
					],
					[
						'type'        => 'checkbox_button_set',
						'heading'     => esc_attr__( 'Element Visibility', 'fusion-builder' ),
						'param_name'  => 'hide_on_mobile',
						'value'       => fusion_builder_visibility_options( 'full' ),
						'default'     => fusion_builder_default_visibility( 'array' ),
						'description' => esc_attr__( 'Choose to show or hide the element on small, medium or large screens. You can choose more than one at a time.', 'fusion-builder' ),
					],

					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'CSS Class', 'fusion-builder' ),
						'param_name'  => 'class',
						'value'       => '',
						'description' => esc_attr__( 'Add a class to the wrapping HTML element.', 'fusion-builder' ),
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'CSS ID', 'fusion-builder' ),
						'param_name'  => 'id',
						'value'       => '',
						'description' => esc_attr__( 'Add an ID to the wrapping HTML element.', 'fusion-builder' ),
					],
				],
			]
		)
	);
}
add_action( 'fusion_builder_before_init', 'fusion_element_text_path' );
