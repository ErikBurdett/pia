/* eslint-disable camelcase, no-console, no-undef */
import AdvAdsGATracker from './ga-tracker';

/**
 * Stores Google Analytics tracking script instances
 */
window.advancedAdsGAInstances = {
	instances: [],

	/**
	 * Return instance for given blog id, creates and stores instance if it doesn't exists.
	 *
	 * @param {number} bId
	 * @return {AdvAdsGATracker} Tracker instance
	 */
	getInstance( bId ) {
		if ( typeof this.instances[ bId ] === 'undefined' ) {
			this.instances[ bId ] = new AdvAdsGATracker(
				bId,
				advads_gatracking_uids[ bId ]
			);
		}

		return this.instances[ bId ];
	},
};

document.addEventListener( 'DOMContentLoaded', function () {
	for ( let bid in advads_tracking_methods ) {
		bid = parseInt( bid, 10 );
		if ( isNaN( bid ) ) {
			continue;
		}
		if ( AdvAdsTrackingUtils.blogUseGA( bid ) ) {
			if (
				typeof advads !== 'undefined' &&
				advads.privacy.get_state() === 'unknown'
			) {
				document.addEventListener(
					'advanced_ads_privacy',
					function ( event ) {
						if (
							event.detail.state === 'not_needed' ||
							event.detail.state === 'accepted'
						) {
							advancedAdsGAInstances.getInstance( bid );
						}
					}
				);
				return;
			}

			advancedAdsGAInstances.getInstance( bid );
		}
	}
} );
