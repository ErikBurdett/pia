<?php
/**
 * Add an element to fusion-builder.
 *
 * @package fusion-builder
 * @since 1.0
 */

if ( fusion_is_element_enabled( 'fusion_youtube' ) ) {

	if ( ! class_exists( 'FusionSC_Youtube' ) ) {
		/**
		 * Shortcode class.
		 *
		 * @since 1.0
		 */
		class FusionSC_Youtube extends Fusion_Element {

			/**
			 * The video counter.
			 *
			 * @access private
			 * @since 5.3
			 * @var int
			 */
			private $video_counter = 1;

			/**
			 * Constructor.
			 *
			 * @access public
			 * @since 1.0
			 */
			public function __construct() {
				parent::__construct();
				add_filter( 'fusion_attr_youtube-shortcode', [ $this, 'attr' ] );
				add_shortcode( 'fusion_youtube', [ $this, 'render' ] );
			}

			/**
			 * Gets the default values.
			 *
			 * @static
			 * @access public
			 * @since 2.0.0
			 * @return array
			 */
			public static function get_element_defaults() {
				$fusion_settings = awb_get_fusion_settings();

				return [
					'api_params'                 => '',
					'autoplay'                   => 'false',
					'mute'                       => 'false',
					'alignment'                  => '',
					'controls'                   => '',
					'center'                     => 'no',
					'class'                      => '',
					'css_id'                     => '',
					'end_time'                   => '',
					'height'                     => 360,
					'loop'                       => 'false',
					'margin_top'                 => '',
					'margin_bottom'              => '',
					'hide_on_mobile'             => fusion_builder_default_visibility( 'string' ),
					'id'                         => '',
					'start_time'                 => '',
					'title_attribute'            => '',
					'thumbnail_size'             => 'auto',
					'width'                      => 600,
					'video_facade'               => $fusion_settings->get( 'video_facade' ),
					'video_facade_no_cookie'     => 'on',
					'structured_data'            => '',
					'video_title'                => '',
					'video_desc'                 => '',
					'video_duration'             => '',
					'video_upload_date'          => '',
					'video_upload_date_timezone' => '',
				];
			}

			/**
			 * Render the shortcode
			 *
			 * @access public
			 * @since 1.0
			 * @param  array  $args    Shortcode parameters.
			 * @param  string $content Content between shortcode.
			 * @return string          HTML output.
			 */
			public function render( $args, $content = '' ) {

				// Make videos 16:9 by default.
				if ( isset( $args['width'] ) && '' !== $args['width'] && ( ! isset( $args['height'] ) || '' === $args['height'] ) ) {
					$args['height'] = round( (float) $args['width'] * 9 / 16 );
				}

				if ( isset( $args['height'] ) && '' !== $args['height'] && ( ! isset( $args['width'] ) || '' === $args['width'] ) ) {
					$args['width'] = round( (float) $args['height'] * 16 / 9 );
				}

				$defaults = FusionBuilder::set_shortcode_defaults( self::get_element_defaults(), $args, 'fusion_youtube' );
				$content  = apply_filters( 'fusion_shortcode_content', $content, 'fusion_youtube', $args );

				$defaults['height'] = FusionBuilder::validate_shortcode_attr_value( $defaults['height'], '' );
				$defaults['width']  = FusionBuilder::validate_shortcode_attr_value( $defaults['width'], '' );

				$this->args     = $defaults;
				$this->defaults = self::get_element_defaults();

				$this->args['margin_bottom'] = FusionBuilder::validate_shortcode_attr_value( $this->args['margin_bottom'], 'px' );
				$this->args['margin_top']    = FusionBuilder::validate_shortcode_attr_value( $this->args['margin_top'], 'px' );

				// Make sure only the video ID is passed to the iFrame.
				$pattern = '~(?:http|https|)(?::\/\/|)(?:www.|)(?:youtu\.be\/|youtube\.com(?:\/embed\/|\/v\/|\/watch\?v=|\/ytscreeningroom\?v=|\/feeds\/api\/videos\/|\/user\S*[^\w\-\s]|\S*[^\w\-\s]))([\w\-]{11})[a-z0-9;:@#?&%=+\/\$_.-]*~i';
				$id      = $this->args['id'];
				preg_match( $pattern, $id, $matches );
				if ( isset( $matches[1] ) ) {
					$id = $matches[1];
				}

				// Structured Data attributes.
				$ds_attr = '';
				if ( 'on' === $this->args['structured_data'] ) {
					$ds_attr = ' itemscope itemtype="http://schema.org/VideoObject"';
				}

				$html = '<div ' . FusionBuilder::attributes( 'youtube-shortcode' ) . $ds_attr . '>';

				// Structured Data.
				if ( 'on' === $this->args['structured_data'] ) {
					$video_duration = '' !== $this->args['video_duration'] ? $this->get_duration( $this->args['video_duration'] ) : '';
					$html          .= $video_duration ? '<meta itemprop="duration" content="' . esc_attr( $video_duration ) . '" />' : '';

					$html .= '' !== $this->args['video_title'] ? '<meta itemprop="name" content="' . esc_attr( $this->args['video_title'] ) . '" />' : '';
					$html .= '' !== $this->args['video_desc'] ? '<meta itemprop="description" content="' . esc_attr( $this->args['video_desc'] ) . '" />' : '';
					if ( '' !== $this->args['video_upload_date'] ) {
						$this->args['video_upload_date'] = str_replace( ' ', 'T', $this->args['video_upload_date'] );
						$this->args['video_upload_date'] .= $this->args['video_upload_date_timezone'];

						$html .= '<meta itemprop="uploadDate" content="' . esc_attr( $this->args['video_upload_date'] ) . '" />';
					}
					$html .= '<meta itemprop="thumbnailUrl" content="https://i3.ytimg.com/vi/' . esc_attr( $id ) . '/hqdefault.jpg" />';
					$html .= '<meta itemprop="embedUrl" content="https://www.youtube.com/embed/' . esc_attr( $id ) . '" />';
				}

				$html .= '<div class="video-shortcode">';
				$title = $this->args['title_attribute'] ? $this->args['title_attribute'] : 'YouTube video player ' . $this->video_counter;

				if ( 'true' === $this->args['mute'] || true === $this->args['mute'] || 'yes' === $this->args['mute'] ) {
					if ( false === strpos( $this->args['api_params'], 'mute=1' ) ) {
						$this->args['api_params'] .= '&mute=1';
					}
				}

				if ( 'true' === $this->args['loop'] ) {
					if ( false === strpos( $this->args['api_params'], 'loop=1' ) ) {
						$this->args['api_params'] .= '&loop=1&playlist=' . esc_attr( $id );
					}
				}

				if ( 'false' === $this->args['controls'] ) {
					if ( false === strpos( $this->args['api_params'], 'controls=0' ) ) {
						$this->args['api_params'] .= '&controls=0';
					}
				}

				if ( $this->args['start_time'] ) {
					if ( false === strpos( $this->args['api_params'], 'start=' ) ) {
						$this->args['api_params'] .= '&start=' . esc_attr( $this->args['start_time'] );
					}
				}

				if ( $this->args['end_time'] ) {
					if ( false === strpos( $this->args['api_params'], 'end=' ) ) {
						$this->args['api_params'] .= '&end=' . esc_attr( $this->args['end_time'] );
					}
				}

				if ( 'on' === $this->args['video_facade'] ) {
					$api_params = ( false === strpos( $this->args['api_params'], 'enablejsapi=1' ) ? $this->args['api_params'] . '&enablejsapi=1' : $this->args['api_params'] );
					$class      = ( $defaults['height'] > $defaults['width'] ) ? 'portrait' : 'landscape';
					$html      .= '<lite-youtube videoid="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . '" params="wmode=transparent&autoplay=1' . esc_attr( $api_params ) . '" title="' . esc_attr( $title ) . '" data-button-label="' . esc_attr__( 'Play Video', 'fusion-builder' ) . '" width="' . esc_attr( $this->args['width'] ) . '" height="' . esc_attr( $this->args['height'] ) . '" data-thumbnail-size="' . esc_attr( $this->args['thumbnail_size'] ) . '" data-no-cookie="' . esc_attr( $this->args['video_facade_no_cookie'] ) . '"></lite-youtube>';
				} else {
					$iframe = '<iframe title="' . esc_attr( $title ) . '" src="https://www.youtube.com/embed/' . esc_attr( $id ) . '?wmode=transparent&autoplay=0' . esc_attr( $this->args['api_params'] ) . '" width="' . esc_attr( $this->args['width'] ) . '" height="' . esc_attr( $this->args['height'] ) . '" allowfullscreen allow="autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture;"></iframe>';

					if ( 0 < $defaults['height'] && 0 < $defaults['width'] ) {
						$iframe = '<div class="fluid-width-video-wrapper" style="padding-top:' . round( $defaults['height'] / $defaults['width'] * 100, 2 ) . '%;" >' . $iframe . '</div>';
					}

					$html .= $iframe;

					$html = fusion_library()->images->apply_global_selected_lazy_loading_to_iframe( $html );
				}

				$html .= '</div></div>';

				$this->on_render();

				$this->video_counter++;

				return apply_filters( 'fusion_element_youtube_content', $html, $args );
			}

			/**
			 * Parses the arguments.
			 *
			 * @access public
			 * @since 1.0
			 * @return array
			 */
			public function attr() {
				$attr = fusion_builder_visibility_atts(
					$this->args['hide_on_mobile'],
					[
						'class' => 'fusion-video fusion-youtube',
						'style' => $this->get_style_vars(),
					]
				);

				if ( 'yes' === $this->args['center'] ) {
					$attr['class'] .= ' center-video';
				}

				if ( '' !== $this->args['alignment'] && ! fusion_element_rendering_is_flex() ) {
					$attr['class'] .= ' fusion-align' . $this->args['alignment'];
				}

				if ( 'true' === $this->args['autoplay'] || true === $this->args['autoplay'] || 'yes' === $this->args['autoplay'] ) {
					$attr['data-autoplay'] = 1;
				}

				if ( $this->args['class'] ) {
					$attr['class'] .= ' ' . $this->args['class'];
				}

				if ( $this->args['css_id'] ) {
					$attr['id'] = $this->args['css_id'];
				}

				return $attr;
			}

			/**
			 * Get style variables.
			 *
			 * @since 3.9
			 * @return string
			 */
			public function get_style_vars() {
				$custom_css_vars = [];

				if ( 'yes' !== $this->args['center'] ) {
					$custom_css_vars['max-width']  = $this->args['width'] . 'px';
					$custom_css_vars['max-height'] = $this->args['height'] . 'px';
				}

				if ( '' !== $this->args['alignment'] ) {
					if ( fusion_element_rendering_is_flex() ) {
						// RTL adjust.
						if ( is_rtl() && 'center' !== $this->args['alignment'] ) {
							$this->args['alignment'] = 'left' === $this->args['alignment'] ? 'right' : 'left';
						}

						if ( 'left' === $this->args['alignment'] ) {
							$custom_css_vars['align-self'] = 'flex-start';
						} elseif ( 'right' === $this->args['alignment'] ) {
							$custom_css_vars['align-self'] = 'flex-end';
						} else {
							$custom_css_vars['align-self'] = 'center';
						}
					}
					$custom_css_vars['width'] = '100%';
				}

				$margin_style = Fusion_Builder_Margin_Helper::get_margin_vars( $this->args );
				return $this->get_custom_css_vars( $custom_css_vars ) . $margin_style;
			}

			/**
			 * The video duration in ISO 8601 format.
			 *
			 * @access public
			 * @since 3.8
			 * @param  string $duration The video duration.
			 * @return string
			 */
			public function get_duration( $duration ) {
				$time     = 'PT';
				$duration = explode( ':', $duration );
				$hours    = '00' !== $duration[0] ? $duration[0] : '';
				$minutes  = '00' !== $duration[1] ? $duration[1] : '';
				$seconds  = '00' !== $duration[2] ? $duration[2] : '';

				$time .= $hours ? ltrim( $hours, '0' ) . 'H' : '';
				$time .= $minutes ? ltrim( $minutes, '0' ) . 'M' : '';
				$time .= $seconds ? ltrim( $seconds, '0' ) . 'S' : '';

				return $time;
			}

			/**
			 * Sets the necessary scripts.
			 *
			 * @access public
			 * @since 3.2
			 * @return void
			 */
			public function on_first_render() {
				Fusion_Dynamic_JS::enqueue_script( 'fusion-video' );
				Fusion_Dynamic_JS::enqueue_script( 'lite-youtube' );
			}

			/**
			 * Load base CSS.
			 *
			 * @access public
			 * @since 3.0
			 * @return void
			 */
			public function add_css_files() {
				FusionBuilder()->add_element_css( FUSION_BUILDER_PLUGIN_DIR . 'assets/css/shortcodes/youtube.min.css' );
				FusionBuilder()->add_element_css( FUSION_BUILDER_PLUGIN_DIR . 'assets/css/lite-yt-embed.min.css' );
			}
		}
	}

	new FusionSC_Youtube();

}

