<?php
/**
 * Add an element to fusion-builder.
 *
 * @package fusion-builder
 * @since 3.1
 */

if ( fusion_is_element_enabled( 'fusion_form_upload' ) ) {

	if ( ! class_exists( 'FusionForm_Upload' ) ) {
		/**
		 * Shortcode class.
		 *
		 * @since 3.1
		 */
		class FusionForm_Upload extends Fusion_Form_Component {

			/**
			 * Constructor.
			 *
			 * @access public
			 * @since 3.1
			 */
			public function __construct() {
				parent::__construct( 'fusion_form_upload' );
			}

			/**
			 * Gets the default values.
			 *
			 * @static
			 * @access public
			 * @since 3.1
			 * @return array
			 */
			public static function get_element_defaults() {
				return [
					'label'               => '',
					'name'                => '',
					'required'            => '',
					'empty_notice'        => '',
					'placeholder'         => '',
					'input_field_icon'    => '',
					'upload_size'         => '',
					'multiple'            => '',
					'single_add_remove'   => 'no',
					'min_upload_files'    => '0',
					'max_upload_files'    => '0',
					'extensions'          => '',
					'capture'             => '',
					'uploads_preview'     => 'filenames',
					'preview_thumb_width' => '115',
					'preview_thumb_scale' => 'no',
					'class'               => '',
					'id'                  => '',
					'logics'              => '',
					'tooltip'             => '',
				];
			}

			/**
			 * Render form field html.
			 *
			 * @access public
			 * @since 3.1
			 * @param string $content The content.
			 * @return string
			 */
			public function render_input_field( $content ) {

				$element_data = $this->create_element_data( $this->args );
				$html         = '';

				if ( '' !== $this->args['tooltip'] ) {
					$element_data['label'] .= $this->get_field_tooltip( $this->args );
				}

				$name     = $this->args['name'];
				$multiple = '';
				if ( 'yes' === $this->args['multiple'] ) {
					$name    .= '[]';
					$multiple = ' multiple';
				}

				$element_data['accept']  = ( isset( $this->args['extensions'] ) && '' !== $this->args['extensions'] ) ? 'accept="' . $this->args['extensions'] . '"' : '';
				$element_data['capture'] = '' !== $this->args['capture'] ? 'capture="' . $this->args['capture'] . '"' : '';

				// Only AJAX submitted forms can use single add / remove, and multiple uploads has to be selected. Min and max files only on multiple uploads.
				$this->args['single_add_remove']   = 'ajax' !== $this->params['form_meta']['form_type'] || 'yes' !== $this->args['multiple'] ? 'no' : $this->args['single_add_remove'];
				$this->args['min_upload_files']    = 'yes' !== $this->args['multiple'] ? '0' : $this->args['min_upload_files'];
				$this->args['max_upload_files']    = 'yes' !== $this->args['multiple'] ? '0' : $this->args['max_upload_files'];
				$preview_classes                   = 'yes' === $this->args['single_add_remove'] ? ' add-remove' : '';
				$preview_classes                  .= 'yes' === $this->args['preview_thumb_scale'] ? ' scale' : '';
				$element_data['single_add_remove'] = ' data-single-add-remove="' . esc_attr( $this->args['single_add_remove'] ) . '"';
				$element_data['min_upload_files']  = ' data-min-upload-files="' . esc_attr( $this->args['min_upload_files'] ) . '"';
				$element_data['max_upload_files']  = ' data-max-upload-files="' . esc_attr( $this->args['max_upload_files'] ) . '"';
				$element_data['uploads_preview']   = ' data-uploads-preview="' . esc_attr( $this->args['uploads_preview'] ) . '"';

				$element_html  = '<div class="fusion-form-upload-field-container">';
				$element_html .= '<input type="file" ';
				$element_html .= '' !== $element_data['empty_notice'] ? 'data-empty-notice="' . $element_data['empty_notice'] . '" ' : '';
				$element_html .= 'id="' . $this->args['name'] . '" name="' . $name . '" value="' . $content . '" ' . $element_data['class'] . $element_data['accept'] . $element_data['capture'] . $element_data['required'] . $element_data['placeholder'] . $element_data['style'] . $element_data['upload_size'] . $multiple . $element_data['single_add_remove'] . $element_data['min_upload_files'] . $element_data['max_upload_files'] . $element_data['uploads_preview'] . '/>';
				$element_html .= '<div class="fusion-form-upload-field" ' . $element_data['style'] . $element_data['holds_private_data'] . '>';
					$element_html .= '<span class="awb-upload-placeholder" data-default="' . esc_attr( $this->args['placeholder'] ) . '">' . esc_html( $this->args['placeholder'] ) . '</span>';
					$element_html .= '<div class="awb-uploads-preview' . esc_attr( $preview_classes ) . '">';
						if ( 'simple' === $this->args['uploads_preview'] ) {
							$element_html .= '<div class="awb-preview-filenames"></div>';
						} else if ( 'list' === $this->args['uploads_preview'] ) {
							$element_html .= '<ul class="awb-preview-list"></ul>';
						} else {
							$element_html .= '<div class="awb-preview-thumbs"></div>';
						}
					$element_html .= '</div>';
				$element_html .= '</div>';
				$element_html .= do_shortcode( '[fusion_button class="fusion-form-upload-field-button" size="medium" shape="square" link="javascript:void();" target="_self" hide_on_mobile="small-visibility,medium-visibility,large-visibility" color="default"  stretch="default"]' . __( 'Choose File', 'fusion-builder' ) . '[/fusion_button]' );
				$element_html .= '</div>';

				if ( isset( $this->args['input_field_icon'] ) && '' !== $this->args['input_field_icon'] ) {
					$icon_html     = '<div class="fusion-form-input-with-icon">';
					$icon_html    .= '<i class=" ' . fusion_font_awesome_name_handler( $this->args['input_field_icon'] ) . '"></i>';
					$element_html  = $icon_html . $element_html;
					$element_html .= '</div>';
				}

				if ( 'above' === $this->params['form_meta']['label_position'] ) {
					$html .= $element_data['label'] . $element_html;
				} else {
					$html .= $element_html . $element_data['label'];
				}

				return $html;
			}

			/**
			 * Get the style variables.
			 *
			 * @access protected
			 * @since 3.14.0
			 * @return string
			 */
			public function get_style_variables() {
				$css_vars_options = [
					'preview_thumb_width' => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
				];

				$styles = $this->get_css_vars_for_options( $css_vars_options );

				return $styles;
			}

			/**
			 * Load base CSS.
			 *
			 * @access public
			 * @since 3.1
			 * @return void
			 */
			public function add_css_files() {
				FusionBuilder()->add_element_css( FUSION_BUILDER_PLUGIN_DIR . 'assets/css/form/upload.min.css' );
			}
		}
	}

	new FusionForm_Upload();
}

