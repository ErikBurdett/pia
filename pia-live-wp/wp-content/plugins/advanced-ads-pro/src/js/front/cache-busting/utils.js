/* eslint-disable */
import jQuery from 'jquery';

export const Utils = {
	debug:
		window.location &&
		window.location.hash &&
		window.location.hash.indexOf('#debug=true') !== -1,

	// Loop over each item in an array-like value.
	each: function (arr, fn, _this) {
		var i,
			len = (arr && arr.length) || 0;
		for (i = 0; i < len; i++) {
			fn.call(_this, arr[i], i);
		}
	},
	// Loop over each key/value pair in a hash.
	each_key: function (obj, fn, _this) {
		if ('object' === typeof obj) {
			var key;
			for (key in obj) {
				if (obj.hasOwnProperty(key)) {
					fn.call(_this, key, obj[key]);
				}
			}
		}
	},

	/**
	 * Log messages to the browser console.
	 */
	log: function () {
		if (this.debug && this.isset(window.console)) {
			var args = Array.prototype.slice.call(arguments);
			args.unshift('Advanced Ads CB:');
			window.console.log.apply(window.console, args);
		}
	},

	/**
	 * Log cache-busting arrays (AJAX and passive cb).
	 */
	print_debug_arrays: function () {
		if (advanced_ads_pro.iterations === 0) {
			// Available when passive cb is enabled for all ads/groups which are not delivered through a placement.
			this.log('passive_ads\n', window.advads_passive_ads);
			this.log('passive_groups\n', window.advads_passive_groups);

			this.log('passive_placements\n', window.advads_passive_placements);
			this.log('ajax_queries\n', window.advads_ajax_queries);

			this.log(
				window.Advads_passive_cb_Conditions.VISITOR_INFO_COOKIE_NAME +
					'\n',
				window.Advads_passive_cb_Conditions.get_stored_info()
			);
		}
	},

	isset: function (str) {
		return typeof str !== 'undefined';
	},

	/**
	 * Check if nested object key exists
	 *
	 * @param {obj}
	 * @params {str} level1, .. levelN
	 * @return {bool} true on success false on failure
	 */
	isset_nested: function (obj) {
		var argsLen = arguments.length;
		for (var i = 1; i < argsLen; i++) {
			if (!obj || !obj.hasOwnProperty(arguments[i])) {
				return false;
			}
			obj = obj[arguments[i]];
		}
		return true;
	},
	is_numeric: function (n) {
		return !isNaN(parseFloat(n)) && isFinite(n);
	},
	// generate a random number between min and max (inclide min and max)
	get_random_number: function (min, max) {
		var rand = min - 0.5 + Math.random() * (max - min + 1);
		return Math.round(rand);
	},

	/**
	 * Get random element by weight
	 *
	 * @param {object} weights e.g. {'A' => 2, 'B' => 3, 'C' => 5}
	 * @param {string} skip to skip, e.g. 'A'
	 * @source applied with fix for order http://stackoverflow.com/a/11872928/904614
	 */
	get_random_el_by_weight: function (weights, skip) {
		var max = 0,
			rand;
		skip = typeof skip !== 'undefined' ? skip : false;

		if (typeof weights === 'object') {
			for (var el in weights) {
				if (el !== skip && weights.hasOwnProperty(el)) {
					max += parseInt(weights[el]) || 0;
				}
			}

			if (max < 1) {
				return null;
			}

			rand = advads_pro_utils.get_random_number(1, max);

			for (var el in weights) {
				if (el !== skip && weights.hasOwnProperty(el)) {
					rand -= weights[el];
					if (rand <= 0) {
						return el;
					}
				}
			}
		}
	},

	/**
	 * A 'polyfill' of the native 'bind' function.
	 *
	 * @param {function} func
	 * @param {obj} context
	 */
	bind: function (func, context) {
		return function () {
			return func.apply(context, arguments);
		};
	},

	/**
	 * Shuffle array (knuthfisheryates).
	 * http://stackoverflow.com/a/2450976/1037948
	 *
	 * @param {array} arr
	 * @return {array} arr
	 */
	shuffle_array: function (arr) {
		var temp,
			j,
			i = arr.length;
		if (!i) {
			return arr;
		}
		while (--i) {
			j = ~~(Math.random() * (i + 1));
			temp = arr[i];
			arr[i] = arr[j];
			arr[j] = temp;
		}

		return arr;
	},

	/**
	 * Check if the selector of the Custom position placement exists.
	 *
	 * @param {array} params Placement options.
	 * @return bool
	 */
	selector_exists: function (params) {
		var cp_target =
			!params.inject_by || params.inject_by === 'pro_custom_element'
				? 'pro_custom_element'
				: 'container_id';
		var el = params[cp_target];
		if (!el) {
			// Not Custom Position placement.
			return true;
		}

		var $el = jQuery(el);

		if (!$el.length) {
			advads_pro_utils.log('selector does not exist', el);
			return false;
		}
		if (
			!advanced_ads_pro_ajax_object.moveintohidden &&
			!$el.filter(':visible').length
		) {
			advads_pro_utils.log('selector is hidden', el);
			return false;
		}
		return true;
	},

	/**
	 * Converts the number in degrees to the radians.
	 */
	deg2rad: function (deg) {
		return (deg * Math.PI) / 180;
	},

	/**
	 * Computes the distance between the coordinates and returns the result.
	 */
	calculate_distance: function (lat1, lon1, lat2, lon2, unit) {
		unit = unit || 'km';
		lat1 = this.deg2rad(lat1);
		lon1 = this.deg2rad(lon1);
		lat2 = this.deg2rad(lat2);
		lon2 = this.deg2rad(lon2);

		const dLon = lon2 - lon1;
		a =
			Math.pow(Math.cos(lat2) * Math.sin(dLon), 2) +
			Math.pow(
				Math.cos(lat1) * Math.sin(lat2) -
					Math.sin(lat1) * Math.cos(lat2) * Math.cos(dLon),
				2
			);
		b =
			Math.sin(lat1) * Math.sin(lat2) +
			Math.cos(lat1) * Math.cos(lat2) * Math.cos(dLon);

		const rad = Math.atan2(Math.sqrt(a), b);
		if (unit === 'mi') {
			return rad * 3958.755865744;
		} else {
			return rad * 6371.0;
		}
	},

	/**
	 * Extract cookie data from a stringified cookie.
	 *
	 * @param {string} cookie {
	 *     A stringified cookie.
	 *
	 *     @type {string} data Cookie data.
	 *     @type {string} expire Expiration time.
	 * }
	 * @return {mixed} The data field on success, original stringified cookie on error.
	 */
	extract_cookie_data(cookie) {
		try {
			var cookie_obj = JSON.parse(cookie);
		} catch (e) {
			return cookie;
		}

		if (typeof cookie_obj !== 'object') {
			return cookie;
		}

		return cookie_obj.data;
	},
};

export const UtilsCompat = () => {
	window.advads_pro_utils = Utils;
};