/**
 * Map shortcode to Avada Builder.
 *
 * @since 1.0
 */
function fusion_element_youtube() {
	fusion_builder_map(
		fusion_builder_frontend_data(
			'FusionSC_Youtube',
			[
				'name'       => esc_attr__( 'YouTube', 'fusion-builder' ),
				'shortcode'  => 'fusion_youtube',
				'icon'       => 'fusiona-youtube',
				'preview'    => FUSION_BUILDER_PLUGIN_DIR . 'inc/templates/previews/fusion-youtube-preview.php',
				'preview_id' => 'fusion-builder-block-module-youtube-preview-template',
				'help_url'   => 'https://avada.com/documentation/youtube-element/',
				'params'     => [
					[
						'type'         => 'textfield',
						'heading'      => esc_attr__( 'Video ID or Url', 'fusion-builder' ),
						'description'  => esc_attr__( 'For example the Video ID for https://www.youtube.com/watch?v=CbOQqvQDrVQ is CbOQqvQDrVQ.', 'fusion-builder' ),
						'param_name'   => 'id',
						'dynamic_data' => true,
						'value'        => '',
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Alignment', 'fusion-builder' ),
						'description' => esc_attr__( "Select the video's alignment.", 'fusion-builder' ),
						'param_name'  => 'alignment',
						'default'     => '',
						'value'       => [
							''       => esc_attr__( 'Text Flow', 'fusion-builder' ),
							'left'   => esc_attr__( 'Left', 'fusion-builder' ),
							'center' => esc_attr__( 'Center', 'fusion-builder' ),
							'right'  => esc_attr__( 'Right', 'fusion-builder' ),
						],
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_attr__( 'Dimensions', 'fusion-builder' ),
						'description'      => esc_attr__( 'In pixels but only enter a number, ex: 600.', 'fusion-builder' ),
						'param_name'       => 'dimensions',
						'value'            => [
							'width'  => '600',
							'height' => '350',
						],
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Start Time', 'fusion-builder' ),
						'description' => esc_attr__( 'Specify a start time for the video (in seconds).', 'fusion-builder' ),
						'param_name'  => 'start_time',
						'value'       => '',
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'End Time', 'fusion-builder' ),
						'description' => esc_attr__( 'Specify an end time for the video (in seconds).', 'fusion-builder' ),
						'param_name'  => 'end_time',
						'value'       => '',
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Autoplay Video', 'fusion-builder' ),
						'description' => esc_attr__( 'Set to yes to make video autoplaying. Muted video required for autoplay video.', 'fusion-builder' ),
						'param_name'  => 'autoplay',
						'value'       => [
							'true'  => esc_attr__( 'Yes', 'fusion-builder' ),
							'false' => esc_attr__( 'No', 'fusion-builder' ),
						],
						'default'     => 'false',
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Mute Video', 'fusion-builder' ),
						'description' => esc_attr__( 'Set to yes to make video muted.', 'fusion-builder' ),
						'param_name'  => 'mute',
						'value'       => [
							'true'  => esc_attr__( 'Yes', 'fusion-builder' ),
							'false' => esc_attr__( 'No', 'fusion-builder' ),

						],
						'default'     => 'false',
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Loop Video', 'fusion-builder' ),
						'description' => esc_attr__( 'Set to yes to let the video loop.', 'fusion-builder' ),
						'param_name'  => 'loop',
						'value'       => [
							'true'  => esc_attr__( 'Yes', 'fusion-builder' ),
							'false' => esc_attr__( 'No', 'fusion-builder' ),
						],
						'default'     => 'false',
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Player Controls', 'fusion-builder' ),
						'description' => esc_attr__( 'Set to yes to display the player controls.', 'fusion-builder' ),
						'param_name'  => 'controls',
						'value'       => [
							'true'  => esc_attr__( 'Yes', 'fusion-builder' ),
							'false' => esc_attr__( 'No', 'fusion-builder' ),
						],
						'default'     => 'true',
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Additional API Parameter', 'fusion-builder' ),
						'description' => esc_attr__( 'Use an additional API parameter, for example, &rel=0 to only display related videos from the same channel.', 'fusion-builder' ),
						'param_name'  => 'api_params',
						'value'       => '',
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Title Attribute', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the title attribute for the iframe embed of your video. Leave empty to use default value of "YouTube video player #".', 'fusion-builder' ),
						'param_name'  => 'title_attribute',
						'value'       => '',
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Video Facade', 'fusion-builder' ),
						'description' => esc_attr__( 'Enable video facade in order to load video player only when video is played.', 'fusion-builder' ),
						'param_name'  => 'video_facade',
						'default'     => '',
						'value'       => [
							''    => esc_attr__( 'Default', 'fusion-builder' ),
							'on'  => esc_attr__( 'On', 'fusion-builder' ),
							'off' => esc_attr__( 'Off', 'fusion-builder' ),
						],
					],
					[
						'type'        => 'select',
						'heading'     => esc_html__( 'Thumbnail Size', 'fusion-builder' ),
						'description' => esc_html__( "Select the video's preview thumbnail size. The sizes are YouTube standard, and not all sizes are available for every video. Leave empty for auto selection depending on video dimensions.", 'fusion-builder' ),
						'param_name'  => 'thumbnail_size',
						'default'     => 'auto',
						'value'       => [
							'auto'          => esc_html__( 'Auto', 'fusion-builder' ),
							'default'       => esc_html__( 'Default (120x90)', 'fusion-builder' ),
							'mqdefault'     => esc_html__( 'Medium (320x180)', 'fusion-builder' ),
							'hqdefault'     => esc_html__( 'High (480x360)', 'fusion-builder' ),
							'sddefault'     => esc_html__( 'Standard (640x480)', 'fusion-builder' ),
							'maxresdefault' => esc_html__( 'Max Resolution (1280x720)', 'fusion-builder' ),
						],
						'or'          => true,
						'dependency'  => [
							[
								'element'  => 'video_facade',
								'value'    => 'on',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Load Via YouTube No Cookie', 'fusion-builder' ),
						'description' => esc_attr__( 'Enable to load the video as trackingless embed via youtube-nocookie.com.', 'fusion-builder' ),
						'param_name'  => 'video_facade_no_cookie',
						'default'     => 'on',
						'value'       => [
							'on'  => esc_attr__( 'On', 'fusion-builder' ),
							'off' => esc_attr__( 'Off', 'fusion-builder' ),
						],
						'or'          => true,
						'dependency'  => [
							[
								'element'  => 'video_facade',
								'value'    => 'on',
								'operator' => '==',
							],
						],
					],
					'fusion_margin_placeholder' => [
						'param_name' => 'margin',
						'group'      => esc_attr__( 'General', 'fusion-builder' ),
						'value'      => [
							'margin_top'    => '',
							'margin_bottom' => '',
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
						'param_name'  => 'class',
						'value'       => '',
						'description' => esc_attr__( 'Add a class to the wrapping HTML element.', 'fusion-builder' ),
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'CSS ID', 'fusion-builder' ),
						'description' => esc_attr__( 'Add an ID to the wrapping HTML element.', 'fusion-builder' ),
						'param_name'  => 'css_id',
						'value'       => '',
					],
					'fusion_conditional_render_placeholder' => [],					
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Structured Data', 'fusion-builder' ),
						'description' => esc_attr__( 'Enable video structured data for better SEO.', 'fusion-builder' ),
						'param_name'  => 'structured_data',
						'default'     => 'off',
						'value'       => [
							'on'  => esc_attr__( 'On', 'fusion-builder' ),
							'off' => esc_attr__( 'Off', 'fusion-builder' ),
						],
						'group'       => esc_attr__( 'Extras', 'fusion-builder' ),
					],
					[
						'type'        => 'date_time_picker',
						'heading'     => esc_attr__( 'Upload Date & Time', 'fusion-builder' ),
						'description' => esc_attr__( 'Select video upload date and time.', 'fusion-builder' ),
						'param_name'  => 'video_upload_date',
						'group'       => esc_attr__( 'Extras', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'structured_data',
								'value'    => 'on',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'select',
						'heading'     => esc_attr__( 'Upload Timezone', 'fusion-builder' ),
						'description' => esc_attr__( 'Select video upload time timezone.', 'fusion-builder' ),
						'param_name'  => 'video_upload_date_timezone',
						'group'       => esc_attr__( 'Extras', 'fusion-builder' ),
						'value'       => [
							'-12:00' => esc_html__( 'UTC−12:00',  'fusion-builder' ),
							'-11:00' => esc_html__( 'UTC−11:00',  'fusion-builder' ),
							'-10:00' => esc_html__( 'UTC−10:00',  'fusion-builder' ),
							'-09:30' => esc_html__( 'UTC−09:30',  'fusion-builder' ),
							'-09:00' => esc_html__( 'UTC−09:00',  'fusion-builder' ),
							'-08:00' => esc_html__( 'UTC−08:00',  'fusion-builder' ),
							'-07:00' => esc_html__( 'UTC−07:00',  'fusion-builder' ),
							'-06:00' => esc_html__( 'UTC−06:00',  'fusion-builder' ),
							'-05:00' => esc_html__( 'UTC−05:00',  'fusion-builder' ),
							'-04:00' => esc_html__( 'UTC−04:00',  'fusion-builder' ),
							'-03:30' => esc_html__( 'UTC−03:30',  'fusion-builder' ),
							'-03:00' => esc_html__( 'UTC−03:00',  'fusion-builder' ),
							'-02:00' => esc_html__( 'UTC−02:00',  'fusion-builder' ),
							'-01:00' => esc_html__( 'UTC−01:00',  'fusion-builder' ),
							'Z'      => esc_html__( 'UTC',  'fusion-builder' ),
							'+01:00' => esc_html__( 'UTC+01:00',  'fusion-builder' ),
							'+02:00' => esc_html__( 'UTC+02:00',  'fusion-builder' ),
							'+03:00' => esc_html__( 'UTC+03:00',  'fusion-builder' ),
							'+03:30' => esc_html__( 'UTC+03:30',  'fusion-builder' ),
							'+04:00' => esc_html__( 'UTC+04:00',  'fusion-builder' ),
							'+04:30' => esc_html__( 'UTC+04:30',  'fusion-builder' ),
							'+05:00' => esc_html__( 'UTC+05:00',  'fusion-builder' ),
							'+05:30' => esc_html__( 'UTC+05:30',  'fusion-builder' ),
							'+05:45' => esc_html__( 'UTC+05:45',  'fusion-builder' ),
							'+06:00' => esc_html__( 'UTC+06:00',  'fusion-builder' ),
							'+06:30' => esc_html__( 'UTC+06:30',  'fusion-builder' ),
							'+07:00' => esc_html__( 'UTC+07:00',  'fusion-builder' ),
							'+08:00' => esc_html__( 'UTC+08:00',  'fusion-builder' ),
							'+08:45' => esc_html__( 'UTC+01:45',  'fusion-builder' ),
							'+09:00' => esc_html__( 'UTC+09:00',  'fusion-builder' ),
							'+09:30' => esc_html__( 'UTC+09:30',  'fusion-builder' ),
							'+10:00' => esc_html__( 'UTC+10:00',  'fusion-builder' ),
							'+10:30' => esc_html__( 'UTC+10:30',  'fusion-builder' ),
							'+11:00' => esc_html__( 'UTC+11:00',  'fusion-builder' ),
							'+12:00' => esc_html__( 'UTC+12:00',  'fusion-builder' ),
							'+12:45' => esc_html__( 'UTC+12:45',  'fusion-builder' ),
							'+13:00' => esc_html__( 'UTC+13:00',  'fusion-builder' ),
							'+14:00' => esc_html__( 'UTC+14:00',  'fusion-builder' ),
						],
						'dependency'  => [
							[
								'element'  => 'structured_data',
								'value'    => 'on',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'date_time_picker',
						'heading'     => esc_attr__( 'Video Duration', 'fusion-builder' ),
						'description' => esc_attr__( 'Insert the video duration.', 'fusion-builder' ),
						'param_name'  => 'video_duration',
						'group'       => esc_attr__( 'Extras', 'fusion-builder' ),
						'date'        => false,
						'dependency'  => [
							[
								'element'  => 'structured_data',
								'value'    => 'on',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Video Title', 'fusion-builder' ),
						'description' => esc_attr__( 'Insert the video title.', 'fusion-builder' ),
						'param_name'  => 'video_title',
						'group'       => esc_attr__( 'Extras', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'structured_data',
								'value'    => 'on',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'textarea',
						'heading'     => esc_attr__( 'Video Description', 'fusion-builder' ),
						'description' => esc_attr__( 'Insert the video description.', 'fusion-builder' ),
						'param_name'  => 'video_desc',
						'group'       => esc_attr__( 'Extras', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'structured_data',
								'value'    => 'on',
								'operator' => '==',
							],
						],
					],
				],
			]
		)
	);
}
add_action( 'fusion_builder_before_init', 'fusion_element_youtube' );
