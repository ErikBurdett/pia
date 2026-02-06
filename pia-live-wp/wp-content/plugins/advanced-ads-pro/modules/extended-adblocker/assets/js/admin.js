( function ( $ ) {
	const overlayNotice = $( '.advads-eab-overlay-notice' );
	const divOverlay = $( '#advanced-ads-adblocker-overlay-options' );
	const divRedirect = $( '#advanced-ads-adblocker-redirect-options' );
	const divExclude = $( '#advanced-ads-adblocker-option-exclude' );

	const adblockerFixEnabled = $( '#advanced-ads-use-adblocker' ).prop(
		'checked'
	);

	$( '.advanced-ads-adblocker-eab-method' ).on( 'change', function () {
		overlayNotice.hide();

		switch ( this.value ) {
			case 'nothing':
				divOverlay.hide();
				divRedirect.hide();
				divExclude.hide();
				break;
			case 'overlay':
				divOverlay.show();
				divRedirect.hide();
				divExclude.show();
				// eslint-disable-next-line no-unused-expressions
				! adblockerFixEnabled && overlayNotice.show();
				break;
			case 'redirect':
				divOverlay.hide();
				divRedirect.show();
				divExclude.show();
				break;
		}
	} );

	const dismissButton = $( '#advanced-ads-adblocker-dismiss-button-input' );
	dismissButton.on( 'change', function () {
		$( '#advanced-ads-adblocker-dismiss-options' ).toggle( ! this.checked );
	} );
	dismissButton.change();
} )( jQuery );
