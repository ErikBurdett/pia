/* eslint-disable camelcase, no-console, no-undef */

window.AdvAdsImpressionTracker = {
	ajaxAds: {},
	passiveAds: {},
	initialAds: {},
	removeDelayedAds( ids ) {
		const trackIds = document.querySelectorAll(
				'[data-' +
					AdvAdsTrackingUtils.getPrefixedAttribute( 'trackid' ) +
					'][data-delayed="1"]'
			),
			trackIdsLength = trackIds.length;

		if ( ! trackIdsLength ) {
			return ids;
		}

		for ( let i = 0; i < trackIdsLength; i++ ) {
			const id = parseInt(
					trackIds[ i ].dataset[
						AdvAdsTrackingUtils.getPrefixedDataSetAttribute(
							'trackid'
						)
					],
					10
				),
				bid = parseInt(
					trackIds[ i ].dataset[
						AdvAdsTrackingUtils.getPrefixedDataSetAttribute(
							'trackbid'
						)
					],
					10
				);

			if (
				AdvAdsTrackingUtils.hasAd( ids ) &&
				typeof ids[ bid ] !== 'undefined'
			) {
				const index = ids[ bid ].indexOf( id );
				if ( index > -1 ) {
					ids[ bid ].splice( index, 1 );
				}
			}
		}

		return ids;
	},
	track( ids, server ) {
		server = server ? server : 'all';
		if ( ! AdvAdsTrackingUtils.hasAd( ids ) ) {
			return;
		} // do not send empty array

		for ( const bid in ids ) {
			if ( AdvAdsTrackingUtils.blogUseGA( bid ) ) {
				// ad IDs already collected and will be sent automatically once the Analytics tracker is ready
				advadsGATracking.deferedAds = AdvAdsTrackingUtils.concat(
					advadsGATracking.deferedAds,
					AdvAdsTrackingUtils.adsByBlog( ids, bid )
				);

				if ( server === 'delayed' ) {
					// "Delayed" tracking. Explicitly defined for placements that initially hide ads (timeout/scroll)
					this.triggerEvent( 'advadsGADelayedTrack' );
				} else {
					// the "usual" deferred tracking (once the GA tracker is ready)
					this.triggerEvent( 'advadsGADeferedTrack' );
				}

				if (
					server === 'ajax' &&
					AdvAdsTrackingUtils.hasAd(
						AdvAdsTrackingUtils.adsByBlog( this.ajaxAds, bid )
					)
				) {
					// remove all tracked ajax ads
					for ( const i in this.ajaxAds[ bid ] ) {
						const index = ids[ bid ].indexOf(
							this.ajaxAds[ bid ][ i ]
						);
						if ( index > -1 ) {
							this.ajaxAds[ bid ].splice( i, 1 );
						}
					}
				}
			}

			if (
				server !== 'ajax' && // ads already tracked through AJAX cache-busting
				( advads_tracking_methods[ bid ] === 'frontend' || // default AJAX handler
					advads_tracking_methods[ bid ] === 'onrequest' ) // also track locally if delayed ads
			) {
				// send tracking data to the server.
				this.sendTrack( bid, ids[ bid ] );
			}

			this.ajaxAds = {};
		}
	},
	triggerEvent( name ) {
		const event = new CustomEvent( name );
		document.dispatchEvent( event );
	},
	sendTrack( bid, ads ) {
		if ( ! ads.length ) {
			return;
		}
		AdvAdsTrackingUtils.post( advads_tracking_urls[ bid ], {
			ads,
			action: window.advadsTracking.impressionActionName,
			referrer: window.location.pathname + window.location.search,
			bid,
		} );
	},
};

( function () {
	const localTracker = function () {
		if ( typeof advads_tracking_ads === 'undefined' ) {
			return;
		}

		advads_tracking_ads =
			window.AdvAdsImpressionTracker.removeDelayedAds(
				advads_tracking_ads
			);
		if ( ! AdvAdsTrackingUtils.hasAd( advads_tracking_ads ) ) {
			return;
		}

		for ( const bid in advads_tracking_ads ) {
			if ( advads_tracking_methods[ bid ] !== 'frontend' ) {
				continue;
			}

			if (
				typeof advads !== 'undefined' &&
				typeof advads.privacy.is_ad_decoded !== 'undefined'
			) {
				// remove ads that have not been decoded.
				advads_tracking_ads[ bid ] = advads_tracking_ads[ bid ].filter(
					advads.privacy.is_ad_decoded
				);
			}

			// cache-busting: off
			window.AdvAdsImpressionTracker.track( advads_tracking_ads );
			// clean cache-busting: off
			advads_tracking_ads = { 1: [] };
		}
	};

	/**
	 * Add a single ad into an ad list object
	 *
	 * @param {Object} list the ad list.
	 * @param {number} bid  blog ID.
	 * @param {number} ad   ad ID.
	 * @return {Object} the updated ad list.
	 */
	function addSingleAd( list, bid, ad ) {
		if ( 'undefined' === typeof list[ bid ] ) {
			list[ bid ] = [];
		}
		list[ bid ].push( ad );
		return list;
	}
	/**
	 * Whether privacy policy allows us to track
	 *
	 * @return {boolean} true if privacy policy is unknown, false otherwise.
	 */
	function privacyRedLight() {
		return (
			typeof advads !== 'undefined' &&
			advads.privacy.get_state() === 'unknown'
		);
	}
	document.addEventListener( 'advads_track_async', function ( ev ) {
		const bid = ev.detail.bid,
			ad = ev.detail.ad;
		switch ( advads_tracking_methods[ bid ] ) {
			case 'frontend':
				if ( 'undefined' === typeof advads_tracking_ads ) {
					advads_tracking_ads = {};
				}
				advads_tracking_ads = addSingleAd(
					advads_tracking_ads,
					bid,
					ad
				);
				if ( ! privacyRedLight() ) {
					// If red light, tracker will be called on approval.
					localTracker();
				}
				break;
			case 'ga':
				if ( 'undefined' === typeof advadsGATracking.delayedAds ) {
					advadsGATracking.delayedAds = {};
				}
				advadsGATracking.delayedAds = addSingleAd(
					advadsGATracking.delayedAds,
					bid,
					ad
				);
				if ( ! privacyRedLight() ) {
					// If red light, delayed ad list is up to date and GA tracker will be instantiated on approval.
					AdvAdsImpressionTracker.triggerEvent(
						'advadsGADelayedTrack'
					);
				}
				break;
			default:
		}
	} );
	if ( privacyRedLight() ) {
		document.addEventListener( 'advanced_ads_privacy', function ( event ) {
			if (
				[ 'not_needed', 'rejected', 'accepted' ].includes(
					event.detail.state
				)
			) {
				localTracker();
			}
		} );
	} else {
		advanced_ads_ready( localTracker, 'interactive' );
	}
} )();
