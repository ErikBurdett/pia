(function ($) {
	'use strict';
	const advadsMediaFrame = function (options) {
		this.defaultOptions = {
			mime: ['text/csv', 'text/html'],
			notice: null,
			context: '',
		};
		this.options = $.extend({}, this.defaultOptions, options);

		// create an instance of wp.media for our usage
		this.wpMediaFrame = wp.media.frames.frame = wp.media({
			title: advadsMediaFrameLocale.selectFile,
			button: {
				text: advadsMediaFrameLocale.button,
			},
			multiple: false,
		});

		const that = this;

		// on media selected (actually when the bottom right button is pressed)
		this.wpMediaFrame.on('select', function () {
			const attachment = that.wpMediaFrame
				.state()
				.get('selection')
				.first()
				.toJSON();
			const isValidFile =
				that.options.mime.indexOf(attachment.mime) !== -1;
			if (isValidFile) {
				if (that.options.notice) {
					that.options.notice.empty();
				}
				$(document).trigger('advadsHasValidFile', [
					that.options.context,
					attachment.id,
				]);
			} else {
				// mime type not allowed
				if (that.options.notice) {
					that.options.notice.html(
						'<span style="color:red">' +
							advadsMediaFrameLocale.invalidFileType +
							'</span>'
					);
				}
			}
		});

		this.wpMediaFrame.open();

		return this;
	};

	// extend jQuery with this object
	$.advadsMediaFrame = function (options) {
		const data = $('#wpwrap').data('advadsMediaFrame');
		if (undefined === data) {
			$('#wpwrap').data(
				'advadsMediaFrame',
				new advadsMediaFrame(options)
			);
		} else {
			data.options = $.extend({}, data.defaultOptions, options);
			data.wpMediaFrame.open();
		}
	};
})(jQuery);
