/* eslint-disable */
import jQuery from 'jquery';

export class PassiveGroup {
	/**
	 * Constructor
	 *
	 * @param {object} item object which contains info about the group.
	 * @param {string} elementid cache busting wrapper id.
	 */
	constructor(item, elementid) {
		if (
			!advads_pro_utils.isset(item.group_info.id) ||
			!advads_pro_utils.isset(item.group_info.type) ||
			!advads_pro_utils.isset(item.group_info.weights) ||
			!advads_pro_utils.isset(item.group_info.ordered_ad_ids) ||
			!advads_pro_utils.isset(item.group_info.ad_count) ||
			!advads_pro_utils.isset(item.ads)
		) {
			throw new SyntaxError('Can not create Advads_passive_cb_Group obj');
		}

		this.id = item.group_info.id;
		this.name = item.group_info.name ? item.group_info.name : this.id;
		this.type = item.group_info.type;
		this.weights = item.group_info.weights;
		this.ordered_ad_ids = item.group_info.ordered_ad_ids;
		this.ad_count = item.group_info.ad_count;
		this.elementid = elementid ? elementid : null;
		this.slider_options = advads_pro_utils.isset(
			item.group_info.slider_options
		)
			? item.group_info.slider_options
			: false;
		this.refresh_enabled = advads_pro_utils.isset(
			item.group_info.refresh_enabled
		);

		if (advads_pro_utils.isset(item.group_info.refresh_interval_for_ads)) {
			this.refresh_interval = item.group_info.refresh_interval_for_ads;
		} else if (advads_pro_utils.isset(item.group_info.refresh_interval)) {
			// Deprecated.
			this.refresh_interval = item.group_info.refresh_interval;
		} else {
			this.refresh_interval = 2000;
		}

		this.placement =
			item instanceof Advads_passive_cb_Placement ? item : false;
		this.random = item.group_info.random;

		this.ads = item.ads;
		this.group_wrap = item.group_wrap;
		this.is_empty = true;
	}

	/**
	 * Inject group output inside the cache busting wrapper
	 */
	output() {
		var ad_for_adblocker =
			this.placement && this.placement.get_ad_for_adblocker();

		advanced_ads_pro.hasAd(this.id, 'group', this.name, 'passive');

		if (!ad_for_adblocker && this.refresh_enabled) {
			this.output_refresh();
			return;
		}

		var ordered_ad_ids,
			ads_displayed = 0,
			output_buffer = [];

		const group_for_ab =
			this.placement && this.placement.get_group_for_adblocker();
		if (group_for_ab) {
			this.ads = group_for_ab.ads;
		}

		switch (this.type) {
			case 'ordered':
			case 'slider':
				ordered_ad_ids = this.shuffle_ordered_ads(
					this.ordered_ad_ids,
					this.weights
				);
				break;
			case 'grid':
				ordered_ad_ids = this.random
					? this.shuffle_ads()
					: this.shuffle_ordered_ads(
							this.ordered_ad_ids,
							this.weights
						);
				break;
			default:
				ordered_ad_ids = this.shuffle_ads();
		}

		if (!Array.isArray(ordered_ad_ids) || !jQuery.isPlainObject(this.ads)) {
			return;
		}

		for (var i = 0; i < ordered_ad_ids.length; i++) {
			if (!this.ads.hasOwnProperty(ordered_ad_ids[i])) {
				continue;
			}
			var ad_info = this.ads[ordered_ad_ids[i]];

			if (typeof ad_info === 'object') {
				var ad = new Advads_passive_cb_Ad(ad_info, this.elementid);
				if (ad.can_display()) {
					if (ad_for_adblocker) {
						ad = ad_for_adblocker;
					}

					const canTrack =
						!advads.privacy ||
						'unknown' !== advads.privacy.get_state();

					if (
						(this.type === 'slider' && this.slider_options) ||
						this.group_wrap
					) {
						output_buffer.push(
							ad.output({
								track: canTrack,
								inject: false,
								do_has_ad: true,
							})
						);
					} else {
						ad.output({
							track: canTrack,
							inject: true,
							do_has_ad: true,
						});
					}
					ads_displayed++;
					this.is_empty = false;
				}
			}
			// break the loop when maximum ads are reached
			if (ads_displayed === this.ad_count) {
				break;
			}
			// show only first ad when an ad blocker is found.
			if (!this.is_empty && ad_for_adblocker) {
				break;
			}
		}

		if (output_buffer.length) {
			if (this.type === 'slider' && this.slider_options) {
				output_buffer = this.output_slider(output_buffer);
			}

			advanced_ads_pro.inject(
				this.elementid,
				this.add_group_wrap(output_buffer, ads_displayed)
			);
		}
	}

