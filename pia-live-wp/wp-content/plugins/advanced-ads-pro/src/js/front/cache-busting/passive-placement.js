/* eslint-disable */
import jQuery from 'jquery';

export class PassivePlacement {
	/**
	 * Constructor
	 *
	 * @param {Object} placement
	 * @param {string} element_id
	 */
	constructor(placement, element_id) {
		if (
			typeof placement !== 'object' ||
			!placement.hasOwnProperty('id') ||
			!placement.hasOwnProperty('type') ||
			!placement.hasOwnProperty('ads') ||
			!placement.hasOwnProperty('placement_info') ||
			typeof placement.ads !== 'object'
		) {
			throw new SyntaxError(
				'Can not create Advads_passive_cb_Placement obj'
			);
		}

		this.id = placement.id;
		this.ajax_query = placement.ajax_query;
		this.type = placement.type;
		this.element_id = element_id;
		this.ads = placement.ads;
		this.ads_for_ab = placement.ads_for_ab;
		this.groups_for_ab = placement.groups_for_ab;
		this.placement_info = placement.placement_info;
		this.placement_id = advads_pro_utils.isset_nested(
			this.placement_info,
			'id'
		)
			? parseInt(this.placement_info.id, 10)
			: null;

		this.group_info = placement.group_info;
		this.group_wrap = placement.group_wrap;
		this.server_info_duration =
			parseInt(placement.server_info_duration, 10) || 0;
		// Conditions that can be checked by passive cache-busting only if cookies exist.
		// If not, ajax cache-busting will be used.
		this.server_conditions = placement.server_conditions;

		if (placement.inject_before) {
			advanced_ads_pro.inject_before.push({
				elementId: this.element_id,
				data: placement.inject_before,
			});
		}
	}

