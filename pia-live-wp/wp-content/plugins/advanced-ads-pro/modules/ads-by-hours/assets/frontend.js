( function () {
	'use strict';
	// eslint-disable-next-line camelcase,no-undef
	if ( ! window.advanced_ads_pro || ! window.advads_pro_utils ) {
		return;
	}

	const date = new Date();

	/**
	 * JS version of WP's PHP zeroise (with threshold fixed to 2)
	 *
	 * @param {string|number} num the number
	 * @return {string} number string with at most one leading zero
	 */
	const zeroise = function ( num ) {
		num = typeof num !== 'string' ? num.toString() : num;
		return num.length < 2 ? `0${ num }` : num;
	};

	// add start and end time into passive CB ad info.
	document.addEventListener(
		'advanced-ads-passive-cb-ad-info',
		function ( ev ) {
			if ( typeof ev.detail.adInfo.by_hours === 'undefined' ) {
				return;
			}

			const hours = ev.detail.adInfo.by_hours.split( '_' );
			ev.detail.ad.by_hours = { start: hours[ 0 ], end: hours[ 1 ] };
		}
	);

	// can display check.
	document.addEventListener(
		'advanced-ads-passive-cb-can-display',
		function ( ev ) {
			if (
				! ev.detail.adInfo.by_hours ||
				! ev.detail.canDisplay.display
			) {
				// no specific hours or already hidden by another check.
				return;
			}

			const now = parseInt(
				zeroise( date.getHours() ) + zeroise( date.getMinutes() ),
				10
			);
			const start = parseInt( ev.detail.adInfo.by_hours.start, 10 );
			const end = parseInt( ev.detail.adInfo.by_hours.end, 10 );
			const canDisplay = {
				show:
					start < end
						? now > start && now < end
						: now > start || now < end,
			};

			// allow filtering of canDisplay for passive cb.
			document.dispatchEvent(
				new CustomEvent( 'advanced-ads-can-display-ads-by-hours', {
					detail: {
						canDisplay,
					},
				} )
			);

			ev.detail.canDisplay.display = canDisplay.show;

			if ( ! canDisplay.show ) {
				// eslint-disable-next-line camelcase,no-undef
				advads_pro_utils.log(
					'passive ad id',
					ev.detail.adInfo.id,
					'cannot be displayed: by_hours'
				);
			}
		}
	);

	// Edit the ajax cb call payload
	document.addEventListener( 'advanced-ads-ajax-cb-payload', function ( ev ) {
		ev.detail.payload.browserTime = `${ zeroise(
			date.getHours()
		) }:${ zeroise( date.getMinutes() ) }`;
	} );
} )();