	/**
	 * Output for group that has a refresh set up
	 */
	output_refresh() {
		var ordered_ad_ids = this.ordered_ad_ids,
			output_buffer = [],
			self = this,
			index = 0,
			ad_id,
			prev_ad_id = false,
			tracked_ads = [],
			ads_displayed = 0,
			interval = this.refresh_interval;

		var $el = jQuery('.' + self.elementid);
		$el = advanced_ads_pro._inject_before(this.elementid, $el);

		if (!Array.isArray(ordered_ad_ids) || !jQuery.isPlainObject(this.ads)) {
			return;
		}

		/**
		 * Track the ad.
		 *
		 * @param {Object} ad Advads_passive_cb_Ad
		 */
		function track_ad(ad) {
			if (jQuery.inArray(ad.id, tracked_ads) < 0 && ad.tracking_enabled) {
				var data = {};
				data[ad.blog_id] = [ad.id];
				advanced_ads_pro.observers.fire({
					event: 'inject_passive_ads',
					ad_ids: data,
				});
			}
		}

		function pick_ids() {
			switch (self.type) {
				case 'ordered':
					var prev_index = ordered_ad_ids.indexOf(prev_ad_id);
					if (prev_index !== -1) {
						var new_ids = ordered_ad_ids
							.slice(prev_index + 1)
							.concat(ordered_ad_ids.slice(0, prev_index));
					} else {
						var new_ids = ordered_ad_ids;
					}
					break;
				default:
					var new_ids = self.shuffle_ads();
					var prev_index = new_ids.indexOf(prev_ad_id);
					if (prev_index !== -1) {
						new_ids.splice(prev_index, 1);
					}
			}
			return new_ids;
		}

		function get_ad_interval(ad_id) {
			if (typeof self.refresh_interval !== 'object') {
				return parseInt(self.refresh_interval, 10) || 2000;
			}

			return parseInt(self.refresh_interval[ad_id], 10) || 2000;
		}

		/**
		 * Get the 'float' property from the ad or the placement.
		 *
		 * @param {Object} ad Advads_passive_cb_Ad
		 * @return string
		 */
		function get_position(ad) {
			var position = '';
			if (
				advads_pro_utils.isset_nested(
					self.placement,
					'placement_info',
					'options',
					'placement_position'
				)
			) {
				position =
					self.placement.placement_info.options.placement_position;
			}
			if (['left', 'right'].indexOf(position) === -1) {
				position = ad.position;
			}

			return position;
		}

		(function tick() {
			var new_ids = pick_ids();
			var idsLen = new_ids.length;
			for (var i = 0; i < idsLen; i++) {
				var ad_id = new_ids[i];
				var ad_info = self.ads[ad_id];
				if (typeof ad_info === 'object') {
					var ad = new Advads_passive_cb_Ad(ad_info, self.elementid);
					if (ad.can_display()) {
						// The first ad will be tracked like all other passive ads.
						if (ads_displayed === 0) {
							output_buffer = [
								ad.output({
									track: true,
									inject: false,
									do_has_ad: true,
								}),
							];
							advanced_ads_group_refresh.prepare_wrapper(
								$el,
								get_position(ad),
								true
							);
						} else {
							var do_has_ad =
								jQuery.inArray(ad_id, tracked_ads) < 0;
							output_buffer = [
								ad.output({
									track: false,
									inject: false,
									do_has_ad: do_has_ad,
								}),
							];
							track_ad(ad);
							advanced_ads_group_refresh.prepare_wrapper(
								$el,
								get_position(ad),
								false
							);
						}
						tracked_ads.push(ad.id);

						advanced_ads_pro.inject(
							self.elementid,
							self.add_group_wrap(output_buffer, 1)
						);
						self.is_empty = false;
						ads_displayed++;
						setTimeout(function () {
							if (
								!self.placement ||
								self.placement.can_display()
							) {
								tick();
							}
						}, get_ad_interval(ad_id));
						prev_ad_id = ad.id;
						break;
					}
				}
			}
		})();
	}

