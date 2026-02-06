(function ($) {
	'use strict';

	const spinner = $('<img src="" alt="loading" class="ajax-spinner" />');
	let existingFilter = false;

	const numberFormats = {
		thousand: '',
		decimal: '',
	};

	function addThousandSep(number) {
		return number
			.toString()
			.replace(/\B(?=(\d{3})+(?!\d))/g, numberFormats.thousand);
	}

	function formatNumber(number, decimals) {
		const i = parseInt(number);
		const d = parseFloat(number) - i;

		let result = addThousandSep(i);
		if ('undefined' === typeof decimals) {
			decimals = 0;
		}
		if (0 !== d && 0 < decimals) {
			let D = parseInt(d * Math.pow(10, decimals)).toString();
			while (D.length < decimals) {
				D = '0' + D;
			}
			if (Math.pow(10, decimals) * d < 1) {
				return formatNumber(number, decimals + 1);
			}
			result += numberFormats.decimal + D;
		}
		return result;
	}

	let cmpBtnStates = {
		prev: null,
		next: null,
	};

	/**
	 *  Check if and ad ID belongs to a given group ID
	 * @param adID
	 * @param groupID
	 */
	function isInGroup(adID, groupID) {
		adID = parseInt(adID, 10);
		if (undefined == adsToGroups[adID]) {
			return false;
		}
		for (const i in adsToGroups[adID]) {
			if (adsToGroups[adID][i] == parseInt(groupID, 10)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Destroy and re-initialize the autocomplete with new ad IDs => ad Titles object
	 * @param ads
	 */
	function rebuildAutoComplete(ads) {
		let disable = false;
		if ($('#ad-filter').prop('disabled')) {
			$('#ad-filter').prop('disabled', false);
			disable = true;
		}

		window.autoCompSrc = [];
		for (const i in ads) {
			window.autoCompSrc.push({
				label: ads[i],
				value: i,
			});
		}
		if ($('#ad-filter').hasClass('ui-autocomplete-input')) {
			$('#ad-filter').autocomplete('destroy');
		}
		$('#ad-filter').autocomplete({
			source: window.autoCompSrc,
			delay: 200,
			select(ev, ui) {
				ev.preventDefault();
				$.statDisplay.afterFilterChange(ui.item.label, ui.item.value);
			},
			focus(ev, ui) {
				ev.preventDefault();
			},
		});
		if (disable) {
			$('#ad-filter').prop('disabled', true);
		}
	}

	/**
	 *  JS equivalent of https://codex.wordpress.org/Function_Reference/zeroise
	 * @param number
	 * @param threshold
	 */
	function zeroise(number, threshold) {
		number = parseInt(number);
		threshold = parseInt(threshold);
		if (0 != threshold) {
			number = number.toString();
			if (threshold > number.length) {
				number = '0'.repeat(threshold - number.length) + number;
			}
		}
		return number.toString();
	}

	/**
	 *  Zeroise all year/month/date number with threshold = 2, no matter the order.
	 * @param dateString
	 * @param useHyphen
	 */
	function zeroiseDate(dateString, useHyphen) {
		if (undefined === useHyphen) {
			useHyphen = false;
		}
		let splited = dateString.split('/');
		let sep = '/';
		if (1 == splited.length) {
			splited = dateString.split('-');
			sep = '-';
		}
		if (1 == splited.length) {
			splited = dateString.split('.');
			sep = '.';
		}
		if (useHyphen) {
			sep = '-';
		}
		if (1 == splited.length) {
			return dateString;
		}
		return (
			zeroise(splited[0], 2) +
			sep +
			zeroise(splited[1], 2) +
			sep +
			zeroise(splited[2], 2)
		);
	}

	/**
	 *  Destroy all DataTable instances and empty all table wrappers
	 */
	function clearTables() {
		$('.DataTable').each(function () {
			$(this).DataTable().destroy();
		});
		$('#adTable,#dateTable').empty();
	}

	/**
	 *  Format a date using PHP date format identifier
	 * @param date
	 * @param format
	 */
	function formatDate(date, format) {
		if (undefined === statsDates.wpDateTimeZoneName) {
			return (
				date.getFullYear() +
				'/' +
				(date.getMonth() + 1) +
				'/' +
				date.getDate()
			);
		}
		// escape PHP's date format identifier in the WP's timezone
		const TZ = statsDates.wpDateTimeZoneName.replace(
			/([dDjlNSwzWFmMntLoYyaABgGhHisueIOPTZcrU])/g,
			function (m, $1, o, s) {
				return '\\' + $1;
			}
		);

		// replace any "e" identifier with the escaped WP time zone name - add extra space then trim to avoid complex REGEX processing
		const dateFormat =
			' ' +
			format.replace(/([^\\])(e)/g, function (m, $1, $2, o, s) {
				return $1 + TZ;
			});
		return date.format(dateFormat.trim());
	}

	/**
	 *  Create and display ad per date table
	 *
	 * @param [str]  wrapId, external wrapper element ID attribute
	 * @param [obj]  data, table data to be used in dataTable
	 * @param wrapId
	 * @param data
	 * @param dataB
	 * @param perA
	 * @param perB
	 */
	function perDatetable(wrapId, data, dataB, perA, perB) {
		if (!$('#' + wrapId).length) {
			return;
		}
		if (undefined === dataB) {
			// simple period
			var theTable = $(
				'<table id="' +
					wrapId +
					'-table" class="advads-stats-table">' +
					'<thead><tr>' +
					'<th>' +
					statsLocale.date +
					'</th>' +
					'<th>' +
					statsLocale.impressions +
					'</th>' +
					'<th>' +
					statsLocale.clicks +
					'</th>' +
					'<th>' +
					statsLocale.ctr +
					'</th>' +
					'</tr></thead>' +
					'<tfoot></tfoot>' +
					'<tbody></tbody>' +
					'</table>'
			);
			let totalImpr = 0;
			let totalClick = 0;
			for (var date in data) {
				var ctr =
					0 != data[date].impr
						? (100 * data[date].click) / data[date].impr
						: '0.00';
				var theDate = new Date(date);

				var theTime = theDate.getTime();
				var clientTZ = theDate.getTimezoneOffset() * 60 * 1000;

				// avoid date to be localized twice, once on tracking, and once again on JS date localization in the table (hence one day gap under some timezone conditions)
				theDate.setTime(theTime + clientTZ);

				// timestamp is for ordering only
				var timestamp = Math.round(theDate.getTime() / 1000);
				var dateFormat =
					'month' == $.statDisplay.argsA.groupby
						? 'F Y'
						: statsDates.wpDateFormat;

				theDate = formatDate(theDate, dateFormat);
				var row =
					'<tr><td>' +
					theDate +
					'<input type="hidden" value="' +
					timestamp +
					'" /></td>' +
					'<td>' +
					formatNumber(data[date].impr) +
					'<input type="hidden" value="' +
					data[date].impr +
					'" /></td>' +
					'<td>' +
					formatNumber(data[date].click) +
					'<input type="hidden" value="' +
					data[date].click +
					'" /></td>' +
					'<td>' +
					formatNumber(ctr, 2) +
					'%<input type="hidden" value="' +
					ctr * 100 +
					'" /></td>' +
					'</tr>';
				theTable.find('tbody').append($(row));
				totalImpr += data[date].impr;
				totalClick += data[date].click;
			}
			const totalCTR =
				0 != totalClick && 0 != totalImpr
					? (100 * totalClick) / totalImpr + '%'
					: '0.00%';
			theTable
				.find('tfoot')
				.append(
					$(
						'<tr><th>' +
							statsLocale.total +
							'</th>' +
							'<th>' +
							formatNumber(totalImpr) +
							'</th>' +
							'<th>' +
							formatNumber(totalClick) +
							'</th>' +
							'<th>' +
							formatNumber(totalCTR, 2) +
							'</th></tr>'
					)
				);
			$('#' + wrapId).append(theTable);
			theTable.DataTable({
				columns: [
					{ orderDataType: 'dom-text' },
					{ orderDataType: 'dom-text' },
					{ orderDataType: 'dom-text' },
					{ orderDataType: 'dom-text' },
				],
				searching: false,
				destroy: true,
				language: window._dataTableLang,
				order: [[0, 'desc']],
			});
		} else {
			// compare periods
			var theTable = $(
				'<table id="' +
					wrapId +
					'-table" class="advads-stats-table">' +
					'<thead><tr>' +
					'<th>' +
					statsLocale.date +
					' (' +
					perA +
					')</th>' +
					'<th>' +
					statsLocale.impressions +
					'</th>' +
					'<th>' +
					statsLocale.clicks +
					'</th>' +
					'<th>' +
					statsLocale.ctr +
					'</th>' +
					'<th>' +
					statsLocale.date +
					' (' +
					perB +
					')</th>' +
					'<th>' +
					statsLocale.impressions +
					'</th>' +
					'<th>' +
					statsLocale.clicks +
					'</th>' +
					'<th>' +
					statsLocale.ctr +
					'</th>' +
					'</tr></thead>' +
					'<tfoot></tfoot>' +
					'<tbody></tbody>' +
					'</table>'
			);
			const rows = [];
			let totalImprA = 0;
			let totalClickA = 0;
			let totalImprB = 0;
			let totalClickB = 0;
			for (var date in data) {
				var ctr =
					0 != data[date].impr
						? (100 * data[date].click) / data[date].impr
						: '0.00';
				var theDate = new Date(date);

				var theTime = theDate.getTime();
				var clientTZ = theDate.getTimezoneOffset() * 60 * 1000;

				theDate.setTime(theTime + clientTZ);

				var dateFormat =
					'month' == $.statDisplay.argsA.groupby
						? 'F Y'
						: statsDates.wpDateFormat;
				var timestamp = Math.round(theDate.getTime() / 1000);
				theDate = formatDate(theDate, dateFormat);
				var row =
					'<tr><td>' +
					theDate +
					'<input type="hidden" value="' +
					timestamp +
					'" /></td>' +
					'<td>' +
					formatNumber(data[date].impr) +
					'<input type="hidden" value="' +
					data[date].impr +
					'" /></td>' +
					'<td>' +
					formatNumber(data[date].click) +
					'<input type="hidden" value="' +
					data[date].click +
					'" /></td>' +
					'<td>' +
					formatNumber(ctr, 2) +
					'%<input type="hidden" value="' +
					ctr * 100 +
					'" /></td>' +
					'</tr>';
				rows.push($(row));
				totalImprA += data[date].impr;
				totalClickA += data[date].click;
			}
			let index = 0;
			for (var date in dataB) {
				if (undefined !== rows[index]) {
					var ctr =
						0 != dataB[date].impr
							? (100 * dataB[date].click) / dataB[date].impr
							: '0.00';
					var theDate = new Date(date);

					var theTime = theDate.getTime();
					var clientTZ = theDate.getTimezoneOffset() * 60 * 1000;

					theDate.setTime(theTime + clientTZ);

					var dateFormat =
						'month' == $.statDisplay.argsA.groupby
							? 'F Y'
							: statsDates.wpDateFormat;

					var timestamp = Math.round(theDate.getTime() / 1000);
					theDate = formatDate(theDate, dateFormat);

					const extraTD =
						'<td>' +
						theDate +
						'<input type="hidden" value="' +
						timestamp +
						'" /></td>' +
						'<td>' +
						formatNumber(dataB[date].impr) +
						'<input type="hidden" value="' +
						dataB[date].impr +
						'" /></td>' +
						'<td>' +
						formatNumber(dataB[date].click) +
						'<input type="hidden" value="' +
						dataB[date].click +
						'" /></td>' +
						'<td>' +
						formatNumber(ctr, 2) +
						'%<input type="hidden" value="' +
						ctr * 100 +
						'" /></td>';
					rows[index].append($(extraTD));
					theTable.find('tbody').append(rows);
					totalImprB += dataB[date].impr;
					totalClickB += dataB[date].click;
				}
				++index;
			}
			const totalCTRA =
				0 != totalClickA && 0 != totalImprA
					? (100 * totalClickA) / totalImprA + '%'
					: '0.00%';
			const totalCTRB =
				0 != totalClickB && 0 != totalImprB
					? (100 * totalClickB) / totalImprB + '%'
					: '0.00%';
			$('#' + wrapId).append(theTable);
			theTable
				.find('tfoot')
				.append(
					$(
						'<tr><th>' +
							statsLocale.total +
							'</th>' +
							'<th>' +
							formatNumber(totalImprA) +
							'</th>' +
							'<th>' +
							formatNumber(totalClickA) +
							'</th>' +
							'<th>' +
							formatNumber(totalCTRA, 2) +
							'</th>' +
							'<th></th>' +
							'<th>' +
							formatNumber(totalImprB) +
							'</th>' +
							'<th>' +
							formatNumber(totalClickB) +
							'</th>' +
							'<th>' +
							formatNumber(totalCTRB, 2) +
							'</th>' +
							'</tr>'
					)
				);
			theTable.DataTable({
				searching: false,
				destroy: true,
				language: window._dataTableLang,
				columns: [
					{ orderDataType: 'dom-text' },
					{ orderDataType: 'dom-text' },
					{ orderDataType: 'dom-text' },
					{ orderDataType: 'dom-text' },
					{ orderDataType: 'dom-text' },
					{ orderDataType: 'dom-text' },
					{ orderDataType: 'dom-text' },
					{ orderDataType: 'dom-text' },
				],
			});
		}
	}

	/**
	 *  Create and display per ad/group ID table
	 * @param wrapId
	 * @param data
	 * @param dataB
	 * @param perA
	 * @param perB
	 */
	function perAdtable(wrapId, data, dataB, perA, perB) {
		if (!$('#' + wrapId).length) {
			return;
		}

		var isGroup = $( '#display-filter-list .display-filter-group' ).length;
		var adHeader = isGroup ? statsLocale.group : statsLocale.ad;
		if (undefined === dataB) {
			// simple period
			var totalImprA = 0;
			var totalClickA = 0;
			var theTable = $(
				'<table id="' +
					wrapId +
					'-table" class="advads-stats-table">' +
					'<thead><tr>' +
					'<th>' +
					adHeader +
					'</th>' +
					'<th>' +
					statsLocale.impressions +
					'</th>' +
					'<th>' +
					statsLocale.clicks +
					'</th>' +
					'<th>' +
					statsLocale.ctr +
					'</th>' +
					'</tr></thead>' +
					'<tfoot></tfoot>' +
					'<tbody></tbody>' +
					'</table>'
			);
			for (var id in data) {
				// if there is a filter, skip deleted ads
				if (
					(
						$('#display-filter-list .display-filter-elem').length ||
						isGroup
					) && 'deleted' == id
				) {
					continue;
				}
				totalImprA += data[id].impr;
				totalClickA += data[id].click;
				var ctr =
					0 != data[id].impr
						? (100 * data[id].click) / data[id].impr
						: '0.00';
				var title = statsLocale.deletedAds;
				if ($('#display-filter-list .display-filter-group').length) {
					title = groupsToAds[id].name;
				} else if ('deleted' != id) {
					title = adTitles[id];
				}

				var link = isGroup ? `admin.php?page=advanced-ads-groups#modal-group-edit-${id}` : `post.php?post=${id}&action=edit`;
				const row = `<tr>
					<td><a class="advads-stats-table__adlink" href="${advancedAds.endpoints.adminUrl}${link}">${title}</a></td>
					<td>${formatNumber(data[id].impr)}<input type="hidden" value="${data[id].impr}" /></td>
					<td>${formatNumber(data[id].click)}<input type="hidden" value="${data[id].click}" /></td>
					<td>${formatNumber(ctr, 2)}%<input type="hidden" value="${ctr * 100}" /></td>
					</tr>`;

				theTable.find('tbody').append($(row));
			}
			var totalCtrA =
				0 != totalImprA ? (100 * totalClickA) / totalImprA : '0.00';
			theTable
				.find('tfoot')
				.append(
					$(
						'<tr><th>' +
							statsLocale.total +
							'</th>' +
							'<th>' +
							formatNumber(totalImprA) +
							'</th>' +
							'<th>' +
							formatNumber(totalClickA) +
							'</th>' +
							'<th>' +
							formatNumber(totalCtrA, 2) +
							'%</th></tr>'
					)
				);
			$('#' + wrapId).append(theTable);
			theTable.DataTable({
				searching: false,
				destroy: true,
				language: window._dataTableLang,
				columns: [
					null,
					{ orderDataType: 'dom-text' },
					{ orderDataType: 'dom-text' },
					{ orderDataType: 'dom-text' },
				],
			});
		} else {
			// compare periods
			var totalImprA = 0;
			let totalImprB = 0;
			var totalClickA = 0;
			let totalClickB = 0;
			var adHeader = $('#display-filter-list .display-filter-group')
				.length
				? statsLocale.group
				: statsLocale.ad;
			var theTable = $(
				'<table id="' +
					wrapId +
					'-table" class="advads-stats-table">' +
					'<thead><tr>' +
					'<th>' +
					adHeader +
					'</th>' +
					'<th>' +
					statsLocale.impressions +
					' (' +
					perA +
					')</th>' +
					'<th>' +
					statsLocale.clicks +
					'</th>' +
					'<th>' +
					statsLocale.ctr +
					'</th>' +
					'<th>' +
					statsLocale.impressions +
					' (' +
					perB +
					')</th>' +
					'<th>' +
					statsLocale.clicks +
					'</th>' +
					'<th>' +
					statsLocale.ctr +
					'</th>' +
					'</tr></thead>' +
					'<tfoot></tfoot>' +
					'<tbody></tbody>' +
					'</table>'
			);
			const rows = [];
			for (var id in data) {
				// if there is an ad filter, skip deleted ads
				if (
					$('#display-filter-list .display-filter-elem').length &&
					'deleted' == id
				) {
					continue;
				}
				totalImprA += data[id].impr;
				totalClickA += data[id].click;
				var ctr =
					0 != data[id].impr
						? (100 * data[id].click) / data[id].impr
						: '0.00';
				var title = statsLocale.deletedAds;
				if ($('#display-filter-list .display-filter-group').length) {
					title = groupsToAds[id].name;
				} else if ('deleted' != id) {
					title = adTitles[id];
				}
				const row =
					'<tr><td>' +
					title +
					'</td>' +
					'<td>' +
					formatNumber(data[id].impr) +
					'<input type="hidden" value="' +
					data[id].impr +
					'" /></td>' +
					'<td>' +
					formatNumber(data[id].click) +
					'<input type="hidden" value="' +
					data[id].click +
					'" /></td>' +
					'<td>' +
					formatNumber(ctr, 2) +
					'%<input type="hidden" value="' +
					ctr * 100 +
					'" /></td>' +
					'</tr>';
				rows.push($(row));
			}
			let index = 0;
			for (var id in dataB) {
				// if there is an ad filter, skip deleted ads
				if (
					$('#display-filter-list .display-filter-elem').length &&
					'deleted' == id
				) {
					++index;
					continue;
				}
				if (undefined !== rows[index]) {
					totalImprB += dataB[id].impr;
					totalClickB += dataB[id].click;
					const ctrB =
						0 != dataB[id].impr
							? (100 * dataB[id].click) / dataB[id].impr
							: '0.00';
					var title = statsLocale.deletedAds;
					if ('deleted' != id) {
						title = adTitles[id];
					}
					const extraTD =
						'<td>' +
						formatNumber(dataB[id].impr) +
						'<input type="hidden" value="' +
						dataB[id].impr +
						'" /></td>' +
						'<td>' +
						formatNumber(dataB[id].click) +
						'<input type="hidden" value="' +
						dataB[id].impr +
						'" /></td>' +
						'<td>' +
						formatNumber(ctrB, 2) +
						'%<input type="hidden" value="' +
						ctrB +
						'" /></td>';
					rows[index].append($(extraTD));
					theTable.find('tbody').append(rows[index]);
				}
				++index;
			}
			var totalCtrA =
				0 != totalImprA ? (100 * totalClickA) / totalImprA : '0.00';
			const totalCtrB =
				0 != totalImprB ? (100 * totalClickB) / totalImprB : '0.00';
			theTable
				.find('tfoot')
				.append(
					$(
						'<tr><th>' +
							statsLocale.total +
							'</th>' +
							'<th>' +
							formatNumber(totalImprA) +
							'</th>' +
							'<th>' +
							formatNumber(totalClickA) +
							'</th>' +
							'<th>' +
							formatNumber(totalCtrA, 2) +
							'%</th>' +
							'<th>' +
							formatNumber(totalImprB) +
							'</th>' +
							'<th>' +
							formatNumber(totalClickB) +
							'</th>' +
							'<th>' +
							formatNumber(totalCtrB, 2) +
							'%</th></tr>'
					)
				);
			$('#' + wrapId).append(theTable);
			theTable.DataTable({
				searching: false,
				destroy: true,
				language: window._dataTableLang,
				columns: [
					null,
					{ orderDataType: 'dom-text' },
					{ orderDataType: 'dom-text' },
					{ orderDataType: 'dom-text' },
					{ orderDataType: 'dom-text' },
					{ orderDataType: 'dom-text' },
					{ orderDataType: 'dom-text' },
				],
			});
		}
	}

	/**
	 *  Get week start ( ISO Week ) date object from Year and week number
	 *
	 *  [http://stackoverflow.com/questions/16590500/javascript-calculate-date-from-week-number]
	 * @param w
	 * @param y
	 */
	function getDateOfISOWeek(w, y) {
		const simple = new Date(y, 0, 1 + (w - 1) * 7);
		const dow = simple.getDay();
		const ISOweekStart = simple;
		if (dow <= 4) {
			ISOweekStart.setDate(simple.getDate() - simple.getDay() + 1);
		} else {
			ISOweekStart.setDate(simple.getDate() + 8 - simple.getDay());
		}
		return ISOweekStart;
	}

	/**
	 *  ISOWeek comparison
	 *
	 * @param [str] w1, week in the format "2016-W09"
	 * @param [str] w2, week in the format "2016-W09"
	 * @param [str] op, comparison operator : <, <=, >, >=, !=, ==
	 *
	 * @param w1
	 * @param w2
	 * @param op
	 *  @return [bool], the result
	 */
	function ISOweekCompare(w1, w2, op) {
		if (undefined === op || undefined === w1 || undefined === w2) {
			return null;
		}
		w1 = w1.split('-W');
		w2 = w2.split('-W');
		if (2 != w1.length || 2 != w2.length) {
			return null;
		}
		switch (op) {
			case '==':
				if (w1[0] == w2[0] && w1[1] == w2[1]) {
					return true;
				}
				return false;
				break;
			case '!=':
				if (w1[0] == w2[0] && w1[1] == w2[1]) {
					return false;
				}
				return true;
				break;
			case '>=':
				if (
					(w1[0] == w2[0] && w1[1] == w2[1]) ||
					(w1[0] == w2[0] && w1[1] > w2[1]) ||
					w1[0] > w2[0]
				) {
					return true;
				}
				return false;
				break;
			case '>':
				if ((w1[0] == w2[0] && w1[1] > w2[1]) || w1[0] > w2[0]) {
					return true;
				}
				return false;
				break;
			case '<=':
				if (
					(w1[0] == w2[0] && w1[1] == w2[1]) ||
					(w1[0] == w2[0] && w1[1] < w2[1]) ||
					w1[0] < w2[0]
				) {
					return true;
				}
				return false;
				break;
			case '<':
				if ((w1[0] == w2[0] && w1[1] < w2[1]) || w1[0] < w2[0]) {
					return true;
				}
				return false;
				break;
			default:
				return null;
		}
	}

	/**
	 *  Get the previous ISOWeek. Accepts and returns week in "2016-W09" format
	 * @param w
	 */
	function decrISOWeek(w) {
		if (undefined === w) {
			return;
		}
		w = w.split('-W');
		if (2 != w.length) {
			return;
		}
		const date = getDateOfISOWeek(parseInt(w[1]) - 1, parseInt(w[0]));
		if (date.getFullYear() == w[0]) {
			// the result is in the same year
			const newWeek = parseInt(w[1]) - 1;
			return 10 > newWeek
				? w[0] + '-W0' + newWeek
				: w[0] + '-W' + newWeek;
		}
		const pastDate = getDateOfISOWeek(53, parseInt(w[0]) - 1);
		if (pastDate.getFullYear() == parseInt(w[0]) - 1) {
			// the past year is a 53 weeks year
			return parseInt(w[0]) - 1 + '-W53';
		}
		return parseInt(w[0]) - 1 + '-W52';
	}

	/**
	 *  Get the next ISOWeek. Accepts and returns week in "2016-W09" format
	 * @param w
	 */
	function incrISOWeek(w) {
		if (undefined === w) {
			return;
		}
		w = w.split('-W');
		if (2 != w.length) {
			return;
		}
		const date = getDateOfISOWeek(parseInt(w[1]) + 1, parseInt(w[0]));
		if (date.getFullYear() == w[0]) {
			// the result is in the same year
			const newWeek = parseInt(w[1]) + 1;
			return 10 > newWeek
				? w[0] + '-W0' + newWeek
				: w[0] + '-W' + newWeek;
		}
		return parseInt(w[0]) + 1 + '-W01';
	}

	function decrMonth(m) {
		if (undefined === m) {
			return;
		}
		m = m.split('-');
		if (2 != m.length) {
			return;
		}
		if (parseInt(m[1]) - 1 < 1) {
			return parseInt(m[0]) - 1 + '-12';
		}
		let newMonth = (parseInt(m[1]) - 1).toString();
		if (1 == newMonth.length) {
			newMonth = '0' + newMonth;
		}
		return m[0] + '-' + newMonth;
	}

	function incrMonth(m) {
		if (undefined === m) {
			return;
		}
		m = m.split('-');
		if (2 != m.length) {
			return;
		}
		if (parseInt(m[1]) - 1 > 12) {
			return parseInt(m[0]) + 1 + '-01';
		}
		let newMonth = (parseInt(m[1]) + 1).toString();
		if (1 == newMonth.length) {
			newMonth = '0' + newMonth;
		}
		return m[0] + '-' + newMonth;
	}

	// limit for stats points
	const MAX_POINTS = 150;
	const COLORS = [
		'#1B183A',
		'#0474A2',
		'#d8b83f',
		'#4b5de4',
		'#953579',
		'#579575',
		'#EAA228',
		'#839557',
		'#ff5800',
		'#958c12',
		'#0085cc',
		'#4bb2c5',
		'#c5b47f',
	];
	const defaultGraphOptions = {
		axes: {
			xaxis: {
				tickOptions: {},
				tickInterval: '',
			},
			yaxis: {
				min: 0,
				tickOptions: { formatString: '%.0f' },
				autoscale: true,
				label: '',
			},
			y2axis: {
				min: 0,
				tickOptions: { formatString: '%.0f' },
				autoscale: true,
				label: '',
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

	const statDisplay = function (options) {
		this.nonce = '';
		this.graph = null;
		this.statsA = false;
		this.origStatsA = {};
		this.argsA = false;
		this.statsB = false;
		this.argsB = false;
		this.lastValidOffset = 0;
		this.lastBtn = '';
		this.lastArgs = {};
		this.offsetLimit = false;
		const _allAds = $('#all-ads').val();
		this.allAds = _allAds ? _allAds.split('-') : false;
		this.localizedDate = false;
		this.init();
		this.evt();

		return this;
	};

	statDisplay.prototype = {
		constructor: statDisplay,

		reverseDisableCompareBtn() {
			if (null === cmpBtnStates.prev) {
				// disabling
				cmpBtnStates = {
					prev: !$('#compare-prev-btn').prop('disabled'),
					next: !$('#compare-next-btn').prop('disabled'),
				};
				$('#compare-prev-btn,#compare-prev-btn').prop('disabled', true);
			} else {
				// enabling if needed
				if (cmpBtnStates.prev) {
					$('#compare-prev-btn').prop('disabled', false);
				}
				if (cmpBtnStates.next) {
					$('#compare-prev-btn').prop('disabled', false);
				}
				cmpBtnStates = {
					prev: null,
					next: null,
				};
			}
		},

		reset() {
			if (this.graph) {
				this.graph.destroy();
			}
			clearTables();
			$('#display-filter-list .display-filter-elem').remove();
			$('#advads-graph-legend .legend-item').not('.donotremove').remove();
			this.statsA = false;
			this.origStatsA = {};
			this.argsA = false;
			this.statsB = false;
			this.argsB = false;
			this.lastValidOffset = 0;
			this.lastBtn = '';
			this.lastArgs = {};
			this.offsetLimit = false;
			this._enableCompare(false);
		},

		localizeDateNames() {
			Date.shortMonths = _dateName.shortMonths;
			Date.longMonths = _dateName.longMonths;
			Date.shortDays = _dateName.shortDays;
			Date.longDays = _dateName.longDays;
			this.localizedDate = true;
		},

		/**
		 *  Clear everything when there is no records for statsA
		 */
		_noRecords() {
			if (this.graph) {
				this.graph.destroy();
			}
			clearTables();
			$('#advads-stats-graph')
				.empty()
				.append(
					$('<p id="no-records">' + statsLocale.noRecords + '</p>')
				);
			$('#advads-graph-legend').css('display', 'none');
		},

		/**
		 *  Reverse the disabled property of inputs within a container
		 *  Avoid trouble for async operations
		 * @param containerID
		 */
		_reverseDisabled(containerID) {
			if (undefined === containerID) {
				containerID = 'wpbody-content';
			}
			$('#' + containerID)
				.find('input, button, textarea, select')
				.not('.donotreversedisable')
				.each(function () {
					const v = $(this).prop('disabled');
					$(this).prop('disabled', !v);
				});
		},

		/**
		 *  Get current group filter
		 */
		getGroupFilters() {
			return $('#display-filter-list .display-filter-group')
				.map(function () {
					return parseInt($(this).attr('data-id'), 10);
				})
				.get();
		},

		/**
		 *  Get the current filters
		 */
		getFilters() {
			if (false !== existingFilter) {
				const result = [parseInt(existingFilter)];
				// an ad ID was found in URL, call the afterFilterChange function and abort ( will be called again by the afterFilterChange function )
				const filterTitle =
					undefined === adTitles[existingFilter]
						? false
						: adTitles[existingFilter];
				const adID = existingFilter;
				existingFilter = false;
				if (filterTitle) {
					this.afterFilterChange(filterTitle, adID, false);
				}
				return result;
			}
			return $('#display-filter-list .display-filter-elem')
				.map(function () {
					return parseInt($(this).attr('data-id'), 10);
				})
				.get();
		},

		/**
		 *  Enable/disable comparison form
		 * @param enable
		 */
		_enableCompare(enable) {
			if (undefined === enable) {
				enable = true;
			}
			if (enable) {
				$('#compare-tr').css('display', 'table-row');
			} else {
				$('#compare-tr').css('display', 'none');
			}
		},

		/**
		 *  Update available comparison period (label and value), for the current statsA args
		 */
		_updateComparablePeriods() {
			let now = new Date();
			const clientTZ = now.getTimezoneOffset() * 60 * 1000;

			/**
			 *  Get timestamp ( in ms ), subtract client (browser) timestamp to get UTC,
			 *  and add WP timezone as this will be compensated in DB custom timestamp
			 */
			now = new Date().getTime() - clientTZ + statsDates.WPGmtOffset;
			let result = {
				prev: {
					from: false,
					to: false,
					label: 'xxx',
					disabled: true,
				},
				next: {
					from: false,
					to: false,
					label: 'xxx',
					disabled: true,
				},
			};
			if (undefined === this.argsA.file) {
				switch (this.argsA.period) {
					case 'today':
						if (this.argsB) {
							var startDate = new Date(
								now - 3600000 * (1 - this.argsB.offset) * 24
							);
							var endDate = startDate;
							result = {
								prev: {
									from:
										startDate.getFullYear() +
										'/' +
										(startDate.getMonth() + 1) +
										'/' +
										startDate.getDate(),
									to:
										endDate.getFullYear() +
										'/' +
										(endDate.getMonth() + 1) +
										'/' +
										endDate.getDate(),
									label: statsLocale.prevDay,
									disabled:
										this.argsB.offset < this.lastValidOffset
											? true
											: false,
								},
							};
							if (-2 < this.argsB.offset) {
								result.next = {
									from: false,
									to: false,
									label: statsLocale.nextDay,
									disabled: true,
								};
							} else {
								startDate = new Date(
									now -
										3600000 * (-1 - this.argsB.offset) * 24
								);
								endDate = startDate;
								result.next = {
									from:
										startDate.getFullYear() +
										'/' +
										(startDate.getMonth() + 1) +
										'/' +
										startDate.getDate(),
									to:
										endDate.getFullYear() +
										'/' +
										(endDate.getMonth() + 1) +
										'/' +
										endDate.getDate(),
									label: statsLocale.nextDay,
									disabled: false,
								};
							}
						} else {
							var startDate = new Date(now - 3600000 * 24);
							var endDate = startDate;
							result = {
								prev: {
									from:
										startDate.getFullYear() +
										'/' +
										(startDate.getMonth() + 1) +
										'/' +
										startDate.getDate(),
									to:
										endDate.getFullYear() +
										'/' +
										(endDate.getMonth() + 1) +
										'/' +
										endDate.getDate(),
									label: statsLocale.prevDay,
									disabled: false,
								},
								next: {
									from: false,
									to: false,
									label: statsLocale.nextDay,
									disabled: true,
								},
							};
						}
						break; // end today
					case 'yesterday':
						if (this.argsB) {
							var startDate = new Date(
								now - 3600000 * (2 - this.argsB.offset) * 24
							);
							var endDate = startDate;
							result = {
								prev: {
									from:
										startDate.getFullYear() +
										'/' +
										(startDate.getMonth() + 1) +
										'/' +
										startDate.getDate(),
									to:
										endDate.getFullYear() +
										'/' +
										(endDate.getMonth() + 1) +
										'/' +
										endDate.getDate(),
									label: statsLocale.prevDay,
									disabled:
										this.argsB.offset < this.lastValidOffset
											? true
											: false,
								},
							};
							if (-2 < this.argsB.offset) {
								result.next = {
									from: false,
									to: false,
									label: statsLocale.nextDay,
									disabled: true,
								};
							} else {
								startDate = new Date(
									now - 3600000 * -this.argsB.offset * 24
								);
								endDate = startDate;
								result.next = {
									from:
										startDate.getFullYear() +
										'/' +
										(startDate.getMonth() + 1) +
										'/' +
										startDate.getDate(),
									to:
										endDate.getFullYear() +
										'/' +
										(endDate.getMonth() + 1) +
										'/' +
										endDate.getDate(),
									label: statsLocale.nextDay,
									disabled: false,
								};
							}
						} else {
							var startDate = new Date(now - 3600000 * 2 * 24);
							var endDate = startDate;
							result = {
								prev: {
									from:
										startDate.getFullYear() +
										'/' +
										(startDate.getMonth() + 1) +
										'/' +
										startDate.getDate(),
									to:
										endDate.getFullYear() +
										'/' +
										(endDate.getMonth() + 1) +
										'/' +
										endDate.getDate(),
									label: statsLocale.prevDay,
									disabled: false,
								},
								next: {
									from: false,
									to: false,
									label: statsLocale.nextDay,
									disabled: true,
								},
							};
						}
						break; // end yesterday
					case 'last7days':
						if (this.argsB) {
							var startDate = new Date(
								now -
									3600000 * (7 * (2 - this.argsB.offset)) * 24
							);
							var endDate = new Date(
								now -
									3600000 * (8 * (1 - this.argsB.offset)) * 24
							);

							result = {
								prev: {
									from:
										startDate.getFullYear() +
										'/' +
										(startDate.getMonth() + 1) +
										'/' +
										startDate.getDate(),
									to:
										endDate.getFullYear() +
										'/' +
										(endDate.getMonth() + 1) +
										'/' +
										endDate.getDate(),
									label: statsLocale['prev%dDays'].replace(
										'%d',
										'7'
									),
									disabled:
										this.argsB.offset < this.lastValidOffset
											? true
											: false,
								},
							};
							if (-2 < this.argsB.offset) {
								result.next = {
									from: false,
									to: false,
									label: statsLocale['next%dDays'].replace(
										'%d',
										'7'
									),
									disabled: true,
								};
							} else {
								startDate = new Date(
									now -
										3600000 *
											(7 * (0 - this.argsB.offset)) *
											24
								);
								endDate = new Date(
									now -
										3600000 *
											(8 * (-1 - this.argsB.offset)) *
											24
								);
								result.next = {
									from:
										startDate.getFullYear() +
										'/' +
										(startDate.getMonth() + 1) +
										'/' +
										startDate.getDate(),
									to:
										endDate.getFullYear() +
										'/' +
										(endDate.getMonth() + 1) +
										'/' +
										endDate.getDate(),
									label: statsLocale['next%dDays'].replace(
										'%d',
										'7'
									),
									disabled: false,
								};
							}
						} else {
							var startDate = new Date(now - 3600000 * 14 * 24);
							var endDate = new Date(now - 3600000 * 8 * 24);
							result = {
								prev: {
									from:
										startDate.getFullYear() +
										'/' +
										(startDate.getMonth() + 1) +
										'/' +
										startDate.getDate(),
									to:
										endDate.getFullYear() +
										'/' +
										(endDate.getMonth() + 1) +
										'/' +
										endDate.getDate(),
									label: statsLocale['prev%dDays'].replace(
										'%d',
										'7'
									),
									disabled: false,
								},
								next: {
									from: false,
									to: false,
									label: statsLocale['next%dDays'].replace(
										'%d',
										'7'
									),
									disabled: true,
								},
							};
						}
						break; // end last7days
					case 'thismonth':
						if (this.argsB) {
							var startDate = new Date(now);
							startDate.setMonth(
								startDate.getMonth() - (1 - this.argsB.offset),
								1
							);
							var endDate = new Date(now);
							endDate.setMonth(
								endDate.getMonth() + this.argsB.offset,
								0
							);
							result = {
								prev: {
									from:
										startDate.getFullYear() +
										'/' +
										(startDate.getMonth() + 1) +
										'/' +
										startDate.getDate(),
									to:
										endDate.getFullYear() +
										'/' +
										(endDate.getMonth() + 1) +
										'/' +
										endDate.getDate(),
									label: statsLocale.prevMonth,
									disabled:
										this.argsB.offset < this.lastValidOffset
											? true
											: false,
								},
							};
							if (-2 < this.argsB.offset) {
								result.next = {
									from: false,
									to: false,
									label: statsLocale.nextMonth,
									disabled: true,
								};
							} else {
								startDate.setMonth(startDate.getMonth() + 2);
								endDate.setMonth(endDate.getMonth() + 3, 0);
								result.next = {
									from:
										startDate.getFullYear() +
										'/' +
										(startDate.getMonth() + 1) +
										'/' +
										startDate.getDate(),
									to:
										endDate.getFullYear() +
										'/' +
										(endDate.getMonth() + 1) +
										'/' +
										endDate.getDate(),
									label: statsLocale.nextMonth,
									disabled: false,
								};
							}
						} else {
							var startDate = new Date(now);
							var endDate = new Date(now);
							startDate.setDate(1);
							endDate.setDate(0);
							result = {
								prev: {
									from:
										startDate.getFullYear() +
										'/' +
										startDate.getMonth() +
										'/' +
										startDate.getDate(),
									to:
										endDate.getFullYear() +
										'/' +
										(endDate.getMonth() + 1) +
										'/' +
										endDate.getDate(),
									label: statsLocale.prevMonth,
									disabled: false,
								},
								next: {
									from: false,
									to: false,
									label: statsLocale.nextMonth,
									disabled: true,
								},
							};
						}
						break; // end thismonth
					case 'lastmonth':
						if (this.argsB) {
							var endDate = new Date(now);
							endDate.setMonth(
								endDate.getMonth() - 1 + this.argsB.offset,
								0
							);
							var startDate = new Date(now);
							startDate.setMonth(
								startDate.getMonth() - 2 + this.argsB.offset,
								1
							);

							result = {
								prev: {
									from:
										startDate.getFullYear() +
										'/' +
										(startDate.getMonth() + 1) +
										'/' +
										startDate.getDate(),
									to:
										endDate.getFullYear() +
										'/' +
										(endDate.getMonth() + 1) +
										'/' +
										endDate.getDate(),
									label: statsLocale.prevMonth,
									disabled:
										this.argsB.offset < this.lastValidOffset
											? true
											: false,
								},
							};
							if (-2 < this.argsB.offset) {
								result.next = {
									from: false,
									to: false,
									label: statsLocale.nextMonth,
									disabled: true,
								};
							} else {
								startDate.setMonth(startDate.getMonth() + 2);
								endDate.setMonth(endDate.getMonth() + 3, 0);
								result.next = {
									from:
										startDate.getFullYear() +
										'/' +
										(startDate.getMonth() + 1) +
										'/' +
										startDate.getDate(),
									to:
										endDate.getFullYear() +
										'/' +
										(endDate.getMonth() + 1) +
										'/' +
										endDate.getDate(),
									label: statsLocale.nextMonth,
									disabled: false,
								};
							}
						} else {
							var endDate = new Date(now);
							endDate.setMonth(endDate.getMonth() - 1, 0);
							var startDate = new Date(now);
							startDate.setMonth(startDate.getMonth() - 2, 1);
							result = {
								prev: {
									from:
										startDate.getFullYear() +
										'/' +
										(startDate.getMonth() + 1) +
										'/' +
										startDate.getDate(),
									to:
										endDate.getFullYear() +
										'/' +
										(endDate.getMonth() + 1) +
										'/' +
										endDate.getDate(),
									label: statsLocale.prevMonth,
									disabled: false,
								},
								next: {
									from: false,
									to: false,
									label: statsLocale.nextMonth,
									disabled: true,
								},
							};
						}
						break; // end lastmonth
					case 'thisyear':
						if (this.argsB) {
							var endDate = new Date(now);
							endDate.setFullYear(
								endDate.getFullYear() - 1 + this.argsB.offset,
								11,
								31
							);
							var startDate = new Date(now);
							startDate.setFullYear(
								startDate.getFullYear() - 1 + this.argsB.offset,
								0,
								1
							);
							result = {
								prev: {
									from:
										startDate.getFullYear() +
										'/' +
										(startDate.getMonth() + 1) +
										'/' +
										startDate.getDate(),
									to:
										endDate.getFullYear() +
										'/' +
										(endDate.getMonth() + 1) +
										'/' +
										endDate.getDate(),
									label: statsLocale.prevYear,
									disabled:
										this.argsB.offset < this.lastValidOffset
											? true
											: false,
								},
							};
							if (-2 < this.argsB.offset) {
								result.next = {
									from: false,
									to: false,
									label: statsLocale.nextYear,
									disabled: true,
								};
							} else {
								endDate.setFullYear(endDate.getFullYear() + 2);
								startDate.setFullYear(
									startDate.getFullYear() + 2
								);
								result.next = {
									from:
										startDate.getFullYear() +
										'/' +
										(startDate.getMonth() + 1) +
										'/' +
										startDate.getDate(),
									to:
										endDate.getFullYear() +
										'/' +
										(endDate.getMonth() + 1) +
										'/' +
										endDate.getDate(),
									label: statsLocale.nextYear,
									disabled: false,
								};
							}
						} else {
							var endDate = new Date(now);
							endDate.setFullYear(
								endDate.getFullYear() - 1,
								11,
								31
							);
							var startDate = new Date(now);
							startDate.setFullYear(
								startDate.getFullYear() - 1,
								0,
								1
							);
							result = {
								prev: {
									from:
										startDate.getFullYear() +
										'/' +
										(startDate.getMonth() + 1) +
										'/' +
										startDate.getDate(),
									to:
										endDate.getFullYear() +
										'/' +
										(endDate.getMonth() + 1) +
										'/' +
										endDate.getDate(),
									label: statsLocale.prevYear,
									disabled: false,
								},
								next: {
									from: false,
									to: false,
									label: statsLocale.nextYear,
									disabled: true,
								},
							};
						}
						break; // end thisyear
					case 'lastyear':
						if (this.argsB) {
							var endDate = new Date(now);
							endDate.setFullYear(
								endDate.getFullYear() - 2 + this.argsB.offset,
								11,
								31
							);
							var startDate = new Date(now);
							startDate.setFullYear(
								startDate.getFullYear() - 2 + this.argsB.offset,
								0,
								1
							);
							result = {
								prev: {
									from:
										startDate.getFullYear() +
										'/' +
										(startDate.getMonth() + 1) +
										'/' +
										startDate.getDate(),
									to:
										endDate.getFullYear() +
										'/' +
										(endDate.getMonth() + 1) +
										'/' +
										endDate.getDate(),
									label: statsLocale.prevYear,
									disabled:
										this.argsB.offset < this.lastValidOffset
											? true
											: false,
								},
							};
							if (-2 < this.argsB.offset) {
								result.next = {
									from: false,
									to: false,
									label: statsLocale.nextYear,
									disabled: true,
								};
							} else {
								endDate.setFullYear(endDate.getFullYear() + 2);
								startDate.setFullYear(
									startDate.getFullYear() + 2
								);
								result.next = {
									from:
										startDate.getFullYear() +
										'/' +
										(startDate.getMonth() + 1) +
										'/' +
										startDate.getDate(),
									to:
										endDate.getFullYear() +
										'/' +
										(endDate.getMonth() + 1) +
										'/' +
										endDate.getDate(),
									label: statsLocale.nextYear,
									disabled: false,
								};
							}
						} else {
							var endDate = new Date(now);
							endDate.setFullYear(
								endDate.getFullYear() - 2,
								11,
								31
							);
							var startDate = new Date(now);
							startDate.setFullYear(
								startDate.getFullYear() - 2,
								0,
								1
							);
							result = {
								prev: {
									from:
										startDate.getFullYear() +
										'/' +
										(startDate.getMonth() + 1) +
										'/' +
										startDate.getDate(),
									to:
										endDate.getFullYear() +
										'/' +
										(endDate.getMonth() + 1) +
										'/' +
										endDate.getDate(),
									label: statsLocale.prevYear,
									disabled: false,
								},
								next: {
									from: false,
									to: false,
									label: statsLocale.nextYear,
									disabled: true,
								},
							};
						}
						break; // end lastyear
					default: // custom
						var len = this._getPeriodLength();
						if (this.argsB) {
							const prevStartDate = new Date(this.lastArgs.from);
							const prevEndDate = new Date(this.lastArgs.to);
							prevEndDate.setTime(
								prevEndDate.getTime() - len.days * 24 * 3600000
							);
							prevStartDate.setTime(
								prevStartDate.getTime() -
									len.days * 24 * 3600000
							);
							const nextStartDate = new Date(this.lastArgs.from);
							const nextEndDate = new Date(this.lastArgs.to);
							nextEndDate.setTime(
								nextEndDate.getTime() + len.days * 24 * 3600000
							);
							nextStartDate.setTime(
								nextStartDate.getTime() +
									len.days * 24 * 3600000
							);
							result = {
								prev: {
									from:
										prevStartDate.getFullYear() +
										'/' +
										(prevStartDate.getMonth() + 1) +
										'/' +
										prevStartDate.getDate(),
									to:
										prevEndDate.getFullYear() +
										'/' +
										(prevEndDate.getMonth() + 1) +
										'/' +
										prevEndDate.getDate(),
									label: statsLocale['prev%dDays'].replace(
										'%d',
										len.days
									),
									disabled: false,
								},
								next: {
									from:
										nextStartDate.getFullYear() +
										'/' +
										(nextStartDate.getMonth() + 1) +
										'/' +
										nextStartDate.getDate(),
									to:
										nextEndDate.getFullYear() +
										'/' +
										(nextEndDate.getMonth() + 1) +
										'/' +
										nextEndDate.getDate(),
									label: statsLocale['next%dDays'].replace(
										'%d',
										len.days
									),
									disabled: false,
								},
							};
							if (!this.statsB) {
								if ('prev' == this.lastBtn) {
									result.prev.disabled = true;
								} else {
									result.next.disabled = true;
								}
							}
						} else {
							var startDate = new Date(this.argsA.from);
							startDate.setTime(
								startDate.getTime() - len.days * 24 * 3600000
							);
							var endDate = new Date(this.argsA.to);
							endDate.setTime(
								endDate.getTime() - len.days * 24 * 3600000
							);
							result = {
								prev: {
									from:
										startDate.getFullYear() +
										'/' +
										(startDate.getMonth() + 1) +
										'/' +
										startDate.getDate(),
									to:
										endDate.getFullYear() +
										'/' +
										(endDate.getMonth() + 1) +
										'/' +
										endDate.getDate(),
									label: statsLocale['prev%dDays'].replace(
										'%d',
										len.days
									),
									disabled: false,
								},
							};

							startDate = new Date(this.argsA.from);
							startDate.setTime(
								startDate.getTime() + len.days * 24 * 3600000
							);
							endDate = new Date(this.argsA.to);
							endDate.setTime(
								endDate.getTime() + len.days * 24 * 3600000
							);

							result.next = {
								from:
									startDate.getFullYear() +
									'/' +
									(startDate.getMonth() + 1) +
									'/' +
									startDate.getDate(),
								to:
									endDate.getFullYear() +
									'/' +
									(endDate.getMonth() + 1) +
									'/' +
									endDate.getDate(),
								label: statsLocale['next%dDays'].replace(
									'%d',
									len.days
								),
								disabled: false,
							};
						}
				}
			} else {
				// allowing access to result and arguments for stats from file
				var hookData = {
					result,
					argsA: this.argsA,
					argsB: this.argsB,
					lastArgs: this.lastArgs,
				};
				$(document).trigger('advadsTrackComparablePeriods', hookData);
			}
			if (undefined !== hookData) {
				result = hookData.result;
				result.prev.from = result.prev.from
					? zeroiseDate(result.prev.from)
					: result.prev.from;
				result.prev.to = result.prev.to
					? zeroiseDate(result.prev.to)
					: result.prev.to;
				result.next.from = result.next.from
					? zeroiseDate(result.next.from)
					: result.next.from;
				result.next.to = result.next.to
					? zeroiseDate(result.next.to)
					: result.next.to;
			}

			if (!this.argsB || undefined === this.argsB.offset) {
				this.argsB = { offset: 0 };
			}
			$('#compare-offset').val(this.argsB.offset);
			$('#compare-from-prev').val(result.prev.from);
			$('#compare-to-prev').val(result.prev.to);
			$('#compare-from-next').val(result.next.from);
			$('#compare-to-next').val(result.next.to);
			$('#compare-prev-btn')
				.text(result.prev.label)
				.prop('disabled', result.prev.disabled);
			$('#compare-next-btn')
				.text(result.next.label)
				.prop('disabled', result.next.disabled);
		},

		/**
		 *  Get the length, in days of the longest period
		 */
		_getPeriodLength() {
			let max = 0;
			let isNegative = false;
			$('#period-table fieldset').each(function () {
				const period = $(this).find('.advads-stats-period').val();
				if ('custom' == period) {
					const fromStr = $(this).find('.advads-stats-from').val();
					const toStr = $(this).find('.advads-stats-to').val();
					if (!fromStr || !toStr) {
						return {
							valid: false,
							isNegative: false,
						};
					}
					const fromDate = new Date(fromStr);
					const toDate = new Date(toStr);
					let diff = parseInt(
						(toDate.getTime() - fromDate.getTime()) /
							(1000 * 3600 * 24),
						10
					);
					// include end day
					diff += 1;
					if (diff < 0) {
						isNegative = {
							valid: false,
							days: diff,
							week: Math.ceil(diff / 7),
							isNegative: true,
						};
					} else if (max < diff) {
						max = diff;
					}
				}
			});
			if (isNegative) {
				return isNegative;
			}
			return { valid: true, days: max, week: Math.ceil(max / 7) };
		},

		/**
		 *  Initialization
		 */
		init() {
			this.nonce = window.advadsStatPageNonce;
			const that = this;
			if (!$('#ad-filter').prop('disabled')) {
				$('#ad-filter').autocomplete({
					source: autoCompSrc,
					delay: 200,
					select(ev, ui) {
						ev.preventDefault();
						that.afterFilterChange(ui.item.label, ui.item.value);
					},
					focus(ev, ui) {
						ev.preventDefault();
					},
				});
			}
			if ($('#group-filter').length) {
				$('#group-filter').autocomplete({
					source: groupAutoCompSrc,
					delay: 200,
					select(ev, ui) {
						ev.preventDefault();
						that.afterGroupFilterChange(
							ui.item.label,
							ui.item.value
						);
					},
					focus(ev, ui) {
						ev.preventDefault();
					},
				});
			}
		},

		/**
		 *  Remove all ad filters if there is more than one before loading statsB
		 */
		maybeRemoveFilters() {
			const adFilters = this.getFilters();
			const groupFilters = this.getGroupFilters();
			if (1 < adFilters.length) {
				$('#display-filter-list .display-filter-elem').remove();
			}
			if (1 < groupFilters.length) {
				$('#display-filter-list .display-filter-group').remove();
			}
		},

		/**
		 *  After a group is selected in autocomplete
		 * @param label
		 * @param value
		 * @param updateDisplay
		 */
		afterGroupFilterChange(label, value, updateDisplay) {
			if (undefined === updateDisplay) {
				updateDisplay = true;
			}
			const groupFilters = this.getGroupFilters();
			const adFilters = this.getFilters();
			const filterList = $('#display-filter-list');

			// no stats loaded yet, abort
			if (!this.statsA) {
				return;
			}

			// do not re-add an already existing group filter
			if (-1 != groupFilters.indexOf(value)) {
				return;
			}

			if (adFilters.length) {
				// remove first any existing ad filters
				$('#display-filter-list .display-filter-elem').remove();
				$('#ad-filter').prop('disabled', false).autocomplete('enable');
			}

			const adsNames = [];
			for (const i in groupsToAds[value].ads) {
				adsNames.push(groupsToAds[value].ads[i].title);
			}

			const newElem = $(
				'<div class="display-filter-group" data-id="' +
					value +
					'">' +
					'<strong>' +
					label +
					'</strong>: ' +
					adsNames.join(' | ') +
					'<i class="dashicons dashicons-no"></i></div>'
			);

			if ('load' == this.lastBtn) {
				// simple stats ( from DB only - CSV files don't contains group information )
				if (3 > groupFilters.length) {
					filterList.css('visibility', 'visible').append(newElem);
					if (2 == groupFilters.length) {
						// currently added the 3rd group
						$('#group-filter')
							.prop('disabled', true)
							.autocomplete('disable');
					}
					if (updateDisplay) {
						this.updateDisplay();
					}
				}
			} else {
				// comparison - one group filter allowed
				if (0 == groupFilters.length) {
					filterList.css('visibility', 'visible').append(newElem);
					$('#group-filter')
						.prop('disabled', true)
						.autocomplete('disable');
					if (updateDisplay) {
						this.updateDisplay();
					}
				}
			}
			$('#group-filter').val('');
		},

		/**
		 *  After ad selected in autocomplete
		 * @param label
		 * @param value
		 * @param updateDisplay
		 */
		afterFilterChange(label, value, updateDisplay) {
			if (undefined === updateDisplay) {
				updateDisplay = true;
			}
			const filters = this.getFilters();
			const groupFilters = this.getGroupFilters();
			if (groupFilters.length) {
				// remove first all existing group filter
				$('#display-filter-list .display-filter-group').remove();
				$('#group-filter')
					.prop('disabled', false)
					.autocomplete('enable');
			}
			if (
				this.statsA &&
				!(-1 != filters.indexOf(value) && 0 != filters.length)
			) {
				// there is data ( '' != this.lastBtn ) && not re-adding an already applied filter

				const filterList = $('#display-filter-list');
				const newElem = $(
					'<div class="display-filter-elem" data-id="' +
						value +
						'">' +
						label +
						'<i class="dashicons dashicons-no"></i></div>'
				);

				if ('load' == this.lastBtn || 'file' == this.lastBtn) {
					// not in comparison, up to 3 ads allowed
					if (3 > filters.length) {
						filterList.css('visibility', 'visible').append(newElem);
						if (2 == filters.length) {
							// currently added the 3rd ad
							$('#ad-filter')
								.prop('disabled', true)
								.autocomplete('disable');
						}
						if (updateDisplay) {
							this.updateDisplay();
						}
					}
				} else {
					// comparing one ad only
					if (0 == filters.length) {
						filterList.css('visibility', 'visible').append(newElem);
						$('#ad-filter')
							.prop('disabled', true)
							.autocomplete('disable');
						if (updateDisplay) {
							this.updateDisplay();
						}
					}
				}
			}
			$('#ad-filter').val('');
		},

		/**
		 *  Events listeners
		 */
		evt() {
			const that = this;

			// avoid normal form submission for the period selection
			$(document).on('submit', '#stats-form', function (ev) {
				ev.preventDefault();
			});

			// click on the load simple stat button
			$(document).on('click', '#load-simple', function () {
				$('#group-filter-wrap').css('display', 'block');
				const period = $('select[name="advads-stats[period]"]').val();
				const _from = $('input[name="advads-stats[from]"]').val();
				const to = $('input[name="advads-stats[to]"]').val();
				that.lastBtn = 'load';
				const args = {
					period,
				};
				if ('custom' == period) {
					args.from = _from;
					args.to = to;
					const dayDiff = that._getPeriodLength();
					if (dayDiff && dayDiff.valid) {
						if (
							MAX_POINTS < dayDiff.days &&
							'day' ==
								$('select[name="advads-stats[groupby]"]').val()
						) {
							$('select[name="advads-stats[groupby]"]').val(
								'week'
							);
						}
						args.groupby = $(
							'select[name="advads-stats[groupby]"]'
						).val();
						that.getSinglePeriod(args, 'loadStatsA');
						return;
					}
					$('#period-td')
						.empty()
						.append(
							$(
								'<span style="color:red;">' +
									statsLocale.periodNotConsistent +
									'</span>'
							)
						);
					return;
				}
				if ('thisyear' == period || 'lastyear' == period) {
					/**
					 * Force to group by year for yearly stats - limit points in graph,
					 * and avoid data format problem from DB when comparing partialluy filled years
					 */
					$('select[name="advads-stats[groupby]"]').val('month');
				}
				args.groupby = $('select[name="advads-stats[groupby]"]').val();
				that.getSinglePeriod(args, 'loadStatsA');
			});

			// compare with previous period
			$(document).on('click', '#compare-prev-btn', function () {
				that.lastBtn = 'prev';
				that.maybeRemoveFilters();
				const args = {
					from: $('#compare-from-prev').val(),
					to: $('#compare-to-prev').val(),
					period: 'custom',
					offset: parseInt($('#compare-offset').val()) - 1,
					groupby: that.argsA.groupby,
				};
				if ($('#stats-attachment-id').val()) {
					args.file = $('#stats-attachment-id').val();
				}
				that.getSinglePeriod(args, 'loadStatsB');
			});

			// compare with next period
			$(document).on('click', '#compare-next-btn', function () {
				that.lastBtn = 'next';
				that.maybeRemoveFilters();
				const args = {
					from: $('#compare-from-next').val(),
					to: $('#compare-to-next').val(),
					period: 'custom',
					offset: parseInt($('#compare-offset').val()) + 1,
					groupby: that.argsA.groupby,
				};
				if ($('#stats-attachment-id').val()) {
					args.file = $('#stats-attachment-id').val();
				}
				that.getSinglePeriod(args, 'loadStatsB');
			});

			// remove single ad filter
			$(document).on(
				'click',
				'#display-filter-list .display-filter-elem .dashicons-no',
				function () {
					$(this).parent().remove();
					if (
						!$(
							'#display-filter-list .display-filter-elem,#display-filter-list .display-filter-group'
						).length
					) {
						$('#display-filter-list').css('visibility', 'hidden');
					}
					if (true == $('#ad-filter').prop('disabled')) {
						$('#ad-filter')
							.prop('disabled', false)
							.autocomplete('enable');
					}
					that.updateDisplay();
				}
			);

			// remove single group filter
			$(document).on(
				'click',
				'#display-filter-list .display-filter-group .dashicons-no',
				function () {
					$(this).parent().remove();
					if (
						!$(
							'#display-filter-list .display-filter-elem, #display-filter-list .display-filter-group'
						).length
					) {
						$('#display-filter-list').css('visibility', 'hidden');
					}
					if (true == $('#group-filter').prop('disabled')) {
						$('#group-filter')
							.prop('disabled', false)
							.autocomplete('enable');
					}
					that.updateDisplay();
				}
			);
		},

		/**
		 *  StatsB updated
		 * @param data
		 * @param args
		 */
		loadStatsB(data, args) {
			$('.ajax-spinner').remove();
			this._reverseDisabled('wpbody-content');
			this.lastArgs = args;
			if (!this.argsB || undefined === this.argsB.offset) {
				this.argsB = { offset: 0 };
			}
			if (undefined !== data.stats && undefined !== data.stats.impr) {
				// if has stats
				this.statsB = data.stats;
				this.argsB.offset = args.offset;
				this.lastValidOffset = args.offset;
				this._updateComparablePeriods();
				this.updateDisplay();
			} else {
				this.statsB = false;
				this.argsB.offset = args.offset;
				this.offsetLimit = this.lastValidOffset;
				this._updateComparablePeriods();
				this.updateDisplay();
			}
		},

		/**
		 *  StatsA updated
		 * @param data
		 * @param args
		 */
		loadStatsA(data, args) {
			if (!this.localizedDate) {
				this.localizeDateNames();
			}
			$('.ajax-spinner').remove();
			this._reverseDisabled('wpbody-content');
			this.statsB = false;
			this.argsB = false;
			if (undefined !== data.stats && undefined !== data.stats.impr) {
				// if has stats
				if (undefined !== data.stats.ads) {
					// replace the ad title from the one found in file
					window.adTitles = data.stats.ads;
					rebuildAutoComplete(data.stats.ads);
				}
				this.statsA = data.stats;
				this.argsA = args;
				this._enableCompare(true);
				this._updateComparablePeriods();
				this.updateDisplay();
			} else {
				this.statsA = false;
				this._enableCompare(false);
				this.updateDisplay();
			}
		},

		/**
		 *  Update display
		 */
		updateDisplay() {
			const that = this;
			$('#advads-graph-legend').css('display', 'block');
			const adFilters = this.getFilters();
			const groupFilters = this.getGroupFilters();
			if (false == this.statsB) {
				// simple period
				if (this.statsA) {
					// per date table data
					var dateTableDataA = {};

					// per Ad table data
					var adTableDataA = {};

					// all ads series - jqplot
					const imprSeries = [];
					const clickSeries = [];

					// per ads series - jqplot
					const imprSeriesPerAd = {};
					const clickSeriesPerAd = {};

					// per group series - jqplot
					const imprSeriesPerGroup = {};
					const clickSeriesPerGroup = {};

					// deleted ad series - jqplot
					var delAdsImprs = [];
					var delAdsclicks = [];

					// TOTAL - datatable
					var imprTotal = 0;
					var clicksTotal = 0;

					// TOTAL after ad filter - datatable
					var imprTotalFiltered = 0;
					var clicksTotalFiltered = 0;

					const hasClicks =
						undefined !== this.statsA.click && this.statsA.click
							? true
							: false;

					// iterate date
					for (var _date in this.statsA.impr) {
						var date = _date;
						if (
							'week' == this.argsA.groupby &&
							-1 != date.indexOf('W')
						) {
							var _W = parseInt(_date.split('W')[1]);
							var _Y = parseInt(_date.split('-')[0]);
							var _WS = getDateOfISOWeek(_W, _Y);
							var _Month = (_WS.getMonth() + 1).toString();
							if (1 == _Month.length) {
								_Month = '0' + _Month;
							}
							var _Day = _WS.getDate().toString();
							if (1 == _Day.length) {
								_Day = '0' + _Day;
							}
							date =
								_WS.getFullYear() + '-' + _Month + '-' + _Day;
						}

						var perDateImpr = 0;
						var perDateClicks = 0;

						// buffers for group stats
						let imprsBuffer = {};
						let clicksBuffer = {};

						// iterate ad ID
						for (var _ID in this.statsA.impr[_date]) {
							var _impr = this.statsA.impr[_date][_ID]
								? parseInt(this.statsA.impr[_date][_ID])
								: 0;
							var _click = 0;

							if (
								hasClicks &&
								undefined !== this.statsA.click[_date] &&
								this.statsA.click[_date][_ID]
							) {
								_click = parseInt(
									this.statsA.click[_date][_ID]
								);
							}

							// deleted ads
							if (-1 == this.allAds.indexOf(_ID)) {
								if (undefined === adTableDataA.deleted) {
									adTableDataA.deleted = {
										impr: _impr,
										click: _click,
									};
								} else {
									adTableDataA.deleted.impr += _impr;
									adTableDataA.deleted.click += _click;
								}
							} else {
								// no ad filter, or current ads passes filters
								if (
									!adFilters.length ||
									-1 != adFilters.indexOf(parseInt(_ID))
								) {
									// found some group filters
									if (groupFilters.length) {
										if (
											undefined !==
											adsToGroups[parseInt(_ID, 10)]
										) {
											// apply group filter - current ad is a part of a group
											for (var f in groupFilters) {
												if (
													isInGroup(
														_ID,
														groupFilters[f]
													)
												) {
													if (
														undefined ===
														adTableDataA[
															groupFilters[f]
														]
													) {
														adTableDataA[
															groupFilters[f]
														] = {
															impr: _impr,
															click: _click,
														};
													} else {
														adTableDataA[
															groupFilters[f]
														].impr += _impr;
														adTableDataA[
															groupFilters[f]
														].click += _click;
													}
												}
											}
										}
									} else {
										// no group filter && ( no ad filter, or current ads passes filters )
										if (undefined === adTableDataA[_ID]) {
											adTableDataA[_ID] = {
												impr: _impr,
												click: _click,
											};
										} else {
											adTableDataA[_ID].impr += _impr;
											adTableDataA[_ID].click += _click;
										}
									}
								}
							}

							// ad filter found
							if (adFilters.length) {
								if (-1 != adFilters.indexOf(parseInt(_ID))) {
									if (undefined === imprSeriesPerAd[_ID]) {
										imprSeriesPerAd[_ID] = [];
									}
									imprSeriesPerAd[_ID].push([date, _impr]);
									if (undefined === clickSeriesPerAd[_ID]) {
										clickSeriesPerAd[_ID] = [];
									}
									clickSeriesPerAd[_ID].push([date, _click]);
									perDateImpr += _impr;
									perDateClicks += _click;
								}
							} else {
								// no ad filters but group filter found
								if (groupFilters.length) {
									// group series by group ID-s and store the sum for each ad of the same group in a buffer to be used in "perDateStats"
									for (var f in groupFilters) {
										if (isInGroup(_ID, groupFilters[f])) {
											if (
												undefined ===
												imprsBuffer[groupFilters[f]]
											) {
												imprsBuffer[groupFilters[f]] =
													0;
											}
											imprsBuffer[groupFilters[f]] +=
												_impr;
											if (
												undefined ===
												clicksBuffer[groupFilters[f]]
											) {
												clicksBuffer[groupFilters[f]] =
													0;
											}
											clicksBuffer[groupFilters[f]] +=
												_click;
											perDateImpr += _impr;
											perDateClicks += _click;
										}
									}
								} else {
									// no ad filter	 && no group filter
									perDateImpr += _impr;
									perDateClicks += _click;
								}
							}
						} // iterate ID

						dateTableDataA[date] = {
							impr: perDateImpr,
							click: perDateClicks,
						};

						if (groupFilters.length) {
							// take the sum in the stats buffer for the currently iterated date
							for (var f in groupFilters) {
								if (
									undefined ===
									imprSeriesPerAd[groupFilters[f]]
								) {
									imprSeriesPerAd[groupFilters[f]] = [];
								}
								if (
									undefined ===
									clickSeriesPerAd[groupFilters[f]]
								) {
									clickSeriesPerAd[groupFilters[f]] = [];
								}
								imprSeriesPerAd[groupFilters[f]].push([
									date,
									imprsBuffer[groupFilters[f]],
								]);
								clickSeriesPerAd[groupFilters[f]].push([
									date,
									clicksBuffer[groupFilters[f]],
								]);
							}
							imprsBuffer = {};
							clicksBuffer = {};
						}

						imprSeries.push([date, perDateImpr]);
						clickSeries.push([date, perDateClicks]);
					} // iterate date

					/**
					 *  Plot data
					 */

					var graphOptions = JSON.parse(
						JSON.stringify(defaultGraphOptions, null, 0)
					);
					graphOptions.axes.xaxis.renderer =
						$.jqplot.DateAxisRenderer;
					if ('month' != this.argsA.groupby) {
						graphOptions.axes.xaxis.min = this.statsA.firstDate;
					}
					graphOptions.axes.xaxis.tickInterval =
						'1 ' + this.argsA.groupby;
					graphOptions.axes.xaxis.tickOptions.formatString =
						this.statsA.xAxisThickformat;
					graphOptions.axes.yaxis.label = statsLocale.impressions;
					graphOptions.axes.yaxis.labelRenderer =
						$.jqplot.CanvasAxisLabelRenderer;
					graphOptions.axes.y2axis.label = statsLocale.clicks;
					graphOptions.axes.y2axis.labelRenderer =
						$.jqplot.CanvasAxisLabelRenderer;

					var lines = [];

					/**
					 *  Series options
					 */
					if (adFilters.length || groupFilters.length) {
						graphOptions.series = [];
						var appliedFilters = adFilters.length
							? adFilters
							: groupFilters;
						for (var _i in appliedFilters) {
							lines.push(imprSeriesPerAd[appliedFilters[_i]]);
							lines.push(clickSeriesPerAd[appliedFilters[_i]]);
							graphOptions.series.push(
								{
									color: COLORS[2 * parseInt(_i)],
									highlighter: {
										formatString:
											'%s: %.0f ' +
											statsLocale.impressions,
									},
									lineWidth: 1,
									markerOptions: {
										size: 5,
										style: 'circle',
									},
								},
								{
									color: COLORS[2 * parseInt(_i) + 1],
									highlighter: {
										formatString:
											'%s: %.0f ' + statsLocale.clicks,
									},
									linePattern: 'dashed',
									lineWidth: 2,
									markerOptions: {
										size: 5,
										style: 'filledSquare',
									},
									yaxis: 'y2axis',
								}
							);
						}
					} else {
						lines = [imprSeries, clickSeries];
						graphOptions.series = [
							{
								color: COLORS[0],
								highlighter: {
									formatString:
										'%s: %.0f ' + statsLocale.impressions,
								},
								lineWidth: 3,
								markerOptions: {
									size: 5,
									style: 'circle',
								},
							},
							{
								color: COLORS[1],
								highlighter: {
									formatString:
										'%s: %.0f ' + statsLocale.clicks,
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
					}
					if (this.graph) {
						this.graph.destroy();
					}

					$('#advads-stats-graph').empty();
					const ticks = [];
					for (const i in lines[0]) {
						const x = lines[0][i];
						ticks.push(x[0]);
					}
					graphOptions.axes.xaxis.ticks = ticks;
					this.graph = $.jqplot(
						'advads-stats-graph',
						lines,
						graphOptions
					);

					if (
						20 < lines[0].length &&
						900 > $('#advads-stats-graph').width()
					) {
						$('.jqplot-xaxis-tick').css(
							'transform',
							'rotate(-45deg)'
						);
						$('.jqplot-xaxis').css('bottom', -10);
						$('#advads-graph-legend').css('margin-top', 25);
					} else {
						$('.jqplot-xaxis-tick').css(
							'transform',
							'rotate(0deg)'
						);
						$('.jqplot-xaxis').css('bottom', 0),
							$('#advads-graph-legend').css('margin-top', 13);
					}

					if (adFilters.length || groupFilters.length) {
						$('#advads-graph-legend .legend-item')
							.not('.donotremove')
							.remove();
						var appliedFilters = adFilters.length
							? adFilters
							: groupFilters;
						for (var _i in appliedFilters) {
							const theTitle = adFilters.length
								? adTitles[appliedFilters[_i]]
								: groupsToAds[appliedFilters[_i]].name;
							$('#advads-graph-legend')
								.append(
									$(
										'<div class="legend-item"><div style="background-color:' +
											COLORS[2 * parseInt(_i)] +
											';" class="ad-color-legend"></div><span>' +
											statsLocale.impressionsFor.replace(
												'%s',
												theTitle
											) +
											'</span></div>'
									)
								)
								.append(
									$(
										'<div class="legend-item"><div style="background-color:' +
											COLORS[2 * parseInt(_i) + 1] +
											';" class="ad-color-legend"></div><span>' +
											statsLocale.clicksFor.replace(
												'%s',
												theTitle
											) +
											'</span></div>'
									)
								);
						}
					}

					if (this.lastBtn !== 'load' && this.lastBtn !== 'file') {
						// doing comparison but nothing in stats B
						$('#advads-graph-legend .legend-item')
							.not('.donotremove')
							.remove();
						$('#advads-graph-legend')
							.append(
								$(
									'<div class="legend-item"><div style="background-color:' +
										COLORS[0] +
										';" class="ad-color-legend"></div><span>' +
										statsLocale.imprFromTo
											.replace(
												'%1$s',
												formatDate(
													new Date(
														that.statsA.periodStart
													),
													statsDates.wpDateFormat
												)
											)
											.replace(
												'%2$s',
												formatDate(
													new Date(
														that.statsA.periodEnd
													),
													statsDates.wpDateFormat
												)
											) +
										'</span></div>'
								)
							)
							.append(
								$(
									'<div class="legend-item"><div style="background-color:' +
										COLORS[1] +
										';" class="ad-color-legend"></div><span>' +
										statsLocale.clicksFromTo
											.replace(
												'%1$s',
												formatDate(
													new Date(
														that.statsA.periodStart
													),
													statsDates.wpDateFormat
												)
											)
											.replace(
												'%2$s',
												formatDate(
													new Date(
														that.statsA.periodEnd
													),
													statsDates.wpDateFormat
												)
											) +
										'</span></div>'
								)
							)
							.append(
								$(
									'<div class="legend-item"><p>' +
										statsLocale.noDataFor
											.replace(
												'%1$s',
												formatDate(
													new Date(
														that.lastArgs.from
													),
													statsDates.wpDateFormat
												)
											)
											.replace(
												'%2$s',
												formatDate(
													new Date(that.lastArgs.to),
													statsDates.wpDateFormat
												)
											) +
										'</p></div>'
								)
							);
					}

					/**
					 *  Datatable
					 */
					clearTables();
					perAdtable('adTable', adTableDataA);
					perDatetable('dateTable', dateTableDataA);
				} else {
					// no record for the given period
					this._noRecords();
				}
			} else {
				// comparison
				const statsA = JSON.parse(JSON.stringify(this.statsA, null, 0));
				// per date table data
				var dateTableDataA = {};
				const dateTableDataB = {};

				// per Ad table data
				var adTableDataA = {};
				const adTableDataB = {};

				// all ads series - jqplot
				const imprSeriesA = [];
				const clickSeriesA = [];
				const imprSeriesB = [];
				const clickSeriesB = [];

				// deleted ad series - jqplot
				var delAdsImprs = [];
				var delAdsclicks = [];

				// TOTAL - datatable
				var imprTotal = 0;
				var clicksTotal = 0;

				// TOTAL after ad filter - datatable
				var imprTotalFiltered = 0;
				var clicksTotalFiltered = 0;

				let compensatedFirstDateA = false;
				let compensatedFirstDateB = false;

				const hasClicksA =
					undefined !== statsA.click && statsA.click ? true : false;

				/**
				 *  Compensate periods length for comparison
				 */
				if (
					Object.keys(statsA.impr).length !=
					Object.keys(this.statsB.impr).length
				) {
					const alen = Object.keys(statsA.impr).length;
					const blen = Object.keys(this.statsB.impr).length;
					const filler = {};
					for (const __i in statsA.impr[
						Object.keys(statsA.impr)[0]
					]) {
						filler[__i] = null;
					}
					if ('day' == this.argsA.groupby) {
						if (alen < blen) {
							// A shorter than B
							var beforeA = false;
							var afterA = false;
							const clickA = {};
							let newFirstDateATS = false;
							if (
								statsA.periodStart ==
									Object.keys(statsA.impr)[0] ||
								Object.keys(statsA.impr)[0] == statsA.firstDate
								/**
								 *  Possible timezone bug when between 00:00 and gmt offset,
								 *
								 *  the PHP code date( 'Y-m-d', strtotime( $firstdate . ' -1 day' ) ) return the same day, not the day before as expected
								 *
								 *  seen on UTC+3 and the bug is gone past 03:00 local time,
								 *  need verification (PHP vs browser timezone or something else)
								 */
							) {
								// compensate with date after last date
								afterA = {};
								const lastDateA = Object.keys(statsA.impr)[
									Object.keys(statsA.impr).length - 1
								];
								const lastDateATS = new Date(
									lastDateA
								).getTime();
								for (var _i = 0; _i <= blen - alen; ++_i) {
									var newDate = new Date(
										_i * 24 * 3600 * 1000 + lastDateATS
									);
									var newDateString = zeroiseDate(
										newDate.getFullYear() +
											'-' +
											(newDate.getMonth() + 1) +
											'-' +
											newDate.getDate()
									);
									if (
										undefined ==
										this.statsB.impr[newDateString]
									) {
										afterA[newDateString] = filler;
									} else {
										afterA[newDateString] =
											this.statsB.impr[newDateString];
										if (
											this.statsB.click &&
											undefined !==
												this.statsB.click[newDateString]
										) {
											clickA[newDateString] =
												this.statsB.click[
													newDateString
												];
										}
									}
								}
							} else {
								beforeA = {};
								var firstDateA = Object.keys(statsA.impr)[0];
								const firstDateATS = new Date(
									firstDateA
								).getTime();
								for (var _i = 0; _i <= blen - alen; ++_i) {
									var newDate = new Date(
										firstDateATS - _i * 24 * 3600 * 1000
									);
									var newDateString = zeroiseDate(
										newDate.getFullYear() +
											'-' +
											(newDate.getMonth() + 1) +
											'-' +
											newDate.getDate()
									);
									if (
										undefined ==
										this.statsB.impr[newDateString]
									) {
										beforeA[newDateString] = filler;
									} else {
										beforeA[newDateString] =
											this.statsB.impr[newDateString];
										if (
											this.statsB.click &&
											undefined !==
												this.statsB.click[newDateString]
										) {
											clickA[newDateString] =
												this.statsB.click[
													newDateString
												];
										}
									}
									newFirstDateATS = newDate.getTime();
								}
								if (newFirstDateATS) {
									newFirstDateATS = new Date(
										newFirstDateATS - 24 * 3600 * 1000
									);
									compensatedFirstDateA = zeroiseDate(
										newFirstDateATS.getFullYear() +
											'-' +
											(newFirstDateATS.getMonth() + 1) +
											'-' +
											newFirstDateATS.getDate()
									);
								}
							}
							var newImpr = {};
							var newClick = false;
							if (afterA) {
								$.extend(newImpr, statsA.impr, afterA);
								if (!$.isEmptyObject(clickA)) {
									if (statsA.click) {
										$.extend(
											newClick,
											statsA.click,
											clickA
										);
									} else {
										newClick = clickA;
									}
								}
							} else {
								$.extend(newImpr, beforeA, statsA.impr);
								if (!$.isEmptyObject(clickA)) {
									if (statsA.click) {
										$.extend(
											newClick,
											clickA,
											statsA.click
										);
									} else {
										newClick = clickA;
									}
								}
							}
							statsA.impr = newImpr;
							if (newClick) {
								statsA.click = newClick;
							}
						} else {
							// B shorter than A
							const BStart = zeroiseDate(
								this.statsB.periodStart,
								true
							);
							var beforeB = false;
							var afterB = false;
							const clickB = {};
							let newFirstDateBTS = false;
							if (BStart == Object.keys(this.statsB.impr)[0]) {
								// compensate after last date
								afterB = {};
								var lastDateB = Object.keys(this.statsB.impr)[
									Object.keys(this.statsB.impr).length - 1
								];
								const lastDateBTS = new Date(
									lastDateB
								).getTime();
								for (var _i = 0; _i <= alen - blen; ++_i) {
									var newDate = new Date(
										_i * 24 * 3600 * 1000 + lastDateBTS
									);
									var newDateString = zeroiseDate(
										newDate.getFullYear() +
											'-' +
											(newDate.getMonth() + 1) +
											'-' +
											newDate.getDate()
									);
									if (
										undefined == statsA.impr[newDateString]
									) {
										afterB[newDateString] = filler;
									} else {
										afterB[newDateString] =
											statsA.impr[newDateString];
										if (
											statsA.click &&
											undefined !==
												statsA.click[newDateString]
										) {
											clickB[newDateString] =
												statsA.click[newDateString];
										}
									}
								}
							} else {
								// compensate before first date
								beforeB = {};
								const firstDateB = Object.keys(
									this.statsB.impr
								)[0];
								const firstDateBTS = new Date(
									firstDateB
								).getTime();
								for (var _i = 0; _i <= alen - blen; ++_i) {
									var newDate = new Date(
										firstDateBTS - _i * 24 * 3600 * 1000
									);
									var newDateString = zeroiseDate(
										newDate.getFullYear() +
											'-' +
											(newDate.getMonth() + 1) +
											'-' +
											newDate.getDate()
									);
									if (
										undefined == statsA.impr[newDateString]
									) {
										beforeB[newDateString] = filler;
									} else {
										beforeB[newDateString] =
											statsA.impr[newDateString];
										if (
											statsA.click &&
											undefined !==
												statsA.click[newDateString]
										) {
											clickB[newDateString] =
												statsA.click[newDateString];
										}
									}
									newFirstDateBTS = newDate.getTime();
								}
								if (newFirstDateBTS) {
									newFirstDateBTS = new Date(
										newFirstDateBTS - 24 * 3600 * 1000
									);
									compensatedFirstDateB = zeroiseDate(
										newFirstDateBTS.getFullYear() +
											'-' +
											(newFirstDateBTS.getMonth() + 1) +
											'-' +
											newFirstDateBTS.getDate()
									);
								}
							}
							var newImpr = {};
							var newClick = false;
							if (afterB) {
								$.extend(newImpr, this.statsB.impr, afterB);
								if (!$.isEmptyObject(clickB)) {
									if (this.statsB.click) {
										$.extend(
											newClick,
											this.statsB.click,
											clickB
										);
									} else {
										newClick = clickB;
									}
								}
							} else {
								$.extend(newImpr, beforeB, this.statsB.impr);
								if (!$.isEmptyObject(clickB)) {
									if (this.statsB.click) {
										$.extend(
											newClick,
											clickB,
											this.statsB.click
										);
									} else {
										newClick = clickB;
									}
								}
							}
							this.statsB.impr = newImpr;
							if (newClick) {
								this.statsB.click = newClick;
							}
						}
					} // group by day

					if ('week' == this.argsA.groupby) {
						if (alen < blen) {
							// A short
							var beforeA = false;
							var afterA = false;
							if (
								ISOweekCompare(
									Object.keys(statsA.impr)[0],
									Object.keys(this.statsB.impr)[blen - 1],
									'>='
								)
							) {
								// statsA is the later period, B comes before A, compensate after the last date in A
								afterA = {};
								var currentWeek = Object.keys(statsA.impr)[
									alen - 1
								];
								for (var _i = 0; _i < blen - alen; ++_i) {
									currentWeek = incrISOWeek(currentWeek);
									afterA[currentWeek] = filler;
								}
							} else {
								// statsA is the early period, compensate with entries before the first date in A
								beforeA = {};
								var currentWeek = Object.keys(statsA.impr)[0];
								for (var _i = 0; _i < blen - alen; ++_i) {
									currentWeek = decrISOWeek(currentWeek);
									beforeA[currentWeek] = filler;
								}
								let __FirstDateA = decrISOWeek(currentWeek);
								__FirstDateA = __FirstDateA.split('-W');
								__FirstDateA = getDateOfISOWeek(
									parseInt(__FirstDateA[1]),
									parseInt(__FirstDateA[0])
								);
								compensatedFirstDateA =
									__FirstDateA.getFullYear() +
									'-' +
									(__FirstDateA.getMonth() + 1) +
									'-' +
									__FirstDateA.getDate();
							}
							var newImpr = {};
							if (afterA) {
								$.extend(newImpr, statsA.impr, afterA);
							} else {
								$.extend(newImpr, beforeA, statsA.impr);
							}
							statsA.impr = newImpr;
						} else {
							// B short
							var beforeB = false;
							var afterB = false;

							if (
								ISOweekCompare(
									Object.keys(statsA.impr)[0],
									Object.keys(this.statsB.impr)[blen - 1],
									'>='
								)
							) {
								// statsA is the later period, B comes before A, compensate before the first date in B
								beforeB = {};
								var currentWeek = Object.keys(
									this.statsB.impr
								)[0];
								for (var _i = 0; _i < alen - blen; ++_i) {
									currentWeek = decrISOWeek(currentWeek);
									beforeB[currentWeek] = filler;
								}
								let __FirstDateB = decrISOWeek(currentWeek);
								__FirstDateB = __FirstDateB.split('-W');
								__FirstDateB = getDateOfISOWeek(
									parseInt(__FirstDateB[1]),
									parseInt(__FirstDateB[0])
								);
								compensatedFirstDateB =
									__FirstDateB.getFullYear() +
									'-' +
									__FirstDateB.getMonth() +
									1 +
									'-' +
									__FirstDateB.getDate();
							} else {
								afterB = {};
								var currentWeek = Object.keys(this.statsB.impr)[
									blen - 1
								];
								for (var _i = 0; _i < alen - blen; ++_i) {
									currentWeek = incrISOWeek(currentWeek);
									afterB[currentWeek] = filler;
								}
							}
							var newImpr = {};
							if (afterB) {
								$.extend(newImpr, this.statsB.impr, afterB);
							} else {
								$.extend(newImpr, beforeB, this.statsB.impr);
							}
							this.statsB.impr = newImpr;
						}
					} // group by week

					if ('month' == this.argsA.groupby) {
						var firstDateA = new Date(Object.keys(statsA.impr)[0]);
						var lastDateB = new Date(
							Object.keys(this.statsB.impr)[blen - 1]
						);
						if (alen < blen) {
							// A short
							var beforeA = false;
							var afterA = false;
							if (firstDateA.getTime() >= lastDateB.getTime()) {
								// statsA is the later period, B comes before A, compensate after the last date in A
								afterA = {};
								var lastMonth = Object.keys(statsA.impr)[
									alen - 1
								];
								for (var _i = 0; _i < blen - alen; ++_i) {
									lastMonth = incrMonth(lastMonth);
									afterA[lastMonth] = filler;
								}
							} else {
								beforeA = {};
								var firstMonth = Object.keys(statsA.impr)[0];
								for (var _i = 0; _i < blen - alen; ++_i) {
									firstMonth = decrMonth(firstMonth);
									beforeA[firstMonth] = filler;
								}
							}
							var newImpr = {};
							if (afterA) {
								$.extend(newImpr, statsA.impr, afterA);
							} else {
								$.extend(newImpr, beforeA, statsA.impr);
							}
							statsA.impr = newImpr;
						} else {
							var beforeB = false;
							var afterB = false;
							if (firstDateA.getTime() >= lastDateB.getTime()) {
								// statsA is the later period, B comes before A, compensate before the first date in B ( B short )
								beforeB = {};
								var firstMonth = Object.keys(
									this.statsB.impr
								)[0];
								for (var _i = 0; _i < alen - blen; ++_i) {
									firstMonth = decrMonth(firstMonth);
									beforeB[firstMonth] = filler;
								}
							} else {
								// statsA is the early period, compensate after the last entry in B
								afterB = {};
								var lastMonth = Object.keys(this.statsB.impr)[
									blen - 1
								];
								for (var _i = 0; _i < alen - blen; ++_i) {
									lastMonth = incrMonth(lastMonth);
									afterB[lastMonth] = filler;
								}
							}
							var newImpr = {};
							if (afterB) {
								$.extend(newImpr, this.statsB.impr, afterB);
							} else {
								$.extend(newImpr, beforeB, this.statsB.impr);
							}
							this.statsB.impr = newImpr;
						}
					} // group by month
				} // need compensation

				// iterate date statsA
				for (var _date in statsA.impr) {
					var date = _date;
					if (
						'week' == this.argsA.groupby &&
						-1 != date.indexOf('W')
					) {
						var _W = parseInt(_date.split('W')[1]);
						var _Y = parseInt(_date.split('-')[0]);
						var _WS = getDateOfISOWeek(_W, _Y);
						var _Month = (_WS.getMonth() + 1).toString();
						if (1 == _Month.length) {
							_Month = '0' + _Month;
						}
						var _Day = _WS.getDate().toString();
						if (1 == _Day.length) {
							_Day = '0' + _Day;
						}
						date = _WS.getFullYear() + '-' + _Month + '-' + _Day;
					}

					var perDateImpr = 0;
					var perDateClicks = 0;

					// iterate ad ID
					for (var _ID in statsA.impr[_date]) {
						var _impr = statsA.impr[_date][_ID]
							? parseInt(statsA.impr[_date][_ID])
							: 0;
						var _click = 0;

						if (
							hasClicksA &&
							undefined !== statsA.click[_date] &&
							statsA.click[_date][_ID]
						) {
							_click = parseInt(statsA.click[_date][_ID]);
						}

						// deleted ads
						if (this.allAds.indexOf(_ID) === -1) {
							if (undefined === adTableDataA.deleted) {
								adTableDataA.deleted = {
									impr: _impr,
									click: _click,
								};
							} else {
								adTableDataA.deleted.impr += _impr;
								adTableDataA.deleted.click += _click;
							}
						} else {
							// no ad filter or current ad passes filters
							if (
								!adFilters.length ||
								adFilters.indexOf(parseInt(_ID, 10)) !== -1
							) {
								if (groupFilters.length) {
									// group filter found
									if (
										undefined !==
										adsToGroups[parseInt(_ID, 10)]
									) {
										// apply group filter - current ad is a part of a group
										for (var f in groupFilters) {
											if (
												isInGroup(_ID, groupFilters[f])
											) {
												if (
													undefined ===
													adTableDataA[
														groupFilters[f]
													]
												) {
													adTableDataA[
														groupFilters[f]
													] = {
														impr: _impr,
														click: _click,
													};
												} else {
													adTableDataA[
														groupFilters[f]
													].impr += _impr;
													adTableDataA[
														groupFilters[f]
													].click += _click;
												}
											}
										}
									}
								} else {
									// no group filter && ( no ad filter or current ad passes filters )
									if (undefined === adTableDataA[_ID]) {
										adTableDataA[_ID] = {
											impr: _impr,
											click: _click,
										};
									} else {
										adTableDataA[_ID].impr += _impr;
										adTableDataA[_ID].click += _click;
									}
								}
							}
						}

						// apply ad filter
						if (adFilters.length) {
							if (adFilters.indexOf(parseInt(_ID)) !== -1) {
								imprSeriesA.push([date, _impr]);
								clickSeriesA.push([date, _click]);
								perDateImpr += _impr;
								perDateClicks += _click;
							}
						} else if (groupFilters.length) {
							// group series by group ID
							for (var f in groupFilters) {
								if (isInGroup(_ID, groupFilters[f])) {
									perDateImpr += _impr;
									perDateClicks += _click;
								}
							}
						} else {
							// no ad nor group filters
							perDateImpr += _impr;
							perDateClicks += _click;
						}
					} // iterate ID

					dateTableDataA[date] = {
						impr: perDateImpr,
						click: perDateClicks,
					};
					if (!adFilters.length) {
						imprSeriesA.push([date, perDateImpr]);
						clickSeriesA.push([date, perDateClicks]);
					}
				} // iterate date statsA

				const hasClicksB =
					undefined !== this.statsB.click && this.statsB.click
						? true
						: false;

				// iterate date statB
				for (var _date in this.statsB.impr) {
					var date = _date;
					if (
						'week' == this.argsA.groupby &&
						-1 != date.indexOf('W')
					) {
						var _W = parseInt(_date.split('W')[1]);
						var _Y = parseInt(_date.split('-')[0]);
						var _WS = getDateOfISOWeek(_W, _Y);
						var _Month = (_WS.getMonth() + 1).toString();
						if (1 == _Month.length) {
							_Month = '0' + _Month;
						}
						var _Day = _WS.getDate().toString();
						if (1 == _Day.length) {
							_Day = '0' + _Day;
						}
						date = _WS.getFullYear() + '-' + _Month + '-' + _Day;
					}

					var perDateImpr = 0;
					var perDateClicks = 0;

					// iterate ad ID
					for (var _ID in this.statsB.impr[_date]) {
						var _impr = this.statsB.impr[_date][_ID]
							? parseInt(this.statsB.impr[_date][_ID])
							: 0;
						var _click = 0;

						if (
							hasClicksA &&
							undefined !== this.statsB.click[_date] &&
							this.statsB.click[_date][_ID]
						) {
							_click = parseInt(this.statsB.click[_date][_ID]);
						}

						// deleted ads
						if (-1 == this.allAds.indexOf(_ID)) {
							if (undefined === adTableDataB.deleted) {
								adTableDataB.deleted = {
									impr: _impr,
									click: _click,
								};
							} else {
								adTableDataB.deleted.impr += _impr;
								adTableDataB.deleted.click += _click;
							}
						} else {
							// apply ad filter
							if (
								!adFilters.length ||
								-1 != adFilters.indexOf(parseInt(_ID))
							) {
								if (groupFilters.length) {
									if (
										undefined !==
										adsToGroups[parseInt(_ID, 10)]
									) {
										// apply group filter - current ad is a part of a group
										for (var f in groupFilters) {
											if (
												isInGroup(_ID, groupFilters[f])
											) {
												if (
													undefined ===
													adTableDataB[
														groupFilters[f]
													]
												) {
													adTableDataB[
														groupFilters[f]
													] = {
														impr: _impr,
														click: _click,
													};
												} else {
													adTableDataB[
														groupFilters[f]
													].impr += _impr;
													adTableDataB[
														groupFilters[f]
													].click += _click;
												}
											}
										}
									}
								} else if (undefined === adTableDataB[_ID]) {
									adTableDataB[_ID] = {
										impr: _impr,
										click: _click,
									};
								} else {
									adTableDataB[_ID].impr += _impr;
									adTableDataB[_ID].click += _click;
								}
							}
						}

						// apply ad filter
						if (adFilters.length) {
							if (-1 != adFilters.indexOf(parseInt(_ID))) {
								imprSeriesB.push([date, _impr]);
								clickSeriesB.push([date, _click]);
								perDateImpr += _impr;
								perDateClicks += _click;
							}
						} else {
							// sums by date
							perDateImpr += _impr;
							perDateClicks += _click;
						}
					} // iterate ID

					dateTableDataB[date] = {
						impr: perDateImpr,
						click: perDateClicks,
					};
					if (!adFilters.length) {
						imprSeriesB.push([date, perDateImpr]);
						clickSeriesB.push([date, perDateClicks]);
					}
				} // iterate date statB

				/**
				 *  Plot data
				 */
				var graphOptions = JSON.parse(
					JSON.stringify(defaultGraphOptions, null, 0)
				);
				graphOptions.axes.xaxis.renderer = $.jqplot.DateAxisRenderer;
				graphOptions.axes.x2axis = {
					renderer: $.jqplot.DateAxisRenderer,
					tickOptions: {},
					tickInterval: '',
				};
				if ('month' != this.argsA.groupby) {
					graphOptions.axes.xaxis.min = compensatedFirstDateA
						? compensatedFirstDateA
						: statsA.firstDate;
					graphOptions.axes.x2axis.min = compensatedFirstDateB
						? compensatedFirstDateB
						: this.statsB.firstDate;
				}
				graphOptions.axes.xaxis.tickInterval =
					'1 ' + this.argsA.groupby;
				graphOptions.axes.xaxis.tickOptions.formatString =
					statsA.xAxisThickformat;
				graphOptions.axes.x2axis.tickInterval =
					'1 ' + this.argsA.groupby;
				graphOptions.axes.x2axis.tickOptions.formatString =
					this.statsB.xAxisThickformat;
				graphOptions.axes.yaxis.label = statsLocale.impressions;
				graphOptions.axes.yaxis.labelRenderer =
					$.jqplot.CanvasAxisLabelRenderer;
				graphOptions.axes.y2axis.label = statsLocale.clicks;
				graphOptions.axes.y2axis.labelRenderer =
					$.jqplot.CanvasAxisLabelRenderer;

				var lines = [
					imprSeriesA,
					clickSeriesA,
					imprSeriesB,
					clickSeriesB,
				];
				/**
				 *  Series options
				 */
				graphOptions.series = [
					{
						color: COLORS[0],
						highlighter: {
							formatString: '%s: %.0f ' + statsLocale.impressions,
						},
						lineWidth: 3,
						markerOptions: {
							size: 5,
							style: 'circle',
						},
						xaxis: 'xaxis',
					},
					{
						color: COLORS[1],
						linePattern: 'dashed',
						lineWidth: 3,
						markerOptions: {
							size: 5,
							style: 'filledSquare',
						},
						highlighter: {
							formatString: '%s: %.0f ' + statsLocale.clicks,
						},
						xaxis: 'xaxis',
						yaxis: 'y2axis',
					},
					{
						color: COLORS[2],
						highlighter: {
							formatString: '%s: %.0f ' + statsLocale.impressions,
						},
						lineWidth: 1,
						markerOptions: {
							size: 5,
							style: 'circle',
						},
						xaxis: 'x2axis',
					},
					{
						color: COLORS[3],
						highlighter: {
							formatString: '%s: %.0f ' + statsLocale.clicks,
						},
						linePattern: 'dashed',
						lineWidth: 2,
						markerOptions: {
							size: 5,
							style: 'filledSquare',
						},
						xaxis: 'x2axis',
						yaxis: 'y2axis',
					},
				];

				if (this.graph) {
					this.graph.destroy();
				}

				$('#advads-stats-graph').empty();
				this.graph = $.jqplot(
					'advads-stats-graph',
					lines,
					graphOptions
				);

				if (
					20 < lines[0].length &&
					900 > $('#advads-stats-graph').width()
				) {
					$('.jqplot-xaxis-tick, .jqplot-x2axis-tick').css(
						'transform',
						'rotate(-45deg)'
					);
					$('.jqplot-x2axis').css('bottom', -10);
					$('#advads-graph-legend').css('margin-top', 25);
				} else {
					$('.jqplot-xaxis-tick, .jqplot-x2axis-tick').css(
						'transform',
						'rotate(0deg)'
					);
					$('.jqplot-x2axis').css('bottom', 0),
						$('#advads-graph-legend').css('margin-top', 13);
				}

				$('#advads-graph-legend .legend-item')
					.not('.donotremove')
					.remove();

				$('#advads-graph-legend')
					.append(
						$(
							'<div class="legend-item"><div style="background-color:' +
								COLORS[0] +
								';" class="ad-color-legend"></div><span>' +
								statsLocale.imprFromTo
									.replace(
										'%1$s',
										formatDate(
											new Date(that.statsA.periodStart),
											statsDates.wpDateFormat
										)
									)
									.replace(
										'%2$s',
										formatDate(
											new Date(that.statsA.periodEnd),
											statsDates.wpDateFormat
										)
									) +
								'</span></div>'
						)
					)
					.append(
						$(
							'<div class="legend-item"><div style="background-color:' +
								COLORS[1] +
								';" class="ad-color-legend"></div><span>' +
								statsLocale.clicksFromTo
									.replace(
										'%1$s',
										formatDate(
											new Date(that.statsA.periodStart),
											statsDates.wpDateFormat
										)
									)
									.replace(
										'%2$s',
										formatDate(
											new Date(that.statsA.periodEnd),
											statsDates.wpDateFormat
										)
									) +
								'</span></div>'
						)
					)
					.append(
						$(
							'<div class="legend-item"><div style="background-color:' +
								COLORS[2] +
								';" class="ad-color-legend"></div><span>' +
								statsLocale.imprFromTo
									.replace(
										'%1$s',
										formatDate(
											new Date(that.statsB.periodStart),
											statsDates.wpDateFormat
										)
									)
									.replace(
										'%2$s',
										formatDate(
											new Date(that.statsB.periodEnd),
											statsDates.wpDateFormat
										)
									) +
								'</span></div>'
						)
					)
					.append(
						$(
							'<div class="legend-item"><div style="background-color:' +
								COLORS[3] +
								';" class="ad-color-legend"></div><span>' +
								statsLocale.clicksFromTo
									.replace(
										'%1$s',
										formatDate(
											new Date(that.statsB.periodStart),
											statsDates.wpDateFormat
										)
									)
									.replace(
										'%2$s',
										formatDate(
											new Date(that.statsB.periodEnd),
											statsDates.wpDateFormat
										)
									) +
								'</span></div>'
						)
					);
				/**
				 *  Datatable
				 */
				clearTables();
				const periodA = statsLocale.aTob
					.replace(
						'%1$s',
						formatDate(
							new Date(that.statsA.periodStart),
							statsDates.wpDateFormat
						)
					)
					.replace(
						'%2$s',
						formatDate(
							new Date(that.statsA.periodEnd),
							statsDates.wpDateFormat
						)
					);
				const periodB = statsLocale.aTob
					.replace(
						'%1$s',
						formatDate(
							new Date(that.statsB.periodStart),
							statsDates.wpDateFormat
						)
					)
					.replace(
						'%2$s',
						formatDate(
							new Date(that.statsB.periodEnd),
							statsDates.wpDateFormat
						)
					);
				perAdtable(
					'adTable',
					adTableDataA,
					adTableDataB,
					periodA,
					periodB
				);
				perDatetable(
					'dateTable',
					dateTableDataA,
					dateTableDataB,
					periodA,
					periodB
				);
			} // comparison
		},

		/**
		 *  Returns the stats for a giver period
		 * @param args
		 * @param callable
		 */
		getSinglePeriod(args, callable) {
			const that = this;
			if (undefined == callable) {
				callable = 'loadStatsA';
			}
			if (undefined === args.period) {
				$('#period-td')
					.empty()
					.append(
						$(
							'<span style="color:red;">' +
								statsLocale.customPeriodMissing +
								'</span>'
						)
					);
				return;
			}
			if (undefined === args.groupby) {
				$('#period-td')
					.empty()
					.append(
						$(
							'<span style="color:red;">' +
								statsLocale.customPeriodMissing +
								'</span>'
						)
					);
				return;
			}
			if ('custom' == args.period) {
				if (undefined === args.from || '' == args.from) {
					$('#period-td')
						.empty()
						.append(
							$(
								'<span style="color:red;">' +
									statsLocale.customPeriodMissing +
									'</span>'
							)
						);
					return;
				}
				if (undefined === args.to || '' == args.to) {
					$('#period-td')
						.empty()
						.append(
							$(
								'<span style="color:red;">' +
									statsLocale.customPeriodMissing +
									'</span>'
							)
						);
					return;
				}
			}

			const formData = {
				nonce: advadsglobal.ajax_nonce,
				action:
					undefined !== args.file
						? 'advads_load_stats_file'
						: 'advads_load_stats',
				ads:
					undefined !== args.file
						? $('#stats-attachment-adIDs').val()
						: $('#all-ads').val(),
				args: $.param(args),
			};

			this._reverseDisabled('wpbody-content');
			const adFilterState = $('#ad-filter').prop('disabled');
			const groupFilterState = $('#group-filter').prop('disabled');
			if (!adFilterState) {
				$('#ad-filter').prop('disabled', true).autocomplete('disable');
			}
			if (!groupFilterState) {
				$('#group-filter')
					.prop('disabled', true)
					.autocomplete('disable');
			}
			$('#statsA-spinner').append(spinner);
			$('#compare-next-btn,#compare-prev-btn').prop('disabled', true);
			$('#period-td').empty();
			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: formData,
				success(resp, textStatus, XHR) {
					if (!adFilterState) {
						$('#ad-filter')
							.prop('disabled', false)
							.autocomplete('enable');
					}
					if (!groupFilterState) {
						$('#group-filter')
							.prop('disabled', false)
							.autocomplete('enable');
					}
					if (resp && resp.status) {
						that[callable].call(that, resp, args);
					} else {
						if (
							undefined !== resp.msg &&
							resp.msg == 'invalid-record'
						) {
							if (that.graph) {
								that.graph.destroy();
							}
							clearTables();
							$('#advads-stats-graph')
								.empty()
								.append(
									$(
										'<p class="advads-notice-inline advads-error">' +
											statsLocale.invalidRecord +
											'</p>'
									)
								);
						}
						$('.ajax-spinner').remove();
						that._reverseDisabled('wpbody-content');
					}
				},
				error(request, textStatus, err) {
					if (adFilterState) {
						$('#ad-filter')
							.prop('disabled', false)
							.autocomplete('enable');
					}
					if (!groupFilterState) {
						$('#group-filter')
							.prop('disabled', false)
							.autocomplete('enable');
					}
				},
			});
		},
	};

	/**
	 *  Get query string in URI
	 *  [http://stackoverflow.com/questions/901115/how-can-i-get-query-string-values-in-javascript]
	 * @param name
	 * @param url
	 */
	function getParameterByName(name, url) {
		if (!url) {
			url = window.location.href;
		}
		name = name.replace(/[\[\]]/g, '\\$&');
		const regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)', 'i'),
			results = regex.exec(url);
		if (!results) {
			return null;
		}
		if (!results[2]) {
			return '';
		}
		return decodeURIComponent(results[2].replace(/\+/g, ' '));
	}

	// on DOM ready
	$(function () {
		spinner.attr('src', advancedAds.endpoints.adminUrl + 'images/spinner.gif');

		// tracking tables are missing abort
		if (!$('#stats-form').length) {
			return;
		}
		// localization
		const lang = $('html').attr('lang');

		var supportedLocale = ['en', 'fr', 'de', 'ar', 'ru', 'pt'];

		if (lang) {
			if (-1 != supportedLocale.indexOf(lang.split('-')[0])) {
				$.jsDate.config.defaultLocale = lang.split('-')[0];
			}
			if ('pt-BR' == lang) {
				// português do Brasil
				$.jsDate.config.defaultLocale = 'pt-BR';
			}

			var supportedLocale = ['en', 'fr', 'de', 'ar', 'ru', 'pt'];

			if (-1 != supportedLocale.indexOf(lang.split('-')[0])) {
				$.jsDate.config.defaultLocale = lang.split('-')[0];
			}
			if ('pt-BR' == lang) {
				// português do Brasil
				$.jsDate.config.defaultLocale = 'pt-BR';
			}
		}
		// display custom from-to fields if custom is selected
		$('.advads-stats-period').change(function () {
			if ('custom' == $(this).val()) {
				$(this)
					.parents('fieldset')
					.find('.advads-stats-from,.advads-stats-to')
					.show();
			} else {
				$(this)
					.parents('fieldset')
					.find('.advads-stats-from,.advads-stats-to')
					.hide();
			}
		});

		// construct date pickers
		$('.advads-stats-from,.advads-stats-to').datepicker({
			dateFormat: 'mm/dd/yy',
		});

		/**
		 * Extend DataTable
		 * plugin's code [https://www.datatables.net/plug-ins/sorting/custom-data-source/dom-text]
		 * @param settings
		 * @param col
		 */
		$.fn.dataTable.ext.order['dom-text'] = function (settings, col) {
			return this.api()
				.column(col, { order: 'index' })
				.nodes()
				.map(function (td, i) {
					return $('input', td).val();
				});
		};

		// Init number formatings for DataTable.
		const split1 = numbersFormated.split('2')[1].split('3');
		if ('' !== split1[0]) {
			numberFormats.thousand = split1[0];
		}

		const split2 = numbersFormated.split('5')[1].split('6');
		if ('' !== split2[0]) {
			numberFormats.decimal = split2[0];
		}

		// build them all
		$.statDisplay = new statDisplay();

		// load automatically stats if data provided in URL
		const advadsStatsPeriod = getParameterByName(
			'advads-stats%5Bperiod%5D'
		);
		const advadsStatsFilter = getParameterByName(
			'advads-stats-filter%5B0%5D'
		);
		if (advadsStatsPeriod && advadsStatsFilter) {
			existingFilter = advadsStatsFilter;
			$('#load-simple').trigger('click');
		} else {
			// load the last seven days stats by default
			$('select[name="advads-stats[period]"]').val('last7days');
			$('#load-simple').trigger('click');
		}

		$('#stats-form');

		$(document).on('change', '#data-source', function () {
			if ('db' == $(this).val()) {
				$('.load-from-db-fields').show();
				$('.load-from-file-fields').hide();
				if ($.statDisplay.statsA) {
					$('#stats-file-description').text(statsLocale.noFile);
					$(
						'#stats-attachment-id,#stats-attachment-firstdate,#stats-attachment-lastdate'
					).val('');
					window.adTitles = window.adTitlesDB;
					$.statDisplay.reset();
					rebuildAutoComplete(window.adTitles);
					$('#load-stats-from-file').prop('disabled', true);
				}
			} else {
				$('.load-from-db-fields').hide();
				$('.load-from-file-fields').show();
			}
		});

		$.formatDate = formatDate;
		$.zeroise = zeroise;
		$.rebuildAutoComplete = rebuildAutoComplete;
	});

	// DataTable dom-text sorting.
	$.fn.dataTable.ext.order['dom-text'] = function (settings, col) {
		return this.api()
			.column(col, { order: 'index' })
			.nodes()
			.map(function (td, i) {
				return $('input', td).val();
			});
	};
})(jQuery);
