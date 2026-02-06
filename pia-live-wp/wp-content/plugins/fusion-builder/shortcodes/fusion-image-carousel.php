<?php
/**
 * Add an element to fusion-builder.
 *
 * @package fusion-builder
 * @since 1.0
 */

if ( fusion_is_element_enabled( 'fusion_images' ) ) {

	if ( ! class_exists( 'FusionSC_ImageCarousel' ) ) {
		/**
		 * Shortcode class.
		 *
		 * @since 1.0
		 */
		class FusionSC_ImageCarousel extends Fusion_Element {

			/**
			 * Image Carousels counter.
			 *
			 * @access private
			 * @since 1.0
			 * @var int
			 */
			private $image_carousel_counter = 1;

			/**
			 * Total number of images.
			 *
			 * @access private
			 * @since 1.8
			 * @var int
			 */
			private $number_of_images = 1;

			/**
			 * The image data.
			 *
			 * @access private
			 * @since 1.0
			 * @var false|array
			 */
			private $image_data = false;

			/**
			 * Parent SC arguments.
			 *
			 * @access protected
			 * @since 1.0
			 * @var array
			 */
			protected $parent_args;

			/**
			 * Child SC arguments.
			 *
			 * @access protected
			 * @since 1.0
			 * @var array
			 */
			protected $child_args;

			/**
			 * Constructor.
			 *
			 * @access public
			 * @since 1.0
			 */
			public function __construct() {
				parent::__construct();
				add_filter( 'fusion_attr_image-carousel-shortcode', [ $this, 'attr' ] );
				add_filter( 'fusion_attr_image-carousel-shortcode-carousel', [ $this, 'carousel_attr' ] );
				add_filter( 'fusion_attr_image-carousel-shortcode-carousel-wrapper', [ $this, 'carousel_wrapper_attr' ] );
				add_filter( 'fusion_attr_image-carousel-shortcode-slide-link', [ $this, 'slide_link_attr' ] );
				add_filter( 'fusion_attr_fusion-image-wrapper', [ $this, 'image_wrapper' ] );
				add_filter( 'fusion_attr_image-carousel-shortcode-caption', [ $this, 'caption_attr' ] );

				add_shortcode( 'fusion_images', [ $this, 'render_parent' ] );
				add_shortcode( 'fusion_image', [ $this, 'render_child' ] );

				add_shortcode( 'fusion_clients', [ $this, 'render_parent' ] );
				add_shortcode( 'fusion_client', [ $this, 'render_child' ] );

				// Ajax mechanism for query related part.
				add_action( 'wp_ajax_get_fusion_image_carousel', [ $this, 'ajax_query_single_child' ] );

				add_action( 'wp_ajax_get_fusion_image_carousel_children_data', [ $this, 'query_children' ] );
			}

			/**
			 * Gets the query data.
			 *
			 * @static
			 * @access public
			 * @since 2.0.0
			 * @return void
			 */
			public function ajax_query_single_child() {
				check_ajax_referer( 'fusion_load_nonce', 'fusion_load_nonce' );
				$this->query_single_child();
			}

			/**
			 * Gets the query data for single children.
			 *
			 * @access public
			 * @since 2.0.0
			 */
			public function query_single_child() {

				// From Ajax Request.
				if ( isset( $_POST['model'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					$defaults = [
						'image_id' => '',
					];
					if ( isset( $_POST['model']['params'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
						$defaults = $_POST['model']['params']; // phpcs:ignore WordPress.Security
					}

					$return_data['image_data'] = fusion_library()->images->get_attachment_data_by_helper( $defaults['image_id'] );

					$image_sizes = [ 'full', 'portfolio-two', 'blog-medium' ];
					foreach ( $image_sizes as $image_size ) {
						$return_data[ $return_data['image_data']['url'] ][ $image_size ] = wp_get_attachment_image( $return_data['image_data']['id'], $image_size );
						$return_data[ $return_data['image_data']['url'] ]['image_data']  = $return_data['image_data'];
					}
					echo wp_json_encode( $return_data );
				}
				wp_die();
			}

			/**
			 * Gets the query data for all children.
			 *
			 * @access public
			 * @since 2.0.0
			 */
			public function query_children() {

				check_ajax_referer( 'fusion_load_nonce', 'fusion_load_nonce' );

				$return_data = [];

				// From Ajax Request.
				if ( isset( $_POST['children'] ) ) {
					$children    = $_POST['children']; // phpcs:ignore WordPress.Security
					$image_sizes = [ 'full', 'portfolio-two', 'blog-medium' ];

					foreach ( $children as $cid => $image_data ) {
						if ( isset( $children[ $cid ]['image_id'] ) && $children[ $cid ]['image_id'] ) {
							$image_id   = explode( '|', $children[ $cid ]['image_id'] );
							$image_id   = $image_id[0];
							$image_data = fusion_library()->get_images_obj()->get_attachment_data_by_helper( $children[ $cid ]['image_id'], $children[ $cid ]['image'] );
						} else {
							$image_data = fusion_library()->images->get_attachment_data_by_helper( '', $children[ $cid ]['image'] );
							$image_id   = $image_data['id'];
						}

						foreach ( $image_sizes as $image_size ) {
							$return_data[ $children[ $cid ]['image'] ][ $image_size ] = wp_get_attachment_image( $image_id, $image_size );
							$return_data[ $children[ $cid ]['image'] ]['image_data']  = $image_data;
						}
					}

					echo wp_json_encode( $return_data );
				}
				wp_die();
			}

			/**
			 * Gets the default values.
			 *
			 * @static
			 * @access public
			 * @since 2.0.0
			 * @param 'parent'|'child' $context Whether we want parent or child.
			 * @return array
			 */
			public static function get_element_defaults( $context ) {
				$fusion_settings = awb_get_fusion_settings();

				$parent = [
					'margin_top'                           => '',
					'margin_right'                         => '',
					'margin_bottom'                        => '',
					'margin_left'                          => '',
					'hide_on_mobile'                       => fusion_builder_default_visibility( 'string' ),
					'class'                                => '',
					'id'                                   => '',
					'autoplay'                             => 'no',
					'autoplay_speed'                       => $fusion_settings->get( 'carousel_speed' ),
					'autoplay_hover_pause'                 => 'no',
					'flex_align_items'                     => 'center',
					'layout'                               => 'carousel',
					'slide_effect'                         => 'fade',
					'centered_slides'                      => 'no',
					'columns'                              => '5',
					'columns_medium'                       => '0',
					'columns_small'                        => '0',
					'column_spacing'                       => '13',
					'coverflow_depth'                      => '100',
					'display_shadow'                       => 'no',
					'image_id'                             => '',
					'order_by'                             => 'desc',
					'lightbox'                             => 'no',
					'mouse_scroll'                         => 'no',
					'free_mode'                            => 'no',
					'picture_size'                         => 'fixed',
					'rotation_angle'                       => '50',
					'scroll_items'                         => '0',
					'show_nav'                             => 'yes',
					'prev_icon'                            => 'awb-icon-angle-left',
					'next_icon'                            => 'awb-icon-angle-right',
					'arrow_box_width'                      => '',
					'arrow_box_height'                     => '',
					'arrow_position_vertical'              => '',
					'arrow_position_horizontal'            => '',
					'arrow_size'                           => $fusion_settings->get( 'slider_arrow_size' ),
					'arrow_border_radius_top_left'         => '',
					'arrow_border_radius_top_right'        => '',
					'arrow_border_radius_bottom_right'     => '',
					'arrow_border_radius_bottom_left'      => '',
					'arrow_bgcolor'                        => $fusion_settings->get( 'carousel_nav_color' ),
					'arrow_color'                          => '#fff',
					'arrow_hover_bgcolor'                  => $fusion_settings->get( 'carousel_hover_color' ),
					'arrow_hover_color'                    => '#fff',
					'arrow_border_hover_color'             => '',
					'dots_position'                        => 'bottom',
					'dots_spacing'                         => '4',
					'progress_bar_size'                    => '4',
					'dots_margin_top'                      => '',
					'dots_margin_bottom'                   => '',
					'dots_align'                           => '',
					'dots_size'                            => '8',
					'dots_color'                           => $fusion_settings->get( 'carousel_hover_color' ),
					'dots_active_size'                     => '8',
					'dots_active_color'                    => $fusion_settings->get( 'carousel_nav_color' ),
					'transition_speed'                     => '500',
					'hover_type'                           => 'none',
					'marquee_direction'                    => 'left',
					'mask_edges'                           => 'no',
					'border_color'                         => 'var(--awb-color3)',
					'border_style'                         => 'solid',
					'border_width'                         => '0',
					'border_radius_top_left'               => '',
					'border_radius_top_right'              => '',
					'border_radius_bottom_right'           => '',
					'border_radius_bottom_left'            => '',

					// Caption params.
					'caption_style'                        => 'off',
					'caption_title_color'                  => '',
					'caption_title_size'                   => '',
					'caption_title_line_height'            => '',
					'caption_title_letter_spacing'         => '',
					'caption_title_transform'              => '',
					'caption_title_tag'                    => '2',
					'fusion_font_family_caption_title_font' => '',
					'fusion_font_variant_caption_title_font' => '',
					'caption_text_color'                   => '',
					'caption_text_size'                    => '',
					'caption_text_line_height'             => '',
					'caption_text_letter_spacing'          => '',
					'caption_text_transform'               => '',
					'fusion_font_family_caption_text_font' => '',
					'fusion_font_variant_caption_text_font' => '',
					'caption_border_color'                 => '',
					'caption_overlay_color'                => $fusion_settings->get( 'primary_color' ),
					'caption_background_color'             => '',
					'caption_margin_top'                   => '',
					'caption_margin_right'                 => '',
					'caption_margin_bottom'                => '',
					'caption_margin_left'                  => '',
					'caption_align'                        => 'none',
					'caption_align_medium'                 => 'none',
					'caption_align_small'                  => 'none',
					'dynamic_params'                       => '',
				];

				$child = [
					'alt'           => '',
					'image'         => '',
					'image_id'      => '',
					'image_title'   => '',
					'image_caption' => '',
					'link'          => '',
					'linktarget'    => '_self',
				];

				if ( 'parent' === $context ) {
					return $parent;
				} elseif ( 'child' === $context ) {
					return $child;
				}
			}

			/**
			 * Used to set any other variables for use on front-end editor template.
			 *
			 * @static
			 * @access public
			 * @since 3.11.15
			 * @return array
			 */
			public static function get_element_extras() {
				$fusion_settings = awb_get_fusion_settings();
				return [
					'carousel_speed'    => $fusion_settings->get( 'carousel_speed' ),
					'visibility_large'  => $fusion_settings->get( 'visibility_large' ),
					'visibility_medium' => $fusion_settings->get( 'visibility_medium' ),
					'visibility_small'  => $fusion_settings->get( 'visibility_small' ),
				];
			}

			/**
			 * Maps settings to extra variables.
			 *
			 * @static
			 * @access public
			 * @since 3.11.15
			 * @return array
			 */
			public static function settings_to_extras() {

				return [
					'autoplay_speed'    => 'carousel_speed',
					'slider_arrow_size' => 'arrow_size',
				];
			}

			/**
			 * Render the parent shortcode.
			 *
			 * @access public
			 * @since 1.0
			 * @param  array  $args    Shortcode parameters.
			 * @param  string $content Content between shortcode.
			 * @return string          HTML output.
			 */
			public function render_parent( $args, $content = '' ) {
				$this->defaults = self::get_element_defaults( 'parent' );
				$defaults       = FusionBuilder::set_shortcode_defaults( $this->defaults, $args, 'fusion_images' );
				$content        = apply_filters( 'fusion_shortcode_content', $content, 'fusion_images', $args );

				$defaults['column_spacing'] = FusionBuilder::validate_shortcode_attr_value( $defaults['column_spacing'], '' );

				$defaults['border_width'] = ! isset( $args['border_width'] ) && isset( $args['border'] ) && 'yes' === $args['border'] ? '1' : $defaults['border_width'];
				$defaults['border_color'] = ! isset( $args['border_color'] ) && isset( $args['border'] ) && 'yes' === $args['border'] ? '#e9eaee' : $defaults['border_color'];

				$this->parent_args = $this->args = $defaults;

				preg_match_all( '/\[fusion_image (.*?)\]/s', $content, $matches );

				if ( isset( $matches[0] ) ) {
					$this->number_of_images = count( $matches[0] );
					$content                = $this->sort_carousel_items( $matches[0] );
				}

				$html  = '<div ' . FusionBuilder::attributes( 'image-carousel-shortcode' ) . '>';
				$html .= '<div ' . FusionBuilder::attributes( 'image-carousel-shortcode-carousel' ) . '>';

				// The main carousel.
				$html .= '<div ' . FusionBuilder::attributes( 'image-carousel-shortcode-carousel-wrapper' ) . '>';

				$acf_repeater_html = '';
				if ( $this->parent_args['dynamic_params'] ) {
					$dynamic_data = json_decode( fusion_decode_if_needed( $this->parent_args['dynamic_params'] ), true );

					if ( isset( $dynamic_data['parent_dynamic_content']['data'] ) ) {
						if ( 'filebird_folder_parent' === $dynamic_data['parent_dynamic_content']['data'] ) {
							$image_ids = Fusion_Dynamic_Data_Callbacks::get_filebird_folder_image_ids( $dynamic_data['parent_dynamic_content'] );
							$content   = '';
							foreach ( $image_ids as $image_id ) {
								$content .= '[fusion_image image_id="' . $image_id . '" /]';
							}
						} elseif ( 'acf_repeater_parent' === $dynamic_data['parent_dynamic_content']['data'] ) {
							$acf_repeater_html = self::get_acf_repeater( $dynamic_data['parent_dynamic_content'], $this->parent_args, $content );
						}
					}
				}

				if ( $acf_repeater_html ) {
					$html .= $acf_repeater_html;
				} else {
					$html .= do_shortcode( $content );
				}
				$html .= '</div>';

				if ( in_array( $this->parent_args['show_nav'], [ 'dots', 'arrows_dots', 'arrows_progress' ], true ) && 'marquee' !== $this->parent_args['layout'] ) {
					$html .= '<div class="swiper-pagination"></div>';
				}

				// Check if navigation should be shown.
				if ( in_array( $this->parent_args['show_nav'], [ 'yes', 'arrows_dots', 'arrows_progress' ], true ) && ( ! in_array( $this->parent_args['layout'], [ 'carousel', 'coverflow', 'marquee' ], true ) || 'no'=== $this->parent_args['mask_edges'] ) ) {
					$html .= awb_get_carousel_nav( fusion_font_awesome_name_handler( $this->parent_args['prev_icon'] ), fusion_font_awesome_name_handler( $this->parent_args['next_icon'] ) );
				}

				$html .= '</div>';
				$html .= '</div>';

				$this->image_carousel_counter++;

				$this->on_render();

				return apply_filters( 'fusion_element_image_carousel_parent_content', $html, $args );
			}

			/**
			 * Builds the attributes array.
			 *
			 * @access public
			 * @since 1.0
			 * @return array
			 */
			public function attr() {

				$attr = fusion_builder_visibility_atts(
					$this->parent_args['hide_on_mobile'],
					[
						'class' => 'fusion-image-carousel fusion-image-carousel-' . $this->parent_args['picture_size'] . ' fusion-image-carousel-' . $this->image_carousel_counter,
						'style' => '',
					]
				);

				if ( 'yes' === $this->parent_args['lightbox'] ) {
					$attr['class'] .= ' lightbox-enabled';
				}

				if ( '0' !== $this->parent_args['border_width'] ) {
					$attr['class'] .= ' fusion-carousel-border';
				}

				if ( in_array( $this->parent_args['caption_style'], [ 'above', 'below' ], true ) ) {
					$attr['class'] .= ' awb-image-carousel-top-below-caption';
				}

				$attr['style'] .= Fusion_Builder_Margin_Helper::get_margins_style( $this->parent_args );

				if ( $this->parent_args['class'] ) {
					$attr['class'] .= ' ' . $this->parent_args['class'];
				}

				if ( $this->parent_args['id'] ) {
					$attr['id'] = $this->parent_args['id'];
				}

				return $attr;
			}

			/**
			 * Builds the carousel attributes array.
			 *
			 * @access public
			 * @since 1.0
			 * @return array
			 */
			public function carousel_attr() {

				$marquee_class = '';
				if ( 'marquee' === $this->parent_args['layout'] ) {
					$attr['data-marquee-direction'] = $this->parent_args['marquee_direction'];
				} else if ( 'slider' === $this->args['layout'] ) {
					$attr['data-slide-effect'] = $this->args['slide_effect'];
				}

				if ( in_array( $this->parent_args['layout'], [ 'carousel', 'coverflow', 'marquee' ], true ) && 'yes'=== $this->parent_args['mask_edges'] ) {
					$marquee_class = ' awb-carousel--masked';
				}

				$pagination_class = 'arrows_progress' === $this->parent_args['show_nav'] ? ' awb-swiper-progress-position-' . $this->parent_args['dots_position'] : ' awb-swiper-dots-position-' . $this->parent_args['dots_position'];

				$attr['class']               = 'awb-carousel awb-swiper awb-swiper-carousel awb-carousel--' . $this->parent_args['layout'] . $pagination_class . $marquee_class;
				$attr['data-layout']         = $this->parent_args['layout'];
				$attr['data-autoplay']       = $this->parent_args['autoplay'];
				$attr['data-autoplayspeed']  = $this->parent_args['autoplay_speed'];
				$attr['data-autoplaypause']  = $this->parent_args['autoplay_hover_pause'];
				$attr['data-columns']        = 1 > $this->parent_args['columns'] ? 1 : $this->parent_args['columns'];
				$attr['data-columnsmedium']  = 1 > $this->parent_args['columns_medium'] ? 1 : $this->parent_args['columns_medium'];
				$attr['data-columnssmall']   = 1 > $this->parent_args['columns_small'] ? 1 : $this->parent_args['columns_small'];
				$attr['data-itemmargin']     = $this->parent_args['column_spacing'];
				$attr['data-itemwidth']      = 180;
				$attr['data-touchscroll']    = 'yes' === $this->parent_args['mouse_scroll'] ? 'drag' : $this->parent_args['mouse_scroll'];
				$attr['data-freemode']       = $this->parent_args['free_mode'];
				$attr['data-imagesize']      = $this->parent_args['picture_size'];
				$attr['data-scrollitems']    = $this->parent_args['scroll_items'];
				$attr['data-centeredslides'] = $this->parent_args['centered_slides'];
				$attr['data-rotationangle']  = $this->parent_args['rotation_angle'];
				$attr['data-depth']          = $this->parent_args['coverflow_depth'];
				$attr['data-speed']          = $this->parent_args['transition_speed'];
				$attr['data-shadow']         = 'auto' === $this->parent_args['picture_size'] ? $this->parent_args['display_shadow'] : 'no';
				$attr['data-pagination']     = 'arrows_progress' === $this->parent_args['show_nav'] ? 'progressbar' : 'bullets';
				$attr['style']               = $this->get_inline_style();

				// Caption style.
				if ( in_array( $this->parent_args['caption_style'], [ 'above', 'below' ], true ) ) {
					$attr['class'] .= ' awb-imageframe-style awb-imageframe-style-' . $this->parent_args['caption_style'] . ' awb-imageframe-style-' . $this->image_carousel_counter;
				}
				return $attr;
			}

			/**
			 * Builds the carousel wrapper attributes array.
			 *
			 * @access public
			 * @since 3.9.1
			 * @return array
			 */
			public function carousel_wrapper_attr() {
				$attr['class'] = 'swiper-wrapper awb-image-carousel-wrapper fusion-flex-align-items-' . $this->args['flex_align_items'];
				return $attr;
			}

			/**
			 * Render the child shortcode.
			 *
			 * @access public
			 * @since 1.0
			 * @param  array  $args   Shortcode parameters.
			 * @param  string $content Content between shortcode.
			 * @return string         HTML output.
			 */
			public function render_child( $args, $content = '' ) {

				$defaults = FusionBuilder::set_shortcode_defaults( self::get_element_defaults( 'child' ), $args, 'fusion_image' );
				$content  = apply_filters( 'fusion_shortcode_content', $content, 'fusion_image', $args );

				$this->child_args = $defaults;

				$width = $height = '';

				$image_size = 'full';
				if ( 'fixed' === $this->parent_args['picture_size'] ) {
					$image_size = 'portfolio-two';
					if ( ( '6' === $this->parent_args['columns'] || '5' === $this->parent_args['columns'] || '4' === $this->parent_args['columns'] ) && ! in_array( $this->parent_args['layout'], [ 'slider', 'cards' ], true ) ) {
						$image_size = 'blog-medium';
					}
				}

				$this->image_data = fusion_library()->images->get_attachment_data_by_helper( $this->child_args['image_id'], $this->child_args['image'] );

				$output = '';
				if ( $this->image_data && $this->image_data['id'] ) {

					// Responsive images.
					$number_of_columns = ( $this->number_of_images < $this->parent_args['columns'] ) ? $this->number_of_images : $this->parent_args['columns'];

					if ( ( 1 < $number_of_columns || 'full' !== $image_size ) && ! in_array( $this->parent_args['layout'], [ 'slider', 'cards' ], true ) ) {
						fusion_library()->images->set_grid_image_meta(
							[
								'layout'       => 'grid',
								'columns'      => $number_of_columns,
								'gutter_width' => $this->parent_args['column_spacing'],
							]
						);
					}

					if ( $this->child_args['alt'] ) {
						$output = wp_get_attachment_image( $this->image_data['id'], $image_size, false, [ 'alt' => $this->child_args['alt'] ] );
					} else {
						$output = wp_get_attachment_image( $this->image_data['id'], $image_size );
					}

					if ( 'full' === $image_size || in_array( $this->parent_args['layout'], [ 'slider', 'cards' ], true ) ) {
						$output = fusion_library()->images->edit_grid_image_src( $output, null, $this->image_data['id'], 'full' );
					}

					fusion_library()->images->set_grid_image_meta( [] );

				} else {
					$output = '<img src="' . $this->child_args['image'] . '" alt="' . $this->child_args['alt'] . '"/>';
				}

				if ( ! empty( $this->image_data ) ) {
					$output = fusion_library()->images->apply_lazy_loading( $output, null, $this->image_data['id'], 'full' );
				}

				// render caption markup.
				if ( ! in_array( $this->parent_args['caption_style'], [ 'off', 'above', 'below' ], true ) ) {
					$output .= $this->render_caption();
				}

				if ( ( 'no' === $this->parent_args['mouse_scroll'] || 'wheel' === $this->parent_args['mouse_scroll'] ) && ( $this->child_args['link'] || 'yes' === $this->parent_args['lightbox'] ) ) {
					$output = '<a ' . FusionBuilder::attributes( 'image-carousel-shortcode-slide-link' ) . '>' . $output . '</a>';
				}

				$li = '<div ' . FusionBuilder::attributes( 'swiper-slide' ) . '><div ' . FusionBuilder::attributes( 'fusion-carousel-item-wrapper' ) . '>';
				if ( 'above' === $this->parent_args['caption_style'] ) {
					$li .= $this->render_caption();
				}
				$li .= '<div ' . FusionBuilder::attributes( 'fusion-image-wrapper' ) . '>' . $output . '</div>';
				if ( 'below' === $this->parent_args['caption_style'] ) {
					$li .= $this->render_caption();
				}
				$li .= '</div></div>';

				return apply_filters( 'fusion_element_image_carousel_child_content', $li, $args );
			}

			/**
			 * Builds the slide-link attributes array.
			 *
			 * @access public
			 * @since 1.0
			 * @return array
			 */
			public function slide_link_attr() {

				$attr = [];

				if ( 'yes' === $this->parent_args['lightbox'] ) {

					if ( ! $this->child_args['link'] ) {
						if ( $this->child_args['image'] ) {
							$this->child_args['link'] = $this->child_args['image'];
						} elseif ( isset( $this->image_data['url'] ) ) {
							$this->child_args['link'] = $this->image_data['url'];
						}
					}

					$attr['data-rel'] = 'iLightbox[image_carousel_' . $this->image_carousel_counter . ']';

					if ( $this->image_data ) {
						$attr['data-caption'] = $this->image_data['caption'];
						$attr['data-title']   = $this->image_data['title'];
						$attr['aria-label']   = $this->image_data['title'];
					}
				}

				$attr['href'] = $this->child_args['link'];

				$attr['target'] = $this->child_args['linktarget'];
				if ( '_blank' === $this->child_args['linktarget'] ) {
					$attr['rel'] = 'noopener noreferrer';
				}
				return $attr;
			}

			/**
			 * Builds the caption attributes array.
			 *
			 * @access public
			 * @since 3.5
			 * @return array
			 */
			public function caption_attr() {

				$attr = [
					'class' => 'awb-imageframe-caption-container',
					'style' => '',
				];

				if ( ! fusion_element_rendering_is_flex() ) {
					return $attr;
				}

				if ( in_array( $this->args['caption_style'], [ 'above', 'below' ], true ) ) {
					// Responsive alignment.
					foreach ( [ 'large', 'medium', 'small' ] as $size ) {
						$key = 'caption_align' . ( 'large' === $size ? '' : '_' . $size );

						$align = ! empty( $this->args[ $key ] ) && 'none' !== $this->args[ $key ] ? $this->args[ $key ] : false;
						if ( $align ) {
							if ( 'large' === $size ) {
								$attr['style'] .= 'text-align:' . $this->args[ $key ] . ';';
							} else {
								$attr['class'] .= ( 'medium' === $size ? ' md-text-align-' : ' sm-text-align-' ) . $this->args[ $key ];
							}
						}
					}
				}

				return $attr;
			}

			/**
			 * Builds the image-wrapper attributes array.
			 *
			 * @access public
			 * @since 1.0
			 * @return array
			 */
			public function image_wrapper() {
				$attr = [
					'class' => 'fusion-image-wrapper',
				];
				if ( $this->parent_args['hover_type'] && in_array( $this->parent_args['caption_style'], [ 'off', 'above', 'below' ], true ) ) {
					$attr['class'] .= ' hover-type-' . $this->parent_args['hover_type'];
				}

				// Caption style.
				if ( ! in_array( $this->parent_args['caption_style'], [ 'off', 'above', 'below' ], true ) ) {
					$attr['class'] .= ' awb-imageframe-style awb-imageframe-style-' . $this->parent_args['caption_style'];
				}
				return $attr;
			}

			/**
			 * Render the caption.
			 *
			 * @access public
			 * @since 3.5
			 * @return string HTML output.
			 */
			public function render_caption() {
				if ( 'off' === $this->parent_args['caption_style'] ) {
					return '';
				}
				$output  = '<div ' . FusionBuilder::attributes( 'image-carousel-shortcode-caption' ) . '><div class="awb-imageframe-caption">';
				$title   = '';
				$caption = '';

				if ( $this->image_data ) {
					if ( '' !== $this->image_data['title'] ) {
						$title = $this->image_data['title'];
					}
					if ( '' !== $this->image_data['caption'] ) {
						$caption = $this->image_data['caption'];
					}
				}

				if ( '' !== $this->child_args['image_title'] ) {
					$title = $this->child_args['image_title'];
				}
				if ( '' !== $this->child_args['image_caption'] ) {
					$caption = $this->child_args['image_caption'];
				}

				if ( '' !== $title ) {
					$title_tag = 'div' === $this->parent_args['caption_title_tag'] ? 'div' : 'h' . $this->parent_args['caption_title_tag'];
					$output   .= sprintf( '<%1$s class="awb-imageframe-caption-title">%2$s</%1$s>', $title_tag, $title );
				}
				if ( '' !== $caption ) {
					$output .= sprintf( '<p class="awb-imageframe-caption-text">%1$s</p>', $caption );
				}
				$output .= '</div></div>';
				return $output;
			}

			/**
			 * Get the inline style.
			 *
			 * @since 3.9
			 * @return string
			 */
			public function get_inline_style() {
				$css_vars_options = [
					'columns',
					'column_spacing'               => [
						'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ],
					],
					'caption_title_color'          => [
						'callback' => [ 'Fusion_Sanitize', 'color' ],
					],
					'caption_title_size'           => [
						'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ],
					],
					'caption_title_transform',
					'caption_title_line_height',
					'caption_title_letter_spacing' => [
						'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ],
					],
					'caption_text_color'           => [
						'callback' => [ 'Fusion_Sanitize', 'color' ],
					],
					'caption_text_size'            => [
						'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ],
					],
					'caption_text_transform',
					'caption_text_line_height',
					'caption_text_letter_spacing'  => [
						'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ],
					],
					'caption_border_color'         => [
						'callback' => [ 'Fusion_Sanitize', 'color' ],
					],
					'caption_overlay_color'        => [
						'callback' => [ 'Fusion_Sanitize', 'color' ],
					],
					'caption_background_color'     => [
						'callback' => [ 'Fusion_Sanitize', 'color' ],
					],
					'caption_margin_top'           => [
						'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ],
					],
					'caption_margin_right'         => [
						'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ],
					],
					'caption_margin_bottom'        => [
						'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ],
					],
					'caption_margin_left'          => [
						'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ],
					],
					'border_style',
					'border_width'                     => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'border_color'                     => [ 'callback' => [ 'Fusion_Sanitize', 'color' ] ],
					'border_radius_top_left'           => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'border_radius_top_right'          => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'border_radius_bottom_right'       => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'border_radius_bottom_left'        => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'arrow_position_vertical'          => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'arrow_position_horizontal'        => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'arrow_size'                       => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'arrow_box_width'                  => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'arrow_box_height'                 => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'arrow_bgcolor'                    => [ 'callback' => [ 'Fusion_Sanitize', 'color' ] ],
					'arrow_color'                      => [ 'callback' => [ 'Fusion_Sanitize', 'color' ] ],
					'arrow_hover_bgcolor'              => [ 'callback' => [ 'Fusion_Sanitize', 'color' ] ],
					'arrow_hover_color'                => [ 'callback' => [ 'Fusion_Sanitize', 'color' ] ],
					'arrow_border_radius_top_left'     => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'arrow_border_radius_top_right'    => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'arrow_border_radius_bottom_right' => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'arrow_border_radius_bottom_left'  => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'dots_color'                       => [ 'callback' => [ 'Fusion_Sanitize', 'color' ] ],
					'dots_active_color'                => [ 'callback' => [ 'Fusion_Sanitize', 'color' ] ],
					'dots_size'                        => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'dots_active_size'                 => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'progress_bar_size'                => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'dots_spacing'                     => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'dots_margin_top'                  => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'dots_margin_bottom'               => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'dots_align',
				];

				$custom_vars = [];
				if ( ! $this->is_default( 'arrow_position_vertical' ) ) {
					$custom_vars['arrow_position_vertical_transform'] = 'none';
				}

				return $this->get_css_vars_for_options( $css_vars_options ) . $this->get_font_styling_vars( 'caption_title_font' ) . $this->get_font_styling_vars( 'caption_text_font' ) . $this->get_custom_css_vars( $custom_vars );
			}

			/**
			 * Sorts carousel items
			 *
			 * @access public
			 * @param mixed $data Carousel items data.
			 * @since 3.9
			 * @return mixed
			 */
			public function sort_carousel_items( $data ) {

				if ( is_array( $data ) ) {
					switch ( $this->args['order_by'] ) {
						case 'asc':
							krsort( $data, SORT_NUMERIC );
							break;
						case 'rand':
							shuffle( $data );
							break;
					}
				}

				return implode( '', $data );
			}

			/**
			 * Sets the necessary scripts.
			 *
			 * @access public
			 * @since 3.2
			 * @return void
			 */
			public function on_first_render() {
				Fusion_Dynamic_JS::enqueue_script( 'fusion-lightbox' );
				Fusion_Dynamic_JS::enqueue_script( 'awb-carousel' );
			}

			/**
			 * Load base CSS.
			 *
			 * @access public
			 * @since 3.0
			 * @return void
			 */
			public function add_css_files() {
				FusionBuilder()->add_element_css( FUSION_BUILDER_PLUGIN_DIR . 'assets/css/shortcodes/image-carousel.min.css' );
			}
		}
	}

	new FusionSC_ImageCarousel();

}

/**
 * Map shortcode to Avada Builder.
 */
function fusion_element_images() {
	$fusion_settings = awb_get_fusion_settings();

	// Navigation section.
	$arrows_dependency = [
		[
			'element'  => 'layout',
			'value'    => 'marquee',
			'operator' => '!=',
		],
		[
			'element'  => 'mask_edges',
			'value'    => 'yes',
			'operator' => '!=',
		],
		[
			'element'  => 'show_nav',
			'value'    => 'no',
			'operator' => '!=',
		],
		[
			'element'  => 'show_nav',
			'value'    => 'dots',
			'operator' => '!=',
		],
	];
	$dots_dependency   = [	
		[
			'element'  => 'layout',
			'value'    => 'marquee',
			'operator' => '!=',
		],
		[
			'element'  => 'mask_edges',
			'value'    => 'yes',
			'operator' => '!=',
		],
		[
			'element'  => 'show_nav',
			'value'    => 'no',
			'operator' => '!=',
		],
		[
			'element'  => 'show_nav',
			'value'    => 'yes',
			'operator' => '!=',
		]
	];

	$dots_progress_dependency = $dots_dependency;

	$dots_dependency[] = [
		'element'  => 'show_nav',
		'value'    => 'arrows_progress',
		'operator' => '!=',
	];

	fusion_builder_map(
		fusion_builder_frontend_data(
			'FusionSC_ImageCarousel',
			[
				'name'          => esc_attr__( 'Image Carousel', 'fusion-builder' ),
				'shortcode'     => 'fusion_images',
				'multi'         => 'multi_element_parent',
				'element_child' => 'fusion_image',
				'icon'          => 'fusiona-images',
				'preview'       => FUSION_BUILDER_PLUGIN_DIR . 'inc/templates/previews/fusion-image-carousel-preview.php',
				'preview_id'    => 'fusion-builder-block-module-image-carousel-preview-template',
				'child_ui'      => true,
				'sortable'      => false,
				'help_url'      => 'https://avada.com/documentation/image-carousel-element/',
				'subparam_map'  => [
					/* Caption title */
					'fusion_font_family_caption_title_font' => 'caption_title_fonts',
					'fusion_font_variant_caption_title_font' => 'caption_title_fonts',
					'caption_title_size'                   => 'caption_title_fonts',
					'caption_title_transform'              => 'caption_title_fonts',
					'caption_title_line_height'            => '',
					'caption_title_letter_spacing'         => '',
					'caption_title_color'                  => '',

					/* Caption text */
					'fusion_font_family_caption_text_font' => 'caption_text_fonts',
					'fusion_font_variant_caption_text_font' => 'caption_text_fonts',
					'caption_text_size'                    => 'caption_text_fonts',
					'caption_text_transform'               => 'caption_text_fonts',
					'caption_text_line_height'             => '',
					'caption_text_letter_spacing'          => '',
					'caption_text_color'                   => '',
				],
				'params'        => [
					[
						'type'            => 'textfield',
						'heading'         => esc_attr__( 'Dynamic Data', 'fusion-builder' ),
						'param_name'      => 'parent_dynamic_content',
						'dynamic_data'    => true,
						'dynamic_options' => [ 'acf_repeater_parent', 'filebird_folder_parent' ],
						'group'           => esc_attr__( 'children', 'fusion-builder' ),
					],
					[
						'type'        => 'tinymce',
						'heading'     => esc_attr__( 'Content', 'fusion-builder' ),
						'description' => esc_attr__( 'Enter some content for this image carousel.', 'fusion-builder' ),
						'param_name'  => 'element_content',
						'value'       => '[fusion_image link="" linktarget="_self" alt="" image_id="" /]',
					],
					[
						'type'             => 'multiple_upload',
						'heading'          => esc_attr__( 'Bulk Image Upload', 'fusion-builder' ),
						'description'      => __( 'This option allows you to select multiple images at once and they will populate into individual items. It saves time instead of adding one image at a time.', 'fusion-builder' ),
						'param_name'       => 'multiple_upload',
						'dynamic_data'     => true,
						'child_params'     => [
							'image'    => 'url',
							'image_id' => 'id',
						],
						'remove_from_atts' => true,
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Layout', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose a carousel layout.', 'fusion-builder' ),
						'param_name'  => 'layout',
						'value'       => [
							'carousel'  => esc_attr__( 'Standard', 'fusion-builder' ),
							'marquee'   => esc_attr__( 'Marquee', 'fusion-builder' ),
							'coverflow' => esc_attr__( 'Coverflow', 'fusion-builder' ),
							'cards'     => esc_attr__( 'Cards', 'fusion-builder' ),
							'slider'    => esc_attr__( 'Slider', 'fusion-builder' ),
						],
						'default'     => 'carousel',
					],	
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Slide Transition Style', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose the transition style for the slider layout.', 'fusion-builder' ),
						'param_name'  => 'slide_effect',
						'value'       => [
							'fade'       => esc_attr__( 'Fade', 'fusion-builder' ),
							'flip'       => esc_attr__( 'Flip', 'fusion-builder' ),
							'flip_vert'  => esc_attr__( 'Flip Vertically', 'fusion-builder' ),
							'swipe'      => esc_attr__( 'Swipe', 'fusion-builder' ),
							'swipe_vert' => esc_attr__( 'Swipe Vertically', 'fusion-builder' ),
							'slide'      => esc_attr__( 'Slide', 'fusion-builder' ),
							'slide_vert' => esc_attr__( 'Slide Vertically', 'fusion-builder' ),
						],
						'default'     => 'fade',
						'dependency'  => [
							[
								'element'  => 'layout',
								'value'    => 'slider',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'param_name'  => 'order_by',
						'heading'     => esc_attr__( 'Order By', 'fusion-builder' ),
						'description' => __( 'Defines how items should be ordered. <strong>NOTE:</strong> This option will not work in the Live Editor.', 'fusion-builder' ),
						'default'     => 'desc',
						'value'       => [
							'desc' => esc_html__( 'DESC', 'fusion-builder' ),
							'asc'  => esc_html__( 'ASC', 'fusion-builder' ),
							'rand' => esc_html__( 'RAND', 'fusion-builder' ),
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Picture Size', 'fusion-builder' ),
						'description' => __( 'fixed = width and height will be fixed <br />auto = width and height will adjust to the image.', 'fusion-builder' ),
						'param_name'  => 'picture_size',
						'value'       => [
							'fixed' => esc_attr__( 'Fixed', 'fusion-builder' ),
							'auto'  => esc_attr__( 'Auto', 'fusion-builder' ),
						],
						'default'     => 'auto',
						'callback'    => [
							'function' => 'fusion_carousel_images',
							'action'   => 'get_fusion_image_carousel_children_data',
							'ajax'     => true,
						],
					],
					[
						'type'        => 'select',
						'heading'     => esc_attr__( 'Hover Type', 'fusion-builder' ),
						'description' => esc_attr__( 'Select the hover effect type. Hover Type will be disabled when caption styles other than Above or Below are chosen.', 'fusion-builder' ),
						'param_name'  => 'hover_type',
						'value'       => [
							'none'    => esc_attr__( 'None', 'fusion-builder' ),
							'zoomin'  => esc_attr__( 'Zoom In', 'fusion-builder' ),
							'zoomout' => esc_attr__( 'Zoom Out', 'fusion-builder' ),
							'liftup'  => esc_attr__( 'Lift Up', 'fusion-builder' ),
						],
						'default'     => 'none',
						'preview'     => [
							'selector' => '.fusion-image-wrapper',
							'type'     => 'class',
							'toggle'   => 'hover',
						],
						'dependency'  => [
							[
								'element'  => 'caption_style',
								'value'    => 'navin',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'dario',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'resa',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'schantel',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'dany',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_html__( 'Marquee Direction', 'fusion-builder' ),
						'description' => esc_html__( 'Select the marquee direction.', 'fusion-builder' ),
						'param_name'  => 'marquee_direction',
						'default'     => 'left',
						'value'       => [
							'left'  => esc_html__( 'Left', 'fusion-builder' ),
							'right' => esc_html__( 'Right', 'fusion-builder' ),
						],
						'dependency'  => [
							[
								'element'  => 'layout',
								'value'    => 'marquee',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Slide Rotation Angle', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the rotation angle for the slides in coverflow layout.', 'fusion-builder' ),
						'param_name'  => 'rotation_angle',
						'value'       => '50',
						'min'         => '0',
						'max'         => '180',
						'step'        => '1',
						'dependency'  => [
							[
								'element'  => 'layout',
								'value'    => 'coverflow',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Slide Depth', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the z-axis translation offset of the slides in coverflow layout.', 'fusion-builder' ),
						'param_name'  => 'coverflow_depth',
						'value'       => '100',
						'min'         => '0',
						'max'         => '250',
						'step'        => '1',
						'dependency'  => [
							[
								'element'  => 'layout',
								'value'    => 'coverflow',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Transition Speed', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the duration of the transition between slides. In milliseconds.', 'fusion-builder' ),
						'param_name'  => 'transition_speed',
						'value'       => '500',
						'min'         => '50',
						'max'         => '10000',
						'step'        => '50',
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Autoplay', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose to autoplay the carousel.', 'fusion-builder' ),
						'param_name'  => 'autoplay',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'default'     => 'no',
						'dependency'  => [
							[
								'element'  => 'layout',
								'value'    => 'marquee',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Autoplay Speed', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the autoplay speed, the duration between transitions. In milliseconds.', 'fusion-builder' ),
						'param_name'  => 'autoplay_speed',
						'value'       => '',
						'default'     => $fusion_settings->get( 'carousel_speed' ),
						'min'         => '0',
						'max'         => '20000',
						'step'        => '100',
						'dependency'  => [
							[
								'element'  => 'layout',
								'value'    => 'marquee',
								'operator' => '!=',
							],
							[
								'element'  => 'autoplay',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Autoplay Pause On Hover', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose to pause autoplay on hover.', 'fusion-builder' ),
						'param_name'  => 'autoplay_hover_pause',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'default'     => 'no',
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Column Alignment', 'fusion-builder' ),
						'description' => esc_attr__( 'Select the column alignment within rows.', 'fusion-builder' ),
						'param_name'  => 'flex_align_items',
						'back_icons'  => true,
						'grid_layout' => true,
						'value'       => [
							'flex-start' => esc_attr__( 'Flex Start', 'fusion-builder' ),
							'center'     => esc_attr__( 'Center', 'fusion-builder' ),
							'flex-end'   => esc_attr__( 'Flex End', 'fusion-builder' ),
							'stretch'    => esc_attr__( 'Stretch', 'fusion-builder' ),
						],
						'icons'       => [
							'flex-start' => '<span class="fusiona-align-top-columns"></span>',
							'center'     => '<span class="fusiona-align-center-columns"></span>',
							'flex-end'   => '<span class="fusiona-align-bottom-columns"></span>',
							'stretch'    => '<span class="fusiona-full-height"></span>',
						],
						'default'     => 'center',
						'dependency'  => [
							[
								'element'  => 'layout',
								'value'    => 'slider',
								'operator' => '!=',
							],
							[
								'element'  => 'layout',
								'value'    => 'cards',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Maximum Columns', 'fusion-builder' ),
						'description' => esc_attr__( 'Select the number of max columns to display. When using the coverflow layout, the total number of columns will also depend on other settings and available space.', 'fusion-builder' ),
						'param_name'  => 'columns',
						'value'       => '5',
						'min'         => '0',
						'max'         => '6',
						'step'        => '0.5',
						'responsive'  => [
							'state'        => 'large',
							'values'       => [
								'small'  => '0',
								'medium' => '0',
							],						
							'descriptions' => [
								'small'  => esc_attr__( 'Set the number of columns per row. Leave at 0 for automatic column breaking.', 'fusion-builder' ),
								'medium' => esc_attr__( 'Set the number of columns per row. Leave at 0 for automatic column breaking.', 'fusion-builder' ),
							],
						],
						'dependency'  => [
							[
								'element'  => 'layout',
								'value'    => 'slider',
								'operator' => '!=',
							],
							[
								'element'  => 'layout',
								'value'    => 'cards',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Column Spacing', 'fusion-builder' ),
						'description' => esc_attr__( 'Insert the spacing between slides without "px". ex: 13.', 'fusion-builder' ),
						'param_name'  => 'column_spacing',
						'value'       => '13',
						'min'         => '0',
						'max'         => '300',
						'step'        => '1',
						'dependency'  => [
							[
								'element'  => 'layout',
								'value'    => 'slider',
								'operator' => '!=',
							],
							[
								'element'  => 'layout',
								'value'    => 'cards',
								'operator' => '!=',
							],
						],						
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Scroll Slides', 'fusion-builder' ),
						'description' => __( 'Insert the number of slides to scroll. Leave at 0 to scroll number of visible slides. <strong>NOTE:</strong> Please make sure that the number of total slides is an even multiple of the number of scrolled slides (2x, 3x, etc.). For larger numbers of scrolled slides, or when using centered slides it is best to have an even larger multiple to ensure smooth looping.', 'fusion-builder' ),
						'param_name'  => 'scroll_items',
						'min'         => '0',
						'max'         => '50',
						'step'        => '1',
						'value'       => '0',
						'dependency'  => [
							[
								'element'  => 'layout',
								'value'    => 'slider',
								'operator' => '!=',
							],
							[
								'element'  => 'layout',
								'value'    => 'cards',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Center Active Slide', 'fusion-builder' ),
						'description' => __( 'Choose to always have the active slide centered. Otherwise it will be left on LTR and right on RTL sites. <strong>NOTE:</strong> To ensure smooth looping, please make sure that the number of total slides is an even multiple of the number of maximum columns + 1 (e.g. 2n + 1).', 'fusion-builder' ),
						'param_name'  => 'centered_slides',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'default'     => 'no',
						'dependency'  => [
							[
								'element'  => 'layout',
								'value'    => 'slider',
								'operator' => '!=',
							],
							[
								'element'  => 'layout',
								'value'    => 'cards',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_html__( 'Mask Edges', 'fusion-builder' ),
						'description' => esc_html__( 'Choose if the edges should be masked with a fade out effect. Navigation arrows will not be displayed, if masked edges are active.', 'fusion-builder' ),
						'param_name'  => 'mask_edges',
						'default'     => 'no',
						'value'       => [
							'yes' => esc_html__( 'Yes', 'fusion-builder' ),
							'no'  => esc_html__( 'No', 'fusion-builder' ),
						],
						'dependency'  => [
							[
								'element'  => 'layout',
								'value'    => 'cards',
								'operator' => '!=',
							],
							[
								'element'  => 'layout',
								'value'    => 'slider',
								'operator' => '!=',
							],
						],
					],					
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_html__( 'Display Shadow', 'fusion-builder' ),
						'description' => esc_html__( 'Choose to show a shadow on the individual slides on coverflow layout or during transitions.', 'fusion-builder' ),
						'param_name'  => 'display_shadow',
						'default'     => 'no',
						'value'       => [
							'yes' => esc_html__( 'Yes', 'fusion-builder' ),
							'no'  => esc_html__( 'No', 'fusion-builder' ),
						],
						'dependency'  => [
							[
								'element'  => 'layout',
								'value'    => 'carousel',
								'operator' => '!=',
							],
							[
								'element'  => 'layout',
								'value'    => 'marquee',
								'operator' => '!=',
							],
							[
								'element'  => 'picture_size',
								'value'    => 'fixed',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Show Navigation', 'fusion-builder' ),
						'description' => __( 'Choose to show navigation buttons on the carousel. <strong>Note:</strong> You can also set the CSS ID (e.g. my-id) for this Carousel and use #my-id-next, #my-id-prev as links on a Button element to navigate through the slides.', 'fusion-builder' ),
						'param_name'  => 'show_nav',
						'value'       => [
							'no'              => esc_attr__( 'None', 'fusion-builder' ),
							'yes'             => esc_attr__( 'Arrows', 'fusion-builder' ),
							'dots'            => esc_attr__( 'Dots', 'fusion-builder' ),
							'arrows_dots'     => esc_attr__( 'Arrows & Dots', 'fusion-builder' ),
							'arrows_progress' => esc_attr__( 'Arrows & Progressbar', 'fusion-builder' ),
						],
						'default'     => 'yes',
						'dependency'  => [
							[
								'element'  => 'layout',
								'value'    => 'marquee',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'dimension',
						'heading'     => esc_attr__( 'Arrow Box Dimensions', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the width and height of the arrow box. Enter values including any valid CSS unit.', 'fusion-builder' ),
						'param_name'  => 'arrow_box',
						'value'       => [
							'arrow_box_width'  => '',
							'arrow_box_height' => '',
						],
						'dependency'  => $arrows_dependency,
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Arrow Icon Size', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the arrow icon size. Enter value including any valid CSS unit, ex: 14px.', 'fusion-builder' ),
						'param_name'  => 'arrow_size',
						'value'       => '',
						'default'     => '',
						'dependency'  => $arrows_dependency,
					],
					[
						'type'        => 'iconpicker',
						'heading'     => esc_attr__( 'Previous Icon', 'fusion-builder' ),
						'param_name'  => 'prev_icon',
						'value'       => '',
						'description' => esc_attr__( 'Click an icon to select, click again to deselect.', 'fusion-builder' ),
						'dependency'  => $arrows_dependency,
					],
					[
						'type'        => 'iconpicker',
						'heading'     => esc_attr__( 'Next Icon', 'fusion-builder' ),
						'param_name'  => 'next_icon',
						'value'       => '',
						'description' => esc_attr__( 'Click an icon to select, click again to deselect.', 'fusion-builder' ),
						'dependency'  => $arrows_dependency,
					],
					[
						'type'        => 'dimension',
						'heading'     => esc_attr__( 'Arrow Position', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the position of the arrow. Enter value including any valid CSS unit, ex: 14px.', 'fusion-builder' ),
						'param_name'  => 'arrow_position',
						'value'       => [
							'arrow_position_horizontal' => '',
							'arrow_position_vertical'   => '',
						],
						'dependency'  => $arrows_dependency,
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_attr__( 'Arrow Border Radius', 'fusion-builder' ),
						'description'      => __( 'Enter values including any valid CSS unit, ex: 10px.', 'fusion-builder' ),
						'param_name'       => 'arrow_border_radius',
						'value'            => [
							'arrow_border_radius_top_left'     => '',
							'arrow_border_radius_top_right'    => '',
							'arrow_border_radius_bottom_right' => '',
							'arrow_border_radius_bottom_left'  => '',
						],
						'dependency'       => array_merge( $arrows_dependency ),
					],
					[
						'type'             => 'subgroup',
						'heading'          => esc_html__( 'Arrows Styling', 'fusion-builder' ),
						'description'      => esc_html__( 'Use filters to see specific type of content.', 'fusion-builder' ),
						'group'            => esc_html__( 'General', 'fusion-builder' ),
						'param_name'       => 'arrow_styling',
						'default'          => 'regular',
						'remove_from_atts' => true,
						'value'            => [
							'regular' => esc_html__( 'Regular', 'fusion-builder' ),
							'hover'   => esc_html__( 'Hover / Active', 'fusion-builder' ),
						],
						'icons'            => [
							'regular' => '<span class="fusiona-regular-state" style="font-size:18px;"></span>',
							'hover'   => '<span class="fusiona-hover-state" style="font-size:18px;"></span>',
						],
						'dependency' => $arrows_dependency,
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Arrow Background Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the background color of arrow.', 'fusion-builder' ),
						'group'       => esc_html__( 'General', 'fusion-builder' ),
						'param_name'  => 'arrow_bgcolor',
						'value'       => '',
						'default'     => $fusion_settings->get( 'carousel_nav_color' ),
						'subgroup'    => [
							'name' => 'arrow_styling',
							'tab'  => 'regular',
						],
						'dependency'  => $arrows_dependency,
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Arrow Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the color of arrow.', 'fusion-builder' ),
						'group'       => esc_html__( 'General', 'fusion-builder' ),
						'param_name'  => 'arrow_color',
						'value'       => '',
						'default'     => '#fff',
						'subgroup'    => [
							'name' => 'arrow_styling',
							'tab'  => 'regular',
						],
						'dependency'  => $arrows_dependency,
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Arrow Background Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the color of arrow.', 'fusion-builder' ),
						'group'       => esc_html__( 'General', 'fusion-builder' ),
						'param_name'  => 'arrow_hover_bgcolor',
						'value'       => '',
						'default'     => $fusion_settings->get( 'carousel_hover_color' ),
						'subgroup'    => [
							'name' => 'arrow_styling',
							'tab'  => 'hover',
						],
						'dependency'  => $arrows_dependency,
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Arrow Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the color of arrow.', 'fusion-builder' ),
						'group'       => esc_html__( 'General', 'fusion-builder' ),
						'param_name'  => 'arrow_hover_color',
						'value'       => '',
						'default'     => '#fff',
						'subgroup'    => [
							'name' => 'arrow_styling',
							'tab'  => 'hover',
						],
						'dependency'  => $arrows_dependency,
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Dots / Progress Bar Position', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the position of the dots / progress bar. Enter value including any valid CSS unit, ex: 14px.', 'fusion-builder' ),
						'param_name'  => 'dots_position',
						'value'       => [
							'above'  => esc_attr__( 'Above', 'fusion-builder' ),
							'top'    => esc_attr__( 'Top', 'fusion-builder' ),
							'bottom' => esc_attr__( 'Bottom', 'fusion-builder' ),
							'below'  => esc_attr__( 'Below', 'fusion-builder' ),
						],
						'default'     => 'bottom',
						'dependency'  => $dots_progress_dependency,
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Progress Bar Size', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the overall size of the progress bar. In pixels.', 'fusion-builder' ),
						'param_name'  => 'progress_bar_size',
						'value'       => '4',
						'min'         => '0',
						'max'         => '50',
						'step'        => '1',
						'dependency'  => [
							[
								'element'  => 'show_nav',
								'value'    => 'arrows_progress',
								'operator' => '=',
							]
						],
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Dots Spacing', 'fusion-builder' ),
						'param_name'  => 'dots_spacing',
						'value'       => '4',
						'min'         => '0',
						'max'         => '100',
						'step'        => '1',
						'description' => esc_attr__( 'In pixels. ', 'fusion-builder' ),
						'dependency'  => $dots_dependency,
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_attr__( 'Dots / Progress Bar Margin', 'fusion-builder' ),
						'description'      => esc_attr__( 'In pixels or percentage, ex: 10px or 10%.', 'fusion-builder' ),
						'param_name'       => 'dots_margin',
						'value'            => [
							'dots_margin_top'    => '',
							'dots_margin_bottom' => '',
						],
						'dependency'       => $dots_progress_dependency,
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Dots Alignment', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the border style of the arrow.', 'fusion-builder' ),
						'param_name'  => 'dots_align',
						'value'       => [
							'left'   => esc_attr__( 'Left', 'fusion-builder' ),
							'center' => esc_attr__( 'Center', 'fusion-builder' ),
							'right'  => esc_attr__( 'Right', 'fusion-builder' ),
						],
						'default'     => 'center',
						'dependency'  => $dots_dependency,
					],
					[
						'type'             => 'subgroup',
						'heading'          => esc_html__( 'Dots / Progress Bar Styling', 'fusion-builder' ),
						'description'      => esc_html__( 'Use filters to see specific type of content.', 'fusion-builder' ),
						'param_name'       => 'dots_styling',
						'default'          => 'regular',
						'group'            => esc_html__( 'General', 'fusion-builder' ),
						'remove_from_atts' => true,
						'value'            => [
							'regular' => esc_html__( 'Regular', 'fusion-builder' ),
							'hover'   => esc_html__( 'Active', 'fusion-builder' ),
						],
						'icons'            => [
							'regular' => '<span class="fusiona-regular-state" style="font-size:18px;"></span>',
							'hover'   => '<span class="fusiona-hover-state" style="font-size:18px;"></span>',
						],
						'dependency'       => $dots_progress_dependency,
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Dots Size', 'fusion-builder' ),
						'param_name'  => 'dots_size',
						'value'       => '8',
						'min'         => '0',
						'max'         => '100',
						'step'        => '1',
						'description' => esc_attr__( 'In pixels. ', 'fusion-builder' ),
						'group'       => esc_attr__( 'General', 'fusion-builder' ),
						'subgroup'    => [
							'name' => 'dots_styling',
							'tab'  => 'regular',
						],
						'dependency'  => $dots_dependency,
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Dots / Progress Bar Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the color of the dots / progress bar background.', 'fusion-builder' ),
						'param_name'  => 'dots_color',
						'value'       => '',
						'default'     => $fusion_settings->get( 'carousel_nav_color' ),
						'group'       => esc_html__( 'General', 'fusion-builder' ),
						'subgroup'    => [
							'name' => 'dots_styling',
							'tab'  => 'regular',
						],
						'dependency'  => $dots_progress_dependency,
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Dots Size', 'fusion-builder' ),
						'param_name'  => 'dots_active_size',
						'value'       => '8',
						'min'         => '0',
						'max'         => '100',
						'step'        => '1',
						'description' => esc_attr__( 'In pixels. ', 'fusion-builder' ),
						'group'       => esc_attr__( 'General', 'fusion-builder' ),
						'subgroup'    => [
							'name' => 'dots_styling',
							'tab'  => 'hover',
						],
						'dependency'  => $dots_dependency,
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Dots / Progress Bar Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the color of the dots / progress bar active background.', 'fusion-builder' ),
						'param_name'  => 'dots_active_color',
						'value'       => '',
						'default'     => $fusion_settings->get( 'carousel_hover_color' ),
						'group'       => esc_html__( 'General', 'fusion-builder' ),
						'subgroup'    => [
							'name' => 'dots_styling',
							'tab'  => 'hover',
						],
						'dependency'  => $dots_progress_dependency,
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Mouse Scroll', 'fusion-builder' ),
						'description' => __( 'Choose to enable mouse drag and/or wheel control control on the carousel. <strong>IMPORTANT:</strong> For easy draggability, when mouse scroll is activated, links will be disabled.', 'fusion-builder' ),
						'param_name'  => 'mouse_scroll',
						'value'       => [
							'yes'        => esc_attr__( 'Drag', 'fusion-builder' ),
							'wheel'      => esc_attr__( 'Wheel', 'fusion-builder' ),
							'drag_wheel' => esc_attr__( 'Drag & Wheel', 'fusion-builder' ),
							'no'         => esc_attr__( 'None', 'fusion-builder' ),
						],
						'default'     => 'no',
						'dependency'  => [
							[
								'element'  => 'layout',
								'value'    => 'marquee',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Free Mode', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose to enable free mode for dragging and scrolling the images arbitrary amounts.', 'fusion-builder' ),
						'param_name'  => 'free_mode',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'default'     => 'no',
						'dependency'  => [
							[
								'element'  => 'layout',
								'value'    => 'marquee',
								'operator' => '!=',
							],
							[
								'element'  => 'mouse_scroll',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Border Width', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the border width of the images. In pixels.', 'fusion-builder' ),
						'param_name'  => 'border_width',
						'value'       => '0',
						'min'         => '0',
						'max'         => '50',
						'step'        => '1',
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Border Style', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the border style of the images.', 'fusion-builder' ),
						'param_name'  => 'border_style',
						'value'       => [
							'solid'  => esc_attr__( 'Solid', 'fusion-builder' ),
							'dashed' => esc_attr__( 'Dashed', 'fusion-builder' ),
							'dotted' => esc_attr__( 'Dotted', 'fusion-builder' ),
							'double' => esc_attr__( 'Double', 'fusion-builder' ),
							'groove' => esc_attr__( 'Groove', 'fusion-builder' ),
							'ridge'  => esc_attr__( 'Ridge', 'fusion-builder' ),
							'inset'  => esc_attr__( 'Inset', 'fusion-builder' ),
							'outset' => esc_attr__( 'Outset', 'fusion-builder' ),
						],
						'default'     => 'solid',
						'dependency'  => [
							[
								'element'  => 'border_width',
								'value'    => '0',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Border Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the border color of the images.', 'fusion-builder' ),
						'param_name'  => 'border_color',
						'value'       => 'var(--awb-color3)',
						'default'     => '',
						'dependency'  => [
							[
								'element'  => 'border_width',
								'value'    => '0',
								'operator' => '!=',
							],
						],
					],				
					'fusion_border_radius_placeholder' => [
						'group'       => esc_attr__( 'General', 'fusion-builder' ),
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Image Lightbox', 'fusion-builder' ),
						'description' => esc_attr__( 'Show image in lightbox. Lightbox must be enabled in Global Options or the image will open up in the same tab by itself.', 'fusion-builder' ),
						'param_name'  => 'lightbox',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'default'     => 'no',
					],
					[
						'type'             => 'select',
						'heading'          => esc_attr__( 'Caption', 'fusion-builder' ),
						'description'      => esc_attr__( 'Choose the caption style.', 'fusion-builder' ),
						'param_name'       => 'caption_style',
						'value'            => [
							'off'      => esc_attr__( 'Off', 'fusion-builder' ),
							'above'    => esc_attr__( 'Above', 'fusion-builder' ),
							'below'    => esc_attr__( 'Below', 'fusion-builder' ),
							'navin'    => esc_attr__( 'Navin', 'fusion-builder' ),
							'dario'    => esc_attr__( 'Dario', 'fusion-builder' ),
							'resa'     => esc_attr__( 'Resa', 'fusion-builder' ),
							'schantel' => esc_attr__( 'Schantel', 'fusion-builder' ),
							'dany'     => esc_attr__( 'Dany', 'fusion-builder' ),
						],
						'default'          => 'off',
						'group'            => esc_attr__( 'Caption', 'fusion-builder' ),
						'child_dependency' => true,
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Image Title Heading Tag', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose HTML tag of the image title, either div or the heading tag, h1-h6.', 'fusion-builder' ),
						'param_name'  => 'caption_title_tag',
						'value'       => [
							'1'   => 'H1',
							'2'   => 'H2',
							'3'   => 'H3',
							'4'   => 'H4',
							'5'   => 'H5',
							'6'   => 'H6',
							'div' => 'DIV',
						],
						'default'     => '2',
						'group'       => esc_attr__( 'Caption', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'caption_style',
								'value'    => 'off',
								'operator' => '!=',
							],
						],
					],
					[
						'type'             => 'typography',
						'heading'          => esc_attr__( 'Image Title Typography', 'fusion-builder' ),
						'description'      => esc_html__( 'Controls the typography of the image title. Leave empty for the global font family.', 'fusion-builder' ),
						'param_name'       => 'caption_title_fonts',
						'choices'          => [
							'font-family'    => 'caption_title_font',
							'font-size'      => 'caption_title_size',
							'text-transform' => 'caption_title_transform',
							'line-height'    => 'caption_title_line_height',
							'letter-spacing' => 'caption_title_letter_spacing',
							'color'          => 'caption_title_color',
						],
						'default'          => [
							'font-family'    => '',
							'variant'        => '400',
							'text-transform' => '',
							'line-height'    => '',
							'letter-spacing' => '',
							'color'          => '',
						],
						'remove_from_atts' => true,
						'global'           => true,
						'group'            => esc_attr__( 'Caption', 'fusion-builder' ),
						'dependency'       => [
							[
								'element'  => 'caption_style',
								'value'    => 'off',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Image Caption Background Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the background color of the caption.', 'fusion-builder' ),
						'param_name'  => 'caption_background_color',
						'value'       => '',
						'group'       => esc_attr__( 'Caption', 'fusion-builder' ),
						'default'     => '',
						'dependency'  => [
							[
								'element'  => 'caption_style',
								'value'    => 'off',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'above',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'below',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'navin',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'dario',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'resa',
								'operator' => '!=',
							],
						],
					],
					[
						'type'             => 'typography',
						'heading'          => esc_attr__( 'Image Caption Typography', 'fusion-builder' ),
						'description'      => esc_html__( 'Controls the typography of the image caption. Leave empty for the global font family.', 'fusion-builder' ),
						'param_name'       => 'caption_text_fonts',
						'choices'          => [
							'font-family'    => 'caption_text_font',
							'font-size'      => 'caption_text_size',
							'text-transform' => 'caption_text_transform',
							'line-height'    => 'caption_text_line_height',
							'letter-spacing' => 'caption_text_letter_spacing',
							'color'          => 'caption_text_color',
						],
						'default'          => [
							'font-family'    => '',
							'variant'        => '400',
							'text-transform' => '',
							'line-height'    => '',
							'letter-spacing' => '',
							'color'          => '',
						],
						'remove_from_atts' => true,
						'global'           => true,
						'group'            => esc_attr__( 'Caption', 'fusion-builder' ),
						'dependency'       => [
							[
								'element'  => 'caption_style',
								'value'    => 'off',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Caption Border Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the color of the caption border.', 'fusion-builder' ),
						'param_name'  => 'caption_border_color',
						'value'       => '',
						'group'       => esc_attr__( 'Caption', 'fusion-builder' ),
						'default'     => 'var(--awb-color1)',
						'dependency'  => [
							[
								'element'  => 'caption_style',
								'value'    => 'off',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'above',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'below',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'navin',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'schantel',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'dany',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Image Overlay Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the color of the image overlay.', 'fusion-builder' ),
						'param_name'  => 'caption_overlay_color',
						'value'       => '',
						'group'       => esc_attr__( 'Caption', 'fusion-builder' ),
						'default'     => $fusion_settings->get( 'primary_color' ),
						'dependency'  => [
							[
								'element'  => 'caption_style',
								'value'    => 'off',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'above',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'below',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Caption Align', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose how to align the caption.', 'fusion-builder' ),
						'param_name'  => 'caption_align',
						'responsive'  => [
							'state' => 'large',
						],
						'value'       => [
							'none'   => esc_attr__( 'Text Flow', 'fusion-builder' ),
							'left'   => esc_attr__( 'Left', 'fusion-builder' ),
							'right'  => esc_attr__( 'Right', 'fusion-builder' ),
							'center' => esc_attr__( 'Center', 'fusion-builder' ),
						],
						'default'     => 'none',
						'group'       => esc_attr__( 'Caption', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'caption_style',
								'value'    => 'off',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'schantel',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'dany',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'navin',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'dario',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'resa',
								'operator' => '!=',
							],
						],
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_attr__( 'Caption Area Margin', 'fusion-builder' ),
						'description'      => esc_attr__( 'In pixels or percentage, ex: 10px or 10%.', 'fusion-builder' ),
						'param_name'       => 'caption_margin',
						'value'            => [
							'caption_margin_top'    => '',
							'caption_margin_right'  => '',
							'caption_margin_bottom' => '',
							'caption_margin_left'   => '',
						],
						'callback'         => [
							'function' => 'fusion_style_block',
						],
						'group'            => esc_attr__( 'Caption', 'fusion-builder' ),
						'dependency'       => [
							[
								'element'  => 'caption_style',
								'value'    => 'off',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'schantel',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'dany',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'navin',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'dario',
								'operator' => '!=',
							],
							[
								'element'  => 'caption_style',
								'value'    => 'resa',
								'operator' => '!=',
							],
						],
					],
					'fusion_margin_placeholder' => [
						'param_name' => 'margin',
						'group'      => esc_attr__( 'General', 'fusion-builder' ),
						'value'      => [
							'margin_top'    => '',
							'margin_right'  => '',
							'margin_bottom' => '',
							'margin_left'   => '',
						],
					],
					[
						'type'        => 'checkbox_button_set',
						'heading'     => esc_attr__( 'Element Visibility', 'fusion-builder' ),
						'param_name'  => 'hide_on_mobile',
						'value'       => fusion_builder_visibility_options( 'full' ),
						'default'     => fusion_builder_default_visibility( 'array' ),
						'description' => __( 'Choose to show or hide the element on small, medium or large screens. You can choose more than one at a time.', 'fusion-builder' ),
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'CSS Class', 'fusion-builder' ),
						'description' => esc_attr__( 'Add a class to the wrapping HTML element.', 'fusion-builder' ),
						'param_name'  => 'class',
						'value'       => '',
						'group'       => esc_attr__( 'General', 'fusion-builder' ),
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'CSS ID', 'fusion-builder' ),
						'description' => esc_attr__( 'Add an ID to the wrapping HTML element.', 'fusion-builder' ),
						'param_name'  => 'id',
						'value'       => '',
						'group'       => esc_attr__( 'General', 'fusion-builder' ),
					],
					'fusion_conditional_render_placeholder' => [],
				],
			],
			'parent'
		)
	);
}
add_action( 'fusion_builder_before_init', 'fusion_element_images' );

/**
 * Map shortcode to Avada Builder.
 */
function fusion_element_fusion_image() {
	fusion_builder_map(
		fusion_builder_frontend_data(
			'FusionSC_ImageCarousel',
			[
				'name'              => esc_attr__( 'Image', 'fusion-builder' ),
				'description'       => esc_attr__( 'Enter some content for this textblock.', 'fusion-builder' ),
				'shortcode'         => 'fusion_image',
				'hide_from_builder' => true,
				'params'            => [
					[
						'type'         => 'upload',
						'heading'      => esc_attr__( 'Image', 'fusion-builder' ),
						'description'  => esc_attr__( 'Upload an image to display.', 'fusion-builder' ),
						'param_name'   => 'image',
						'value'        => '',
						'dynamic_data' => true,
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Image ID', 'fusion-builder' ),
						'description' => esc_attr__( 'Image ID from Media Library.', 'fusion-builder' ),
						'param_name'  => 'image_id',
						'value'       => '',
						'hidden'      => true,
						'callback'    => [
							'function' => 'fusion_ajax',
							'action'   => 'get_fusion_image_carousel',
							'ajax'     => true,
						],
					],
					[
						'type'         => 'textfield',
						'heading'      => esc_attr__( 'Image Title', 'fusion-builder' ),
						'description'  => esc_attr__( 'Enter title text to be displayed on image.', 'fusion-builder' ),
						'param_name'   => 'image_title',
						'value'        => '',
						'dynamic_data' => true,
						'dependency'   => [
							[
								'element'  => 'parent_caption_style',
								'value'    => 'off',
								'operator' => '!=',
							],
						],
					],
					[
						'type'         => 'textfield',
						'heading'      => esc_attr__( 'Image Caption', 'fusion-builder' ),
						'description'  => esc_attr__( 'Enter caption text to be displayed on image.', 'fusion-builder' ),
						'param_name'   => 'image_caption',
						'value'        => '',
						'dynamic_data' => true,
						'dependency'   => [
							[
								'element'  => 'parent_caption_style',
								'value'    => 'off',
								'operator' => '!=',
							],
						],
					],
					[
						'type'         => 'link_selector',
						'heading'      => esc_attr__( 'Image Link', 'fusion-builder' ),
						'description'  => esc_attr__( 'Add the url the image should link to. If lightbox option is enabled, you can also use this to open a different image in the lightbox.', 'fusion-builder' ),
						'param_name'   => 'link',
						'value'        => '',
						'dynamic_data' => true,
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Link Target', 'fusion-builder' ),
						'description' => esc_html__( 'Controls how the link will open.', 'fusion-builder' ),
						'param_name'  => 'linktarget',
						'value'       => [
							'_self'  => esc_html__( 'Same Window/Tab', 'fusion-builder' ),
							'_blank' => esc_html__( 'New Window/Tab', 'fusion-builder' ),
						],
						'default'     => '_self',
					],
					[
						'type'         => 'textfield',
						'heading'      => esc_attr__( 'Image Alt Text', 'fusion-builder' ),
						'description'  => esc_attr__( 'The alt attribute provides alternative information if an image cannot be viewed.', 'fusion-builder' ),
						'param_name'   => 'alt',
						'value'        => '',
						'dynamic_data' => true,
					],
				],
				'tag_name'          => 'div',
				'callback'          => [
					'function' => 'fusion_ajax',
					'action'   => 'get_fusion_image_carousel',
					'ajax'     => true,
				],
			],
			'child'
		)
	);
}
add_action( 'fusion_builder_before_init', 'fusion_element_fusion_image' );
