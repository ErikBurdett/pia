/* global fusionBuilderText */
var FusionPageBuilder = FusionPageBuilder || {};

( function() {

	jQuery( document ).ready( function() {
		// Fusion Form Upload View.
		FusionPageBuilder.fusion_form_upload = FusionPageBuilder.FormComponentView.extend( {

			/**
			 * Modify template attributes.
			 *
			 * @since 3.1
			 * @param {Object} atts - The attributes object.
			 * @return {Object}
			 */
			filterTemplateAtts: function( atts ) {
				var attributes = {};

				this.values = atts.values;

				// Create attribute objects;
				attributes.html = this.generateFormFieldHtml( this.generateUploadField( atts.values ) );

				return attributes;
			},


			generateUploadField: function ( atts ) {
				var elementData,
					elementHtml,
					html = '';

				atts[ 'class' ] = ( '' !== atts[ 'class' ] ) ? atts[ 'class' ] + ' fusion-form-file-upload' : 'fusion-form-file-upload';

				elementData = this.elementData( atts );

				elementData = this.generateTooltipHtml( atts, elementData );

				elementData.multiple = 'yes' === atts.multiple ? ' multiple' : '';

				elementData.name     = atts.name;
				elementData.multiple = '';
				if ( 'yes' === atts.multiple ) {
					elementData.name     += '[]';
					elementData.multiple = ' multiple';
				}

				elementData.accept  = 'undefined' !== typeof atts.extensions && '' !== atts.extensions ? 'accept="' + atts.extensions + '"' : '';
				elementData.capture = 'undefined' !== typeof atts.capture && '' !== atts.capture ? 'capture="' + atts.capture + '"' : '';

				// Only AJAX submitted forms can use single add / remove, and multiple uploads has to be selected. Min and max files only on multiple uploads.
				atts.single_add_remove        = 'yes' !== atts.multiple ? 'no' : atts.single_add_remove;
				atts.min_upload_files         = 'yes' !== atts.multiple ? '0' : atts.min_upload_files;
				atts.max_upload_files         = 'yes' !== atts.multiple ? '0' : atts.max_upload_files;
				let previewClasses            = 'yes' === atts.single_add_remove ? ' add-remove' : '';
				previewClasses               += 'yes' === atts.preview_thumb_scale ? ' scale' : '';
				elementData.single_add_remove = ' data-single-add-remove="' + atts.single_add_remove + '"';
				elementData.min_upload_files  = ' data-min-upload-files="' + atts.min_upload_files + '"';
				elementData.max_upload_files  = ' data-max-upload-files="' + atts.max_upload_files + '"';
				elementData.uploads_preview   = ' data-uploads-preview="' + atts.uploads_preview +  '"';

				elementHtml  = '<div class="fusion-form-upload-field-container">';
				elementHtml += '<input type="file" name="' + elementData.name + '" value="" ' + elementData[ 'class' ] + elementData.accept + elementData.capture + elementData.required + elementData.single_add_remove + elementData.min_upload_files + elementData.max_upload_files + elementData.uploads_preview + elementData.placeholder + elementData.upload_size + elementData.multiple + '/>';
				elementHtml += '<div class="fusion-form-upload-field" ' + elementData.style + elementData.holds_private_data + '>';
				elementHtml += '<span class="awb-upload-placeholder" data-default="' + atts.placeholder + '">' + atts.placeholder + '</span>';
				elementHtml += '<div class="awb-uploads-preview' + previewClasses + '">';
				if ( 'simple' === atts.uploads_preview ) {
					elementHtml += '<div class="awb-preview-filenames"></div>';
				} else if ( 'list' === atts.uploads_preview ) {
					elementHtml += '<ul class="awb-preview-list"></ul>';
				} else {
					elementHtml += '<div class="awb-preview-thumbs"></div>';
				}
				elementHtml += '</div>';
				elementHtml += '</div>';
				elementHtml += '<a class="fusion-button button-flat button-medium button-default button-1 fusion-button-default-span fusion-button-default-type fusion-form-upload-field-button" style="border-radius:0;"><span class="fusion-button-text">' + fusionBuilderText.choose_file + '</span></a>';
				elementHtml += '</div>';

				elementHtml = this.generateIconHtml( atts, elementHtml );
				elementHtml = this.generateIconWrapperHtml( elementHtml );

				html = this.generateLabelHtml( html, elementHtml, elementData.label );

				return html;
			},

			/**
			 * Gets style variables.
			 *
			 * @since 3.14.0
			 * @param {Object} values - The values.
			 * @return {String}
			 */
			getStyleVariables: function( values ) {
				let cssVarsOptions = [];

				cssVarsOptions.preview_thumb_width = { 'callback': _.fusionGetValueWithUnit };

				return this.getCssVarsForOptions( cssVarsOptions );
			}

		} );
	} );
}( jQuery ) );
