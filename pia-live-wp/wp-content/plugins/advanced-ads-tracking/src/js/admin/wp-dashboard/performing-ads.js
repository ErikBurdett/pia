import jQuery from 'jquery';

class PerformingAds {
	constructor() {
		this.classTrack = jQuery('.advads-performing-ads-track');
		this.inpPeriod = jQuery('[name=advads-performing-ads-period]');
		this.customFrom = jQuery('[name=advads-custom-from]');
		this.customTo = jQuery('[name=advads-custom-to]');
		this.adsContainer = jQuery('#advads-performing-container');
		this.spinner = jQuery('.advads-spinner');
		this.activeTab = '';

		// save user prefs on change only
		this.oldPrefs = {};

		this.init();
	}

	enableElements(enable = true) {
		this.classTrack.toggleClass('disabled', !enable);
		this.inpPeriod.prop('disabled', !enable);
		this.customFrom.prop('disabled', !enable);
		this.customTo.prop('disabled', !enable);
		this.spinner.toggleClass('disabled', enable);
	}

	tabs() {
		this.classTrack.find('ul li').on('click', (event) => {
			const $this = jQuery(event.currentTarget);
			$this.addClass('active').siblings().removeClass('active');

			this.activeTab = $this.data('tab');
			jQuery(`.advads-performing-tab-${this.activeTab}`)
				.addClass('active')
				.siblings()
				.removeClass('active');

			this.saveUserPrefs();
		});
	}

	handlePeriodChange() {
		const customPeriodWrapper = jQuery('.advads-custom-period-wrapper');

		this.inpPeriod.on('change', () => {
			const args = {
				period: this.inpPeriod.val(),
				groupby: 'day',
			};

			const isCustomPeriod = args.period === 'custom';
			customPeriodWrapper.toggle(isCustomPeriod);

			if (isCustomPeriod) {
				args.from = this.customFrom.val();
				args.to = this.customTo.val();

				const fromDate = new Date(args.from);
				const toDate = new Date(args.to);

				if (
					isNaN(fromDate.getTime()) ||
					isNaN(toDate.getTime()) ||
					fromDate > toDate
				) {
					return;
				}
			}

			const data = {
				action: 'advads_render_dashboard_ads_widget',
				args: jQuery.param(args),
				nonce: advadsglobal.ajax_nonce,
			};

			this.renderStats(data);
			this.classTrack
				.find(`[data-tab=${this.activeTab}]`)
				.trigger('click');
		});
	}

	renderStats(data) {
		jQuery
			.ajax({
				url: ajaxurl,
				type: 'POST',
				data,
				beforeSend: () => {
					this.enableElements(false);
					this.adsContainer.html('');
				},
				complete: () => {
					this.enableElements();
				},
			})
			.done((response) => {
				this.adsContainer.html(response);
				jQuery(`.advads-performing-tab-${this.activeTab}`).addClass(
					'active'
				);
			});
	}

	saveUserPrefs() {
		const newPrefs = {
			metric: this.activeTab,
			customFrom: this.customFrom.val(),
			customTo: this.customTo.val(),
			period: this.inpPeriod.val(),
		};

		if (
			newPrefs.metric === this.oldPrefs.metric &&
			newPrefs.customFrom === this.oldPrefs.customFrom &&
			newPrefs.customTo === this.oldPrefs.customTo &&
			newPrefs.period === this.oldPrefs.period
		) {
			return;
		}

		this.oldPrefs.metric = newPrefs.metric;
		this.oldPrefs.customFrom = newPrefs.customFrom;
		this.oldPrefs.customTo = newPrefs.customTo;
		this.oldPrefs.period = newPrefs.period;

		const data = {
			action: 'advads_dashboard_ads_widget_user_prefs',
			nonce: advadsglobal.ajax_nonce,
			period: this.inpPeriod.val(),
			metric: this.activeTab,
			custom_from: this.customFrom.val(),
			custom_to: this.customTo.val(),
		};

		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			data,
		});
	}

	getUserPrefs() {
		this.oldPrefs.metric = advancedAds.dashboardAdsWidget.metric;
		this.oldPrefs.customFrom = advancedAds.dashboardAdsWidget.customFrom;
		this.oldPrefs.customTo = advancedAds.dashboardAdsWidget.customTo;
		this.oldPrefs.period = advancedAds.dashboardAdsWidget.period;

		this.activeTab = this.oldPrefs.metric;
		this.classTrack.find(`[data-tab=${this.activeTab}]`);

		this.customFrom.val(this.oldPrefs.customFrom);
		this.customTo.val(this.oldPrefs.customTo);

		this.inpPeriod
			.val(advancedAds.dashboardAdsWidget.period)
			.trigger('change');
	}

	handleCustomPeriod() {
		this.customFrom.on('change', () => this.dateChange());
		this.customTo.on('change', () => this.dateChange());
	}

	dateChange() {
		const fromDate = new Date(this.customFrom.val());
		const toDate = new Date(this.customTo.val());

		if (
			!isNaN(fromDate.getTime()) &&
			!isNaN(toDate.getTime()) &&
			fromDate < toDate
		) {
			this.inpPeriod.trigger('change');
		}
	}

	init() {
		this.enableElements();
		this.tabs();
		this.handlePeriodChange();
		this.handleCustomPeriod();

		// date pickers
		this.customFrom.datepicker({ dateFormat: 'mm/dd/yy' });
		this.customTo.datepicker({ dateFormat: 'mm/dd/yy' });

		// set user prefs
		this.getUserPrefs();
	}
}

export default function () {
	new PerformingAds();
}
