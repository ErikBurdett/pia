/* fusionBuilderText */
var FusionPageBuilder = FusionPageBuilder || {};

( function() {

	jQuery( document ).ready( function() {

		// Dynamic Data Element View.
		FusionPageBuilder.awb_dd = FusionPageBuilder.ElementView.extend( {

			/**
			 * Modify template attributes.
			 *
			 * @since 2.0
			 * @param {Object} atts - The attributes.
			 * @return {Object} - Returns the attributes.
			 */
			filterTemplateAtts: function( atts ) {
				var attributes = {};

				this.values = atts.values;

				// Create attribute objects
				attributes.attr   = this.buildAttr( this.values );
				attributes.params = this.buildParamsObject( atts.params );
				attributes.output = 'undefined' !== typeof this.values.awb_dd_output && this.values.awb_dd_output ? this.values.awb_dd_output : fusionBuilderText.select_dynamic_content;

				return attributes;
			},

			/**
			 * Parses the dynamic data arguments into an object string.
			 *
			 * @since 3.13.0
			 * @param {Object} values - The values.
			 * @return {string} - The params pbject string.
			 */
			buildParamsObject: function( values, replaceEndpoint = true ) {
				const nonDynamicKeys = [ 'element_content', 'open_settings', 'hide_on_mobile', 'class', 'id' ];

				let dataEndpoint = '',
					objectString = '{';

				_.each( values, function( value, key ) {
					if ( 'dynamic_data' === key ) {
						if ( '0' !== value ) {
							objectString += value;
						}

						dataEndpoint = value + '__';
					} else if ( ! nonDynamicKeys.includes (key ) && -1 !== key.indexOf( dataEndpoint ) ) {
						const newKey = replaceEndpoint ? key.replace( dataEndpoint, '' ) : key;
						objectString += ',' + newKey + ':' + value;
					}
				} );

				objectString += '}';

				return objectString;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 3.13.0
			 * @param {Object} values - The values.
			 * @return {Object} - Returns the element attributes.
			 */
			buildAttr: function( values ) {
				var attr = _.fusionVisibilityAtts( values.hide_on_mobile, {
					class: 'awb-dynamic-data awb-dynamic-data-' + this.model.get( 'cid' ),
					style: ''
				} );

				if ( '' !== values[ 'class' ] ) {
					attr[ 'class' ] += ' ' + values[ 'class' ];
				}

				if ( '' !== values.id ) {
					attr.id = values.id;
				}

				return attr;
			}
		} );
	} );
}( jQuery ) );
