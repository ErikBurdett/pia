export default () => {
	'use strict';

	const fallbacks = {},
		ajaxResponses = {};
	let Observer = null,
		observedAds = 0;

	/**
	 * Extract AdSense fallback item data from the AdSense ad wrapper
	 *
	 * @param {Node} node the ad or group wrapper.
	 * @return {{}} fallback item data
	 */
	const extractFallBackData = ( node ) => {
		const result = {};
		node.classList.forEach( ( className ) => {
			if ( className.startsWith( 'gas_fallback-' ) ) {
				const exploded = className.split( '-' );
				result.ad = exploded[ 1 ];
				result.fallback = exploded[ 2 ];
			}
		} );
		return result;
	};

	/**
	 * Bind the mutation observer to an AdSense ad tag
	 *
	 * @param {Node} ins the <ins/> tag of the AdSense ad which hold the "data-ad-status" attribute.
	 */
	const observeAd = ( ins ) => {
		if ( null === Observer ) {
			Observer = new window.MutationObserver( ( records ) => {
				for ( const record of records ) {
					if (
						'unfilled' !==
						record.target.getAttribute( 'data-ad-status' )
					) {
						return;
					}
					document.dispatchEvent(
						new CustomEvent( 'advanced_ads_pro_adsense_unfilled', {
							detail: { ad: record.target },
						} )
					);
				}
			} );
		}

		// Only observe the `data-ad-status` attribute, and only one the <ins/> tag.
		Observer.observe( ins, { attributeFilter: [ 'data-ad-status' ] } );
		observedAds++;
	};

	/**
	 * Inject the fallback ad or group using passive CB
	 *
	 * @param {Object} fallBackData fallback item data extracted from the AdSense ad wrapper.
	 * @param {Object} placement    CB placement data (can be in passive or AJAX format).
	 */
	const passiveInject = ( fallBackData, placement ) => {
		const fallBackItem = fallBackData.fallback.split( '_' );

		// The fallback item is an ad.
		if ( 'ad' === fallBackItem[ 0 ] ) {
			const adInfo = getAdInfo( placement, fallBackItem[ 1 ] );

			if ( ! adInfo ) {
				return;
			}

			const passiveAd = new window.Advads_passive_cb_Ad(
				adInfo,
				fallBackData.cbId
			);

			// Remove the current AdSense ad wrapper then inject the fallback ad's output.
			fallBackData.wrapper.remove();
			passiveAd.output( {
				track: true,
				inject: true,
				do_has_ad: true,
			} );
		}

		// The fallback item is a group.
		if ( 'group' === fallBackItem[ 0 ] ) {
			placement.id = parseInt( fallBackItem[ 1 ], 10 );
			placement.type = 'group';
			placement.group_info = placement.adsense_fallback_group_info;

			const passiveGroup = new window.Advads_passive_cb_Group(
				placement,
				fallBackData.cbId
			);

			// Remove the current AdSense ad wrapper then inject the fallback group's output.
			fallBackData.wrapper.remove();
			passiveGroup.output();
		}
	};

	/**
	 * Inject (using passive CB) the fallback into the AdSense ad's CB wrapper.
	 *
	 * Check into the passive placements list first as this is the most common case.
	 * Then check into the AJAX CB placement afterward.
	 * If no matching placement is found do not inject. The feature works only with CB placements.
	 *
	 * @param {Object} fallbackData fallback ad/group data for the current AdSense ad.
	 */
	const injectFallback = ( fallbackData ) => {
		const placement =
			getPassivePlacement( fallbackData.cbId ) ||
			getAjaxPlacement( fallbackData.cbId );

		if ( placement ) {
			passiveInject( fallbackData, placement );
			adHealthEntry( placement );
		}
	};

	/**
	 * Add an ad health entry about the unfilled AdSense slot.
	 *
	 * @param {Object} placement data about the placement containing the failed AdSense ad.
	 */
	const adHealthEntry = ( placement ) => {
		if (
			! advancedAds.adHealthNotice.enabled ||
			! document.getElementById( 'wpadminbar' )
		) {
			return;
		}
		const allFine = document.getElementById(
			'wp-admin-bar-advanced_ads_ad_health_fine'
		);
		allFine?.remove();
		const adHealth = document
			.getElementById( 'wp-admin-bar-advanced_ads_ad_health' )
			.querySelector( 'ul' );
		const li = document.createElement( 'li' );
		li.role = 'group';
		li.id = 'wp-admin-bar-advanced_ads_gads_fallback';
		const div = document.createElement( 'div' );
		div.className = 'ab-item ab-empty-item';
		div.role = 'menuitem';
		div.textContent = window.advancedAds.adHealthNotice.pattern.replace(
			'[ad_title]',
			placement.ads[
				parseInt( placement.placement_info.item.split( '_' )[ 1 ], 10 )
			].title
		);
		adHealth.appendChild( li );
		li.appendChild( div );
	};

	/**
	 * Get data about a potential passive CB placement given the CB wrapper ID (HTML id attribute).
	 *
	 * @param {string} cbId CB wrapper ID (a.k.a. elementid or elementId).
	 * @return {Object|boolean} false if no matching placement found.
	 */
	const getPassivePlacement = ( cbId ) => {
		return (
			Object.values( window.advads_passive_placements ).find(
				( placement ) => placement.elementid.indexOf( cbId ) === 0
			) || false
		);
	};

	/**
	 * Get data about a potential AJAX CB placement given the CB wrapper ID (HTML id attribute).
	 *
	 * @param {string} cbId CB wrapper ID (a.k.a. elementid or elementId).
	 * @return {Object|boolean} false if no matching placement found.
	 */
	const getAjaxPlacement = ( cbId ) => {
		return 'undefined' !== typeof ajaxResponses[ cbId ]
			? ajaxResponses[ cbId ]
			: false;
	};

	/**
	 * Get ad data for passive CB injection of the fallback — iterate (call it repeatedly) through all ads for a group
	 *
	 * @param {Object} placement placement data.
	 * @param {string} adId      the ad ID.
	 * @return {Object|boolean} false if no matching ad found.
	 */
	const getAdInfo = ( placement, adId ) => {
		return placement.ads[ adId ] || false;
	};

	/**
	 * Process fallback when Google signals an unfilled spot
	 */
	document.addEventListener( 'advanced_ads_pro_adsense_unfilled', ( ev ) => {
		observedAds--;
		if ( 0 === observedAds ) {
			// Cleanup when all observed ads are unfilled.
			Observer.disconnect();
		}

		const fallbackData = extractFallBackData(
			ev.detail.ad.closest( '[class^="gas_fallback-"]' )
		);

		if ( ! fallbacks[ fallbackData.ad ] ) {
			return;
		}

		injectFallback( fallbacks[ fallbackData.ad ] );
	} );

	/**
	 * Collect AJAX CB placements data
	 */
	document.body.addEventListener(
		'advads_ajax_cb_response',
		( { detail } ) => {
			detail.response.forEach( ( placement ) => {
				ajaxResponses[ placement.elementId ] = placement;
			} );
		}
	);

	// Initialization
	window.advanced_ads_ready( () => {
		if ( ! window.advanced_ads_pro ) {
			return;
		}

		/**
		 * Observe any newly injected AdSense Ad (injected via CB)
		 */
		window.advanced_ads_pro.postscribeObservers.add( ( event ) => {
			if ( 'postscribe_done' !== event.event ) {
				return;
			}

			if ( -1 === event.ad.indexOf( 'gas_fallback-' ) ) {
				return;
			}

			const cbWrapper = event.ref[ 0 ],
				itemWrapper = cbWrapper.querySelector(
					'[class^="gas_fallback-"]'
				);

			const fallbackData = extractFallBackData( itemWrapper );

			fallbacks[ fallbackData.ad ] = {
				fallback: fallbackData.fallback,
				wrapper: itemWrapper,
				cbId: cbWrapper.id,
			};
			observeAd( itemWrapper.querySelector( 'ins' ) );
		} );
	} );
};