/**
 * Map shortcode to Fusion Builder
 *
 * @since 3.1
 */
function fusion_form_upload() {
	$max_upload_size = size_format( wp_max_upload_size() );
	if ( ! $max_upload_size ) {
		$max_upload_size = 0;
	}

	fusion_builder_map(
		fusion_builder_frontend_data(
			'FusionForm_Upload',
			[
				'name'           => esc_attr__( 'Upload Field', 'fusion-builder' ),
				'shortcode'      => 'fusion_form_upload',
				'icon'           => 'fusiona-af-upload',
				'form_component' => true,
				'preview'        => FUSION_BUILDER_PLUGIN_DIR . 'inc/templates/previews/fusion-form-element-preview.php',
				'preview_id'     => 'fusion-builder-block-module-form-element-preview-template',
				'params'         => [
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Field Label', 'fusion-builder' ),
						'description' => esc_attr__( 'Enter the label for the input field. This is how users will identify individual fields.', 'fusion-builder' ),
						'param_name'  => 'label',
						'value'       => '',
						'placeholder' => true,
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Field Name', 'fusion-builder' ),
						'description' => esc_attr__( 'Enter the field name. Please use only lowercase alphanumeric characters, dashes, and underscores.', 'fusion-builder' ),
						'param_name'  => 'name',
						'value'       => '',
						'placeholder' => true,
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Required Field', 'fusion-builder' ),
						'description' => esc_attr__( 'Make a selection to ensure that this field is completed before allowing the user to submit the form.', 'fusion-builder' ),
						'param_name'  => 'required',
						'default'     => 'no',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Empty Input Notice', 'fusion-builder' ),
						'description' => esc_attr__( 'Enter text validation notice that should display if data input is empty.', 'fusion-builder' ),
						'param_name'  => 'empty_notice',
						'value'       => '',
						'dependency'  => [
							[
								'element'  => 'required',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Placeholder Text', 'fusion-builder' ),
						'param_name'  => 'placeholder',
						'value'       => esc_attr__( 'Click or drag a file to this area to upload.', 'fusion-builder' ),
						'description' => esc_attr__( 'The placeholder text to display as hint for the input type.', 'fusion-builder' ),
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Tooltip Text', 'fusion-builder' ),
						'param_name'  => 'tooltip',
						'value'       => '',
						'description' => esc_attr__( 'The text to display as tooltip hint for the input.', 'fusion-builder' ),
					],
					[
						'type'        => 'range',
						'heading'     => esc_html__( 'Max File Upload Size', 'fusion-builder' ),
						/* translators: Maximum upload size. */
						'description' => sprintf( __( 'Maximum size limit for file upload. The default is 2 MB. Maximum upload size on your site is currently set to %s.', 'fusion-builder' ), $max_upload_size ),
						'param_name'  => 'upload_size',
						'value'       => '2',
						'min'         => '1',
						'max'         => '100',
						'step'        => '1',
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Allow Multiple Uploads', 'fusion-builder' ),
						'description' => esc_attr__( 'Decide if multiple files can be uploaded.', 'fusion-builder' ),
						'param_name'  => 'multiple',
						'default'     => 'no',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Allow Single File Addition / Removal', 'fusion-builder' ),
						'description' => esc_attr__( 'Decide if single files can be added and removed. Note: This option only works with forms that are AJAX-submitted.', 'fusion-builder' ),
						'param_name'  => 'single_add_remove',
						'default'     => 'no',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'dependency'  => [
							[
								'element'  => 'multiple',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'range',
						'heading'     => esc_html__( 'Min Number Of Files', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the minimum number of files a user can upload. Set to 0, if you don\'t want a limit.', 'fusion-builder' ),
						'param_name'  => 'min_upload_files',
						'value'       => '0',
						'min'         => '0',
						'max'         => '25',
						'step'        => '1',
						'dependency'  => [
							[
								'element'  => 'multiple',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'range',
						'heading'     => esc_html__( 'Max Number Of Files', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the maximum number of files a user can upload. Set to 0, if you don\'t want a limit.', 'fusion-builder' ),
						'param_name'  => 'max_upload_files',
						'value'       => '0',
						'min'         => '0',
						'max'         => '25',
						'step'        => '1',
						'dependency'  => [
							[
								'element'  => 'multiple',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_html__( 'Allowed Filetypes / Extensions', 'fusion-builder' ),
						'param_name'  => 'extensions',
						'value'       => '',
						'description' => esc_html__( 'Please enter the comma separated filetypes or extensions that you want to allow. Leave empty to allow all. Example input: .jpg, .png, image/*. Note, WordPress file type permissions still apply.', 'fusion-builder' ),
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Capture Camera', 'fusion-builder' ),
						'description' => esc_attr__( 'Recommends to (mobile) browsers to prompt users to capture new media directly instead of selecting an existing file. Some directly open the camera app. Corresponding file types must have been set above. "User" indicates that the user-facing camera should be used, while  "environment" specifies that the outward-facing camera should be used. If not set, the browser will decide automatically, and likely load the standard file dialog. ', 'fusion-builder' ),
						'param_name'  => 'capture',
						'default'     => '',
						'value'       => [
							''            => esc_attr__( 'Browser', 'fusion-builder' ),
							'user'        => esc_attr__( 'User', 'fusion-builder' ),
							'environment' => esc_attr__( 'Environment', 'fusion-builder' ),
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Uploads Preview', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose which preview you want to provide', 'fusion-builder' ),
						'param_name'  => 'uploads_preview',
						'default'     => 'simple',
						'value'       => [
							'simple'              => esc_attr__( 'Filenames Comma Separated', 'fusion-builder' ),
							'list'                => esc_attr__( 'List With Details', 'fusion-builder' ),
							'thumbnails'          => esc_attr__( 'Thumbnails', 'fusion-builder' ),
							'thumbnails_detailes' => esc_attr__( 'Thumbnails With Details', 'fusion-builder' ),
						],
					],
					[
						'type'        => 'range',
						'heading'     => esc_html__( 'Preview Thumbnail Width', 'fusion-builder' ),
						'description' => esc_attr__( 'Set the width of each thumbnail in the preview. In pixel.', 'fusion-builder' ),
						'param_name'  => 'preview_thumb_width',
						'value'       => '115',
						'min'         => '60',
						'max'         => '250',
						'step'        => '1',
						'dependency'  => [
							[
								'element'  => 'uploads_preview',
								'value'    => 'simple',
								'operator' => '!=',
							],
							[
								'element'  => 'uploads_preview',
								'value'    => 'list',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Preview Thumbnail Hover Scale', 'fusion-builder' ),
						'description' => esc_attr__( 'Set to "yes" if preview thumbnails should scale on hover to reveal more of the filename.', 'fusion-builder' ),
						'param_name'  => 'preview_thumb_scale',
						'default'     => 'no',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'dependency'  => [
							[
								'element'  => 'uploads_preview',
								'value'    => 'simple',
								'operator' => '!=',
							],
							[
								'element'  => 'uploads_preview',
								'value'    => 'list',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'iconpicker',
						'heading'     => esc_attr__( 'Input Field Icon', 'fusion-builder' ),
						'param_name'  => 'input_field_icon',
						'value'       => 'fa-upload fas',
						'description' => esc_attr__( 'Select an icon for the input field, click again to deselect.', 'fusion-builder' ),
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
					'fusion_form_logics_placeholder' => [],
				],
			]
		)
	);
}
add_action( 'fusion_builder_before_init', 'fusion_form_upload' );
