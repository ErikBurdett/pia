/**
 * Sets the value or checked state of an input element.
 *
 * @param {HTMLElement}    element            - The input element to set the value or checked state for.
 * @param {string|boolean} value              - The value to set for the input element.
 * @param {boolean}        [isCheckbox=false] - Whether the input element is a checkbox.
 *
 * @return {void}
 */
function setInputValue(element, value, isCheckbox = false) {
	// Early bail!!
	if (!element) {
		return;
	}

	if (isCheckbox) {
		element.checked = value;
	} else {
		element.value = value;
	}
}

/**
 * Fills the input fields in the specified row with the provided data.
 *
 * @param {number} id   - The ID of the ad.
 * @param {Object} data - The data to fill the input fields with.
 *
 * @return {void}
 */
function fillInputs(id, data) {
	const row = document.querySelector(`#edit-${id}`);

	setInputValue(row.querySelector('[name="target_url"]'), data.targetUrl);
	setInputValue(
		row.querySelector('[name="cloak_link"]'),
		data.cloakLink,
		true
	);
	setInputValue(
		row.querySelector('[name="target_window"]'),
		data.targetWindow
	);
	setInputValue(row.querySelector('[name="nofollow"]'), data.nofollow);
	setInputValue(row.querySelector('[name="sponsored"]'), data.sponsored);
	setInputValue(
		row.querySelector('[name="report_recipient"]'),
		data.reportRecipient
	);

	const trackingMethod = row.querySelector('[name="tracking_method"]');
	if (trackingMethod && data.trackingChoices) {
		Object.entries(data.trackingChoices).forEach(function ([value, label]) {
			const option = document.createElement('option');
			option.value = value;
			option.text = label;
			trackingMethod.appendChild(option);
		});
		trackingMethod.value = data.trackingMethod;
	}
}

export default function () {
	if (window.wp && window.wp.hooks) {
		window.wp.hooks.addAction(
			'advanced-ads-quick-edit-fields-init',
			'advancedAdsTracking',
			function (id, data) {
				fillInputs(id, data);
			}
		);
	}
}
