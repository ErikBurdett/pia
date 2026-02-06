var FusionPageBuilder = FusionPageBuilder || {};

( function() {

	jQuery( document ).ready( function() {
		// Fusion Form Turnstile View.
		FusionPageBuilder.fusion_form_turnstile = FusionPageBuilder.FormComponentView.extend( {

			/**
			 * Modify template attributes.
			 *
			 * @since 3.1
			 * @param {Object} atts - The attributes object.
			 * @return {Object}
			 */
			filterTemplateAtts: function( atts ) { // eslint-disable-line no-unused-vars
				var attributes = {};

				return attributes;
			}

		} );
	} );
}( jQuery ) );
