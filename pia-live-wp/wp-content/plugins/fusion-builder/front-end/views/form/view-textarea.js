var FusionPageBuilder = FusionPageBuilder || {};

( function() {

	jQuery( document ).ready( function() {
		// Fusion Form Textarea View.
		FusionPageBuilder.fusion_form_textarea = FusionPageBuilder.FormComponentView.extend( {

			/**
			 * Runs on init.
			 *
			 * @since 3.11.12
			 * @return {void}
			 */
			onRender: function() {
				this.updateValue();
			},

			/**
			 * Runs after view DOM is patched.
			 *
			 * @since 3.11.12
			 * @return {void}
			 */
			afterPatch: function() {
				this.updateValue();
			},

			/**
			 * Adjusts the textarea value.
			 *
			 * @since 3.11.12
			 * @return {void}
			 */
			updateValue: function() {
				const $textarea = jQuery( '#fb-preview' )[ 0 ].contentWindow.jQuery( this.$el.find( 'textarea' ) );

				jQuery( $textarea ).val( jQuery( $textarea ).attr( 'data-value' ) );
			},

			/**
			 * Modify template attributes.
			 *
			 * @since 3.1
			 * @param {Object} atts - The attributes object.
			 * @return {Object}
			 */
			filterTemplateAtts: function( atts ) {
				var attributes = {};

				// Create attribute objects;
				attributes.styles = this.buildStyles( atts.values );
				attributes.html   = this.generateFormFieldHtml( this.generateTextareaField( atts.values ) );

				return attributes;
			},

			generateTextareaField: function ( atts ) {
				var elementData,
					elementHtml,
					initValue,
					value,
					html = '';

				elementData = this.elementData( atts );

				elementData = this.generateTooltipHtml( atts, elementData );

				value       = 'undefined' !== typeof elementData.value ? elementData.value : '';
				initValue   = 'undefined' !== typeof atts.value ? atts.value : value;
				elementHtml = '<textarea cols="40" data-value="' + initValue + '" rows="' + atts.rows + '" name="' + atts.name + '"' + elementData[ 'class' ] + elementData.required + elementData.disabled + elementData.placeholder + elementData.holds_private_data + '>' + value + '</textarea>';

				elementHtml = this.generateIconHtml( atts, elementHtml );

				html = this.generateLabelHtml( html, elementHtml, elementData.label );

				return html;
			}

		} );
	} );
}( jQuery ) );
