/* eslint-disable camelcase */
/**
 * Show notice if %link% is in the editor but the URL field is empty.
 * Show notice if URL exists but %link% is not in the editor.
 */
function advadsTrackingCheckEditor() {
	const text = window.advancedAds.admin.getSourceCode();
	const advadsUrl = jQuery('#advads-url');
	const condition1 =
		text && text.includes(' href=') && text.includes('%link%');
	const condition2 = advadsUrl && '' === advadsUrl.val();
	jQuery('.advads-ad-notice-tracking-missing-url-field').toggleClass(
		'hidden',
		!(condition1 && condition2)
	);
}

/**
 * Check if there is a link attribute in the content field that is not %link%
 *
 * @return {undefined}
 */
function advadsTrackingCheckLink() {
	// check if url is given and not empty
	if (!jQuery('#advads-url').length || '' === jQuery('#advads-url').val()) {
		return;
	}
	// fetch the contents of the source editor via our global function
	const text = window.advancedAds.admin.getSourceCode();
	// search for href attribute
	const errormessage = jQuery(
		'.advads-ad-notice-tracking-link-placeholder-missing'
	);

	if (text.search(' href=') > 0 && text.search('%link%') < 0) {
		if (errormessage.is(':hidden')) {
			errormessage.show();
		}
	} else {
		// hide error message
		errormessage.hide();
	}
}

function makeid(length) {
	let text = '';
	const possible =
		'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

	for (let i = 0; i < length; i++) {
		text += possible.charAt(Math.floor(Math.random() * possible.length));
	}
	return text;
}

/**
 * Display click tracking limitation fields based on ad type
 *
 * @param {string} adType
 */
function advadsTrackingDisplayClickLimitField(adType) {
	// Show / hide click tracking row.
	jQuery('.advads-tracking-click-limit-row').toggle(
		advads_tracking_clickable_ad_types.indexOf(adType) !== -1
	);
	const optionsList = jQuery('#advanced-ads-ad-parameters').siblings(
		'.advads-option-list'
	);
	const tracking = optionsList
		.find('span.label:first-of-type')
		.add(optionsList.find(' > div:first-of-type'))
		.add(optionsList.find(' > hr:first-of-type'));

	switch (adType) {
		case 'adsense':
		case 'gam':
		case 'group':
			optionsList.find('> * ').not(tracking).hide();
			break;
		default:
			optionsList.find('> * ').show();
	}
}

/**
 * Draw the graph
 */
function drawGraph() {
	// no stats to show yet or not relevant for this ad type (e.g., Analytics tracking method used)
	if (
		'undefined' === typeof advads_stats ||
		false === advads_stats.impressions
	) {
		return;
	}

	const imprs = [];
	for (var date in advads_stats.impressions) {
		var val = advads_stats.impressions[date][advads_stats.ID] || 0;
		imprs.push([date, parseInt(val)]);
	}
	const clicks = [];
	for (var date in advads_stats.clicks) {
		var val = advads_stats.clicks[date][advads_stats.ID] || 0;
		clicks.push([date, parseInt(val)]);
	}

	const graphOptions = {
		axes: {
			xaxis: {
				tickOptions: {},
				tickInterval: '',
			},
			yaxis: {
				min: 0,
				formatString: '$%.2f',
				autoscale: true,
				label: '',
				tickOptions: { formatString: "%'.0f" },
			},
			y2axis: {
				min: 0,
				autoscale: true,
				label: '',
				tickOptions: { formatString: "%'.0f" },
			},
		},
		grid: {
			background: '#ffffff',
			borderWidth: 1.0,
			shadow: false,
			gridLineColor: '#e5e5e5',
			drawBorder: false,
		},
		highlighter: {
			show: true,
			sizeAdjust: 7.5,
		},
		cursor: {
			show: false,
		},
		title: {
			show: true,
		},
		seriesDefaults: {
			rendererOptions: {
				smooth: true,
			},
		},
	};

	graphOptions.axes.xaxis.renderer = jQuery.jqplot.DateAxisRenderer;
	graphOptions.axes.xaxis.tickInterval = '1 day';
	graphOptions.axes.xaxis.tickOptions.formatString = '%b&nbsp;%#d';
	graphOptions.axes.yaxis.label = advadsStatsLocale.impressions;
	graphOptions.axes.yaxis.labelRenderer =
		jQuery.jqplot.CanvasAxisLabelRenderer;
	graphOptions.axes.y2axis.label = advadsStatsLocale.clicks;
	graphOptions.axes.y2axis.labelRenderer =
		jQuery.jqplot.CanvasAxisLabelRenderer;

	graphOptions.series = [
		{
			color: '#1B183A',
			highlighter: {
				formatString: "%s: %'.0f " + advadsStatsLocale.impressions,
			},
			lineWidth: 3,
			markerOptions: {
				size: 5,
				style: 'circle',
			},
		},
		{
			color: '#0474A2',
			highlighter: {
				formatString: "%s: %'.0f " + advadsStatsLocale.clicks,
			},
			linePattern: 'dashed',
			lineWidth: 3,
			markerOptions: {
				size: 5,
				style: 'filledSquare',
			},
			yaxis: 'y2axis',
		},
	];
	const lines = [imprs, clicks];
	const ticks = [];
	for (const i in imprs) {
		const x = imprs[i];
		ticks.push(x[0]);
	}
	graphOptions.axes.xaxis.ticks = ticks;
	jQuery.jqplot('stats-jqplot', lines, graphOptions);
}

function advadsTrackingChecks() {
	advadsTrackingCheckEditor();
	advadsTrackingCheckLink();
}

/**
 * Check if there is a link in the content field and a tracking url given
 */
jQuery(function () {
	advadsTrackingDisplayClickLimitField(
		jQuery('#advanced-ad-type input:checked').val()
	);

	jQuery(document).on('change', '#advanced-ad-type input', function () {
		advadsTrackingDisplayClickLimitField(jQuery(this).val());
	});

	jQuery(document).on('click', '#regenerateSharableLink', function (ev) {
		ev.preventDefault();
		const pid = makeid(48);

		jQuery('[name="advanced_ad[tracking][public-id]"]').val(pid);

		jQuery('#regenerateSharableLink').css('display', 'none');
		jQuery('#save-new-public-link').css('display', 'inline');
	});

	drawGraph();
});

function waitForCodeEditor(callback, interval = 100, maxAttempts = 30) {
	let attempts = 0;

	const checkInterval = setInterval(() => {
		if (window.advancedAds.admin.getSourceCode || attempts >= maxAttempts) {
			clearInterval(checkInterval);
			if (window.advancedAds.admin.getSourceCode) {
				callback();
			}
		}
		attempts++;
	}, interval);
}

/**
 * Validate urlfield & code editor url logic
 */
waitForCodeEditor(() => {
	advadsTrackingChecks();

	const advadsUrl = jQuery('#advads-url');
	const codemirror = window.advancedAds.admin.codeMirror;

	advadsUrl?.on('keyup', advadsTrackingChecks);
	codemirror?.on('keyup', advadsTrackingChecks);

	jQuery('#advanced-ads-ad-parameters textarea#advads-ad-content-plain').on(
		'keyup',
		advadsTrackingCheckLink
	);
});
