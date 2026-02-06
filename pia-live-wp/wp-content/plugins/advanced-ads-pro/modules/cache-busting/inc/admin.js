/* eslint-disable camelcase */
/* global Prescribe */

jQuery( document ).ready( function ( $ ) {
	$( '.advads-option-group-refresh input:checkbox:checked' ).each(
		function () {
			const numberOption = $( this )
				.parents( '.advads-ad-group-form' )
				.find( '.advads-option-group-number' );
			numberOption.val( 'all' ).hide();
		}
	);

	$( '.advads-option-group-refresh input:checkbox' ).on(
		'click',
		function () {
			const numberOption = $( this )
				.parents( '.advads-ad-group-form' )
				.find( '.advads-option-group-number' );
			if ( this.checked ) {
				numberOption.val( 'all' ).hide();
			} else {
				numberOption.show();
			}
		}
	);

	jQuery( '.advads-option-placement-cache-busting input' ).on(
		'change',
		function () {
			const state = jQuery( this ).val(),
				$inputs = jQuery( this )
					.closest( '.advads-placements-table-options' )
					.find( '.advanced-ads-inputs-dependent-on-cb' );

			if ( 'off' === state ) {
				// Hide UI elements that work only with cache-busting.
				$inputs.hide().next().show();
			} else {
				$inputs.show().next().hide();
			}
		}
	);

	$( '#advads-pro-vc-hash-change' ).on( 'click', function () {
		const $button = $( this );
		const $ok = jQuery( '#advads-pro-vc-hash-change-ok' );
		const $error = jQuery( '#advads-pro-vc-hash-change-error' );

		$( '<span class="spinner advads-spinner"></span>' ).insertAfter(
			$button
		);
		$button.hide();
		$ok.hide();
		$error.hide();

		jQuery
			.ajax( {
				type: 'POST',
				url: ajaxurl,
				data: {
					action: 'advads-reset-vc-cache',
					security: $( '#advads-pro-reset-vc-cache-nonce' ).val(),
				},
			} )
			.done( function ( data ) {
				jQuery( '#advads-pro-vc-hash' ).val( data );
				$ok.show();
			} )
			.fail( function () {
				$error.show();
			} )
			.always( function () {
				$( 'span.spinner' ).remove();
				$button.show();
			} );
	} );

	$( '.js-placement-activate-cb' ).on( 'click', function ( e ) {
		e.preventDefault();
		const button = $( this );
		const placement = button.data( 'placement' );
		const loader = jQuery( '<span class="advads-loader"></span>' );

		// Replace the dynamic field with the loader.
		button.parent().replaceWith( loader );

		$.post( ajaxurl, {
			action: 'advads-placement-activate-cb',
			placement: placement.toString(),
			nonce: window.advads_geo_translation.nonce,
		} )
			.done( function ( result ) {
				if ( ! $.isPlainObject( result ) ) {
					return;
				}
				loader.replaceWith(
					'<p class="advads-notice-inline advads-idea">' +
						result.data +
						'</p>'
				);
			} )
			.fail( function ( jqXHR ) {
				loader.replaceWith(
					'<p class="advads-notice-inline advads-error">' +
						jqXHR.responseJSON.data +
						'</p>'
				);
			} );
	} );
} );

function advads_cb_check_set_status( status, msg ) {
	if ( status === true ) {
		jQuery( '#advads-cache-busting-possibility' ).val( true );
	} else {
		jQuery( '#advads-cache-busting-possibility' ).val( false );
		jQuery( '#advads-cache-busting-error-result' )
			.append( msg ? '<br />' + msg : '' )
			.show();
	}
}

// eslint-disable-next-line no-unused-vars
function advads_cb_check_ad_markup( adContent ) {
	if ( ! adContent ) {
		return;
	}

	// checks whether the ad contains the jQuery.document.ready() and document.write(ln) functions
	if (
		( /\)\.ready\(/.test( adContent ) ||
			/(\$|jQuery)\(\s*?function\(\)/.test( adContent ) ) &&
		/document\.write/.test( adContent )
	) {
		advads_cb_check_set_status( false );
		return;
	}

	const search_str = 'cache_busting_test';
	const source = adContent + search_str;
	const parser = new Prescribe( source, { autoFix: true } );
	let tok,
		result = '';

	while ( ( tok = parser.readToken() ) ) {
		if ( tok ) {
			result += Prescribe.tokenToString( tok );
		}
	}
	advads_cb_check_set_status(
		result.substr( -search_str.length ) === search_str
	);
}
