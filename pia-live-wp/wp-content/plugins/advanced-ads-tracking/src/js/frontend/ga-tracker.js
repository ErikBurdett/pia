/* eslint-disable camelcase, no-console, no-undef */

export default function AdvAdsGATracker( blogId, propertyIds ) {
	this.blogId = blogId;
	this.propertyIds =
		typeof propertyIds === 'string' ? [ propertyIds ] : propertyIds;
	this.normalTrackingDone = false;
	this.clickTimer = null;

	const self = this;

	this.getQueryString = function ( URL ) {
		const anchorElement = document.createElement( 'a' );
		anchorElement.href = URL;
		let queryString = anchorElement.search;
		if ( queryString.length ) {
			queryString = queryString.substr( 1 );
			queryString = queryString.split( '&' );
			if ( queryString.length ) {
				const results = {};
				for ( const i in queryString ) {
					const exp = queryString[ i ].split( '=' );
					results[ exp[ 0 ] ] = exp[ 1 ];
				}
				return results;
			}
		}

		return [];
	};

	this.appendQueryString = function ( url, queryString ) {
		for ( const i in queryString ) {
			if ( -1 !== url.indexOf( '?' ) ) {
				url += '&' + i + '=' + queryString[ i ];
			} else {
				url += '?' + i + '=' + queryString[ i ];
			}
		}
		return url;
	};

	this.trackImpressions = function ( delayed ) {
		if ( typeof delayed === 'undefined' ) {
			delayed = false;
		}
		let trackedAds = [];

		// Normal (not deferred) tracking.
		if (
			! this.normalTrackingDone &&
			AdvAdsTrackingUtils.hasAd(
				AdvAdsTrackingUtils.adsByBlog(
					advads_tracking_ads,
					self.blogId
				)
			)
		) {
			trackedAds = trackedAds.concat(
				advads_tracking_ads[ self.blogId ]
			);
		}

		if ( advads_tracking_methods[ self.blogId ] === 'frontend' ) {
			// means parallel tracking. ads ID-s will be sent at the same time as the normal ajax tracking call
			trackedAds = [];
		}

		if ( delayed ) {
			// delayed ads.
			if (
				typeof advadsGATracking.delayedAds !== 'undefined' &&
				AdvAdsTrackingUtils.hasAd(
					AdvAdsTrackingUtils.adsByBlog(
						advadsGATracking.delayedAds,
						self.blogId
					)
				)
			) {
				trackedAds = trackedAds.concat(
					advadsGATracking.delayedAds[ self.blogId ]
				);
				advadsGATracking.delayedAds[ self.blogId ] = [];
			}
		} else {
			// deferred ads.
			// eslint-disable-next-line no-lonely-if
			if (
				typeof advadsGATracking.deferedAds !== 'undefined' &&
				AdvAdsTrackingUtils.hasAd(
					AdvAdsTrackingUtils.adsByBlog(
						advadsGATracking.deferedAds,
						self.blogId
					)
				)
			) {
				trackedAds = trackedAds.concat(
					advadsGATracking.deferedAds[ self.blogId ]
				);
				advadsGATracking.deferedAds[ self.blogId ] = [];
			}
		}

		if (
			typeof advads !== 'undefined' &&
			typeof advads.privacy.is_ad_decoded !== 'undefined'
		) {
			// remove ads that have not been decoded.
			trackedAds = trackedAds.filter( advads.privacy.is_ad_decoded );
		}

		if ( ! trackedAds.length ) {
			// no ads to track
			return;
		}

		for ( const i in trackedAds ) {
			if (
				typeof advads_gatracking_allads[ self.blogId ][
					trackedAds[ i ]
				] !== 'undefined' &&
				advads_gatracking_allads[ self.blogId ][ trackedAds[ i ] ]
					.impression
			) {
				self.sendEvent( advancedAds.tracking.googleEvents.impression, {
					event_category: 'Advanced Ads',
					event_label:
						'[' +
						trackedAds[ i ] +
						'] ' +
						advads_gatracking_allads[ self.blogId ][
							trackedAds[ i ]
						].title,
					non_interaction: true,
				} );
			}
		}

		this.normalTrackingDone = true;
	};

	this.trackClick = function ( id, serverSide, ev, el ) {
		if ( typeof serverSide === 'undefined' ) {
			serverSide = true;
		}

		const trackData = {
			event_category: 'Advanced Ads',
			event_label:
				'[' +
				id +
				'] ' +
				advads_gatracking_allads[ self.blogId ][ id ].title,
			non_interaction: true,
		};

		// Send the data and stop workflow if it is not a linkout link
		if ( ! ev && ! el ) {
			self.sendEvent(
				advancedAds.tracking.googleEvents.click,
				trackData
			);
			return;
		}

		let url = advads_gatracking_allads[ self.blogId ][ id ].target;
		if ( typeof advadsGATracking.postContext === 'undefined' ) {
			url = url.replace(
				'[CAT_SLUG]',
				advadsGATracking.postContext.cats
			);
			url = url.replace(
				'[POST_ID]',
				advadsGATracking.postContext.postID
			);
			url = url.replace(
				'[POST_SLUG]',
				advadsGATracking.postContext.postSlug
			);
		}
		url = url.replace( '[AD_ID]', id );

		const href = el.getAttribute( 'href' );
		if ( serverSide ) {
			url = href;
		} else {
			url = self.appendQueryString( url, self.getQueryString( href ) );
			if (
				typeof advads_gatracking_transmitpageqs[ self.blogId ] !==
					'undefined' &&
				advads_gatracking_transmitpageqs[ self.blogId ][ id ]
			) {
				url = self.appendQueryString(
					url,
					self.getQueryString( document.location.href )
				);
			}
		}

		const newTab = !! el.getAttribute( 'target' );
		if ( newTab ) {
			// the url is opened in a new tab/window
			self.sendEvent(
				advancedAds.tracking.googleEvents.click,
				trackData
			);
			// no server side tracking, change the link to the real target before the browser opens a new tab
			if ( ! serverSide ) {
				el.setAttribute( 'href', url );
			}
		} else {
			// intercept the default click event behavior
			ev.preventDefault();
			// Creates a timeout to redirect after one second.
			self.clickTimer = setTimeout( function () {
				abortAndRedirect( url, newTab );
			}, 1000 );

			// create a callback to be used as event callback.
			function abortAndRedirect() {
				if ( self.clickTimer !== null ) {
					clearTimeout( self.clickTimer );
					self.clickTimer = null;
				}
				window.location = url;
			}

			trackData.event_callback = abortAndRedirect;
			self.sendEvent(
				advancedAds.tracking.googleEvents.click,
				trackData
			);
		}
	};

	this.sendEvent = ( type, data ) => {
		self.propertyIds.forEach( ( propertyId ) => {
			data.send_to = propertyId;
			gtag( 'event', type, structuredClone( data ) );
		} );
	};

	// pseudo-constructor
	( function () {
		if ( typeof gtag !== 'function' ) {
			// No one has requested gtag.js at this point, require it.
			const script = document.createElement( 'script' );
			script.src = 'https://www.googletagmanager.com/gtag/js';
			script.async = true;

			document.body.appendChild( script );

			window.dataLayer = window.dataLayer || [];
			window.gtag = function () {
				dataLayer.push( arguments );
			};
			gtag( 'js', new Date() );
		}

		const config = { send_page_view: false, transport_type: 'beacon' };
		if ( window.advads_gatracking_anonym ) {
			config.anonymize_ip = true;
		}
		self.propertyIds.forEach( ( id ) => {
			gtag( 'config', id, config );
		} );

		document.addEventListener( 'advadsGADeferedTrack', function () {
			self.trackImpressions( false );
		} );
		document.addEventListener( 'advadsGADelayedTrack', function () {
			self.trackImpressions( true );
		} );
		self.trackImpressions();
	} )();

	return this;
}
