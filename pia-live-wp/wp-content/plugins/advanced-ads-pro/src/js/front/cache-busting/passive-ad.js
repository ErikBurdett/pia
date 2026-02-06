/* eslint-disable */
import jQuery from 'jquery';

export class PassiveAd {
	/**
	 * Constructor
	 *
	 * @param {Object} ad_info   object which contains info about the ad.
	 * @param {string} elementid id of wrapper div.
	 */
	constructor(ad_info, elementid) {
		if (
			typeof ad_info !== 'object' ||
			!advads_pro_utils.isset(ad_info.id) ||
			!advads_pro_utils.isset(ad_info.title) ||
			!advads_pro_utils.isset(ad_info.content)
		) {
			throw new SyntaxError('Can not create Advads_passive_cb_Ad obj');
		}

		this.id = ad_info.id;
		this.title = ad_info.title;
		this.content = ad_info.content ? ad_info.content : '';
		this.type = ad_info.type;
		this.expiry_date = parseInt(ad_info.expiry_date) || 0;
		this.visitors = ad_info.visitors;
		this.once_per_page = ad_info.once_per_page;
		this.elementid = elementid ? elementid : null;
		this.day_indexes = ad_info.day_indexes ? ad_info.day_indexes : null;
		this.debugmode = ad_info.debugmode;
		this.tracking_enabled =
			ad_info.tracking_enabled === undefined ||
			ad_info.tracking_enabled === true;
		this.blog_id = ad_info.blog_id ? ad_info.blog_id : '';
		this.privacy = ad_info.privacy ? ad_info.privacy : {};
		this.position = ad_info.position ? ad_info.position : '';

		// Let modules and add-ons add info to an ad.
		document.dispatchEvent(
			new CustomEvent('advanced-ads-passive-cb-ad-info', {
				detail: {
					ad: this,
					adInfo: ad_info,
				},
			})
		);
	}

	/**
	 * Returns the ad output
	 *
	 * @param {Object} options output options
	 *     track - track if true, do not track if false
	 *     inject - inject the ad if true, return the ad content if false
	 *     do_has_ad - true if we need to call the hasAd function
	 *
	 * @return {string} the ad output if inject is `false`.
	 */
	output(options) {
		options = options || {};

		if (this.debugmode) {
			var is_displayed = this.can_display({ ignore_debugmode: true })
				? 'displayed'
				: 'hidden';
			var debug_message = jQuery(this.content)
				.find('.advads-passive-cb-debug')
				.data(is_displayed);
			// inject debug info
			this.content = this.content.replace(
				'##advanced_ads_passive_cb_debug##',
				debug_message
			);
		}

		if (options.do_has_ad) {
			advanced_ads_pro.hasAd(this.id, 'ad', this.title, 'passive');
		}

		if (options.track && this.tracking_enabled) {
			if (!advanced_ads_pro.passive_ads[this.blog_id]) {
				advanced_ads_pro.passive_ads[this.blog_id] = [];
			}
			advanced_ads_pro.passive_ads[this.blog_id].push(this.id);
		}

		advads_pro_utils.log(
			'output passive ad',
			this.id,
			this.elementid,
			this.content
		);

		if (!options.inject) {
			return this.content;
		}

		advanced_ads_pro.inject(this.elementid, this.content);
	}

	/**
	 * Check if the ad can be displayed in frontend due to its own conditions
	 *
	 * @param {Object} check_options possibility to bypass the check when in debug mode.
	 * @returns {boolean} whether the ad can be displayed.
	 */
	can_display(check_options) {
		check_options = check_options || {};

		if (this.debugmode && !check_options.ignore_debugmode) {
			return true;
		}

		if ('' === this.content.trim()) {
			advads_pro_utils.log(
				'passive ad id',
				this.id,
				'cannot be displayed: empty content'
			);
			return false;
		}
		if (!this.can_display_by_visitor()) {
			advads_pro_utils.log(
				'passive ad id',
				this.id,
				'cannot be displayed: by_visitor'
			);
			return false;
		}
		if (!this.can_display_by_expiry_date()) {
			advads_pro_utils.log(
				'passive ad id',
				this.id,
				'cannot be displayed: by_expiry_date'
			);
			return false;
		}
		if (!this.can_display_by_timeout()) {
			advads_pro_utils.log(
				'passive ad id',
				this.id,
				'cannot be displayed: by_timeout'
			);
			return false;
		}
		if (!this.can_display_by_display_limit()) {
			advads_pro_utils.log(
				'passive ad id',
				this.id,
				'cannot be displayed: by_display_limit'
			);
			return false;
		}
		if (!this.can_display_by_weekday()) {
			advads_pro_utils.log(
				'passive ad id',
				this.id,
				'cannot be displayed: by_weekday'
			);
			return false;
		}
		if (!this.can_display_by_cfp()) {
			advads_pro_utils.log(
				'passive ad id',
				this.id,
				'cannot be displayed: by_cfp'
			);
			return false;
		}
		if (!this.can_display_by_consent()) {
			advads_pro_utils.log(
				'passive ad id',
				this.id,
				'cannot be displayed: by_consent'
			);
			return false;
		}

		const canDisplay = { display: true };

		// Let modules and add-ons do a `canDisplay` check.
		document.dispatchEvent(
			new CustomEvent('advanced-ads-passive-cb-can-display', {
				detail: {
					canDisplay,
					checkOptions: check_options,
					adInfo: this,
				},
			})
		);

		return canDisplay.display;
	}

