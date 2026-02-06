/* eslint-disable camelcase, no-console, no-undef */

const AdvAdsTrackingUtils = {
	/**
	 * Check if there are ads.
	 *
	 * @param {Object} data
	 * @return {boolean} true if there are ads, false otherwise.
	 */
	hasAd( data ) {
		for ( const i in data ) {
			if ( Array.isArray( data[ i ] ) && data[ i ].length ) {
				return true;
			}
		}
		return false;
	},

	/**
	 * Custom implementation of jQuery.param.
	 *
	 * @param {Object} data
	 * @return {string} Query string
	 */
	param( data ) {
		return Object.keys( data )
			.map( function ( k ) {
				if ( Array.isArray( data[ k ] ) ) {
					return Object.keys( data[ k ] )
						.map( function ( m ) {
							return (
								encodeURIComponent( k ) +
								'[]=' +
								encodeURIComponent( data[ k ][ m ] )
							);
						} )
						.join( '&' );
				}
				return (
					encodeURIComponent( k ) +
					'=' +
					encodeURIComponent( data[ k ] )
				);
			} )
			.join( '&' )
			.replace( /%20/g, '+' );
	},

	/**
	 * Concat two arrays.
	 *
	 * @return {{}} Object with concatenated arrays.
	 */
	concat() {
		const args = Array.prototype.slice.call( arguments ),
			result = {};

		for ( const i in args ) {
			for ( const j in args[ i ] ) {
				if ( 'undefined' === typeof result[ j ] ) {
					result[ j ] = args[ i ][ j ];
				} else if ( 'function' === typeof result[ j ].concat ) {
					result[ j ] = result[ j ].concat( args[ i ][ j ] );
				}
			}
		}
		return result;
	},

	/**
	 * Get the ads for the gived blog id.
	 *
	 * @param {Object} ads
	 * @param {number} bid
	 * @return {Object} Object with ads for the given blog id.
	 */
	adsByBlog( ads, bid ) {
		const result = {};
		if ( typeof ads[ bid ] !== 'undefined' ) {
			result[ bid ] = ads[ bid ];
		}
		return result;
	},

	/**
	 * Add the frontend prefix to requested data-attributes.
	 *
	 * @param {string} name
	 * @return {string} Prefixed attribute name.
	 */
	getPrefixedAttribute( name ) {
		return '' + window.advadsTracking.frontendPrefix + name;
	},

	/**
	 * Add the frontend prefix to requested attributes from dataset.
	 * These need to be camelCased.
	 *
	 * @param {string} name
	 * @return {string} Prefixed attribute name.
	 */
	getPrefixedDataSetAttribute( name ) {
		return this.getPrefixedAttribute( name )
			.toLowerCase()
			.replace( 'data-', '' )
			.replace( /-([a-z]?)/g, ( m, g ) => g.toUpperCase() );
	},

	/**
	 * Replacement for jQuery.extend.
	 *
	 * @return {Object} Extended object with all properties from the arguments.
	 */
	extend() {
		const extended = {};

		for ( const key in arguments ) {
			const argument = arguments[ key ];
			for ( const prop in argument ) {
				if ( Object.prototype.hasOwnProperty.call( argument, prop ) ) {
					extended[ prop ] = argument[ prop ];
				}
			}
		}

		return extended;
	},

	/**
	 * InArray polyfill.
	 *
	 * @param {(string|number)} needle
	 * @param {Array}           haystack
	 * @return {boolean} True if needle is in haystack, false otherwise.
	 */
	inArray( needle, haystack ) {
		return haystack.indexOf( needle ) > -1;
	},

	/**
	 * Find parent element with specific classname
	 *
	 * @param {Element} el
	 * @param {string}  className
	 */
	findParentByClassName( el, className ) {
		while (
			( el = el.parentElement ) &&
			! el.classList.contains( className )
		) {}
		return el;
	},

	/**
	 * Create current timestamp
	 *
	 * @return {number} Current timestamp in seconds.
	 */
	getTimestamp() {
		if ( ! Date.now ) {
			Date.now = function () {
				return new Date().getTime();
			};
		}
		return Math.floor( Date.now() / 1000 );
	},

	/**
	 * Extend array with unique function.
	 *
	 * @param {string} value
	 * @param {number} index
	 * @param {Array}  self
	 * @return {*[]} unique array.
	 */
	arrayUnique( value, index, self ) {
		return self.indexOf( value ) === index;
	},

	/**
	 * Check if the current blog uses GA tracking (setting or parallel) and UID is set.
	 *
	 * @param {number} bid
	 * @return {boolean} True if the blog uses GA tracking, false otherwise.
	 */
	blogUseGA( bid ) {
		// phpcs:ignore WordPress.WhiteSpace.OperatorSpacing
		return (
			( advads_tracking_methods[ bid ] === 'ga' ||
				advads_tracking_parallel[ bid ] ) &&
			!! advads_gatracking_uids[ bid ]
		);
	},

	/**
	 * POST XHR, replaces jQuery.post
	 *
	 * @param {string}          url
	 * @param {(object|string)} data
	 * @param {boolean}         [async=true]
	 * @return {Promise} Promise that resolves with the response or rejects with an error.
	 */
	post( url, data, async ) {
		const xhr = new XMLHttpRequest();

		if ( false !== async ) {
			xhr.timeout = 5000;
		}

		// Return it as a Promise
		return new Promise( function ( resolve, reject ) {
			xhr.onreadystatechange = function () {
				// Wait for request to complete.
				if ( xhr.readyState !== XMLHttpRequest.DONE ) {
					return;
				}

				// Resolve if 2xx status, reject otherwise.
				if (
					xhr.status === 0 ||
					( xhr.status >= 200 && xhr.status < 300 )
				) {
					resolve( xhr );
				} else {
					reject( {
						status: xhr.status,
						statusText: xhr.statusText,
					} );
				}
			};

			if ( 'undefined' === typeof async ) {
				async = true;
			}

			xhr.open( 'POST', url, async );
			xhr.setRequestHeader(
				'Content-Type',
				'application/x-www-form-urlencoded; charset=UTF-8'
			);
			xhr.send(
				typeof data === 'string'
					? data
					: AdvAdsTrackingUtils.param( data )
			);
		} );
	},
};
window.AdvAdsTrackingUtils = AdvAdsTrackingUtils;
