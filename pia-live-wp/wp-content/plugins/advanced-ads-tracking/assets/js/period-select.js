( function ( $ ) {
	'use strict';

	$.advadsIsConsistentPeriod = function ( from, to ) {
		if ( from === undefined || to === undefined || from === '' || to === '' ) {
			return false;
		}
		var start = from.split( '/' );
		var end   = to.split( '/' );

		return parseInt( start[2] + start[0] + start[1] ) <= parseInt( end[2] + end[0] + end[1] );
	};

	$( document ).on( 'change', '.advads-period', function () {
		if ( $( this ).val() === 'custom' ) {
			$( this ).siblings( 'input' ).show();
		} else {
			$( this ).siblings( 'input' ).hide();
		}
	} );

	$( function () {
		$( '.advads-datepicker' ).datepicker( {dateFormat: 'mm/dd/yy'} );
	} );

} )( jQuery );