	/**
	 * Check visitor conditions
	 *
	 * @return {boolean} true if the ad can be displayed in frontend based on visitor settings, false otherwise
	 */
	can_display_by_visitor() {
		if (!Array.isArray(this.visitors) || this.visitors.length === 0) {
			return true;
		}

		window.Advads_passive_cb_Conditions.init();

		var pos = 0,
			last_result = false,
			_condition;
		for (var i = 0; i < this.visitors.length; ++i) {
			_condition = this.visitors[pos];
			// ignore OR if last result was true
			if (last_result && _condition.connector === 'or') {
				pos++;
				continue;
			}

			last_result = window.Advads_passive_cb_Conditions.frontend_check(
				_condition,
				this
			);
			if (!last_result) {
				// return false only, if the next condition doesn’t have an OR operator
				pos++;
				if (
					!this.visitors[pos] ||
					this.visitors[pos].connector !== 'or'
				) {
					return false;
				}
			} else {
				pos++;
			}
		}

		return true;
	}

	/**
	 * Check expiry date
	 *
	 * @return {boolean} true if not expired yet
	 */
	can_display_by_expiry_date() {
		if (this.expiry_date <= 0) {
			return true;
		}
		// check against current time (universal time)
		return this.expiry_date > ~~(new Date().getTime() / 1000);
	}

	/**
	 * Check if ad can be displayed today
	 *
	 * @return {boolean} true if ad can be displayed
	 */
	can_display_by_weekday() {
		if (!this.day_indexes) {
			return true;
		}

		var date = new Date(),
			offsetMinutes =
				window.advanced_ads_pro_ajax_object.wp_timezone_offset / 60,
			offsetHours =
				offsetMinutes / 60 >= 0
					? Math.floor(offsetMinutes / 60)
					: Math.ceil(offsetMinutes / 60);

		offsetMinutes = date.getUTCMinutes() + (offsetMinutes % 60);

		if (offsetMinutes > 60) {
			offsetHours++;
			offsetMinutes %= 60;
		}

		date.setHours(date.getUTCHours() + offsetHours);
		date.setMinutes(offsetMinutes);

		return jQuery.inArray(date.getDay(), this.day_indexes) >= 0;
	}

	/**
	 * Check close and timeout feature implemented by Advads Layer
	 *
	 * @return {boolean} true if ad can be displayed in frontend based on expiry date, false otherwise
	 */
	can_display_by_timeout() {
		//check if ad was closed with a cookie before (Advads layer plugin)
		return !advads_pro_utils.isset(advads.get_cookie('timeout_' + this.id));
	}

	/**
	 * Check if the ad can be displayed based on display limit
	 *
	 * @return {boolean} true if limit is not reached, false otherwise
	 */
	can_display_by_display_limit() {
		if (this.once_per_page) {
			var adsLen = advanced_ads_pro.ads.length;
			for (var i = 0; i < adsLen; i++) {
				if (
					advanced_ads_pro.ads[i].type === 'ad' &&
					parseInt(advanced_ads_pro.ads[i].id, 10) === this.id
				) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Check if the user is banned (Click Fraud Protection module).
	 *
	 * @return {boolean} false if banned
	 */
	can_display_by_cfp() {
		// Check if the global setting is ignored.
		for (const visitor of this.visitors) {
			if (
				visitor['type'] === 'ad_clicks' &&
				visitor['exclude-from-global']
			) {
				return true;
			}
		}

		// Check if the user is banned
		return !advads.get_cookie('advads_pro_cfp_ban');
	}

	/**
	 * Check if ad can be displayed based on user's consent.
	 *
	 * @return {boolean} true if allowed
	 */
	can_display_by_consent() {
		// If consent is not needed for the ad.
		if (
			!advads.privacy ||
			this.privacy.ignore ||
			(this.type === 'adsense' &&
				advads.privacy.is_adsense_npa_enabled()) ||
			((this.type === 'image' || this.type === 'dummy') &&
				!this.privacy.needs_consent)
		) {
			return true;
		}

		var state = advads.privacy.get_state();
		return state === 'accepted' || state === 'not_needed';
	}
}

export const PassiveAdCompat = () => {
	window.Advads_passive_cb_Ad = PassiveAd;
};
