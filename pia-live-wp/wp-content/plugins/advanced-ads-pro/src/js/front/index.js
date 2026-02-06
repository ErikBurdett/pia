import jQuery from 'jquery';
import { PassiveAdCompat } from './cache-busting/passive-ad';
import { PassivePlacementCompat } from './cache-busting/passive-placement';
import { CacheBusting, CacheBustingCompat } from './cache-busting/cacheBusting';
import { PassiveConditionsCompat } from './cache-busting/passive-conditions';
import { PassiveGroupCompat } from './cache-busting/passive-group';
import { Utils, UtilsCompat } from './cache-busting/utils';
import { GroupRefreshCompat } from './cache-busting/group-refresh';
import Adsense from './adsense/adsense';

CacheBustingCompat();
PassiveConditionsCompat();
PassivePlacementCompat();
PassiveAdCompat();
PassiveGroupCompat();
UtilsCompat();
GroupRefreshCompat();

jQuery( () => {
	Adsense();
} );

/* eslint-disable */
if (
	typeof advads !== 'undefined' &&
	typeof advads.privacy.dispatch_event !== 'undefined'
) {
	// check for changes in privacy settings.
	document.addEventListener( 'advanced_ads_privacy', function ( event ) {
		if (
			event.detail.previousState !== 'unknown' &&
			! (
				event.detail.previousState === 'rejected' &&
				event.detail.state === 'accepted'
			)
		) {
			Utils.log(
				'no action! transition from ' +
					event.detail.previousState +
					' to ' +
					event.detail.state
			);
			return;
		}

		Utils.log(
			'reload ads! transition from ' +
				event.detail.previousState +
				' to ' +
				event.detail.state
		);

		if (
			event.detail.state === 'accepted' ||
			event.detail.state === 'not_needed'
		) {
			var encodedAd =
				'script[type="text/plain"][data-tcf="waiting-for-consent"]';

			// Find all scripts and decode them.
			document.querySelectorAll( encodedAd ).forEach( function ( node ) {
				// Add the decoded ad ids to passive_ads, so they can be tracked.
				if (
					! CacheBusting.passive_ads.hasOwnProperty(
						node.dataset.bid
					)
				) {
					CacheBusting.passive_ads[ node.dataset.bid ] = [];
				}
				CacheBusting.passive_ads[ node.dataset.bid ].push(
					parseInt( node.dataset.id, 10 )
				);
				advads.privacy.decode_ad( node );
			} );

			// Observe all child node changes on body; check for dynamically added encoded ads.
			new MutationObserver( function ( mutations ) {
				var decoded_ads = {},
					decode_ad = function ( node ) {
						if (
							typeof node.dataset.noTrack === 'undefined' ||
							node.dataset.noTrack !== 'impressions'
						) {
							if (
								! decoded_ads.hasOwnProperty( node.dataset.bid )
							) {
								decoded_ads[ node.dataset.bid ] = [];
							}
							decoded_ads[ node.dataset.bid ].push(
								parseInt( node.dataset.id, 10 )
							);
						}
						advads.privacy.decode_ad( node );
					};
				mutations.forEach( function ( mutation ) {
					mutation.addedNodes.forEach( function ( node ) {
						// The injected node is the ad itself.
						if (
							typeof node.tagName !== 'undefined' &&
							typeof node.dataset !== 'undefined' &&
							node.tagName.toLowerCase() === 'script' &&
							node.dataset.tcf === 'waiting-for-consent'
						) {
							decode_ad( node );
							return;
						}

						// The injected node might hold encoded ads, e.g. in infinite scroll.
						if (
							typeof node.dataset === 'undefined' ||
							node.dataset.tcf !== 'waiting-for-consent'
						) {
							document
								.querySelectorAll( encodedAd )
								.forEach( decode_ad );
						}
					} );
				} );
				if ( Object.keys( decoded_ads ).length ) {
					CacheBusting.observers.fire( {
						event: 'advanced_ads_decode_inserted_ads',
						ad_ids: decoded_ads,
					} );
				}
			} ).observe( document, {
				subtree: true,
				childList: true,
			} );
		}

		// Wait for advanced_ads_pro to return to idle state.
		if ( CacheBusting.busy ) {
			// Only hook this once to prevent infinite loops.
			document.addEventListener(
				'advanced_ads_pro.idle',
				CacheBusting.process_passive_cb,
				{ once: true }
			);
			return;
		}

		CacheBusting.process_passive_cb();
	} );
} else {
	// Fallback for older versions of base plugin.
	( window.advanced_ads_ready || jQuery( document ).ready ).call(
		null,
		function () {
			CacheBusting.process_passive_cb();
		}
	);
}
/* eslint-enable */

// Reload ads when screen resizes.
jQuery( document ).on( 'advanced-ads-resize-window', function () {
	const handleResize = function () {
		// Remove ajax and passive ads.
		let cbCount = CacheBusting.ads.length;
		while ( cbCount-- ) {
			if ( 'off' !== CacheBusting.ads.cb_method ) {
				CacheBusting.ads.splice( cbCount, 1 );
			}
		}
		CacheBusting.process_passive_cb();
	};
	// Wait for advanced_ads_pro to return to idle state.
	if ( CacheBusting.busy ) {
		// Only hook this once to prevent infinite loops.
		document.addEventListener( 'advanced_ads_pro.idle', handleResize, {
			once: true,
		} );
		return;
	}

	handleResize();
} );

/**
 * Removes placement placeholder if cache busting could not fill it.
 */
document.addEventListener( 'advads_pro_cache_busting_done', ( ev ) => {
	if ( ! ev.detail.isEmpty || ! ev.detail.extra.emptyCbOption ) {
		return;
	}

	let wrapper = document.getElementById( ev.detail.elementId );

	if ( ! wrapper ) {
		return;
	}

	// If the placement is the only thing in the widget (e.g. not within a column block).
	if (
		wrapper.parentNode &&
		wrapper.parentNode.classList.contains( 'widget' )
	) {
		wrapper = wrapper.parentNode;
	}

	wrapper.remove();
} );

/**
 * Update ad health ad count on ad injection.
 */
const updateAdHealthCount = () => {
	const adHealth = document.getElementById(
		'wp-admin-bar-advanced_ads_ad_health_highlight_ads'
	);
	if ( ! adHealth ) {
		return;
	}
	const adCounter = adHealth.querySelector( '.highlighted_ads_count' );
	if ( adCounter ) {
		adCounter.innerText = document.querySelectorAll(
			`.${ window.advancedAds.frontendPrefix }highlight-wrapper`
		).length;
	}
};

CacheBusting.observers.add( ( event ) => {
	if (
		-1 ===
			[ 'inject_passive_ads', 'inject_ajax_ads' ].indexOf(
				event.event
			) ||
		( Array.isArray( event.ad_ids ) && ! event.ad_ids.length )
	) {
		return;
	}
	updateAdHealthCount();
} );
