/* eslint no-mixed-spaces-and-tabs: 0 */
/* global fusionAllElements, FusionApp, FusionPageBuilderApp */
var FusionPageBuilder = FusionPageBuilder || {};

( function() {


	jQuery( document ).ready( function() {

		// Fusion Post Cards View.
		FusionPageBuilder.fusion_post_cards = FusionPageBuilder.ElementView.extend( {

			/**
			 * Inner data for nested columns.
			 */
			nestedData: { 'default': { rows: { cols: {} } } },

			onInit: function() {
				if ( this.model.attributes.markup && '' === this.model.attributes.markup.output ) {
					this.model.attributes.markup.output = this.getComponentPlaceholder();
				}

				this.listenTo( window.FusionEvents, 'fusion-preview-viewport-update', this.viewportPreviewChange );
			},

			/**
			 * Runs when the viewport preview changes.
			 *
			 * @since 3.13.0
			 * @return {void}
			 */
			viewportPreviewChange: function() {
				const carousel = this.$el.find( '.awb-carousel' );

				if ( carousel.length && carousel[0].swiper ) {

					// Next line is needed, otherwise the native functions of swiper won't work.
					carousel.children( '.swiper-wrapper' ).css( 'height', '' );
					carousel[0].swiper.update();
					carousel[0].swiper.updateAutoHeight();
				}
			},

			/**
			 * Runs before view DOM is patched.
			 *
			 * @since 2.0
			 * @return {void}
			 */
			beforePatch: function() {
				let uniqueIds = [ ...new Set(
					this.$el.find( '.post-card[data-post-card-id]' ).map( function() {
						return jQuery( this ).data( 'post-card-id' );
					} ).get()
				) ];
				const self = this;

				uniqueIds = ! uniqueIds.length ? [ 'default' ] : uniqueIds;

				uniqueIds.forEach( function( id ) {
					self.nestedData[ id ] = { rows: {} };

					const firstMatch = 'default' === id ? self.$el : self.$el.find( '.post-card[data-post-card-id=' + id +']' ).first();

					const innerRows = firstMatch.find( '.fusion-builder-row-inner' );
					if ( innerRows.length ) {
						innerRows.each( function( rowIndex ) {
							self.nestedData[ id ].rows[ rowIndex ] = {};

							self.nestedData[ id ].rows[ rowIndex ].class   = jQuery( this ).attr( 'class' );
							self.nestedData[ id ].rows[ rowIndex ].style   = jQuery( this ).attr( 'style' );
							self.nestedData[ id ].rows[ rowIndex ].columns = {};

							const innerCloumns = jQuery( this ).find( '.fusion_builder_column_inner' );
							if ( innerCloumns.length ) {
								innerCloumns.each( function( colIndex ) {
									self.nestedData[ id ].rows[ rowIndex ].columns[ colIndex ] = {};

									self.nestedData[ id ].rows[ rowIndex ].columns[ colIndex ].class = jQuery( this ).attr( 'class' );
									self.nestedData[ id ].rows[ rowIndex ].columns[ colIndex ].style = jQuery( this ).attr( 'style' );
								} );
							}
						} );
					}
				} );

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
				let uniqueIds = [ ...new Set(
					this.$el.find( '.post-card[data-post-card-id]' ).map( function() {
						return jQuery( this ).data( 'post-card-id' );
					} ).get()
				) ];
				const self = this;

				uniqueIds = ! uniqueIds.length ? [ 'default' ] : uniqueIds;

				uniqueIds.forEach( function( id ) {
					let matches = 'default' === id ? self.$el.find( '.post-card' ) : self.$el.find( '.post-card[data-post-card-id=' + id +']' );

					matches.each( function() {
						const innerRows = jQuery( this ).find( '.fusion-builder-row-inner' );
						if ( innerRows.length ) {
							innerRows.each( function( rowIndex ) {
								if ( 'undefined' !== typeof self.nestedData[ id ] && 'undefined' !== typeof self.nestedData[ id ].rows[ rowIndex ] ) {
									jQuery( this ).attr( 'class', self.nestedData[ id ].rows[ rowIndex ].class );
									jQuery( this ).attr( 'style', self.nestedData[ id ].rows[ rowIndex ].style );

									const innerCloumns = jQuery( this ).find( '.fusion_builder_column_inner' );
									if ( innerCloumns.length ) {
										innerCloumns.each( function( colIndex ) {

											if ( 'undefined' !== typeof self.nestedData[ id ].rows[ rowIndex ].columns[ colIndex ] ) {
												jQuery( this ).attr( 'class', self.nestedData[ id ].rows[ rowIndex ].columns[ colIndex ].class );
												jQuery( this ).attr( 'style', self.nestedData[ id ].rows[ rowIndex ].columns[ colIndex ].style );
											}
										} );
									}

								}
							} );
						}
					} );
				} );

				// This will trigger a JS event on the preview frame.
				this._refreshJs();
			},

			/**
			 * Modify template attributes.
			 *
			 * @since 3.3
			 * @param {Object} atts - The attributes.
			 * @return {Object}
			 */
			filterTemplateAtts: function( atts ) {
				var attributes = {};

				// Validate values.
				this.validateValues( atts.values );
				this.values = atts.values;
				this.extras = atts.extras;

				// Any extras that need passed on.
				attributes.cid           = this.model.get( 'cid' );
				attributes.attr          = this.buildAttr( atts.values );
				attributes.productsLoop  = this.buildOutput( atts );
				attributes.filters       = this.buildFilters( atts );
				attributes.productsAttrs = this.buildProductsAttrs( atts.values );
				attributes.query_data    = atts.query_data;
				attributes.values        = atts.values;
				attributes.loadMoreText  = _.has( atts.extras, 'load_more_text_' + atts.values.post_type ) ? atts.extras[ 'load_more_text_' + atts.values.post_type ] : atts.extras.load_more_text;

				// carousel & slider.
				if ( _.contains( [ 'slider', 'carousel', 'coverflow' ], atts.values.layout ) ) {
					attributes.prevAttr = this.buildNavIconAttr( atts.values.prev_icon );
					attributes.nextAttr = this.buildNavIconAttr( atts.values.next_icon );
				}

				if ( 'undefined' !== typeof atts.query_data && 'undefined' !== typeof atts.query_data.max_num_pages ) {
					if ( 'undefined' !== typeof atts.query_data.paged ) {
						attributes.pagination = this.buildPagination( atts );
					}
				}

				return attributes;
			},

			/**
			 * Modifies the values.
			 *
			 * @since  3.3
			 * @param  {Object} values - The values object.
			 * @return {void}
			 */
			validateValues: function( values ) {
				if ( 'undefined' !== typeof values.margin_top && '' !== values.margin_top ) {
					values.margin_top = _.fusionGetValueWithUnit( values.margin_top );
				}

				if ( 'undefined' !== typeof values.margin_right && '' !== values.margin_right ) {
					values.margin_right = _.fusionGetValueWithUnit( values.margin_right );
				}

				if ( 'undefined' !== typeof values.margin_bottom && '' !== values.margin_bottom ) {
					values.margin_bottom = _.fusionGetValueWithUnit( values.margin_bottom );
				}

				if ( 'undefined' !== typeof values.margin_left && '' !== values.margin_left ) {
					values.margin_left = _.fusionGetValueWithUnit( values.margin_left );
				}

				if ( 'undefined' !== typeof values.filters_font_size && '' !== values.filters_font_size ) {
					values.filters_font_size = _.fusionGetValueWithUnit( values.filters_font_size );
				}

				if ( 'undefined' !== typeof values.filters_letter_spacing && '' !== values.filters_letter_spacing ) {
					values.filters_letter_spacing = _.fusionGetValueWithUnit( values.filters_letter_spacing );
				}

				if ( 'undefined' !== typeof values.active_filter_border_size && '' !== values.active_filter_border_size ) {
					values.active_filter_border_size = _.fusionGetValueWithUnit( values.active_filter_border_size );
				}

				if ( 'undefined' !== typeof values.filters_border_bottom && '' !== values.filters_border_bottom ) {
					values.filters_border_bottom = _.fusionGetValueWithUnit( values.filters_border_bottom );
				}

				if ( 'undefined' !== typeof values.filters_border_top && '' !== values.filters_border_top ) {
					values.filters_border_top = _.fusionGetValueWithUnit( values.filters_border_top );
				}

				if ( 'undefined' !== typeof values.filters_border_left && '' !== values.filters_border_left ) {
					values.filters_border_left = _.fusionGetValueWithUnit( values.filters_border_left );
				}

				if ( 'undefined' !== typeof values.filters_height && '' !== values.filters_height ) {
					values.filters_height = _.fusionGetValueWithUnit( values.filters_height );
				}

				if ( 'undefined' !== typeof values.filters_border_right && '' !== values.filters_border_right ) {
					values.filters_border_right = _.fusionGetValueWithUnit( values.filters_border_right );
				}

				if ( 1 === parseInt( values.columns ) && ( 'grid' === values.layout || 'masonry' === values.layout ) ) {
					values.column_spacing = '0';
				}

				if ( 0 === parseInt( values.columns ) ) {
					values.columns = 4;
				}

				// No delay offering for carousels and sliders.
				if ( 'grid' !== values.layout ) {
					values.animation_delay = 0;
				}
			},

			/**
			 * Builds attributes.
			 *
			 * @since  3.3
			 * @param  {Object} values - The values object.
			 * @return {Object}
			 */
			buildAttr: function( values ) {
				var attr = _.fusionVisibilityAtts( values.hide_on_mobile, {
						class: 'fusion-post-cards fusion-post-cards-' + this.model.get( 'cid' ),
						style: this.getInlineStyle( values )
					} ),
					animationValues;


				if ( '' !== values.animation_type ) {

					// Grid and has delay, set parent args here, otherwise it will be on children.
					if ( 'grid' === values.layout && 0 !== parseInt( values.animation_delay ) ) {
						// Post cards has another animation delay implemented, not on whole element, but between each post cards.
						animationValues = JSON.parse( JSON.stringify( values ) );
						animationValues.animation_delay = '';

						attr = _.fusionAnimations( animationValues, attr, false );

						attr[ 'data-animation-delay' ] = values.animation_delay;
						attr[ 'class' ]               += ' fusion-delayed-animation';
					} else {
						// Post cards has another animation delay implemented, not on whole element, but between each post cards.
						animationValues = JSON.parse( JSON.stringify( values ) );
						animationValues.animation_delay = '';

						// Not grid always no delay, add to parent.
						attr = _.fusionAnimations( animationValues, attr );
					}
				}

				if ( -1 !== jQuery.inArray( values.layout, [ 'slider', 'carousel', 'marquee', 'coverflow' ] ) ) {
					attr[ 'class' ] += ' awb-carousel awb-swiper awb-swiper-' + values.layout + ' awb-swiper-dots-position-' + values.dots_position;

					attr[ 'data-autoplay' ]       = values.autoplay;
					attr[ 'data-autoplayspeed' ]  = values.autoplay_speed;
					attr[ 'data-autoplaypause' ]  = values.autoplay_hover_pause;
					attr[ 'data-loop' ]           = values.loop;
					attr[ 'data-columns' ]        = values.columns;
					attr[ 'data-columnsmedium' ]  = values.columns_medium;
					attr[ 'data-columnssmall' ]   = values.columns_small;
					attr[ 'data-itemmargin' ]     = values.column_spacing;
					attr[ 'data-itemwidth' ]      = 180;
					attr[ 'data-touchscroll' ]    = values.mouse_scroll;
					attr[ 'data-imagesize' ]      = 'auto';
					attr[ 'data-scrollitems' ]    = values.scroll_items;
					attr[ 'data-mousepointer' ]   = values.mouse_pointer;
					attr[ 'data-layout' ]         = values.layout;
					attr[ 'data-centeredslides' ] = values.centered_slides;
					attr[ 'data-shadow' ]         = values.display_shadow;
					attr[ 'data-speed' ]          = values.transition_speed;
					attr[ 'data-rotationangle' ]  = values.rotation_angle;
					attr[ 'data-depth' ]          = values.coverflow_depth;

					if ( 'marquee' === values.layout) {
						attr[ 'data-marquee-direction' ] = values.marquee_direction;
					}

					if ( 'yes' === values.mouse_scroll && 'custom' === values.mouse_pointer ) {
						attr[ 'data-scrollitems' ] = 1;
					}

					if ( 'slider' === values.layout ) {
						attr[ 'data-slide-effect' ] = values.slider_animation;
					}

					if ( -1 !== jQuery.inArray( values.layout, [ 'carousel', 'coverflow', 'marquee' ] ) && 'yes' === values.mask_edges ) {
						attr[ 'class' ] += ' awb-swiper-masked';
					}

					if ( 'custom' === values.mouse_pointer ) {
						attr[ 'data-cursor-color-mode' ] = values.cursor_color_mode;

						if ( 'custom' === values.cursor_color_mode ) {
							attr[ 'data-cursor-color' ] = values.cursor_color;
						}
					}

				} else if ( ( 'grid' === values.layout || 'masonry' === values.layout || 'stacking' === values.layout  ) && 'terms' !== values.source ) {
					attr[ 'class' ] += ' fusion-grid-archive';
					attr[ 'class' ] += 'masonry' === values.layout ? ' fusion-post-cards-masonry' : '';
				}

				if ( 'grid' === values.layout ) {
					attr[ 'class' ] += ' fusion-grid-columns-' + this.values.columns;
				}

				if ( 'grid' === values.layout &&  1 == this.values.columns && 'no' === this.values.scrolling ) {
					attr[ 'class' ] += ' fusion-grid-flex-grow';
				}

				if ( '' !== values[ 'class' ] ) {
					attr[ 'class' ] += ' ' + values[ 'class' ];
				}

				if ( '' !== values.id ) {
					attr.id = values.id;
				}

				return attr;
			},

			/**
			 * Builds nav icon attributes.
			 *
			 * @since 3.9
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
			 * Builds items UL attributes.
			 *
			 * @since 3.3
			 * @param {Object} values - The values.
			 * @return {Object}
			 */
			buildProductsAttrs: function( values ) {
				var attr = {
					class: ''
				};

				if ( 'grid' === values.layout || 'masonry' === values.layout ) {
					attr[ 'class' ] += 'fusion-grid fusion-grid-' + values.columns + ' fusion-flex-align-items-' + values.flex_align_items + ' fusion-' + values.layout + '-posts-cards';
				} else if ( 'stacking' === values.layout ) {
					attr[ 'class' ] += 'fusion-grid fusion-' + values.layout + '-posts-cards';
				} else if ( -1 !== jQuery.inArray( values.layout, [ 'slider', 'carousel', 'marquee', 'coverflow' ] ) ) {
					attr[ 'class' ] += 'swiper-wrapper';

					if ( 'slider' !== values.layout ) {
						attr[ 'class' ] += ' fusion-flex-align-items-' + values.flex_align_items;
					}
				}

				if ( this.isLoadMore() ) {
					attr[ 'class' ] += ' fusion-grid-container-infinite';
				}

				if ( 'load_more_button' === values.scrolling ) {
					attr[ 'class' ] += ' fusion-grid-container-load-more';
				}
				return attr;
			},

			/**
			 * Builds columns classes.
			 *
			 * @since 3.3
			 * @param {Object} atts - The attributes.
			 * @return {string}
			 */
			buildColumnClasses: function( atts ) {
				var classes = '';

				if ( 'grid' === atts.values.layout || 'masonry' === atts.values.layout || 'stacking' === atts.values.layout  ) {
					classes += 'fusion-grid-column fusion-post-cards-grid-column';
				} else if ( _.contains( [ 'slider', 'carousel', 'marquee', 'coverflow'  ], atts.values.layout ) ) {
					classes += 'swiper-slide';
				}

				if ( 'product' === atts.values.post_type && 'posts' === atts.values.source ) {
					classes += ' product';
				}
				return classes;
			},

			/**
			 * Builds columns wrapper.
			 *
			 * @since 3.3
			 * @param {Object} atts - The attributes.
			 * @return {string}
			 */
			buildColumnWrapper: function( atts ) {
				var classes = '';

				if ( 'carousel' === atts.values.layout || 'marquee' === atts.values.layout || 'coverflow' === atts.values.layout ) {
					classes += 'fusion-carousel-item-wrapper';
				}
				return classes;
			},

			/**
			 * Builds the pagination.
			 *
			 * @since 3.3
			 * @param {Object} atts - The attributes.
			 * @return {string}
			 */
			buildPagination: function( atts ) {
				var globalPagination  = atts.extras.pagination_global,
					globalStartEndRange = atts.extras.pagination_start_end_range_global,
					range            = atts.extras.pagination_range_global,
					paged            = '',
					pages            = '',
					paginationCode   = '',
					queryData        = atts.query_data,
					values           = atts.values;

				if ( -1 == values.number_posts ) {
					values.scrolling = 'no';
				}

				if ( 'no' !== values.scrolling ) {
					paged = queryData.paged;
					pages = queryData.max_num_pages;

					paginationCode = _.fusionPagination( pages, paged, range, values.scrolling, globalPagination, globalStartEndRange );
				}
				return paginationCode;
			},

			/**
			 * Check is load more.
			 *
			 * @since 3.3
			 * @return {boolean}
			 */
			isLoadMore: function() {
				return -1 !== jQuery.inArray( this.values.scrolling, [ 'infinite', 'load_more_button' ] );
			},

			/**
			 * Get reverse number.
			 *
			 * @since 3.3
			 * @param {String} value - the number value.
			 * @return {String}
			 */
			getReverseNum: function( value ) {
				return -1 !== value.indexOf( '-' ) ? value.replace( '-', '' ) : '-' + value;
			},

			/**
			 * Get grid width value.
			 *
			 * @since 3.3
			 * @param {String} columns - the columns count.
			 * @return {String}
			 */
			getGridWidthVal: function( columns ) {
				var cols = [ '100%', '50%', '33.3333%', '25%', '20%', '16.6666%' ];
				return cols[ columns - 1 ];
			},

			/**
			 * Builds output.
			 *
			 * @since  3.3
			 * @param  {Object} values - The values object.
			 * @return {String}
			 */
			buildOutput: function( atts ) {
				var output     = '',
					tag        = '',
					childTag   = '',
					loopOutput = '',
					liOutput   = '',
					_self      = this,
					lists;

				if ( 'undefined' !== typeof atts.markup && 'undefined' !== typeof atts.markup.output && 'undefined' === typeof atts.query_data ) {
					output = jQuery( jQuery.parseHTML( atts.markup.output ) ).html();
					output = ( 'undefined' === typeof output ) ? atts.markup.output : output;
				} else if ( 'undefined' !== typeof atts.query_data && 'undefined' !== typeof atts.query_data.loop_product ) {
					if ( _.contains( [ 'slider', 'carousel', 'marquee', 'coverflow' ], this.values.layout ) ) {
						tag      = 'div';
						childTag = 'div';
					} else {
						tag      = 'ul';
						childTag = 'li';
					}

					loopOutput = jQuery( atts.query_data.loop_product ).filter( function() {
						return 3 !== this.nodeType; // 3 = Text Node.
					} );

					loopOutput.each( function() {
						const $newElement = jQuery( '<' + childTag + '>' ).html( jQuery( this ).html() );

						// Copy attributes from the original div.
						jQuery.each( this.attributes, function( _, attr ) {
							$newElement.attr( attr.name, attr.value );
						} );

						liOutput += $newElement[0].outerHTML;
					} );

					loopOutput = liOutput;
					lists      = jQuery( '<' + tag + '>' + loopOutput + '</' + tag + '>' );

					lists.children( childTag ).each( function() {
						jQuery( this ).removeClass( 'fusion-grid-column fusion-post-cards-grid-column swiper-slide product' )
						.addClass( _self.buildColumnClasses( atts ) )
						.find( '.fusion-column-wrapper' ).removeClass( 'fusion-carousel-item-wrapper' )
						.addClass( _self.buildColumnWrapper( atts ) );

						if ( 'yes' === atts.values.apply_alternate_post_cards ) {
							let self           = this,
								style          = jQuery( this ).attr( 'style' ),
								cssString      = '',
								postCardValues = _.unescape( FusionPageBuilderApp.base64Decode( atts.values.alternate_post_cards ) ),
								columnSpan     = {
									'columns_large': 1,
									'columns_medium': 0,
									'columns_small': 0,
								};

							const columnSizes  = {
								'columns_large': parseInt( atts.values.columns ),
								'columns_medium': parseInt( atts.values.columns_medium ),
								'columns_small': parseInt( atts.values.columns_small )
							};

							if ( '' !== postCardValues ) {
								postCardValues = JSON.parse( postCardValues );

								jQuery.each( postCardValues, function( index, postCard ) {
									if ( 'undefined' !== typeof postCard.alternate_post_card && postCard.alternate_post_card === jQuery( self ).attr( 'data-post-card-id' ) ) {
										columnSpan = {
											'columns_large': '' !== postCard.column_span ? parseInt( postCard.column_span ) : 0,
											'columns_medium': '' !== postCard.column_span_medium ? parseInt( postCard.column_span_medium ) : 0,
											'columns_small': '' !== postCard.column_span_small ? parseInt( postCard.column_span_small ) : 0,
										}
									}
								} );

								jQuery.each( columnSizes, function( name, columns ) {
									let cssValue = '';

									if ( 0 === columnSpan[ name ] || 1 === columnSpan[ name ] ) {
										cssValue += '';
									} else if ( columnSpan[ name ] >= columns ) {
										cssValue += '100%';
									} else {
										cssValue += 'calc(100% / ' + columns + ' * ' + columnSpan[ name ] + ')';
									}

									if ( cssValue ) {
										if ( 'columns_large' === name ) {
											cssValue = 'width:' + cssValue + ';';
										} else {
											cssValue = '--awb-' + name.replace( /_/g, '-' ) + ':' + cssValue + ';';
										}
									}

									cssString += cssValue;
								} );

								style = style.replace( /width:[^;]*;/, '' ).replace( /--awb-columns-medium:[^;]*;/, '' ).replace( /--awb-columns-small:[^;]*;/, '' );

								jQuery( this ).attr( 'style', style + cssString );
							}

						}

						// Separators are always added into data, just remove if not valid.
						if ( 'grid' !== _self.values.layout || 1 !== parseInt( _self.values.columns ) ) {
							jQuery( this ).find( '.fusion-absolute-separator' ).remove();
						} else {
							jQuery( this ).find( '.fusion-absolute-separator' ).css( { display: 'block' } );
						}

						// No need this class for carousels & slider.
						if ( 'slider' === _self.values.layout || 'carousel' === _self.values.layout || 'marquee' === _self.values.layout || 'coverflow' === _self.values.layout ) {
							jQuery( this ).removeClass( 'fusion-layout-column' );
						}
					} );
					output = lists.html();
				}
				return output;
			},

			buildFilters: function( atts ) {
				var output = '';
				if ( 'undefined' !== typeof atts.markup && 'undefined' !== typeof atts.markup.output && 'undefined' === typeof atts.query_data ) {
					output = jQuery( jQuery.parseHTML( atts.markup.output ) );
					output = ( 'undefined' === typeof output ) ? '' : '<div role="menubar">' + output.find( 'div[role="menubar"]' ).html() + '</div>';
				} else if ( 'undefined' !== typeof atts.query_data && 'undefined' !== typeof atts.query_data.filters ) {
					output = atts.query_data.filters;
				}

				return output;
			},

			/**
			 * Get inline style.
			 *
			 * @since 3.9
			 * @param {object} values
			 * @param {object} extras
			 * @return string
			 */
			 getInlineStyle: function( values ) {
				var cssVarsOptions,
					customVars = [];
				this.values = values;

				cssVarsOptions = [
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
					'filters_line_height',
					'filters_text_transform',
					'filters_alignment',
					'column_spacing',
					'row_spacing',
					'filters_color',
					'filters_hover_color',
					'filters_active_color',
					'active_filter_border_color',
					'filters_border_color',
					'load_more_btn_color',
					'load_more_btn_bg_color',
					'load_more_btn_hover_color',
					'load_more_btn_hover_bg_color'
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
				cssVarsOptions.dots_size = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.dots_active_size = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.dots_margin_top = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.dots_margin_bottom = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.dots_spacing = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.column_spacing = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.row_spacing = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.filters_font_size = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.filters_letter_spacing = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.active_filter_border_size = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.filters_border_bottom = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.filters_border_top = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.filters_border_left = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.filters_border_right = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.filters_height = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.margin_top = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.margin_right = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.margin_bottom = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.margin_left = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.stacking_offset = { 'callback': _.fusionGetValueWithUnit };

				if ( ! this.isDefault( 'arrow_position_vertical' ) ) {
					customVars.arrow_position_vertical_transform = 'none';
				}
				if ( ! this.isDefault( 'filters_border_left' ) ) {
					customVars.filters_border_left_style = 'solid';
				}
				if ( ! this.isDefault( 'filters_border_right' ) ) {
					customVars.filters_border_right_style = 'solid';
				}

				// Responsive Columns.
				if ( 'stacking' === values.layout ) {
					customVars.columns_medium = '100%';
					customVars.columns_small  = '100%';

				} else if ( 'grid' === values.layout || 'masonry' === values.layout ) {
					_.each( [ 'medium', 'small' ], function( size ) {
						var key = 'columns_' + size;
						if ( ! this.isDefault( key ) ) {
							customVars[ key ] = this.getGridWidthVal( values[ key ] );
						}
					}, this );
				}

				// Responsive Filters Alignment.
				if ( 'no' !== values.filters && ( 'grid' === values.layout || 'masonry' === values.layout ) && 'posts' === values.source ) {
					_.each( [ 'medium', 'small' ], function( size ) {
						var key = 'filters_alignment_' + size;
						customVars[ key ] = this.values[ key ];
					}, this );
				}

				return this.getCssVarsForOptions( cssVarsOptions ) + this.getFontStylingVars( 'filters_font', values ) + this.getCustomCssVars( customVars );
			},

			/**
			 * Runs just after render on cancel.
			 *
			 * @since 3.5
			 * @return null
			 */
			 beforeGenerateShortcode: function() {
				var elementType = this.model.get( 'element_type' ),
					options     = fusionAllElements[ elementType ].params,
					values      = jQuery.extend( true, {}, fusionAllElements[ elementType ].defaults, _.fusionCleanParameters( this.model.get( 'params' ) ) );

				if ( 'object' !== typeof options ) {
					return;
				}

				// If images needs replaced lets check element to see if we have media being used to add to object.
				if ( 'undefined' !== typeof FusionApp.data.replaceAssets && FusionApp.data.replaceAssets && ( 'undefined' !== typeof FusionApp.data.fusion_element_type || 'fusion_template' === FusionApp.getPost( 'post_type' ) ) ) {

					this.mapStudioImages( options, values );

					if ( '' !== values.post_card ) {
						// If its not within object already, add it.
						if ( 'undefined' === typeof FusionPageBuilderApp.mediaMap.post_cards[ values.post_card ] ) {
							FusionPageBuilderApp.mediaMap.post_cards[ values.post_card ] = true;
						}
					}

				}
			}
		} );
	} );
}( jQuery ) );
