/* eslint-disable camelcase, no-console, no-undef */
import '../../scss/public-stats.css';

( function ( $ ) {
	'use strict';

	$( function () {
		const dateSelector = $( '.stats-from,.stats-to' );

		$( document ).on( 'change', '#period-form select', function () {
			const isCustom = 'custom' === $( this ).val();
			// display from-to fields if custom else submit form
			initDateSelector( isCustom );
			if ( ! isCustom ) {
				$( this ).parents( 'form' ).submit();
			}
		} );

		function initDateSelector( active ) {
			if ( active ) {
				dateSelector
					.show()
					.prop( 'required', true )
					.attr( 'form', 'period-form' );
			} else {
				dateSelector
					.hide()
					.prop( 'required', false )
					.removeAttr( 'form' );
			}
		}

		// construct date pickers
		dateSelector.datepicker( { dateFormat: 'mm/dd/yy' } );

		$( document ).on( 'submit', '#period-form', function () {
			const overlay = $( '<div />' )
				.css( {
					position: 'fixed',
					width: '100%',
					height: '100%',
					top: 0,
					left: 0,
					textAlign: 'center',
					zindex: 900,
					backgroundColor: 'rgba( 255, 255, 255, 0.8)',
				} )
				.append(
					$(
						`<img alt="" style="margin-top:150px;" class="ajax-spinner" src="${ AAT_SPINNER_URL }" />`
					)
				);
			$( '#stats-content' ).append( overlay );
		} );
	} );

	$( function () {
		const lang = $( 'html' ).attr( 'lang' );
		const currentLang = lang.split( '-' )[ 0 ];
		const supportedLocale = [ 'en', 'fr', 'de', 'ar', 'ru', 'pt' ];

		if ( supportedLocale.includes( currentLang ) ) {
			$.jsDate.config.defaultLocale = currentLang;
		}

		if ( 'pt-BR' === lang ) {
			// português do Brasil
			$.jsDate.config.defaultLocale = 'pt-BR';
		}

		statsGraphOptions.axes.xaxis.renderer = $.jqplot.DateAxisRenderer;

		window.myGraph = $.jqplot(
			'public-stat-graph',
			lines,
			statsGraphOptions
		);
	} );
} )( jQuery );
