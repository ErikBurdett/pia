/* global FusionEvents, FusionApp, fusionBuilderText */

var FusionPageBuilder = FusionPageBuilder || {};

( function() {

	FusionPageBuilder.DynamicValues = Backbone.Model.extend( {
		defaults: {
			values: {},
			options: {},
			orderedParams: false
		},

		getOrderedParams: function() {
			var params  = this.get( 'orderedParams' ),
				options = this.getOptions();

			if ( ! params ) {
				params = {};
				_.each( options, function( object, id ) {
					var group,
						groupText;

					if ( 'object' !== typeof object ) {
						return;
					}

					group     = object.group;
					groupText = group;

					if ( 'string' !== typeof object.group ) {
						group     = 'other';
						groupText = fusionBuilderText.other;
					}

					group = group.replace( /\s+/g, '_' ).toLowerCase();

					if ( 'object' !== typeof params[ group ] ) {
						params[ group ] = {
							label: '',
							params: {}
						};
					}

					params[ group ].label        = groupText;
					params[ group ].params[ id ] = object;
				} );
			}
			return params;
		},

		addData: function( data, options ) {
			this.set( 'values', data );
			this.set( 'options', options );
		},

		getOptions: function() {
			var options = this.get( 'options' );

			return jQuery.extend( true, {}, options );
		},

		getOption: function( param ) {
			var options = this.getOptions();

			return 'undefined' !== typeof options[ param ] ? options[ param ] : false;
		},

		getAll: function() {
			var values = this.get( 'values' );

			return jQuery.extend( true, {}, values );
		},

		getValue: function( args ) {
			var values   = this.getAll(),
				id       = args.data,
				postId   = FusionApp.getDynamicPost( 'post_id' ),
				idValues = false,
				match    = false;

			if ( 'undefined' !== typeof values[ postId ] ) {
				idValues = 'object' === typeof values[ postId ][ id ] ? values[ postId ][ id ] : false;
			}

			// No initial match, fetch it.
			if ( ! idValues ) {
				return this.fetchValue( id, args );
			}

			// Handle inline dynamic data. (typeof idValues[0].value part is for values stored after callback).
			if ( 0 === id.indexOf( '{' ) && 1 === Object.keys( idValues ).length && ( 'string' === typeof idValues[0] || 'string' === typeof idValues[0].value ) ) {
				match = 'string' === typeof idValues[0] ? { 'value': idValues[0] } : { 'value': idValues[0].value };
			} else {

				// Check each value object with same ID.
				match = this.findMatch( idValues, args );
			}

			// We found a matching object, then return its value.
			if ( match ) {
				return match.value;
			}

			// No match, fetch.
			return this.fetchValue( id, args );
		},

		findMatch: function( idValues, args, idWanted ) {
			var match = false;

			idWanted = 'undefined' === typeof idWanted ? false : idWanted;

			_.each( idValues, function( idValue, idCount ) {
				var argsMatch = true;

				// Already found a match, just return early.
				if ( match ) {
					return true;
				}

				// Value object has no args, then set match and return.
				if ( 'undefined' === typeof idValue.args ) {
					match = idWanted ? idCount : idValue;
					return true;
				}

				// We do have args, check that each value matches.
				if ( 'object' === typeof idValue.args ) {
					if ( Object.keys( idValue.args ).length !== Object.keys( args ).length ) {
						argsMatch = false;
						return true;
					}
					_.each( idValue.args, function( argValue, argId ) {
						if ( 'undefined' === typeof args[ argId ] || 'before' === argId || 'after' === argId || 'fallback' === argId ) {
							return true;
						}
						if ( args[ argId ] !== argValue ) {
							argsMatch = false;
						}
					} );

					if ( argsMatch ) {
						match = idWanted ? idCount : idValue;
					}
				}
			} );

			return match;
		},

		fetchValue: function( id, args ) {
			var options          = this.getOptions(),
				optionId         = id.replace( '{', '' ).replace( '}', '' ).split( ',' )[0],
				callbackArgs     = args,
				param            = 'object' === typeof options && 'object' === typeof options[ optionId ] ? options[ optionId ] : false,
				callback         = param && 'undefined' !== typeof param.callback ? param.callback : false,
				callbackFunction = callback && 'string' === typeof callback[ 'function' ] ? callback[ 'function' ] : false,
				callbackExists   = callbackFunction && 'function' === typeof FusionApp.callback[ callbackFunction ] ? true : false,
				callbackAjax     = callbackExists && 'undefined' !== typeof callback.ajax ? callback.ajax : false,
				dynamicPost,
				value;

			// If no callback found, use default ajax one.
			if ( ! callbackExists ) {
				callbackFunction = 'defaultDynamicCallback';
				callbackAjax     = true;
			}

			if ( ! param ) {
				this.setValue( args, false );
				return false;
			}

			// Return default (dummy) value if template post is set as target post.
			dynamicPost = 'fusion_tb_section' === FusionApp.data.postDetails.post_type || 'post_cards' === FusionApp.data.template_category;

			if ( true === FusionApp.data.is_singular && dynamicPost && -99 === FusionApp.getDynamicPost( 'post_id' ) && 'undefined' !== typeof param[ 'default' ] ) {
				return param[ 'default' ];
			}

			// Inline dynamic data handling.
			callbackArgs.store_id = callbackArgs.data;
			callbackArgs.data     = optionId;

			// If ajax callback should be run when template is edited.
			if ( true === FusionApp.data.is_singular && dynamicPost && 'undefined' !== typeof param.ajax_on_template && true === param.ajax_on_template ) {
				return FusionApp.callback.defaultDynamicCallback( callbackArgs );
			}

			if ( callbackAjax ) {
				return FusionApp.callback[ callbackFunction ]( callbackArgs );
			}

			value = FusionApp.callback[ callbackFunction ]( callbackArgs );
			this.setValue( args, value );
			return value;
		},

		setValue: function( args, value ) {
			var values   = this.getAll(),
				id       = 'undefined' !== typeof args.store_id ? args.store_id : args.data,
				postId   = FusionApp.getDynamicPost( 'post_id' ),
				matchId  = false,
				newData  = {
					args: jQuery.extend( true, {}, args ),
					value: value
				};

			if ( 'object' !== typeof values[ postId ] ) {
				values[ postId ] = {};
			}

			if ( 'object' !== typeof values[ postId ][ id ] ) {
				values[ postId ][ id ] = {};
			}

			matchId = this.findMatch( values[ postId ][ id ], args, true );

			if ( ! matchId ) {
				const nextIndex = Object.keys( values[ postId ][ id ] )
				.map( Number )
				.filter( n => !isNaN( n ) )
				.reduce( ( max, n ) => Math.max( max, n ), -1 ) + 1;

				values[ postId ][ id ][ nextIndex ] = newData;
			} else {
				values[ postId ][ id ][ matchId ] = newData;
			}

			this.set( 'values', values );

			// ReRender the element.  Perhaps via event using id.
			FusionEvents.trigger( 'fusion-dynamic-data-value', id );
		},

		removeValue: function( id ) {
			var values = this.getAll(),
				postId   = FusionApp.getDynamicPost( 'post_id' );

			if ( 'object' === typeof values[ postId ][ id ] ) {
				delete values[ postId ][ id ];
			}
			this.set( 'values', values );
		}
	} );
}( jQuery ) );
