/* eslint-disable camelcase, no-console, no-undef */

// needs jQuery because the event gets fired from jQuery.
( function () {
	let targets = 'advads-sticky-trigger';
	if ( typeof advanced_ads_layer_settings !== 'undefined' ) {
		targets += ' ' + advanced_ads_layer_settings.layer_class + '-trigger';
	}

	jQuery( document ).on( targets, function ( ev ) {
		const $target = jQuery( ev.target );
		const ads = {};
		const addAd = function ( id, bid ) {
			id = parseInt( id, 10 );
			bid = parseInt( bid, 10 );
			if ( typeof ads[ bid ] === 'undefined' ) {
				ads[ bid ] = [];
			}

			ads[ bid ].push( id );
		};

		let bid = $target.attr(
			'data-' + AdvAdsTrackingUtils.getPrefixedAttribute( 'trackbid' )
		);

		if ( bid ) {
			if (
				! $target.data( 'delayed' ) ||
				! $target.data(
					AdvAdsTrackingUtils.getPrefixedAttribute( 'impression' )
				)
			) {
				return;
			}

			const id = $target.attr(
				'data-' + AdvAdsTrackingUtils.getPrefixedAttribute( 'trackid' )
			);
			addAd( id, bid );
		} else {
			if (
				! $target.find(
					'[data-' +
						AdvAdsTrackingUtils.getPrefixedAttribute( 'trackbid' ) +
						']'
				).length
			) {
				return;
			}
			$target
				.find(
					'[data-' +
						AdvAdsTrackingUtils.getPrefixedAttribute( 'trackbid' ) +
						']'
				)
				.each( function () {
					const $this = jQuery( this );
					if (
						! $this.data( 'delayed' ) ||
						! $this.data(
							AdvAdsTrackingUtils.getPrefixedAttribute(
								'impression'
							)
						)
					) {
						return;
					}
					bid = $this.attr(
						'data-' +
							AdvAdsTrackingUtils.getPrefixedAttribute(
								'trackbid'
							)
					);
					const id = $this.attr(
						'data-' +
							AdvAdsTrackingUtils.getPrefixedAttribute(
								'trackid'
							)
					);
					addAd( id, bid );
				} );
		}

		if ( AdvAdsTrackingUtils.blogUseGA( bid ) ) {
			advadsGATracking.delayedAds = AdvAdsTrackingUtils.concat(
				advadsGATracking.delayedAds,
				ads
			);
		}

		AdvAdsImpressionTracker.track( ads, 'delayed' );
	} );
} )();
