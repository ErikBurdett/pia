/* eslint-disable camelcase, no-console, no-shadow, no-undef, no-var, prefer-const */

( function () {
	if ( typeof advanced_ads_pro === 'undefined' ) {
		return;
	}

	advanced_ads_pro.observers.add( function ( event ) {
		if (
			[
				'inject_passive_ads',
				'inject_ajax_ads',
				'advanced_ads_decode_inserted_ads',
				'inject_placement',
			].indexOf( event.event ) === -1
		) {
			return;
		}

		if ( Array.isArray( event.ad_ids ) && ! event.ad_ids.length ) {
			event.ad_ids = {};
		}

		let server = 'all',
			adIds = {};

		// find targets for click tracking.
		if ( document.readyState !== 'complete' ) {
			document.addEventListener( 'readystatechange', function ( e ) {
				if ( e.target.readyState === 'complete' ) {
					window.AdvAdsClickTracker.findTargets();
				}
			} );
		} else {
			window.AdvAdsClickTracker.findTargets();
		}

		switch ( event.event ) {
			// waiting for the moment when all passive cache-busting ads will be inserted into html
			case 'inject_passive_ads':
				window.AdvAdsImpressionTracker.passiveAds =
					AdvAdsTrackingUtils.concat(
						window.AdvAdsImpressionTracker.passiveAds,
						event.ad_ids
					);

				for ( let bid in event.ad_ids ) {
					if ( advads_tracking_methods[ bid ] === 'frontend' ) {
						// cache-busting: off + cache-busting: passive
						adIds = AdvAdsTrackingUtils.concat(
							advads_tracking_ads,
							event.ad_ids
						);

						// clean cache-busting: off
						advads_tracking_ads = { 1: [] };
					} else {
						// select only passive cache-busting ads
						server = 'passive';
						adIds = event.ad_ids;
					}

					// remove ads that have not been decoded.
					if (
						typeof advads !== 'undefined' &&
						typeof advads.privacy.is_ad_decoded !== 'undefined'
					) {
						for ( let bid in adIds ) {
							adIds[ bid ] = adIds[ bid ].filter(
								advads.privacy.is_ad_decoded
							);
						}
					}
				}
				break;
			case 'inject_ajax_ads':
				const is_tcf =
					typeof advads !== 'undefined' &&
					window.advads_options.privacy.enabled &&
					window.advads_options.privacy[ 'consent-method' ] ===
						'iab_tcf_20';

				for ( var bid in event.ad_ids ) {
					if ( ! AdvAdsTrackingUtils.blogUseGA( bid ) && ! is_tcf ) {
						continue;
					}

					for ( const i in event.ad_ids[ bid ] ) {
						const el = document.querySelector(
							'[data-' +
								AdvAdsTrackingUtils.getPrefixedAttribute(
									'trackid'
								) +
								'="' +
								event.ad_ids[ bid ][ i ] +
								'"][data-' +
								AdvAdsTrackingUtils.getPrefixedAttribute(
									'trackbid'
								) +
								'="' +
								bid +
								'"]'
						);

						// this is a trigger-able ad. will be tracked on display if using frontend or ga method.
						if (
							el !== null &&
							typeof advads_items !== 'undefined' &&
							typeof advads_items.showed !== 'undefined' &&
							advads_items.showed.indexOf( el.id ) > -1 &&
							typeof el.dataset.delayed !== 'undefined'
						) {
							continue;
						}

						if (
							typeof window.AdvAdsImpressionTracker.ajaxAds[
								bid
							] === 'undefined'
						) {
							window.AdvAdsImpressionTracker.ajaxAds[ bid ] = [];
						}
						window.AdvAdsImpressionTracker.ajaxAds[ bid ].push(
							event.ad_ids[ bid ][ i ]
						);
					}
				}

				// If the privacy setting is TCF and we're using frontend tracking, track all IDs via JS.
				server =
					is_tcf && advads_tracking_methods[ bid ] === 'frontend'
						? 'all'
						: 'ajax';
				adIds = JSON.parse(
					JSON.stringify( window.AdvAdsImpressionTracker.ajaxAds )
				);
				break;
			case 'advanced_ads_decode_inserted_ads':
				adIds = event.ad_ids;
				break;
		}

		// wait for pro to become idle, to make sure we have all ads correctly injected.
		if ( advanced_ads_pro.busy ) {
			document.addEventListener(
				'advanced_ads_pro.idle',
				function () {
					window.AdvAdsImpressionTracker.track(
						window.AdvAdsImpressionTracker.removeDelayedAds(
							adIds
						),
						server
					);
				},
				{ once: true }
			);
		} else {
			window.AdvAdsImpressionTracker.track(
				window.AdvAdsImpressionTracker.removeDelayedAds( adIds ),
				server
			);
		}
	} );
} )();
