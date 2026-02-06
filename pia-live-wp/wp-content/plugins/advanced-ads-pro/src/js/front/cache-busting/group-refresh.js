/* eslint-disable */
/*
 * Group refresh
 */
import jQuery from 'jquery';
import { PassivePlacement } from './passive-placement';

export const GroupRefresh = {
	/**
	 * Wrapper ids of groups with refresh enabled.
	 */
	element_ids: {},

	/**
	 * Equivalent passive cb data for groups using ajax refresh
	 */
	passiveRefresh: {},

	/**
	 * Collect placement group and ad data on the very first impression of an ajax refresh group
	 *
	 * @param {Object} data all the data needed for a passive placement creation.
	 */
	collectPassiveRefreshData: (data) => {
		GroupRefresh.passiveRefresh[data.cb_id] = data;
	},

	/**
	 * Empty a CB wrapper then create a passive placement using the same wrapper
	 *
	 * @param {string} cbid id of the CB wrapper.
	 */
	switchToPassive: (cbid) => {
		const data = GroupRefresh.passiveRefresh[cbid];
		setTimeout(
			() => {
				jQuery(`.${data.cb_id}`).empty();
				GroupRefresh.launchRefresh(data);
			},
			parseInt(data.default_interval, 10)
		);
	},

	/**
	 * Create and output a passive placement with a refreshed group
	 *
	 * @param {Object} data data needed for a passive placement creation.
	 */
	launchRefresh: (data) => {
		new PassivePlacement(
			{
				id: data.placement_info.id,
				type: data.type,
				ads: data.ads,
				placement_info: data.placement_info,
				group_info: data.group_info,
				group_wrap: data.group_wrap,
			},
			data.cb_id
		).output();
	},

	/**
	 * Requests group with AJAX. Uses single request, if possible.
	 *
	 * @param {obj} query
	 * @param {int} interval
	 */
	add_query: (function advanced_ads_group_refresh_add_query(query, interval) {
		var queries = [];
		return function (query, interval) {
			var elementid = query.elementid;
			var call_at = new Date().getTime() + interval;
			queries[call_at] = queries[call_at] || [];
			queries[call_at].push(query);

			setTimeout(function () {
				var now = new Date().getTime();
				var requests = [];

				for (const call_time in queries) {
					if (!queries.hasOwnProperty(call_time)) {
						continue;
					}

					if (now > call_time - 1000) {
						// Gather multiple requests into one request.
						var queries_for_time = queries[call_time];
						var queries_length = queries_for_time.length;

						for (var i = 0; i < queries_length; i++) {
							requests.push(queries_for_time[i]);
						}

						delete queries[call_time];
					}
				}

				advanced_ads_pro.process_ajax_ads(requests);
			}, interval);
		};
	})(),

	/**
	 * Find a first floated div.
	 *
	 * @param {jQuery} $el Element id.
	 * @return string/false
	 */
	find_float: function ($el) {
		var r = false;
		$el.find('div').each(function (i, el) {
			if (this.style.float === 'left' || this.style.float === 'right') {
				r = this.style.float;
				return false;
			}
		});
		return r;
	},

	/**
	 * Prepare the wrapper for inserting new ad.
	 *
	 * @param {jQuery} $el Wrapper id.
	 * @param {string|bool} float The float setting of the ad.
	 * @param {bool} is_first_time True for the first ad injection.
	 */
	prepare_wrapper: function ($el, float, is_first_time) {
		if (!is_first_time) {
			this.maybe_increase_sizes($el);
			$el.empty();
		}
		this.set_float($el, float);
	},

	/**
	 * Increase the width and height of the wrapper to fit an ad.
	 * The width and height can only be increased, not decreased to minimize content jumping.
	 *
	 * @param {jQuery} $el Element id.
	 */
	maybe_increase_sizes: function ($el) {
		var float = $el.css('float');
		if (['left', 'right'].indexOf(float) === -1) {
			float = false;
		}
		var sizes = {};

		if (float) {
			// Check if we need to increase the width.
			var prev_w = parseInt($el.css('min-width'), 10) || 0;
			// Get measured width.
			var now_w = $el.prop('scrollWidth') || 0;
			if (now_w > prev_w) {
				sizes['min-width'] = now_w;
			}
		}

		// Check if we need to increase the height.
		var prev_h = parseInt($el.css('min-height'), 10) || 0;
		// Get measured height.
		var now_h = $el.prop('scrollHeight') || 0;

		if (now_h > prev_h) {
			sizes['min-height'] = now_h;
		}

		if (sizes['min-height'] || sizes['min-width']) {
			$el.css(sizes);
		}
	},

	/**
	 * Get the 'float' attribute from the ad or the placement (nested floated divs) and assign it to '$el'.
	 *
	 * @param {jQuery} $el Wrapper id.
	 * @param {string|bool} float The float setting of the ad.
	 */
	set_float: function ($el, float) {
		if (['left', 'right'].indexOf(float) === -1) {
			float = false;
		}

		var prev_float = $el.data('prev_float') || false;

		if (float !== prev_float) {
			$el.data('prev_float', float);

			if (float) {
				// Allow to measure the size of the '$el' that contains floated divs using 'scrollWidth/Height'
				// Allow texts to be floated around the '$el' that has 'min-height' set.
				$el.css({ 'min-width': '', 'min-height': '', float: float });
			} else {
				$el.css({ 'min-width': '', 'min-height': '', float: '' });
			}
		}
	},
};

export const GroupRefreshCompat = () => {
	window.advanced_ads_group_refresh = GroupRefresh;
};
