/* eslint-disable */
import jQuery from 'jquery';
export const CacheBusting = {
	ads: [],
	passive_ads: {}, // passive ads that should be tracked
	deferedAds: [], // ad requests that will be sent to the server using AJAX
	blockme: false,
	blockmeQueue: [],
	observers: jQuery.Callbacks(),
	postscribeObservers: jQuery.Callbacks(),
	random_placements: false,
	iterations: 0,
	adblocker_active: false, // whether an ad blocker is active
	injected: 0, // internal counter how many ads should be injected.
	injected_done: 0, // internal counter how many ads have been injected.
	options: {
		action: 'advads_ad_select', // service action
	},
	inject_before: [], // scripts to inject to the wrapper before other output.
	ajaxAdArgsByElementId: {},

	/**
	 * Dispatch a custom event after placement has been processed by cache busting.
	 *
	 * @param {string} id wrapper HTML ID.
	 * @param {boolean} isEmpty whether the wrapper get filled.
	 * @param {string} cacheBustingType cache busting type.
	 * @param {object|any} extra extra data to be dispatched with the event data.
	 */
	dispatchWrapperCBEvent(id, isEmpty, cacheBustingType, extra) {
		if (typeof extra === 'undefined') {
			extra = {};
		}
		document.dispatchEvent(
			new CustomEvent('advads_pro_cache_busting_done', {
				detail: {
					elementId: id,
					isEmpty: isEmpty,
					type: cacheBustingType,
					extra: extra,
				},
			})
		);
	},

	// whether cache-busting is working.
	isBusy: false,
	/**
	 * Set busy state, and fire event.
	 *
	 * @param {boolean} busy
	 */
	set busy(busy) {
		this.isBusy = busy;
		document.dispatchEvent(
			new CustomEvent('advanced_ads_pro.' + (busy ? 'busy' : 'idle'))
		);
	},
	/**
	 * Get busy state.
	 *
	 * @returns {boolean}
	 */
	get busy() {
		return this.isBusy;
	},

	/**
	 * Prepare ajax requests.
	 *
	 * @param {obj} args
	 */
	load: function (args) {
		// read arguments
		'use strict';
		var id, method, params, elementId, placement_id, is_lazy, blog_id;
		id = args.hasOwnProperty('id') ? args.id : null;
		method = args.hasOwnProperty('method') ? args.method : null;
		params =
			args.hasOwnProperty('params') && typeof args.params === 'object'
				? this.add_general_ajax_args(args.params)
				: {};
		elementId = args.hasOwnProperty('elementid') ? args.elementid : null;
		is_lazy =
			advanced_ads_pro_ajax_object.lazy_load_module_enabled &&
			params &&
			params.lazy_load === 'enabled';
		blog_id = args.hasOwnProperty('blog_id') ? args.blog_id : '';

		var server_conditions = args.hasOwnProperty('server_conditions')
			? args.server_conditions
			: '';

		if (elementId && this.iterations > 1) {
			jQuery('.' + elementId).empty();
		}

		if (params && typeof params === 'object') {
			// do not show `Custom Position` placement ad if selector doesn't exist
			if (!window.advads_pro_utils.selector_exists(params)) {
				return;
			}

			// do not deliver placement, that belongs to a test, and was not randomly selected by weight
			if (params.test_id) {
				if (
					jQuery.inArray(params.slug, this.get_random_placements()) <
					0
				) {
					return;
				}
			}

			params.adblocker_active = this.adblocker_active;

			params = JSON.stringify(params);
		}

		var obj = {
			ad_id: id,
			ad_method: method,
			ad_args: params,
			elementId: elementId,
			blog_id: blog_id,
			server_conditions: server_conditions,
		};

		if (is_lazy) {
			advanced_ads_pro.lazy_load.add(elementId, 'ajax', obj);
			return;
		}

		this.deferedAds[this.deferedAds.length] = obj;
	},

	/**
	 * Add general AJAX arguments that were removed to reduce the size of the array printed in footer.
	 *
	 * @param {object} args A list of arguments with general arguments removed.
	 * @return {object} A full list of arguments.
	 */
	add_general_ajax_args: function (args) {
		if (
			window.advads_pro_utils.isset(args.post) &&
			advads_ajax_queries_args[args.post]
		) {
			args.post = advads_ajax_queries_args[args.post];
		}
		return args;
	},

	hasAd: function (id, method, title, cb_type, elementId) {
		'use strict';
		var ad = {
			id: id,
			type: method,
			title: title,
			cb_type: cb_type,
			elementId: elementId,
		};
		this.ads.push(ad);
		this.observers.fire({ event: 'hasAd', ad: ad });
	},

	// inject blocked ads that should not be defered any further
	injectBlocked: function () {
		'use strict';
		var queue = this.blockmeQueue,
			ad,
			i,
			l = queue.length;
		this.blockmeQueue = [];
		for (i = 0; i < l; i += 1) {
			ad = queue[i];
			this.inject(ad[0], ad[1]);
		}
	},

	/**
	 * Inject a script prior to injecting ads.
	 *
	 * @param {str} elementId Element id.
	 * @param {jQuery} ref Wrapper to inject ads.
	 * @return {jQuery} ref Wrapper to inject ads.
	 */
	_inject_before: function (elementId, ref) {
		if (elementId) {
			window.advads_pro_utils.each(
				advanced_ads_pro.inject_before,
				function (item) {
					if (item.elementId === elementId) {
						window.advads_pro_utils.each(
							item.data,
							function (data) {
								ref.append(data);
							}
						);
						ref = jQuery('.' + elementId);
						item.data = [];
					}
				}
			);
		}
		return ref;
	},

	/**
	 * Inject ad content, block if needed.
	 *
	 * @param {string} elementId id of the wrapper
	 * @param {string} ad content
	 */
	inject: function (elementId, ad) {
		'use strict';
		var that = this,
			async,
			ref;

		if (this.blockme) {
			this.blockmeQueue.push([elementId, ad]);
			return;
		}

		this.injected++;

		// If the ad is encoded, decode before injection.
		var regExp = new RegExp(
			/^([\s\S]*?)<script[^>]+?data-tcf="waiting-for-consent"[^>]+>(.+?)<\/script>([\s\S]*)$/i
		);
		var decodeAd = regExp.exec(ad);
		// If this a wrapped group, iterate until there are no matches left.
		while (decodeAd !== null) {
			var script = document.createElement('script');
			script.setAttribute('type', 'text/plain');
			script.textContent = decodeAd[2];
			ad =
				decodeAd[1] +
				advads.privacy.decode_ad(script, false) +
				decodeAd[3];
			decodeAd = regExp.exec(ad);
		}

		try {
			async = ad.match(/<script[^>]+src/) && ad.indexOf(' async') === -1;

			if (elementId === null) {
				ref = jQuery('head ');
			} else {
				ref = jQuery('.' + elementId);
				if (!ref.length) {
					this.injected--;
					return;
				}
			}

			if (async) {
				this.blockme = true;

				ref = that._inject_before(elementId, ref);

				ref.each(function () {
					var $this = jQuery(this);
					advads_postscribe($this, ad, {
						beforeWriteToken: that.beforeWriteToken,
						afterAsync: function () {
							that.blockme = false;
							that.injectBlocked();
						},
						done: function () {
							that.postscribeObservers.fire({
								event: 'postscribe_done',
								ref: ref,
								ad: ad,
							});
						},
						error: function (e) {
							console.error(e);
							advanced_ads_pro.injected--;
						},
					});
				});
			} else {
				ref = that._inject_before(elementId, ref);

				if (ad.indexOf('gform.initializeOnLoaded') !== -1) {
					// move submit button event handlers into separate `<script />` tag.
					const div = document.createElement('DIV');
					div.innerHTML = ad;
					const btn = div.querySelector('input[type="submit"]');

					if (btn) {
						const onclick = btn.getAttribute('onclick'),
							onkeypress = btn.getAttribute('onkeypress'),
							id = btn.id,
							script = document.createElement('SCRIPT');

						btn.removeAttribute('onclick');
						btn.removeAttribute('onkeypress');
						script.innerHTML = ['click', 'keypress']
							.map(
								function (type) {
									return `document.body.addEventListener("${type}", function(event){if (event.target && event.target.id === "${this.id}"){${this[type]}}})`;
								},
								{ id: id, click: onclick, keypress: onkeypress }
							)
							.join(';');
						div.append(script);
					}

					const form = div.querySelector('form'),
						action = form.getAttribute('action');

					if (action.includes('#gf')) {
						// AJAX submit
						form.setAttribute(
							'action',
							`${window.location.href.split('#')[0]}#${action.split('#')[1]}`
						);
					}

					ad = div.innerHTML;
				}

				ref.each(function () {
					var $this = jQuery(this);
					advads_postscribe($this, ad, {
						beforeWriteToken: that.beforeWriteToken,
						done: function () {
							that.postscribeObservers.fire({
								event: 'postscribe_done',
								ref: ref,
								ad: ad,
							});
						},
						error: function (e) {
							console.error(e);
							advanced_ads_pro.injected--;
						},
					});
				});
			}
		} catch (err) {
			console.error(err);
			this.injected--;
		}
	},

	/**
	 * Called before the `postscribe.js` library writes a token.
	 *
	 * @param {obj} tok a Token.
	 * @return {obj} tok a Token.
	 */
	beforeWriteToken: function (tok) {
		// Handle JSON attributes that contain double quotes, i.e.: `<tag attribute='{"attrname":"attrdata"}'`
		if (tok.type === 'startTag') {
			for (var a in tok.attrs) {
				var str = tok.attrs[a];
				if (str.substring(0, 2) === '{"') {
					str = str.replace(/\"/g, '&quot;');
					tok.attrs[a] = str;
				}
			}
		}

		// Convert ampersand in script src to prevent the "remote script failed" error.
		if (tok.type === 'atomicTag' && tok.src) {
			tok.src = tok.src.replace(/&amp;/g, '&');
		}

		return tok;
	},

	loadAjaxAds: function () {
		'use strict';

		if (!this.deferedAds.length) {
			advanced_ads_pro.observers.fire({
				event: 'inject_ajax_ads',
				ad_ids: [],
			});
			advanced_ads_pro.return_to_idle_injections_done();
			return;
		}

		const time = new Date();

		var data = {
			action: 'advads_ad_select',
			ad_ids: this.ads,
			deferedAds: this.deferedAds,
			consent:
				typeof advads === 'undefined'
					? 'not_needed'
					: advads.privacy.get_state(),
			theId: window.advanced_ads_pro_ajax_object.the_id,
			isSingular: advanced_ads_pro_ajax_object.is_singular,
		};

		// Let modules and add-ons modify the payload fo ajax ad.
		document.dispatchEvent(
			new CustomEvent('advanced-ads-ajax-cb-payload', {
				detail: {
					payload: data,
				},
			})
		);

		// Store deferred ads arguments
		for (const deferredAd of this.deferedAds) {
			this.ajaxAdArgsByElementId[deferredAd.elementId] = JSON.parse(
				deferredAd.ad_args
			);
		}

		document.dispatchEvent(
			new CustomEvent('advads_ajax_ad_select', { detail: data })
		);

		this.deferedAds = [];
		const self = this;

		jQuery
			.ajax({
				url: advanced_ads_pro_ajax_object.ajax_url,
				method: 'POST',
				data: data,
				dataType: 'json',
			})
			.done(function (msg_bunch) {
				var ajax_ads = {};

				if (Array.isArray(msg_bunch)) {
					advanced_ads_pro.observe_injections();
					for (var j = 0; j < msg_bunch.length; j++) {
						var msg = msg_bunch[j];
						if (
							msg.hasOwnProperty('status') &&
							msg.status === 'success' &&
							msg.hasOwnProperty('item') &&
							msg.item
						) {
							if (msg.inject_before) {
								advanced_ads_pro.inject_before.push({
									elementId: msg.elementId,
									data: msg.inject_before,
								});
							}

							advanced_ads_pro.inject(
								msg.elementId,
								msg.item,
								true
							); // inject if item is not empty

							if (
								msg.hasOwnProperty('ads') &&
								Array.isArray(msg.ads)
							) {
								for (var i = 0; i < msg.ads.length; i++) {
									advanced_ads_pro.hasAd(
										msg.ads[i].id,
										msg.ads[i].type,
										msg.ads[i].title,
										'ajax',
										msg.elementId
									);

									if (
										msg.ads[i].type === 'ad' &&
										msg.ads[i].tracking_enabled
									) {
										var blog_id = msg.blog_id
											? msg.blog_id
											: 1;
										if (
											typeof ajax_ads[blog_id] ===
											'undefined'
										) {
											ajax_ads[blog_id] = [];
										}
										ajax_ads[blog_id].push(msg.ads[i].id);
									}
								}
							}
						}
						if (msg.status) {
							advanced_ads_pro.dispatchWrapperCBEvent(
								msg.elementId,
								msg.status === 'error',
								'ajax',
								{
									emptyCbOption: Boolean(
										self.ajaxAdArgsByElementId[
											msg.elementId
										].cache_busting_empty
									),
								}
							);
						}
						if (
							msg.hasOwnProperty('method') &&
							msg.method === 'placement'
						) {
							advanced_ads_pro.observers.fire({
								event: 'inject_placement',
								id: msg.id,
								is_empty: !!msg.item,
								cb_type: 'ajax',
							});
						}
					}
					advanced_ads_pro.observers.fire({
						event: 'inject_ajax_ads',
						ad_ids: ajax_ads,
					});
					window.advads_pro_utils.log(
						'AJAX CB response\n',
						msg_bunch
					);
					document.body.dispatchEvent(
						new CustomEvent('advads_ajax_cb_response', {
							detail: {
								response: msg_bunch,
							},
						})
					);
					advanced_ads_pro.return_to_idle_injections_done();
				}
			})
			.fail(function () {
				advanced_ads_pro.return_to_idle_injections_done();
			});
	},

	/**
	 * select random placements based on weight from placement tests
	 *
	 * @param {obj} placement_tests
	 * @return {array}
	 */
	get_random_placements: function (placement_tests) {
		if (this.random_placements !== false) {
			return this.random_placements;
		}

		this.random_placements = [];

		window.advads_pro_utils.each_key(
			placement_tests,
			function (placement_id, item) {
				if (typeof item === 'object') {
					const random_placement =
						window.advads_pro_utils.get_random_el_by_weight(
							item.placements
						);
					if (random_placement) {
						this.random_placements.push(random_placement);
					}
				}
			},
			this
		);

		return this.random_placements;
	},

	/**
	 * Create non-existent arrays.
	 */
	create_non_existent_arrays: function () {
		var self = this;
		if (self.iterations === 0) {
			window.advads_pro_utils.each(
				[
					'advads_passive_ads',
					'advads_passive_groups',
					'advads_passive_placements',
				],
				function (name) {
					if (!window.advads_pro_utils.isset(window[name])) {
						window[name] = {};
					}
				}
			);

			window.advads_pro_utils.each(
				[
					'advads_placement_tests',
					'advads_ajax_queries',
					'advads_has_ads',
					'advads_js_items',
				],
				function (name) {
					if (!window.advads_pro_utils.isset(window[name])) {
						window[name] = [];
					}
				}
			);
		}
	},

	/**
	 * Cache-busting entry point. Called after document is ready.
	 */
	process_passive_cb: function () {
		var self = this;

		self.create_non_existent_arrays();
		window.advads_pro_utils.print_debug_arrays();

		/**
		 * Process both types of cache-busting.
		 *
		 * @param {bool} adblocker_active Whether an ad blocker is active.
		 */
		var fn = function (adblocker_active) {
			self.busy = true;
			self.iterations++;
			self.lazy_load.clear();
			self.adblocker_active = adblocker_active;

			self.observe_injections();

			window.advads_pro_utils.each(advads_has_ads, function (query) {
				advanced_ads_pro.hasAd.apply(advanced_ads_pro, query);
			});

			self.get_random_placements(advads_placement_tests);

			// inject all passive ads
			window.advads_pro_utils.each_key(
				window.advads_passive_ads,
				function (key, item) {
					var _ = (key + '').indexOf('_');
					if (_ !== -1) {
						key = key.slice(0, _);
					}

					window.advads_pro_utils.each(
						item.elementid,
						function (element_id) {
							if (advanced_ads_pro.iterations > 1) {
								jQuery('.' + element_id).empty();
							}
							var ad = new Advads_passive_cb_Ad(
								item.ads[key],
								element_id
							); // only one ad, pass it as argument

							if (ad.can_display()) {
								ad.output({
									track: true,
									inject: true,
									do_has_ad: true,
								});
							}
						}
					);
				}
			);

			window.advads_pro_utils.each_key(
				window.advads_passive_groups,
				function (key, item) {
					window.advads_pro_utils.each(
						item.elementid,
						function (element_id) {
							if (advanced_ads_pro.iterations > 1) {
								jQuery('.' + element_id).empty();
							}
							var group = new Advads_passive_cb_Group(
								item,
								element_id
							);
							group.output();
						}
					);
				}
			);

			window.advads_pro_utils.each_key(
				window.advads_passive_placements,
				function (key, item) {
					window.advads_pro_utils.each(
						item.elementid,
						function (element_id) {
							if (advanced_ads_pro.iterations > 1) {
								jQuery('.' + element_id).empty();
							}

							var placement = new Advads_passive_cb_Placement(
								item,
								element_id
							);
							if (!placement.can_use_passive_cb()) {
								// Use AJAX cache-busting.
								advanced_ads_pro.load(placement.ajax_query);
								return;
							}
							if (
								advanced_ads_pro_ajax_object.lazy_load_module_enabled &&
								item.placement_info.lazy_load &&
								'enabled' === item.placement_info.lazy_load
							) {
								advanced_ads_pro.lazy_load.add(
									element_id,
									'passive',
									{
										key: key,
										placement_id: item.placement_info.id,
									}
								);
								return;
							}

							placement.output();
						}
					);
				}
			);

			if (window.advads_pro_utils.isset(window.advads_js_items)) {
				window.advads_pro_utils.each_key(
					advads_js_items,
					function (key, item) {
						if (advanced_ads_pro.iterations > 1) {
							return;
						}
						// don’t show `Custom Position` placement ad if selector doesn’t exist
						if (
							!window.advads_pro_utils.selector_exists(item.args)
						) {
							return;
						}

						if (item.inject_before) {
							advanced_ads_pro.inject_before.push({
								elementId: item.elementid,
								data: item.inject_before,
							});
						}

						advanced_ads_pro.inject(
							item.elementid,
							item.output,
							true
						);

						window.advads_pro_utils.each(
							item.has_js_items,
							function (query) {
								advanced_ads_pro.hasAd(
									query.id,
									query.type,
									query.title
								);
								if (query.type === 'ad') {
									if (
										!advanced_ads_pro.passive_ads[
											query.blog_id
										]
									) {
										advanced_ads_pro.passive_ads[
											query.blog_id
										] = [];
									}
									advanced_ads_pro.passive_ads[
										query.blog_id
									].push(query.id);
								}
							}
						);
					}
				);
			}

			self.observers.fire({
				event: 'inject_passive_ads',
				ad_ids: self.passive_ads,
			});
			self.passive_ads = {};

			// then, load and inject all ajax ads with a single request
			self.process_ajax_ads(advads_ajax_queries);

			self.lazy_load.enable();
		};

		if ('function' === typeof advanced_ads_check_adblocker) {
			advanced_ads_check_adblocker(function (is_enabled) {
				fn(is_enabled);
			});
		} else {
			fn(false);
		}
	},

	/**
	 * Attach handler to postscribe done event to count injected ads.
	 */
	observe_injections: function () {
		// Only attach one handler.
		if (advanced_ads_pro.injected_done > 0) {
			return;
		}
		advanced_ads_pro.postscribeObservers.add(function (event) {
			if (event.event !== 'postscribe_done') {
				return;
			}
			advanced_ads_pro.injected_done++;
		});
	},

	/**
	 * Wait for ads to be injected before returning to idle state.
	 */
	return_to_idle_injections_done: function () {
		var count = 1000,
			waiting_for_ads_inject = setInterval(function () {
				count -= 10; // return to idle if not done after one second.
				if (
					advanced_ads_pro.injected_done >=
						advanced_ads_pro.injected ||
					count < 0
				) {
					advanced_ads_pro.injected = 0;
					advanced_ads_pro.injected_done = 0;
					advanced_ads_pro.busy = false;
					clearInterval(waiting_for_ads_inject);
				}
			}, 10);
	},

	/**
	 * Process ajax ads.
	 *
	 * @param {array} ajax_queries
	 */
	process_ajax_ads: function (ajax_queries) {
		if (Array.isArray(ajax_queries)) {
			window.advads_pro_utils.each(ajax_queries, function (query) {
				advanced_ads_pro.load(query);
			});
		}

		this.loadAjaxAds();
	},

	lazy_load: {
		// <wrapper_id -> data>
		lazy_map: {},
		// Whether 'IntersectionObserver' or 'scroll' handler was initialized
		did_init: false,

		/**
		 * Add new lazy item.
		 *
		 * @param {string} wrapper_id.
		 * @param {string} type 'ajax' or 'passive'
		 * @param {data} data
		 */
		add: function (wrapper_id, type, data) {
			var node = document.getElementById(wrapper_id);
			var placement_id;
			if (!node) {
				return;
			}
			if (data.placement_id) {
				placement_id = data.placement_id;
			} else if (data.ad_method === 'placement') {
				placement_id = data.ad_id;
			}

			this.lazy_map[wrapper_id] = {
				node: node,
				type: type,
				data: data,
				offset: this.get_offset(placement_id),
			};
		},

		/**
		 * Get offset for given placement id.
		 *
		 * @param {string} placement_id
		 * @return {int} offset Offset in px.
		 */
		get_offset: function (placement_id) {
			var offset = 0;
			if (advanced_ads_pro_ajax_object.lazy_load) {
				if (
					advanced_ads_pro_ajax_object.lazy_load.offsets[placement_id]
				) {
					offset = parseInt(
						advanced_ads_pro_ajax_object.lazy_load.offsets[
							placement_id
						],
						10
					);
				} else {
					offset = parseInt(
						advanced_ads_pro_ajax_object.lazy_load.default_offset,
						10
					);
				}
			}
			return offset;
		},

		/**
		 * Delete all lazy items.
		 */
		clear: function () {
			this.lazy_map = {};
		},

		/**
		 * Create 'IntersectionObserver' or 'scroll' handler.
		 */
		enable: function () {
			var self = this;

			if (self.did_init) {
				jQuery(window).trigger('scroll');
				return;
			}

			self._create_scroll_handler();

			self.did_init = true;
		},

		/**
		 * Create 'scroll' handler.
		 */
		_create_scroll_handler: function () {
			var self = this;
			var did_scroll = true;

			function scrollHandler() {
				var window_height = jQuery(window).height();
				window.advads_pro_utils.each_key(
					self.lazy_map,
					function (wrapper_id, lazy_item) {
						var rect = lazy_item.node.getBoundingClientRect();
						var offset = lazy_item.offset;

						if (
							rect.top + offset >= 0 &&
							rect.bottom - offset <= window_height
						) {
							self._display(wrapper_id);
						}
					}
				);

				did_scroll = false;
			}

			function RAF(callback) {
				var fn =
					window.requestAnimationFrame ||
					window.mozRequestAnimationFrame ||
					window.webkitRequestAnimationFrame ||
					function (callback) {
						return setTimeout(callback, 16);
					};

				fn.call(window, callback);
			}

			jQuery(window).on('scroll', function () {
				if (!did_scroll) {
					did_scroll = true;
					RAF(scrollHandler);
				}
			});
			RAF(scrollHandler);
		},

		/**
		 * Display an ad when its wrapper becomes visible.
		 *
		 * @param {string} wrapper_id.
		 */
		_display: function (wrapper_id) {
			var lazy_item = this.lazy_map[wrapper_id];
			if (!lazy_item) {
				return;
			}
			delete this.lazy_map[wrapper_id];

			if (lazy_item.type === 'ajax') {
				advanced_ads_pro.deferedAds.push(lazy_item.data);
				advanced_ads_pro.process_ajax_ads();
			} else {
				var passive_placement =
					window.advads_passive_placements[lazy_item.data.key];
				var placement = new Advads_passive_cb_Placement(
					passive_placement,
					wrapper_id
				);
				placement.output();

				advanced_ads_pro.observers.fire({
					event: 'inject_passive_ads',
					ad_ids: advanced_ads_pro.passive_ads,
				});
				advanced_ads_pro.passive_ads = {};
			}

			advanced_ads_pro.busy = false;
		},
	},
};

export const CacheBustingCompat = () => {
	window.advanced_ads_pro = CacheBusting;
};