	/**
	 * Check if the placement can be displayed in frontend due to its own conditions
	 *
	 * @return {boolean} true if the placement can be displayed in frontend, false otherwise
	 */
	can_display() {
		if (
			advads_pro_utils.isset_nested(this.placement_info, 'test_id') &&
			jQuery.inArray(
				this.placement_info.slug,
				advanced_ads_pro.get_random_placements()
			) < 0
		) {
			// do not deliver placement, that belongs to a test, and was not randomly selected by weight
			return false;
		}

		//check if placement was closed with a cookie before (Advads layer plugin)
		if (
			advads_pro_utils.isset_nested(
				this.placement_info,
				'layer_placement',
				'close',
				'enabled'
			) &&
			this.placement_info.layer_placement.close.enabled
		) {
			if (
				advads_pro_utils.isset_nested(
					this.placement_info,
					'layer_placement',
					'close',
					'timeout_enabled'
				) &&
				this.placement_info.layer_placement.close.timeout_enabled &&
				advads_pro_utils.isset(
					advads.get_cookie(
						'timeout_placement_' + this.placement_info.slug
					)
				)
			) {
				return false;
			}
		}

		//check if placement was closed with a cookie before (Sticky Ads plugin)
		if (
			advads_pro_utils.isset_nested(
				this.placement_info,
				'close',
				'enabled'
			) &&
			this.placement_info.close.enabled
		) {
			if (
				advads_pro_utils.isset_nested(
					this.placement_info,
					'close',
					'timeout_enabled'
				) &&
				this.placement_info.close.timeout_enabled &&
				advads_pro_utils.isset(
					advads.get_cookie(
						'timeout_placement_' + this.placement_info.slug
					)
				)
			) {
				return false;
			}
		}

		// don’t show `Custom Position` placement ad if selector doesn’t exist
		if (
			advads_pro_utils.isset_nested(this.placement_info, 'options') &&
			typeof this.placement_info.options === 'object'
		) {
			var params = this.placement_info.options;
			// do not show `Custom Position` placement ad if selector doesn't exist
			if (!advads_pro_utils.selector_exists(params)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if passive cache-busting can be used, i.e. all needed cookies exist.
	 *
	 * @return {boolean} true if passive method can be used for this placement
	 */
	can_use_passive_cb() {
		if (!this.ajax_query) {
			return true;
		}

		var stored_info = window.Advads_passive_cb_Conditions.get_stored_info();
		var now = ~~(new Date().getTime() / 1000);

		for (var hash in this.server_conditions) {
			if (!this.server_conditions.hasOwnProperty(hash)) {
				continue;
			}

			var condition = this.server_conditions[hash];

			var stored_type = stored_info[condition.type];
			if ('object' !== typeof stored_type) {
				return false;
			}

			var stored_condition = stored_type[hash];
			if ('object' !== typeof stored_condition) {
				return false;
			}

			if (
				(parseInt(stored_condition.time, 10) || 0) +
					this.server_info_duration <
				now
			) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Fill the placement with the final output
	 */
	output() {
		let is_empty = true;
		const group_for_ab = this.get_group_for_adblocker();

		if (this.can_display()) {
			switch (this.type) {
				case 'ad':
					if (!this.can_display()) {
						break;
					}
					let ad = new Advads_passive_cb_Ad(
						this.ads[this.id],
						this.element_id
					); // only one ad, pass it as argument

					if (!ad.can_display()) {
						break;
					}

					const ad_for_adblocker = this.get_ad_for_adblocker();

					if (group_for_ab) {
						this.swap_group_info(group_for_ab);
						const group = new Advads_passive_cb_Group(
							this,
							this.element_id
						);
						group.output();
						is_empty = group.is_empty;
						break;
					} else if (
						ad_for_adblocker &&
						ad_for_adblocker.can_display()
					) {
						ad = ad_for_adblocker;
					}

					ad.output({
						track:
							!advads.privacy ||
							'unknown' !== advads.privacy.get_state(),
						inject: true,
						do_has_ad: true,
					});
					is_empty = false;
					break;
				case 'group':
					if (typeof this.group_info === 'object') {
						if (group_for_ab) {
							this.swap_group_info(group_for_ab);
						}
						const group = new Advads_passive_cb_Group(
							this,
							this.element_id
						);
						group.output();
						is_empty = group.is_empty;
					}
					break;
			}
		}

		advanced_ads_pro.dispatchWrapperCBEvent(
			this.element_id,
			is_empty,
			'passive',
			{
				emptyCbOption: Boolean(this.placement_info.cache_busting_empty),
			}
		);
		advanced_ads_pro.observers.fire({
			event: 'inject_placement',
			id: this.placement_id,
			is_empty: is_empty,
			cb_type: 'passive',
		});
		advanced_ads_pro.hasAd(
			this.placement_id,
			'placement',
			this.placement_info.title,
			'passive'
		);
	}

	/**
	 * Swap placement item to group type (if not group yet) and use an alternate group info data
	 *
	 * @param {Array} group data about the alternative group to use
	 */
	swap_group_info(group) {
		this.id = group.id;
		this.type = 'group';
		this.group_info = group;
	}

	/**
	 * Get passive ad object from the ad blocker item of the placement
	 *
	 * @return {Object|boolean} Advads_passive_cb_Ad or false if no ad for ad blocker is set up.
	 */
	get_ad_for_adblocker() {
		return advanced_ads_pro.adblocker_active && this.ads_for_ab
			? new Advads_passive_cb_Ad(
					this.ads_for_ab[Object.keys(this.ads_for_ab)[0]],
					this.element_id
				)
			: false;
	}

	/**
	 * Get group info from the ad blocker item of the placement
	 *
	 * @return {Array|boolean} Group info or false if no group is set up as ad blocker item.
	 */
	get_group_for_adblocker() {
		return advanced_ads_pro.adblocker_active && this.groups_for_ab
			? this.groups_for_ab
			: false;
	}
}

export const PassivePlacementCompat = () => {
	window.Advads_passive_cb_Placement = PassivePlacement;
};
