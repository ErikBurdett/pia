/* global FusionPageBuilderViewManager */
var FusionPageBuilder = FusionPageBuilder || {};

( function() {

	jQuery( document ).ready( function() {

		// Image Carousel Parent View.
		FusionPageBuilder.fusion_images = FusionPageBuilder.ParentElementView.extend( {

			/**
			 * Image map of child element images and thumbs.
			 *
			 * @since 2.0
			 */
			imageMap: {},

			/**
			 * Initial data has run.
			 *
			 * @since 2.0
			 */
			initialData: false,

			/**
			 * Runs before view DOM is patched.
			 *
			 * @since 2.0
			 * @return {void}
			 */
			beforePatch: function() {

				// Destroy the Swiper instance before creating the new one after patch.
				if ( this.$el.find( '.awb-carousel' ).length && this.$el.find( '.awb-carousel' )[0].swiper ) {
					this.$el.find( '.awb-carousel' )[0].swiper.destroy();
				}

			},

			/**
			 * Runs after view DOM is patched.
			 *
			 * @since 2.0
			 * @return {void}
			 */
			afterPatch: function() {
				this.appendChildren( '.swiper-wrapper' );
				this._refreshJs();
			},

			onRender: function() {
				var columnView = FusionPageBuilderViewManager.getView( this.model.get( 'parent' ) );
				setTimeout( function() {
					if ( columnView && 'function' === typeof columnView._equalHeights ) {
						columnView._equalHeights();
					}
				}, 500 );
			},

			/**
			 * Modify template attributes.
			 *
			 * @since 2.0
			 * @return {Object}
			 */
			filterTemplateAtts: function( atts ) {
				var attributes = {},
					images = window.FusionPageBuilderApp.findShortcodeMatches( atts.params.element_content, 'fusion_image' ),
					imageElement,
					imageElementAtts;

				this.model.attributes.showPlaceholder = false;

				if ( 1 <= images.length ) {
					imageElement     = images[ 0 ].match( window.FusionPageBuilderApp.regExpShortcode( 'fusion_image' ) );
					imageElementAtts = '' !== imageElement[ 3 ] ? window.wp.shortcode.attrs( imageElement[ 3 ] ) : '';

					this.model.attributes.showPlaceholder = ( 'undefined' === typeof imageElementAtts.named || 'undefined' === typeof imageElementAtts.named.image ) ? true : false;
				}

				// Validate values.
				this.validateValues( atts.values );
				this.extras = atts.extras;
				this.values = atts.values;

				// Create attribute objects
				attributes.attr          = this.buildAttr( atts.values );
				attributes.attrCarousel  = this.buildCarouselAttr( atts.values );
				attributes.attrCarouselWrapper  = this.buildCarouselWrapperAttr( atts.values );
				attributes.captionStyles = this.buildCaptionStyles( atts );

				// Whether it has a dynamic data stream.
				attributes.usingDynamic = 'undefined' !== typeof atts.values.multiple_upload && 'Select Images' !== atts.values.multiple_upload;

				attributes.usingDynamicParent = this.isParentHasDynamicContent( atts.values );

				attributes.prevAttr = this.buildNavIconAttr( atts.values.prev_icon );
				attributes.nextAttr = this.buildNavIconAttr( atts.values.next_icon );

				// Any extras that need passed on.
				attributes.values   = atts.values;

				return attributes;
			},

			/**
			 * Modifies the values.
			 *
			 * @since 2.0
			 * @param {Object} values - The values object.
			 * @return {void}
			 */
			validateValues: function( values ) {
				values.column_spacing = _.fusionValidateAttrValue( values.column_spacing, 'px' );
				values.margin_bottom  = _.fusionValidateAttrValue( values.margin_bottom, 'px' );
				values.margin_left    = _.fusionValidateAttrValue( values.margin_left, 'px' );
				values.margin_right   = _.fusionValidateAttrValue( values.margin_right, 'px' );
				values.margin_top     = _.fusionValidateAttrValue( values.margin_top, 'px' );
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values object.
			 * @return {Object}
			 */
			buildAttr: function( values ) {
				var attr = _.fusionVisibilityAtts( values.hide_on_mobile, {
					class: 'fusion-image-carousel fusion-image-carousel-' + this.model.get( 'cid' ),
					style: ''
				} );

				attr[ 'class' ] += ' fusion-image-carousel-' + values.picture_size;

				if ( true === this.model.attributes.showPlaceholder ) {
					attr[ 'class' ] += ' fusion-show-placeholder';
				}

				if ( '' !== values.margin_top ) {
					attr.style += 'margin-top:' + values.margin_top + ';';
				}

				if ( '' !== values.margin_right ) {
					attr.style += 'margin-right:' + values.margin_right + ';';
				}

				if ( '' !== values.margin_bottom ) {
					attr.style += 'margin-bottom:' + values.margin_bottom + ';';
				}

				if ( '' !== values.margin_left ) {
					attr.style += 'margin-left:' + values.margin_left + ';';
				}

				if ( 'yes' === values.lightbox ) {
					attr[ 'class' ] += ' lightbox-enabled';
				}

				if ( '0' !== values.border_width ) {
					attr[ 'class' ] += ' fusion-carousel-border';
				}

				if ( '' !== values[ 'class' ] ) {
					attr[ 'class' ] += ' ' + values[ 'class' ];
				}

				if ( -1 !== jQuery.inArray( values.caption_style, [ 'above', 'below' ] ) ) {
					attr[ 'class' ] += ' awb-image-carousel-top-below-caption awb-imageframe-style awb-imageframe-style-' + values.caption_style + ' awb-imageframe-style-' + this.model.get( 'cid' );
				}

				if ( '' !== values.id ) {
					attr.id = values.id;
				}

				return attr;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values object.
			 * @return {Object}
			 */
			buildCarouselAttr: function( values ) {
				var attr = {
					class: 'awb-carousel awb-swiper awb-swiper-carousel',
					style: this.getInlineStyle( values )
				};

				attr[ 'class' ] += 'arrows_progress' === values.show_nav ? ' awb-swiper-progress-position-' + values.dots_position: ' awb-swiper-dots-position-' + values.dots_position
				attr[ 'class' ] += ' awb-carousel--' + values.layout;

				if ( 'marquee' === values.layout ) {
					attr[ 'data-marquee-direction' ] = values.marquee_direction;
				} else if ( 'slider' === values.layout ) {
					attr[ 'data-slide-effect' ] = values.slide_effect;
				}

				if ( -1 !== jQuery.inArray( values.layout, [ 'carousel', 'coverflow', 'marquee' ] ) && 'yes' === values.mask_edges ) {
					attr[ 'class' ] += ' awb-carousel--masked';
				}

				attr[ 'data-layout' ]         = values.layout;
				attr[ 'data-autoplay' ]       = values.autoplay;
				attr[ 'data-autoplayspeed' ]  = values.autoplay_speed;
				attr[ 'data-autoplaypause' ]  = values.autoplay_hover_pause;
				attr[ 'data-columns' ]        = 1 > values.columns ? 1 : values.columns;
				attr[ 'data-columnsmedium' ]  = 1 > values.columns_medium ? 1 : values.columns_medium;
				attr[ 'data-columnssmall' ]   = 1 > values.columns_small ? 1 : values.columns_small;
				attr[ 'data-itemmargin' ]     = values.column_spacing.toString();
				attr[ 'data-itemwidth' ]      = '180';
				attr[ 'data-touchscroll' ]    = values.mouse_scroll;
				attr[ 'data-imagesize' ]      = values.picture_size;
				attr[ 'data-scrollitems' ]    = values.scroll_items;
				attr[ 'data-centeredslides' ] = values.centered_slides;
				attr[ 'data-rotationangle' ]  = values.rotation_angle;
				attr[ 'data-depth' ]          = values.coverflow_depth;
				attr[ 'data-speed' ]          = values.transition_speed;
				attr[ 'data-shadow' ]         = 'auto' === values.picture_size ? values.display_shadow : 'no';
				attr[ 'data-pagination' ]     = 'arrows_progress' === values.show_nav ? 'progressbar' : 'bullets';

				return attr;
			},

			/**
			 * Builds carousel wrapper attributes.
			 *
			 * @since 3.9.1
			 * @param {Object} values - The values object.
			 * @return {Object}
			 */
			buildCarouselWrapperAttr: function( values ) {
				var attr = {
					class: 'swiper-wrapper awb-image-carousel-wrapper fusion-child-element fusion-flex-align-items-' + values.flex_align_items
				};

				return attr;
			},

			/**
			 * Builds nav icon attributes.
			 *
			 * @since 3.12
			 * @param {String} value - The value.
			 * @return {Object}
			 */
			buildNavIconAttr: function( value ) {
				var icon = {
					class: _.fusionFontAwesome( value ),
					'aria-hidden': 'true'
				};

				return icon;
			},

			/**
			 * Extendable function for when child elements get generated.
			 *
			 * @since 2.0.0
			 * @param {Object} modules An object of modules that are not a view yet.
			 * @return {void}
			 */
			onGenerateChildElements: function( modules ) {
				this.addImagesToImageMap( modules, false, false );
			},

			/**
			 * Add images to the view's image map.
			 *
			 * @since 2.0
			 * @param {Object} childrenData - The children for which images need added to the map.
			 * @param bool async - Determines if the AJAX call should be async.
			 * @param bool async - Determines if the view should be re-rendered.
			 * @return void
			 */
			addImagesToImageMap: function( childrenData, async, reRender ) {
				var view      = this,
					queryData = {};

				async     = ( 'undefined' === typeof async ) ? true : async;
				reRender  = ( 'undefined' === typeof reRender ) ?  true : reRender;

				view.initialData = true;

				_.each( childrenData, function( child ) {
					var params = ( 'undefined' !== typeof child.get ) ? child.get( 'params' ) : child.params,
						cid    = ( 'undefined' !== typeof child.get ) ? child.get( 'cid' ) : child.cid,
						image  = params.image;

					if ( 'undefined' === typeof view.imageMap[ image ] && image ) {
						queryData[ cid ] = params;
					}
				} );

				// Send this data with ajax or rest.
				if ( ! _.isEmpty( queryData ) ) {
					jQuery.ajax( {
						async: async,
						url: window.fusionAppConfig.ajaxurl,
						type: 'post',
						dataType: 'json',
						data: {
							action: 'get_fusion_image_carousel_children_data',
							children: queryData,
							fusion_load_nonce: window.fusionAppConfig.fusion_load_nonce
						}
					} )
					.done( function( response ) {
						view.updateImageMap( response );

						_.each( response, function( imageSizes, image ) {
							if ( 'undefined' === typeof view.imageMap[ image ] ) {
								view.imageMap[ image ] = imageSizes;
							}
						} );

						view.model.set( 'query_data', response );

						if ( reRender ) {
							view.reRender();
						}
					} );
				} else if ( reRender ) {
					view.reRender();
				}
			},

			/**
			 * Update the view's image map.
			 *
			 * @since 2.0
			 * @param {Object} images - The images object to inject.
			 * @return void
			 */
			updateImageMap: function( images ) {
				var imageMap = this.imageMap;

				_.each( images, function( imageSizes, image ) {
					if ( 'undefined' === typeof imageMap[ image ] ) {
						imageMap[ image ] = imageSizes;
					}
				} );
			},

			/**
			 * Get inline style.
			 *
			 * @since 3.9
			 * @param {object} values
			 * @return string
			 */
			getInlineStyle: function( values ) {
				var cssVarsOptions = [
					'arrow_bgcolor',
					'arrow_color',
					'arrow_hover_bgcolor',
					'arrow_hover_color',
					'arrow_border_color',
					'arrow_border_hover_color',
					'dots_color',
					'dots_active_color',
					'arrow_border_style',
					'dots_align',
					'columns',
					'caption_title_transform',
					'caption_title_line_height',
					'caption_text_transform',
					'caption_text_line_height',
					'caption_title_color',
					'caption_text_color',
					'caption_border_color',
					'caption_overlay_color',
					'caption_background_color',
					'border_color',
					'border_style'
				];

				cssVarsOptions.arrow_position_vertical = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.arrow_position_horizontal = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.arrow_size = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.arrow_box_width = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.arrow_box_height = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.arrow_border_sizes_top = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.arrow_border_sizes_right = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.arrow_border_sizes_bottom = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.arrow_border_sizes_left = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.arrow_border_radius_top_left = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.arrow_border_radius_top_right = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.arrow_border_radius_bottom_right = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.arrow_border_radius_bottom_left = { 'callback': _.fusionGetValueWithUnit };

				cssVarsOptions.progress_bar_size = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.dots_size = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.dots_active_size = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.dots_margin_top = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.dots_margin_bottom = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.dots_spacing = { 'callback': _.fusionGetValueWithUnit };

				cssVarsOptions.border_width = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.border_radius_top_left = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.border_radius_top_right = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.border_radius_bottom_right = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.border_radius_bottom_left = { 'callback': _.fusionGetValueWithUnit };

				cssVarsOptions.caption_title_size = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.caption_title_letter_spacing = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.caption_text_size = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.caption_text_letter_spacing = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.caption_margin_top = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.caption_margin_right = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.caption_margin_bottom = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.caption_margin_left = { 'callback': _.fusionGetValueWithUnit };

				return this.getCssVarsForOptions( cssVarsOptions ) + this.getFontStylingVars( 'caption_title_font', values ) + this.getFontStylingVars( 'caption_text_font', values );
			},

			/**
			 * Builds caption styles.
			 *
			 * @since 3.5
			 * @param {Object} atts - The atts object.
			 * @return {string}
			 */
			buildCaptionStyles: function( atts ) {
				var selectors,
					css = '',
					media,
					responsive = '';
				this.dynamic_css  = {};
				this.baseSelector = '.fusion-image-carousel.fusion-image-carousel-' + this.model.get( 'cid' );

				if ( 'off' === atts.values.caption_style ) {
					return '';
				}

				if ( -1 !== jQuery.inArray( atts.values.caption_style, [ 'above', 'below' ] ) ) {
					_.each( [ '', 'medium', 'small' ], function( size ) {
						var key = 'caption_align' + ( '' === size ? '' : '_' + size );

						// Check for default value.
						if ( this.isDefault( key ) ) {
							return;
						}

						this.dynamic_css  = {};

						// Build responsive alignment.
						selectors = [ this.baseSelector + ' .awb-imageframe-caption-container' ];
						this.addCssProperty( selectors, 'text-align', atts.values[ key ] );

						if ( '' === size ) {
							responsive += this.parseCSS();
						} else {
							media       = '@media only screen and (max-width:' + this.extras[ 'visibility_' + size ] + 'px)';
							responsive += media + '{' + this.parseCSS() + '}';
						}
					}, this );
					css += responsive;
				}

				return ( css ) ? '<style>' + css + '</style>' : '';
			}
		} );

		// Image carousel children data callback.
		_.extend( FusionPageBuilder.Callback.prototype, {
			fusion_carousel_images: function( name, value, modelData, args, cid, action, model, view ) { // jshint ignore: line
				view.model.attributes.params[ name ] = value;

				// TODO: on initial load we shouldn't really need to re-render, but may cause issues.
				view.addImagesToImageMap( view.model.children.models, true, view.initialData );

			}
		} );
	} );
}( jQuery ) );
