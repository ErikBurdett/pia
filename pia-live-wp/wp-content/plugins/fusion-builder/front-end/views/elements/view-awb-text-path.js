var FusionPageBuilder = FusionPageBuilder || {};

( function() {

	jQuery( document ).ready( function() {

		// Text Path Element View.
		FusionPageBuilder.awb_text_path = FusionPageBuilder.ElementView.extend( {

			customSvgData: {},

			/**
			 * Modify template attributes.
			 *
			 * @since 3.13.0
			 * @param {Object} atts - The attributes object.
			 * @return {Object}
			 */
			filterTemplateAtts: function( atts ) {
				var attributes = {};

				this.values         = atts.values;
				this.values.svgData = this.getSvgData( atts.values.path_type );

				// Create attribute objects
				attributes.attr                  = this.buildAttr( atts.values );
				attributes.pathAnimation         = this.buildPathAnimationAttr( atts.values );
				attributes.pathAnimationSkewY    = this.buildpathAnimationSkewYAttr( atts.values );
				attributes.path                  = this.buildPathAttr( atts.values );
				attributes.text                  = this.buildTextAttr( atts.values );
				attributes.textPath              = this.buildTextPathAttr( atts.values );
				attributes.textAnimation         = this.buildTextAnimationAttr( atts.values );
				attributes.textAnimationFontSize = this.buildTextAnimationFontSizeFactorAttr( atts.values );
				attributes.link                  = this.buildLinkAttr( atts.values );

				attributes.customSvg             = this.getCustomSVG( atts.values );

				// Any extras that need passed on.
				attributes.cid    = this.model.get( 'cid' );
				attributes.values = atts.values;

				return attributes;
			},

			/**
			 * Get a custom SVG.
			 *
			 * @since 3.13.0
			 * @param {Object} values - The values object.
			 * @return {object} The svg and the path tag.
			 */	
			getCustomSVG: function( values ) {
				if ( '' === values.custom_svg_id ) {
					this.customSvgData = {};
					return this.customSvgData;
				}

				const self = this,
					svgId = -1 !== values.custom_svg_id.indexOf( '|' ) ? values.custom_svg_id.split( '|' )[ 0 ] : values.custom_svg_id,
					media = wp.media.attachment( svgId );

				if ( _.isEmpty( this.customSvgData ) || svgId !== self.customSvgData.id ) {
					media.fetch().then( function() {
						fetch( media.get( 'url' ) )
							.then( response => response.text() )
							.then( svgText => {
								const svgTag  = svgText.match( /<svg[^>]+>/ );
								const pathTag = svgText.match( /<path[^>]+>/ );

								if ( svgTag && pathTag ) {
									self.customSvgData = {
										'id': svgId,
										'svgTag': svgTag[0],
										'svgPath': pathTag[0]
									};

									self.reRender();
									self._refreshJs();
								}
							} )
						.catch( err => console.error( 'Error loading SVG:', err ) );
					} );

					return {};
				} else {
					return self.customSvgData;
				}
			},

			/**
			 * Modify the values.
			 *
			 * @since 3.13.0
			 * @param {Object} values - The values object.
			 * @return {void}
			 */
			validateValues: function( values ) {

				values.margin_bottom = _.fusionValidateAttrValue( values.margin_bottom, 'px' );
				values.margin_left   = _.fusionValidateAttrValue( values.margin_left, 'px' );
				values.margin_right  = _.fusionValidateAttrValue( values.margin_right, 'px' );
				values.margin_top    = _.fusionValidateAttrValue( values.margin_top, 'px' );

				values.padding_bottom = _.fusionValidateAttrValue( values.padding_bottom, 'px' );
				values.padding_left   = _.fusionValidateAttrValue( values.padding_left, 'px' );
				values.padding_right  = _.fusionValidateAttrValue( values.padding_right, 'px' );
				values.padding_top    = _.fusionValidateAttrValue( values.padding_top, 'px' );
			},

			/**
			 * Get path data.
			 *
			 * @since 3.13.0
			 * @param {string} name - The SVG path name.
			 * @return {void}
			 */
			getSvgData: function( name ) {
				svgData = {};

				switch( name ) {
					case 'wave_1':
						svgData = {
							'width'   : '250',
							'height'  : '42.25',
							'viewbox' : '0 0 250 42.25',
							'path'    : 'M 0,42.25 C 62.5,42.25 62.5,0.25 125,0.25 S 187.5,42.25 250,42.25',
						};
						break;
					case 'wave_2':
						svgData = {
							'width'   : '250',
							'height'  : '80',
							'viewbox' : '0 0 250 80',
							'path'    : 'M0 38Q62.5-37 125 38T250 38',
						};
							break;
					case 'arc':
						svgData = {
							'width'   : '250',
							'height'  : '125',
							'viewbox' : '0 0 250 125',
							'path'    : 'M 0,125 A 125,125 0 0 1 250,125',
						};
						break;
					case 'arc_bottom':
						svgData = {
							'width'     : '250',
							'height'    : '125',
							'viewbox'   : '0 0 250 125',
							'path'      : 'M0 0A125 125 0 00250 0',
							'text_attr' : { 'key' : 'dominant-baseline', 'value' : 'hanging' },
						};
						break;
					case 'circle':
						svgData = {
							'width'   : '250',
							'height'  : '250',
							'viewbox' : '0 0 250 250',
							'path'    : 'M 0,125 A 125,125 0 1 1 250,125 A 125,125 0 1 1 0,125.01',
						};
						break;
					case 'oval':
						svgData = {
							'width'   : '250',
							'height'  : '150',
							'viewbox' : '0 0 250 150',
							'path'    : 'M 0,75 A 125,75 0 1 1 250,75 A 125,75 0 1 1 0,75.01',
						};
						break;
					case 'spiral_1':
						svgData = {
							'width'   : '250',
							'height'  : '250',
							'viewbox' : '0 0 250 250',
							'path'    : 'M 0 49.0219a149.3489 149.3489 0 01210.9824-9.8266 119.479 119.479 0 017.8613 168.786A95.5831 95.5831 0 0183.8152 214.27a76.4666 76.4666 0 01-5.0312-108.023',
						};
						break;
					case 'spiral_2':
						svgData = {
							'width'   : '330',
							'height'  : '360',
							'viewbox' : '0 0 330 360',
							'path'    : 'M140 1C242 0 330 83 330 175c0 94-76 175-171 175-86 0-161-71-160-158 1-79 65-147 145-146 71 1 132 60 131 132-2 64-54 119-119 117-56-1-104-48-102-105 2-49 42-91 92-88 41 2 76 37 73 79-2 34-31 63-66 59-26-3-48-26-44-53 3-19 21-35 40-30 11 3 21 16 15 27-3 5-14 9-14 1',
						};
						break;
					case 'star':
						svgData = {
							'width'   : '192',
							'height'  : '192',
							'viewbox' : '0 0 192 192',
							'path'    : 'M0 71 72 67 96 0 120 67 192 71 134 117 154 191 96 152 38 191 58 117Z',
						};
						break;
					case 'heart':
						svgData = {
							'width'   : '210',
							'height'  : '165',
							'viewbox' : '0 0 210 160',
							'path'    : 'M105 160C0 80 0 0 55 0 90 0 105 30 105 30 105 30 120 0 155 0 210 0 210 80 105 160Z',
						};
						break;
				}

				return svgData;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 3.13.0
			 * @param {Object} values - The values object.
			 * @return {Object}
			 */
			buildAttr: function( values ) {
				var attr = _.fusionVisibilityAtts( values.hide_on_mobile, {
						class: 'awb-text-path',
						style: this.getStyleVars( values ),
					} );

				attr[ 'data-counter' ] = this.model.get( 'cid' );

				if ( '' !== values[ 'class' ] ) {
					attr[ 'class' ] += ' ' + values[ 'class' ];
				}

				if ( '' !== values.id ) {
					attr.id = values.id;
				}

				attr = _.fusionAnimations( values, attr );

				return attr;
			},

			getStyleVars: function( values ) {
				var cssVars = [
					'word_spacing',
					'text_align',
					'font_size',
					'line_height',
					'letter_spacing',
					'text_transform',
					'text_color',
				];

				cssVars.path_width            = { 'callback': _.fusionGetValueWithUnit };
				cssVars.path_rotation         = { 'callback': _.fusionGetValueWithUnit, 'args': [ values.path_rotation, 'deg' ] };
				cssVars.padding_top           = { 'callback': _.fusionGetValueWithUnit };
				cssVars.padding_right         = { 'callback': _.fusionGetValueWithUnit };
				cssVars.padding_bottom        = { 'callback': _.fusionGetValueWithUnit };
				cssVars.padding_left          = { 'callback': _.fusionGetValueWithUnit };
				cssVars.padding_top_medium    = { 'callback': _.fusionGetValueWithUnit };
				cssVars.padding_right_medium  = { 'callback': _.fusionGetValueWithUnit };
				cssVars.padding_bottom_medium = { 'callback': _.fusionGetValueWithUnit };
				cssVars.padding_left_medium   = { 'callback': _.fusionGetValueWithUnit };
				cssVars.padding_top_small     = { 'callback': _.fusionGetValueWithUnit };
				cssVars.padding_right_small   = { 'callback': _.fusionGetValueWithUnit };
				cssVars.padding_bottom_small  = { 'callback': _.fusionGetValueWithUnit };
				cssVars.padding_left_small    = { 'callback': _.fusionGetValueWithUnit };
				cssVars.margin_top            = { 'callback': _.fusionGetValueWithUnit };
				cssVars.margin_right          = { 'callback': _.fusionGetValueWithUnit };
				cssVars.margin_bottom         = { 'callback': _.fusionGetValueWithUnit };
				cssVars.margin_left           = { 'callback': _.fusionGetValueWithUnit };
				cssVars.margin_top_medium     = { 'callback': _.fusionGetValueWithUnit };
				cssVars.margin_right_medium   = { 'callback': _.fusionGetValueWithUnit };
				cssVars.margin_bottom_medium  = { 'callback': _.fusionGetValueWithUnit };
				cssVars.margin_left_medium    = { 'callback': _.fusionGetValueWithUnit };
				cssVars.margin_top_small      = { 'callback': _.fusionGetValueWithUnit };
				cssVars.margin_right_small    = { 'callback': _.fusionGetValueWithUnit };
				cssVars.margin_bottom_small   = { 'callback': _.fusionGetValueWithUnit };
				cssVars.margin_left_small     = { 'callback': _.fusionGetValueWithUnit };

				if ( 'yes' === values.show_text_stroke ) {
					cssVars.push( 'text_stroke_width' );
					cssVars.push( 'text_stroke_color' );
				}

				if ( 'yes' === values.show_path ) {
					cssVars.push( 'path_stroke_width' );
					cssVars.push( 'path_stroke_color' );
					cssVars.push( 'path_fill_color' );
				}

				return this.getCssVarsForOptions( cssVars ) + this.getFontStylingVars( 'text_font', values );
			},

			/**
			 * Builds attributes.
			 *
			 * @since 3.13.0
			 * @param {Object} values - The values object.
			 * @return {Object}
			 */
			buildPathAnimationAttr: function( values ) {
				let attr = {
					'attributeName': 'transform',
					'attributeType': 'XML',
					'type': String( values.path_animation_type ).replace( '_x', '' ).replace( '_y', '' ),
					'begin': '0s',
					'dur': values.path_animation_duration,
					'repeatCount': 'indefinite'
				};

				let elongation = 0,
				rotValues;

				switch( values.path_animation_type ) {
					case 'scale':
						attr.values = '1;' + values.path_animation_scale + ';1';
						break;
					case 'wiggle':
						attr.type     = 'undefined' !== typeof values.skewy ? 'skewY' : 'skewX';
						attr.values   = '-' + values.path_animation_wiggle + ';' + values.path_animation_wiggle +  ';-' + values.path_animation_wiggle;
						attr.additive = 'sum';
						break;
					case 'translate_x':
						elongation = parseInt( values.path_width ) * parseInt( values.path_animation_slide ) / 100;
						attr.values = '-' + elongation + ',0;' + elongation + ',0;-' + elongation + ',0';
						break;
					case 'translate_y':
						elongation = parseInt( values.path_width ) * parseInt( values.path_animation_slide ) / 100;
						attr.values = '0,-' + elongation + ';0,' + elongation + ';0,-' + elongation;
						break;
					case 'rotate':
						rotValues = '360' === values.path_animation_rotate ? '0;' + values.path_animation_rotate : '0;' + values.path_animation_rotate + ';0';
						attr.values = rotValues;
						break;
				}

				return attr;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 3.13.0
			 * @param {Object} values - The values object.
			 * @return {Object}
			 */
			buildpathAnimationSkewYAttr: function( values ) {
				values.skewy = true;

				return this.buildPathAnimationAttr( values );
			},

			/**
			 * Builds attributes.
			 *
			 * @since 3.13.0
			 * @param {Object} values - The values object.
			 * @return {Object}
			 */
			buildPathAttr: function( values ) {
				let attr = {
					'class': 'awb-text-path__path awb-text-path__path_' + values.show_path,
					'id': values.path_type + '-' + this.model.get( 'cid' ),
					'd': this.values.svgData.path,
					'style': 'transform-origin: center;'
				};

				return attr;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 3.13.0
			 * @param {Object} values - The values object.
			 * @return {Object}
			 */
			buildTextAttr: function( values ) {
				let attr = {};

				if ( values.text_direction ) {
					attr.direction = values.text_direction;
				}

				if ( 'undefined' !== typeof this.values.svgData.text_attr ) {
					attr[ this.values.svgData.text_attr.key ] = this.values.svgData.text_attr.value;
				}

				return attr;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 3.13.0
			 * @param {Object} values - The values object.
			 * @return {Object}
			 */
			buildTextPathAttr: function( values ) {
				let attr = {
					'class' : 'awb-text-path__text-path awb-text-path__text-path_' + values.show_text_stroke,
					'href' : '#' + values.path_type + '-' + this.model.get( 'cid' ),
					'style': ''
				};

				if ( ( jQuery( 'body' ).hasClass( 'rtl' ) && 'ltr' !== values.text_direction ) || 'rtl' === values.text_direction ) {
					attr.startOffset = ( 100 - parseInt( values.start_offset ) ) + '%';
				} else {
					attr.startOffset = values.start_offset + '%';
				}

				if ( 'yes' === values.text_shadow ) {
					attr.style = 'text-shadow:' + _.fusionGetTextShadowStyle( values ).trim() + ';';
				}

				if ( 'yes' === values.lock_font_size ) {
					const factor = 250 / parseInt( values.path_width );
					attr.style += 'font-size:' + factor + 'em;';
				}

				return attr;
			},	

			/**
			 * Builds attributes.
			 *
			 * @since 3.13.0
			 * @param {Object} values - The values object.
			 * @return {Object}
			 */
			buildTextAnimationAttr: function( values ) {
				let attr = {
					'attributeName' : 'startOffset',
					'dur' : values.text_animation_duration,
					'repeatCount' : 'indefinite'
				};

				const isRtl = ( jQuery( 'body' ).hasClass( 'rtl' ) && 'ltr' !== values.text_direction ) || 'rtl' === values.text_direction;

				let startOffset = '';
				let endtOffset = '';

				switch( values.text_animation_direction ) {
					case 'ticker':
						attr.values = isRtl ? '200%;0%' : '-100%;100%;';
						break;
					case 'ltr':
						attr.values = isRtl ? '0%;200%;0%' : '-100%;100%;-100%';
						break;
					case 'rtl':
						attr.values = isRtl ? '200%;0%;200%' : '100%;-100%;100%';
						break;
					case 'custom':
						startOffset = isRtl ? ( 100 - parseFloat( values.text_animation_start_offset ) ) + '%' : values.text_animation_start_offset + '%';
						endtOffset = isRtl ? ( 100 - parseFloat( values.text_animation_end_offset ) ) + '%' : values.text_animation_end_offset + '%';

						attr.values = startOffset + ';' + endtOffset + ';' + startOffset;
				}

				return attr;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 3.13.0
			 * @param {Object} values - The values object.
			 * @return {Object}
			 */
			buildTextAnimationFontSizeFactorAttr: function( values ) {
				let baseFactor = 1;
				if ( 'yes' === values.lock_font_size ) {
					baseFactor = 250 / parseInt( values.path_width );
				}

				let attr = {
					'attributeName' : 'font-size',
					'dur' : values.text_animation_duration,
					'repeatCount' : 'indefinite',
					'values': baseFactor + 'em;' + parseFloat(values.text_animation_font_size_factor * baseFactor) + 'em;' + baseFactor + 'em;'
				};

				return attr;
			},		

			/**
			 * Builds attributes.
			 *
			 * @since 3.13.0
			 * @param {Object} values - The values object.
			 * @return {Object}
			 */
			buildLinkAttr: function( values ) {
				let attr = {
					'class' : 'awb-text-path__link',
					'href' : values.link,
					'target' : values.link_target,
				};

				return attr;
			}
		} );
	} );
}( jQuery ) );
