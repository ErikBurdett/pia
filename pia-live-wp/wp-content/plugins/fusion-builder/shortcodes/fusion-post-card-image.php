<?php
/**
 * Add an element to fusion-builder.
 *
 * @package fusion-builder
 * @since 1.0
 */

if ( fusion_is_element_enabled( 'fusion_post_card_image' ) ) {

	if ( ! class_exists( 'FusionSC_PostCardImage' ) ) {
		/**
		 * Shortcode class.
		 *
		 * @since 3.3
		 */
		class FusionSC_PostCardImage extends Fusion_Element {

			/**
			 * The counter.
			 *
			 * @access private
			 * @since 3.3
			 * @var int
			 */
			public $element_counter = 1;

			/**
			 * An array of generated CSS rules.
			 *
			 * @access protected
			 * @since 3.3
			 * @var array
			 */
			protected $element_css = [];

			/**
			 * Constructor.
			 *
			 * @access public
			 * @since 3.3
			 */
			public function __construct() {
				parent::__construct();
				add_filter( 'fusion_attr_post-card-image', [ $this, 'attr' ] );
				add_shortcode( 'fusion_post_card_image', [ $this, 'render' ] );

				// Ajax mechanism for live editor.
				add_action( 'wp_ajax_get_fusion_post_card_image', [ $this, 'ajax_render' ] );

				add_action( 'fusion_post_card_rendered', [ $this, 'parent_post_card_rendered' ] );
			}

			/**
			 * Gets the default values.
			 *
			 * @static
			 * @access public
			 * @since 3.3
			 * @return array
			 */
			public static function get_element_defaults() {
				$fusion_settings = awb_get_fusion_settings();

				return [
					'hide_on_mobile'             => fusion_builder_default_visibility( 'string' ),
					'class'                      => '',
					'id'                         => '',
					'fallback_image'             => '',
					'fallback_image_id'          => '',
					'layout'                     => 'static',
					'number_of_images'           => '3',
					'show_title'                 => 'yes',
					'show_buttons'               => 'yes',
					'button_layout'              => '',
					'show_cats'                  => 'yes',
					'show_nav'                   => 'yes',
					'show_price'                 => 'yes',
					'show_rating'                => 'yes',
					'show_sale'                  => 'no',
					'show_outofstock'            => 'no',
					'image_link'                 => 'yes',
					'image_link_target'          => '_self',
					'image_link_custom'          => '',
					'show_cart_indicator'        => 'yes',
					'swap_nav_color'             => 'var(--awb-color3)',
					'swap_nav_color_hover'       => 'var(--awb-color4)',
					'swap_nav_height'            => '5',

					// Margins.
					'margin_bottom'              => '',
					'margin_left'                => '',
					'margin_right'               => '',
					'margin_top'                 => '',

					// Border Radius.
					'border_radius_top_left'     => '',
					'border_radius_top_right'    => '',
					'border_radius_bottom_right' => '',
					'border_radius_bottom_left'  => '',

					'crossfade_bg_color'         => '',

					// aspect ratio.
					'aspect_ratio'               => '',
					'custom_aspect_ratio'        => '',
					'aspect_ratio_position'      => '',

					// Animation.
					'animation_type'             => '',
					'animation_direction'        => 'down',
					'animation_speed'            => '0.1',
					'animation_delay'            => '',
					'animation_offset'           => $fusion_settings->get( 'animation_offset' ),
					'animation_color'            => '',
				];
			}

			/**
			 * Render for live editor.
			 *
			 * @static
			 * @access public
			 * @since 3.3
			 * @param array $defaults An array of defaults.
			 * @return void
			 */
			public function ajax_render( $defaults ) {
				check_ajax_referer( 'fusion_load_nonce', 'fusion_load_nonce' );

				$return_data = [];
				// From Ajax Request.
				if ( isset( $_POST['model'] ) && isset( $_POST['model']['params'] ) && ! apply_filters( 'fusion_builder_live_request', false ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					global $post;
					$args           = $_POST['model']['params']; // phpcs:ignore WordPress.Security
					$post_id        = isset( $_POST['post_id'] ) ? $_POST['post_id'] : get_the_ID(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
					$this->defaults = self::get_element_defaults();
					$this->args     = FusionBuilder::set_shortcode_defaults( $this->defaults, $args, 'fusion_post_card_image' );

					// Check if dynamic source is a term and if so emulate.
					if ( isset( $_POST['fusion_meta'] ) ) {
						$meta = fusion_string_to_array( $_POST['fusion_meta'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
						if ( isset( $meta['_fusion']['dynamic_content_preview_type'] ) && 'term' === $meta['_fusion']['dynamic_content_preview_type'] && isset( $meta['_fusion']['preview_term'] ) && '' !== $meta['_fusion']['preview_term'] ) {
							$GLOBALS['wp_query']->is_tax         = true;
							$GLOBALS['wp_query']->is_archive     = true;
							$GLOBALS['wp_query']->queried_object = get_term_by( 'id', $post_id, (string) $meta['_fusion']['preview_term'] );
						}
					}

					if ( $post_id ) {
						$post = get_post( $post_id );
					}

					fusion_set_live_data();
					add_filter( 'fusion_builder_live_request', '__return_true' );

					global $product;
					if ( function_exists( 'wc_get_product' ) && is_null( $product ) ) {
						$product = wc_get_product( $post_id );
					}

					$return_data['fusion_post_card_image'] = $this->get_post_card_image_content( $post_id );
				}

				echo wp_json_encode( $return_data );
				wp_die();
			}

			/**
			 * Render the shortcode.
			 *
			 * @access public
			 * @since 3.3
			 * @param  array  $args   Shortcode parameters.
			 * @param  string $content Content between shortcode.
			 * @return string         HTML output.
			 */
			public function render( $args, $content = '' ) {
				$is_builder = ( function_exists( 'fusion_is_preview_frame' ) && fusion_is_preview_frame() ) || ( function_exists( 'fusion_is_builder_frame' ) && fusion_is_builder_frame() );
				$html       = '';

				$this->defaults = self::get_element_defaults();
				$this->args     = FusionBuilder::set_shortcode_defaults( $this->defaults, $args, 'fusion_post_card_image' );

				$image = $this->get_post_card_image_content( get_the_ID() );

				$html .= '<div ' . FusionBuilder::attributes( 'post-card-image' ) . '>' . $image . '</div>';

				$this->on_render();

				$this->element_counter++;

				return apply_filters( 'fusion_element_post_card_image_content', $html, $args );
			}

			/**
			 * Set necesarry stuff after the first post card has rendered.
			 * For example, counter needs to be reset and also we need to generate styles only once.
			 */
			public function parent_post_card_rendered() {
				$this->element_counter = 1;
			}

			/**
			 * Builds HTML for Post Card Image.
			 *
			 * @static
			 * @access public
			 * @since 3.3
			 * @param  int $post_id Post ID.
			 * @return string
			 */
			public function get_post_card_image_content( $post_id = '' ) {

				if ( ! $post_id ) {
					$post_id = get_the_ID();
				}

				$this->args['in_cart'] = fusion_library()->woocommerce->is_product_in_cart( $post_id );

				$featured_image_size = 'full';

				fusion_library()->get_images_obj()->set_grid_image_meta(
					[
						'layout'       => 'grid',
						'columns'      => FusionBuilder()->post_card_data['columns'],
						'gutter_width' => FusionBuilder()->post_card_data['column_spacing'],
					]
				);

				$image_args = [
					'post_id'                   => $post_id,
					'post_featured_image_size'  => $featured_image_size,
					'post_permalink'            => get_permalink( $post_id ),
					'display_placeholder_image' => 'rollover' !== $this->args['layout'] && ! $this->args['fallback_image_id'] ? true : false,
					'display_woo_sale'          => 'yes' === $this->args['show_sale'],
					'display_woo_outofstock'    => 'yes' === $this->args['show_outofstock'],
					'display_woo_price'         => 'yes' === $this->args['show_price'],
					'display_woo_buttons'       => 'yes' === $this->args['show_buttons'],
					'display_woo_rating'        => 'yes' === $this->args['show_rating'],
					'display_post_categories'   => 'yes' === $this->args['show_cats'] ? 'enable' : 'disable',
					'display_post_title'        => 'yes' === $this->args['show_title'] ? 'enable' : 'disable',
					'display_rollover'          => 'rollover' === $this->args['layout'] ? 'yes' : 'no',
					'image_link'                => 'no' !== $this->args['image_link'] ? true : false,
				];

				if ( 'attachment' === get_post_type() ) {
					$image_args['type'] = 'attachment';
				}

				if ( is_tax() ) {
					$term_link = get_term_link( get_queried_object() );
					if ( ! is_wp_error( $term_link ) ) {
						$image_args['post_permalink']          = $term_link;
						$image_args['post_id']                 = get_queried_object()->term_id;
						$image_args['type']                    = 'taxonomy';
						$image_args['display_post_categories'] = 'disable';
					}
				}

				if ( 'custom' === $this->args['image_link'] ) {
					$image_args['post_permalink'] = $this->args['image_link_custom'];
				}

				if ( 'no' !== $this->args['image_link'] && '_blank' === $this->args['image_link_target'] ) {
					add_filter( 'fusion_builder_post_links_target', [ $this, 'set_rollover_image_link_target' ], 11, 2 );
				}

				// Add necessary class for image variation changes.
				if ( 'product' === get_post_type( $post_id ) ) {
					$image_args['attributes'] = [
						'class' => 'woocommerce-product-gallery__image',
					];

					add_filter( 'wp_get_attachment_image_attributes', [ $this, 'add_product_image_attr' ], 10, 3 );
					add_filter( 'awb_crossfade_image_classes', [ $this, 'add_to_crossfade_attr' ], 10, 2 );
				}

				$image_args['aspect_ratio'] = $this->get_aspect_ratio_value();

				if ( 'static' === $this->args['layout'] || 'rollover' === $this->args['layout'] || is_tax() ) {
					add_filter( 'awb_rollover_button_delimiter', [ $this, 'adjust_button_delimiter' ] );
					$image = avada_first_featured_image_markup( $image_args );
					remove_filter( 'awb_rollover_button_delimiter', [ $this, 'adjust_button_delimiter' ] );
				} else if ( 'swap' === $this->args['layout'] ) {
					$image = $this->swap_render( $post_id );
				} else {
					$image = $this->crossfade_render( $post_id );
				}

				if ( 'no' === $this->args['show_cart_indicator'] && ( 'static' === $this->args['layout'] || 'crossfade' === $this->args['layout'] ) ) {
					$image = preg_replace( '/<div class="cart-loading".*<\/div>/', '', $image );
				}

				if ( false === strpos( $image, '<img' ) ) {
					$image_args['type']    = 'attachment';
					$image_args['post_id'] = $this->args['fallback_image_id'];
					$image = avada_first_featured_image_markup( $image_args );
				}

				if ( 'no' !== $this->args['image_link'] && '_blank' === $this->args['image_link_target'] ) {
					remove_filter( 'fusion_builder_post_links_target', [ $this, 'set_rollover_image_link_target' ], 11 );
				}

				if ( 'product' === get_post_type( $post_id ) ) {
					remove_filter( 'wp_get_attachment_image_attributes', [ $this, 'add_product_image_attr' ], 10, 3 );
					remove_filter( 'awb_crossfade_image_classes', [ $this, 'add_to_crossfade_attr' ], 10, 2 );
				}

				fusion_library()->get_images_obj()->set_grid_image_meta( [] );

				return $image;
			}

			/**
			 * Add data attribute to product image.
			 *
			 * @param array  $attr The attributes.
			 * @param Object $attachment The attachment.
			 * @param string $size The size.
			 * @since 3.8
			 * @return array
			 */
			public function add_product_image_attr( $attr, $attachment, $size ) {
				$full_size = apply_filters( 'woocommerce_gallery_full_size', apply_filters( 'woocommerce_product_thumbnails_large_size', 'full' ) );
				$full_src  = wp_get_attachment_image_src( $attachment->ID, $full_size );

				$attr['data-caption']            = _wp_specialchars( get_post_field( 'post_excerpt', $attachment->ID ), ENT_QUOTES, 'UTF-8', true );
				$attr['data-src']                = esc_url( $full_src[0] );
				$attr['data-large_image']        = esc_url( $full_src[0] );
				$attr['data-large_image_width']  = esc_attr( $full_src[1] );
				$attr['data-large_image_height'] = esc_attr( $full_src[2] );
				return $attr;
			}

			/**
			 * Add data attribute to product image.
			 *
			 * @param array  $classes The classes.
			 * @param Object $attachment The attachment.
			 * @since 3.8
			 * @return array
			 */
			public function add_to_crossfade_attr( $classes, $attachment ) {
				$classes[] = 'woocommerce-product-gallery__image';
				return $classes;
			}

			/**
			 * Change link target if necessary.
			 *
			 * @access public
			 * @since 3.3
			 * @param string $target   The rollover target.
			 * @param int    $post_id  Post ID.
			 * @return string
			 */
			public function set_rollover_image_link_target( $target, $post_id ) {

				// If post has link set in PO then use target from PO as well.
				if ( fusion_get_page_option( 'link_icon_url', $post_id ) ) {
					return $target;
				}

				return 'yes';
			}

			/**
			 * Adjusts the button delimiter.
			 *
			 * @access public
			 * @since 3.13
			 * @param string $delimiter The delimiter
			 * @return string The adjusted delimiter
			 */
			public function adjust_button_delimiter( $delimiter ) {
				 if ( 'clean' === $this->args['button_layout'] ) {
					return '/';
				 } else if ( 'classic' === $this->args['button_layout'] ) {
					return '';
				 }

				 return $delimiter;
			}

			/**
			 * Get markup for crossfade image.
			 *
			 * @access public
			 * @since 3.3
			 * @param  int $post_id Post ID.
			 * @return string
			 */
			public function crossfade_render( $post_id ) {
				global $product;

				$woo_badges = '';
				$image      = '';
				$link_open  = '';
				$link_close = '';

				if ( is_object( $product ) ) {
					ob_start();
					if ( 'yes' === $this->args['show_outofstock'] && method_exists( $product, 'is_in_stock' ) ) {
						get_template_part( 'templates/wc-product-loop-outofstock-flash' );
					}

					if ( 'yes' === $this->args['show_sale'] && function_exists( 'woocommerce_show_product_sale_flash' ) ) {
						woocommerce_show_product_sale_flash();
					}
					$woo_badges = ob_get_clean();

					if ( '' !== $woo_badges ) {
						$woo_badges = '<div class="fusion-woo-badges-wrapper">' . $woo_badges . '</div>';
					}
				}

				ob_start();
				get_template_part( 'templates/wc-thumbnail-classic' );
				$image = ob_get_clean();

				// Tax source doesn't use crossfade layout.
				if ( 'no' !== $this->args['image_link'] ) {
					$link          = 'yes' === $this->args['image_link'] ? get_the_permalink() : $this->args['image_link_custom'];
					$link_icon_url = apply_filters( 'fusion_builder_link_icon_url', '', $post_id );
					$link          = '' !== $link_icon_url ? $link_icon_url : $link;

					$post_links_target = apply_filters( 'fusion_builder_post_links_target', '', $post_id );
					$link_target       = 'yes' === $post_links_target ? ' target="_blank"' : '';

					$link_open  = '<a href="' . esc_url_raw( $link ) . '" title="' . esc_attr( get_the_title() ) . '"' . $link_target . '>';
					$link_close = '</a>';
				}

				return $woo_badges . $link_open . $image . $link_close;
			}

			/**
			 * Get markup for swap image gallery.
			 *
			 * @access public
			 * @since 3.7.15
			 * @param  int $post_id Post ID.
			 * @return string
			 */
			public function swap_render( $post_id ) {
				global $product;

				$woo_badges   = '';
				$swap_images  = '';
				$cart_spinner = '';
				$link_open    = '';
				$link_close   = '';
				$image_ids    = [];
				$post_type    = get_post_type();

				if ( is_object( $product ) ) {
					ob_start();
					if ( 'yes' === $this->args['show_outofstock'] && method_exists( $product, 'is_in_stock' ) ) {
						get_template_part( 'templates/wc-product-loop-outofstock-flash' );
					}

					if ( 'yes' === $this->args['show_sale'] && function_exists( 'woocommerce_show_product_sale_flash' ) ) {
						woocommerce_show_product_sale_flash();
					}
					$woo_badges = ob_get_clean();

					if ( '' !== $woo_badges ) {
						$woo_badges = '<div class="fusion-woo-badges-wrapper">' . $woo_badges . '</div>';
					}
				}				

				$is_product = class_exists( 'WooCommerce' ) && 'product' === $post_type;
				$size       = 'full';

				if ( true === $is_product ) {
					$in_cart = fusion_library()->woocommerce->is_product_in_cart( $post_id );
					$size    = 'woocommerce_thumbnail';					
					$gallery = get_post_meta( $post_id, '_product_image_gallery', true );

					if ( ! empty( $gallery ) ) {
						$image_ids   = explode( ',', $gallery );

						if ( '0' !== $this->args['number_of_images'] ) {
							$image_ids = array_slice( $image_ids, 0, $this->args['number_of_images'] );
						}
					}

					if ( 'yes' === $this->args['show_cart_indicator'] ) {
						$icon_class   = ( $in_cart ) ? 'awb-icon-shopping-cart' : 'awb-icon-spinner';
						$cart_spinner = '<div class="cart-loading"><i class="' . esc_attr( $icon_class ) . '" aria-hidden="true"></i></div>';
					}
				} else {
					$this->args['number_of_images'] = '0' === $this->args['number_of_images'] ? fusion_library()->get_option( 'posts_slideshow_number' ) : $this->args['number_of_images'];
					
					for ( $i = 2; $i <= (int) $this->args['number_of_images']; $i++ ) {
						$featured_image = fusion_get_featured_image_id( 'featured-image-' . $i, $post_type );

						if ( $featured_image ) {
							$image_ids[] = fusion_get_featured_image_id( 'featured-image-' . $i, $post_type );
						}
					}
				}

				$swap_images = '<div id="awb-pc-image__swap-grid--' . $this->element_counter . '" class="awb-pc-image__swap-grid">';
				$swap_nav    = '<div class="awb-pc-image__swap-nav">';
				for ( $i = 0; $i < count( $image_ids ); $i++ ) {
					$swap_images .= '<div class="awb-pc-image__swap-image" data-index="' . esc_attr( $i ) . '" data-src="' . wp_get_attachment_image_url( $image_ids[ $i ], $size ) . '" data-srcset="' . wp_get_attachment_image_srcset( $image_ids[ $i ], $size ) . '"></div>';
					$swap_nav    .= '<div class="awb-pc-image__swap-nav-item" data-index="' . esc_attr( $i ) . '"></div>';
				}
				$swap_images .= '</div>';
				$swap_nav    .= '</div>';

				if ( '0' === $this->args['swap_nav_height'] || 1 === count( $image_ids ) ) {
					$swap_nav = '';
				}

				if ( 1 <= count( $image_ids ) ) {
					$swap_nav    .= '<div class="awb-pc-image__swap-nav-mobile">';
					$swap_nav    .= '<div class="awb-pc-image__prev" tabindex="0" role="button" aria-label="' . esc_attr__( 'Previous Image' ) . '" aria-controls="awb-pc-image__swap-grid--' . $this->element_counter . '"><i class="awb-icon-angle-left"></i></div>';
					$swap_nav    .= '<div class="awb-pc-image__next" tabindex="0" role="button" aria-label="' . esc_attr__( 'Next Image' ) . '" aria-controls="awb-pc-image__swap-grid--' . $this->element_counter . '"><i class="awb-icon-angle-right"></i></div>';
					$swap_nav    .= '</div>';
				}

				$thumb_image = get_the_post_thumbnail( $post_id, $size );

				if ( ! $thumb_image && function_exists( 'wc_placeholder_img_src' ) && wc_placeholder_img_src() ) {
					$thumb_image = wc_placeholder_img( $size );
				}

				$thumb_image = str_replace( 'class="', 'class="awb-pc-image__thumb-image ', $thumb_image );

				$images = '<div class="awb-pc-image__swap-images featured-image woocommerce-product-gallery__image">';
				$images .= $swap_images . $swap_nav . $thumb_image . $cart_spinner;
				$images .= '</div>';		

				// Tax source doesn't use swap layout.
				if ( 'no' !== $this->args['image_link'] ) {
					$link          = 'yes' === $this->args['image_link'] ? get_the_permalink() : $this->args['image_link_custom'];
					$link_icon_url = apply_filters( 'fusion_builder_link_icon_url', '', $post_id );
					$link          = '' !== $link_icon_url ? $link_icon_url : $link;

					$post_links_target = apply_filters( 'fusion_builder_post_links_target', '', $post_id );
					$link_target       = 'yes' === $post_links_target ? ' target="_blank"' : '';

					$link_open  = '<a href="' . esc_url_raw( $link ) . '" title="' . esc_attr( get_the_title() ) . '"' . $link_target . '>';
					$link_close = '</a>';
				}

				return $woo_badges . $link_open . $images . $link_close;				

			}

			/**
			 * Builds the array of atributes.
			 *
			 * @access public
			 * @since 3.3
			 * @return array
			 */
			public function attr() {
				$fusion_settings = awb_get_fusion_settings();

				$woo_box_design_class = $this->args['button_layout'] ? 'fusion-' . esc_attr( $this->args['button_layout'] ) . '-product-image-wrapper' : 'fusion-' . esc_attr( $fusion_settings->get( 'woocommerce_product_box_design', false, 'classic' ) ) . '-product-image-wrapper';

				$attr = fusion_builder_visibility_atts(
					$this->args['hide_on_mobile'],
					[
						'class'       => $woo_box_design_class . ' fusion-woo-product-image fusion-post-card-image fusion-post-card-image-' . $this->element_counter,
						'data-layout' => $this->args['layout'],
						'style'       => $this->get_style_variables(),
					]
				);

				if ( $this->args['animation_type'] ) {
					$attr = Fusion_Builder_Animation_Helper::add_animation_attributes( $this->args, $attr );
				}

				if ( $this->args['in_cart'] ) {
					$attr['class'] .= ' fusion-item-in-cart';
				}

				if ( 'rollover' === $this->args['layout'] && 'no' === $this->args['image_link'] ) {
					$attr['class'] .= ' fusion-disable-link';
				}

				if ( $this->args['class'] ) {
					$attr['class'] .= ' ' . $this->args['class'];
				}

				if ( $this->args['id'] ) {
					$attr['id'] = $this->args['id'];
				}

				if ( 'crossfade' === $this->args['layout'] || 'swap' === $this->args['layout'] ) {
					$attr['class'] .= ' product-images';
				}

				if ( '' !== $this->args['aspect_ratio'] ) {
					$attr['class'] .= ' has-aspect-ratio';
				}

				// Add necessary class for image variation changes.
				if ( 'product' === get_post_type( get_the_ID() ) ) {
					$attr['class'] .= ' images';
				}

				return $attr;
			}

			/**
			 * Get the style variables.
			 *
			 * @access protected
			 * @since 3.9
			 * @return string
			 */
			protected function get_style_variables() {
				$custom_vars      = [];
				$css_vars_options = [
					'margin_top'                 => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'margin_right'               => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'margin_bottom'              => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'margin_left'                => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'crossfade_bg_color'         => [ 'callback' => [ 'Fusion_Sanitize', 'color' ] ],
					'swap_nav_color'             => [ 'callback' => [ 'Fusion_Sanitize', 'color' ] ],
					'swap_nav_color_hover'       => [ 'callback' => [ 'Fusion_Sanitize', 'color' ] ],
					'swap_nav_height'            => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'border_radius_top_left'     => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'border_radius_top_right'    => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'border_radius_bottom_right' => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'border_radius_bottom_left'  => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'aspect_ratio_position',
				];

				if ( ! $this->is_default( 'aspect_ratio' ) ) {
					$custom_vars['aspect_ratio'] = $this->get_aspect_ratio_value();
				}

				return $this->get_css_vars_for_options( $css_vars_options ) . $this->get_custom_css_vars( $custom_vars );
			}

			/**
			 * Get the aspect ratio css value. Returns empty if not set.
			 *
			 * @since 3.9
			 * @return string
			 */
			private function get_aspect_ratio_value() {
				$value = '';

				if ( 'custom' === $this->args['aspect_ratio'] && '' !== $this->args['custom_aspect_ratio'] ) {
					$value = 100 / $this->args['custom_aspect_ratio'];
				} elseif ( ! empty( $this->args['aspect_ratio'] ) ) {
					$aspect_ratio = explode( '-', $this->args['aspect_ratio'] );
					$width        = isset( $aspect_ratio[0] ) ? $aspect_ratio[0] : '';
					$height       = isset( $aspect_ratio[1] ) ? $aspect_ratio[1] : '';

					$value = $width . ' / ' . $height;
				}

				return $value;
			}

			/**
			 * Sets the necessary scripts.
			 *
			 * @access public
			 * @since 3.11.15
			 * @return void
			 */
			public function on_first_render() {
				// Skip if empty.
				if ( null === $this->args || empty( $this->args ) ) {
					return;
				}

				Fusion_Dynamic_JS::enqueue_script(
					'awb-post-card-image',
					FusionBuilder::$js_folder_url . '/general/awb-post-card-image.js',
					FusionBuilder::$js_folder_path . '/general/awb-post-card-image.js',
					[ 'jquery' ],
					FUSION_BUILDER_VERSION,
					true
				);
			}

			/**
			 * Load base CSS.
			 *
			 * @access public
			 * @since 3.3
			 * @return void
			 */
			public function add_css_files() {
				FusionBuilder()->add_element_css( FUSION_BUILDER_PLUGIN_DIR . 'assets/css/shortcodes/post-card-image.min.css' );

				Fusion_Media_Query_Scripts::$media_query_assets[] = [
					'awb-post-card-image-sm',
					FUSION_BUILDER_PLUGIN_DIR . 'assets/css/media/post-card-image-sm.min.css',
					[],
					FUSION_BUILDER_VERSION,
					Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-small' ),
				];				
			}
		}
	}

	new FusionSC_PostCardImage();

}

/**
 * Map shortcode to Avada Builder.
 *
 * @since 3.3
 */
function fusion_element_post_card_image() {
	$fusion_settings = awb_get_fusion_settings();

	fusion_builder_map(
		fusion_builder_frontend_data(
			'FusionSC_PostCardImage',
			[
				'name'      => esc_attr__( 'Post Card Image', 'fusion-builder' ),
				'shortcode' => 'fusion_post_card_image',
				'icon'      => 'fusiona-post-cards-image',
				'templates' => [ 'post_cards' ],
				'component' => true,
				'help_url'  => 'https://avada.com/documentation/post-card-image-element/',
				'params'    => [
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Layout', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose image layout.', 'fusion-builder' ),
						'param_name'  => 'layout',
						'value'       => [
							'static'    => esc_attr__( 'Static', 'fusion-builder' ),
							'crossfade' => esc_attr__( 'Crossfade', 'fusion-builder' ),
							'swap'      => esc_attr__( 'Swap', 'fusion-builder' ),
							'rollover'  => esc_attr__( 'Rollover', 'fusion-builder' ),
						],
						'default'     => 'static',
						'callback'    => [
							'function' => 'fusion_ajax',
							'action'   => 'get_fusion_post_card_image',
							'ajax'     => true,
						],
					],
					[
						'type'         => 'upload',
						'heading'      => esc_attr__( 'Fallback Image', 'fusion-builder' ),
						'description'  => esc_attr__( 'Upload a fallback image to display if no featured image is available.', 'fusion-builder' ),
						'param_name'   => 'fallback_image',
						'value'        => '',
						'dynamic_data' => true,
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Fallback Image ID', 'fusion-builder' ),
						'description' => esc_attr__( 'Image ID from Media Library.', 'fusion-builder' ),
						'param_name'  => 'fallback_image_id',
						'value'       => '',
						'hidden'      => true,
					],					
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Number of Swap Images', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the number of images to be used in image swap. Set to 0, to use all.', 'fusion-builder' ),
						'param_name'  => 'number_of_images',
						'value'       => '3',
						'min'         => '0',
						'max'         => '20',
						'step'        => '1',
						'dependency'  => [
							[
								'element'  => 'layout',
								'value'    => 'swap',
								'operator' => '==',
							],
						],
						'callback'    => [
							'function' => 'fusion_ajax',
							'action'   => 'get_fusion_post_card_image',
							'ajax'     => true,
						],
					],
					[
						'type'        => 'select',
						'heading'     => esc_attr__( 'Image Aspect Ratio', 'fusion-builder' ),
						'description' => esc_attr__( 'Select an aspect ratio for the image.', 'fusion-builder' ),
						'param_name'  => 'aspect_ratio',
						'value'       => [
							''       => esc_attr__( 'Automatic', 'fusion-builder' ),
							'1-1'    => esc_attr__( '1:1', 'fusion-builder' ),
							'2-1'    => esc_attr__( '2:1', 'fusion-builder' ),
							'2-3'    => esc_attr__( '2:3', 'fusion-builder' ),
							'3-1'    => esc_attr__( '3:1', 'fusion-builder' ),
							'3-2'    => esc_attr__( '3:2', 'fusion-builder' ),
							'4-1'    => esc_attr__( '4:1', 'fusion-builder' ),
							'4-3'    => esc_attr__( '4:3', 'fusion-builder' ),
							'5-4'    => esc_attr__( '5:4', 'fusion-builder' ),
							'16-9'   => esc_attr__( '16:9', 'fusion-builder' ),
							'9-16'   => esc_attr__( '9:16', 'fusion-builder' ),
							'21-9'   => esc_attr__( '21:9', 'fusion-builder' ),
							'9-21'   => esc_attr__( '9:21', 'fusion-builder' ),
							'custom' => esc_attr__( 'Custom', 'fusion-builder' ),
						],
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Custom Aspect Ratio', 'fusion-builder' ),
						'description' => esc_attr__( 'Set a custom aspect ratio for the image.', 'fusion-builder' ),
						'param_name'  => 'custom_aspect_ratio',
						'min'         => 1,
						'max'         => 500,
						'value'       => 100,
						'dependency'  => [
							[
								'element'  => 'aspect_ratio',
								'value'    => 'custom',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'image_focus_point',
						'heading'     => esc_attr__( 'Image Focus Point', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the image focus point by dragging the blue dot.', 'fusion-builder' ),
						'param_name'  => 'aspect_ratio_position',
						'image'       => 'element_content',
						'image_id'    => 'image_id',
						'dependency'  => [
							[
								'element'  => 'aspect_ratio',
								'value'    => '',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Show Cart Indicator', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose to show or hide the add to cart / added to cart indicator.', 'fusion-builder' ),
						'param_name'  => 'show_cart_indicator',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'default'     => 'yes',
						'dependency'  => [
							[
								'element'  => 'layout',
								'value'    => 'rollover',
								'operator' => '!=',
							],
						],						
						'callback'    => [
							'function' => 'fusion_ajax',
							'action'   => 'get_fusion_post_card_image',
							'ajax'     => true,
						],
					],					
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Show Title', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose to show or hide the title.', 'fusion-builder' ),
						'param_name'  => 'show_title',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'default'     => 'yes',
						'dependency'  => [
							[
								'element'  => 'layout',
								'value'    => 'rollover',
								'operator' => '==',
							],
						],
						'callback'    => [
							'function' => 'fusion_ajax',
							'action'   => 'get_fusion_post_card_image',
							'ajax'     => true,
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Show Categories', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose to show or hide the categories.', 'fusion-builder' ),
						'param_name'  => 'show_cats',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'default'     => 'yes',
						'dependency'  => [
							[
								'element'  => 'layout',
								'value'    => 'rollover',
								'operator' => '==',
							],
						],
						'callback'    => [
							'function' => 'fusion_ajax',
							'action'   => 'get_fusion_post_card_image',
							'ajax'     => true,
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Show Price', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose to show or hide the price. Applies only to WooCommerce products.', 'fusion-builder' ),
						'param_name'  => 'show_price',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'default'     => 'yes',
						'dependency'  => [
							[
								'element'  => 'layout',
								'value'    => 'rollover',
								'operator' => '==',
							],
						],
						'callback'    => [
							'function' => 'fusion_ajax',
							'action'   => 'get_fusion_post_card_image',
							'ajax'     => true,
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Show Rating', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose to show or hide the rating. Applies only to WooCommerce products.', 'fusion-builder' ),
						'param_name'  => 'show_rating',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'default'     => 'yes',
						'dependency'  => [
							[
								'element'  => 'layout',
								'value'    => 'rollover',
								'operator' => '==',
							],
						],
						'callback'    => [
							'function' => 'fusion_ajax',
							'action'   => 'get_fusion_post_card_image',
							'ajax'     => true,
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Show Sale Badge', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose to show or hide the sale badge. Applies only to WooCommerce products.', 'fusion-builder' ),
						'param_name'  => 'show_sale',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'default'     => 'no',
						'callback'    => [
							'function' => 'fusion_ajax',
							'action'   => 'get_fusion_post_card_image',
							'ajax'     => true,
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Show Out Of Stock Badge', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose to show or hide the out of stock badge. Applies only to WooCommerce products.', 'fusion-builder' ),
						'param_name'  => 'show_outofstock',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'default'     => 'no',
						'callback'    => [
							'function' => 'fusion_ajax',
							'action'   => 'get_fusion_post_card_image',
							'ajax'     => true,
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Show Woo Buttons', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose to show or hide Add to Cart / Details buttons on the rollover. Applies only to WooCommerce products.', 'fusion-builder' ),
						'param_name'  => 'show_buttons',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'default'     => 'yes',
						'dependency'  => [
							[
								'element'  => 'layout',
								'value'    => 'rollover',
								'operator' => '==',
							],
						],
						'callback'    => [
							'function' => 'fusion_ajax',
							'action'   => 'get_fusion_post_card_image',
							'ajax'     => true,
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Woo Button Layout', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose the layout for the Add to Cart / Details buttons on the rollover. Applies only to WooCommerce products.', 'fusion-builder' ),
						'param_name'  => 'button_layout',
						'value'       => [
							''        => esc_attr__( 'Default', 'fusion-builder' ),
							'classic' => esc_attr__( 'Classic', 'fusion-builder' ),
							'clean'   => esc_attr__( 'Clean', 'fusion-builder' ),
						],
						'default'     => '',
						'dependency'  => [
							[
								'element'  => 'layout',
								'value'    => 'rollover',
								'operator' => '==',
							],
						],
						'callback'    => [
							'function' => 'fusion_ajax',
							'action'   => 'get_fusion_post_card_image',
							'ajax'     => true,
						],
					],					
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Link Image', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose to link image to default or custom link or disable link completely.', 'fusion-builder' ),
						'param_name'  => 'image_link',
						'value'       => [
							'yes'    => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'     => esc_attr__( 'No', 'fusion-builder' ),
							'custom' => esc_attr__( 'Custom', 'fusion-builder' ),
						],
						'default'     => 'yes',
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Link Target', 'fusion-builder' ),
						'description' => esc_html__( 'Controls how the link will open.', 'fusion-builder' ),
						'param_name'  => 'image_link_target',
						'value'       => [
							'_self'  => esc_html__( 'Same Window/Tab', 'fusion-builder' ),
							'_blank' => esc_html__( 'New Window/Tab', 'fusion-builder' ),
						],
						'default'     => '_self',
						'dependency'  => [
							[
								'element'  => 'image_link',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
					],
					[
						'type'         => 'textfield',
						'heading'      => esc_attr__( 'Custom Link', 'fusion-builder' ),
						'description'  => esc_attr__( 'Choos to link image to default or custom link or disable link completely.', 'fusion-builder' ),
						'param_name'   => 'image_link_custom',
						'value'        => '',
						'dynamic_data' => true,
						'dependency'   => [
							[
								'element'  => 'image_link',
								'value'    => 'custom',
								'operator' => '==',
							],
						],
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
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_attr__( 'Margin', 'fusion-builder' ),
						'description'      => esc_attr__( 'In pixels or percentage, ex: 10px or 10%.', 'fusion-builder' ),
						'param_name'       => 'margin',
						'value'            => [
							'margin_top'    => '',
							'margin_right'  => '',
							'margin_bottom' => '',
							'margin_left'   => '',
						],
						'group'            => esc_attr__( 'Design', 'fusion-builder' ),
						'callback'         => [
							'function' => 'fusion_style_block',
						],
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_html__( 'Border Radius', 'fusion-builder' ),
						'description'      => esc_html__( 'Enter values including any valid CSS unit, ex: 10px.', 'fusion-builder' ),
						'param_name'       => 'border_radius',
						'group'            => esc_attr__( 'Design', 'fusion-builder' ),
						'callback'         => [
							'function' => 'fusion_style_block',
							'args'     => [

								'dimension' => true,
							],
						],
						'value'            => [
							'border_radius_top_left'     => '',
							'border_radius_top_right'    => '',
							'border_radius_bottom_right' => '',
							'border_radius_bottom_left'  => '',
						],
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Crossfade Background Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the background color of crossfade image container.', 'fusion-builder' ),
						'param_name'  => 'crossfade_bg_color',
						'value'       => '',
						'default'     => $fusion_settings->get( 'title_border_color' ),
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'layout',
								'value'    => 'crossfade',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'range',
						'heading'     => esc_attr__( 'Swap Indicator Height', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the height of the swap indicator.', 'fusion-builder' ),
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'param_name'  => 'swap_nav_height',
						'min'         => 0,
						'max'         => 30,
						'step'        => 0.5,
						'value'       => 5,
						'dependency'  => [
							[
								'element'  => 'layout',
								'value'    => 'swap',
								'operator' => '==',
							],
						],
					],					
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Swap Inidcator Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the color of the swap indicator.', 'fusion-builder' ),
						'param_name'  => 'swap_nav_color',
						'value'       => '',
						'default'     => 'var(--awb-color3)',
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'layout',
								'value'    => 'swap',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Swap Inidcator Hover Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the hover color of the swap inidcator.', 'fusion-builder' ),
						'param_name'  => 'swap_nav_color_hover',
						'value'       => '',
						'default'     => 'var(--awb-color4)',
						'group'       => esc_attr__( 'Design', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'layout',
								'value'    => 'swap',
								'operator' => '==',
							],
						],
					],										
					'fusion_conditional_render_placeholder' => [],
					'fusion_animation_placeholder' => [
						'preview_selector' => '.fusion-post-card-image',
					],
				],
				'callback'  => [
					'function' => 'fusion_ajax',
					'action'   => 'get_fusion_post_card_image',
					'ajax'     => true,
				],
			]
		)
	);
}
add_action( 'fusion_builder_wp_loaded', 'fusion_element_post_card_image' );