	/**
	 * Get markup to inject around each ad and around entire set of ads (if needed)
	 *
	 * @param {Array}  output_buffer output buffer.
	 * @param {number} ads_displayed amount of ad to show for the group.
	 * @returns {string} output with wrapper (if any)
	 */
	add_group_wrap(output_buffer, ads_displayed) {
		if (!output_buffer.length) {
			return '';
		}

		var before = '',
			after = '';

		if (this.group_wrap) {
			for (var i = 0; i < this.group_wrap.length; i++) {
				var wrap = this.group_wrap[i];
				wrap.min_ads = wrap.min_ads || 1;

				if (typeof wrap !== 'object' || wrap.min_ads > ads_displayed) {
					continue;
				}
				if (wrap.before) {
					before = wrap.before + before;
				}
				if (wrap.after) {
					after = after + wrap.after;
				}
				if (typeof wrap.each === 'string') {
					for (var j = 0; j < output_buffer.length; j++) {
						output_buffer[j] = wrap.each.replace(
							'%s',
							output_buffer[j]
						);
					}
				} else if (typeof wrap.each === 'object') {
					var each_obj = wrap.each;

					for (var j = 0; j < output_buffer.length; j++) {
						for (var format_index in each_obj) {
							var ad_wrapped = false;

							if (
								each_obj.hasOwnProperty(format_index) &&
								format_index !== 'all' &&
								(1 + j) % parseInt(format_index, 10) === 0
							) {
								output_buffer[j] = each_obj[
									format_index
								].replace('%s', output_buffer[j]);
								ad_wrapped = true;
								break;
							}
						}

						if (!ad_wrapped && each_obj.all) {
							// applied here since JavaScript does not guarantee object key order
							output_buffer[j] = each_obj.all.replace(
								'%s',
								output_buffer[j]
							);
						}
					}
				}
			}
		}

		return before + output_buffer.join('') + after;
	}

	/**
	 * Output slider markup around slides
	 *
	 * @param   output_buffer output buffer
	 * @deprecated since AAS_VERSION > 1.3.1
	 * @returns {*[]}
	 */
	output_slider(output_buffer) {
		var output_html, ads_output;

		if (
			output_buffer.length > 1 &&
			typeof jQuery.fn.unslider === 'function'
		) {
			ads_output = output_buffer.join('</li><li>');
			output_buffer = [];

			output_buffer.push(
				'<div id="' +
					this.slider_options.slider_id +
					'" class="' +
					this.slider_options.init_class +
					' ' +
					this.slider_options.prefix +
					'slider"><ul><li>'
			);
			output_buffer.push(ads_output);
			output_buffer.push('</li></ul></div>');
			/* custom css file was added with version 1.1 of Advads Slider. Deactivate the following lines if there are issues with your layout
			 output_buffer.push( "<style>.advads-slider { position: relative; width: 100% !important; overflow: hidden; } " );
			 output_buffer.push( ".advads-slider ul, .advads-slider li { list-style: none; margin: 0 !important; padding: 0 !important; } " );
			 output_buffer.push( ".advads-slider ul li { width: 100%; float: left; }</style>" );
			 */
			output_buffer.push(
				'<scr' +
					"ipt>jQuery(function() { jQuery('." +
					this.slider_options.init_class +
					"').unslider({ " +
					this.slider_options.settings +
					' }); });</scr' +
					'ipt>'
			);
		}

		return output_buffer;
	}

	/**
	 * Shuffle ads that have the same weights.
	 *
	 * @param {Array} ordered_ad_ids Ad ids.
	 * @param {Object} weights ad_id: weight pairs.
	 * @return {Array} ordered_ad_ids Ad ids.
	 */
	shuffle_ordered_ads(ordered_ad_ids, weights) {
		// Get weights of ordered ad ids.
		const weight_array = [];
		for (var i = 0; i < ordered_ad_ids.length; i++) {
			var weight = weights[ordered_ad_ids[i]];
			if (!weight) {
				return ordered_ad_ids;
			}

			weight_array.push(weight);
		}

		var count = weight_array.length;
		var pos = 0;
		for (var i = 1; i <= count; i++) {
			if (i == count || weight_array[i] !== weight_array[i - 1]) {
				var slice_len = i - pos;
				if (slice_len !== 1) {
					var shuffled = advads_pro_utils.shuffle_array(
						ordered_ad_ids.slice(pos, pos + slice_len)
					);
					// Replace the unshuffled chunk of array with the shuffled one.
					var arg = [pos, slice_len].concat(shuffled);
					Array.prototype.splice.apply(ordered_ad_ids, arg);
				}
				pos = i;
			}
		}
		return ordered_ad_ids;
	}

	/**
	 * shuffle ads based on ad weight
	 *
	 * @return {Array} shuffled array with ad ids
	 */
	shuffle_ads() {
		const shuffled_ads = [],
			ad_weights = jQuery.extend({}, this.weights);

		let random_ad_id = advads_pro_utils.get_random_el_by_weight(ad_weights);

		// while non-zero weights are set select random next
		while (null !== random_ad_id) {
			// remove chosen ad from weights array
			delete ad_weights[random_ad_id];
			// put random ad into shuffled array
			shuffled_ads.push(parseInt(random_ad_id, 10));
			random_ad_id = advads_pro_utils.get_random_el_by_weight(ad_weights);
		}
		return shuffled_ads;
	}
}

export const PassiveGroupCompat = () => {
	window.Advads_passive_cb_Group = PassiveGroup;
};
