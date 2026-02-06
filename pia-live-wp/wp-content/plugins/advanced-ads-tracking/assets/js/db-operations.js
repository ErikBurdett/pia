(function($){
	"use strict";

	function disable() {
		$( 'input,select,button,textarea' ).prop( 'disabled', true );
	}

	function enable() {
		$( 'input,select,button,textarea' ).prop( 'disabled', false );
	}

	function getSpinnerCode() {
		return '<span class="spinner advads-spinner"></span>';
	}

	// export stats data
	$( document ).on( 'submit', '#export-stats-form', function( ev ) {
		ev.preventDefault();
		var period = $( this ).find( '.advads-period' ).val();

		if ( period === 'custom' ) {
			var from = $( this ).find( '.advads-from' ).val();
			var to   = $( this ).find( '.advads-to' ).val();

			if ( ! $.advadsIsConsistentPeriod( from, to ) ) {
				$( '#export-period-error' ).show();
				return false;
			}
		}
		$( '#export-period-error' ).hide();
		var url = ajaxurl + '?action=advads_tracking_export&period=' + period + '&nonce=' + advadsTrackingDbopNonce;
		if ( undefined !== to ) {
			url += '&from=' + from + '&to=' + to;
		}
		$( '#stats-download-frame' ).attr( 'src', url );
	} );

	// remove stats
	$( document ).on( 'submit', '#remove-stats-form', function( ev ) {
		ev.preventDefault();
		var period = $( this ).find( '.advads-period' ).val();

		var formData = {
			nonce: advadsTrackingDbopNonce,
			action: 'advads_tracking_remove',
			period: period,
		};
		$( this ).find( '.button' ).after( $( getSpinnerCode() ) );
		disable();

		$.ajax( {
			type: 'POST',
			url: ajaxurl,
			data: formData,
			success: function ( resp, textStatus, XHR ) {
				$( '.advads-spinner' ).remove();
				if ( undefined !== resp.status && resp.status ) {
					if ( undefined !== resp['alt-msg'] ) {
						$( '#remove-error-notice' ).text( trackingDbopLocale.optimizeFailure ).removeClass( 'hidden' );
						enable();
					} else {
						$( '#remove-error-notice' ).empty().addClass( 'hidden' );
						location.reload();
					}
				} else {
					enable();
					$( '#remove-error-notice' ).text( trackingDbopLocale.SQLFailure ).removeClass( 'hidden' );
					if ( undefined !== resp.msg ) {
						console.log( resp.msg );
					}
				}
			},
			error: function ( request, textStatus, err ) {
				$( '.advads-spinner' ).remove();
				enable();
				console.log( request );
				alert( trackingDbopLocale.serverFail );
			}
		} );

	} );

	$( document ).on( 'submit', '#debug-mode-form', function ( ev ) {
		ev.preventDefault();
		$( this ).find( '.button' ).after( $( getSpinnerCode() ) );
		disable();
		wp.ajax.send( 'advads_tracking_debug_mode', {
			data: {
				nonce: advadsTrackingDbopNonce,
				ad:    $( '#debug-mode-adID' ).val()
			}
		} )
		  .done( function () {
			  location.reload();
		  } )
		  .fail( function ( response ) {
			  $( '.widefat' ).before( '<div class="error"><p>' + response.responseJSON.data.message + '</p></div>' );
		  } )
		  .always( function ( response ) {
			  $( '.advads-spinner' ).remove();
			  enable();
			  console.log( response );
		  } );
	} );

	$( document ).on( 'submit', '#reset-stats-form', function ( ev ) {
		ev.preventDefault();
		var ad = $( '#reset-stats-adID' ).val();
		if ( '' == ad ) {
			$( '#reset-error-notice' ).text( trackingDbopLocale.resetNoAd ).removeClass( 'hidden' );
		} else {
			$( '#reset-error-notice' ).empty().addClass( 'hidden' );
			var adName    = $( '#reset-stats-adID option:selected' ).text();
			var reconfirm = confirm( trackingDbopLocale.resetConfirm + ' ' + adName );
			if ( reconfirm ) {
				var formData = {
					nonce: advadsTrackingDbopNonce,
					action: 'advads_tracking_reset',
					ad: ad,
				};
				$( this ).find( '.button' ).after( $( getSpinnerCode() ) );
				disable();
				$.ajax( {
					type: 'POST',
					url: ajaxurl,
					data: formData,
					success: function ( resp ) {
						var $errorNotice = $( '#reset-error-notice' );
						if (typeof resp.data !== 'undefined') {
							resp = resp.data;
						}
						$( '.advads-spinner' ).remove();
						if ( undefined !== resp.status && resp.status ) {
							$errorNotice.empty();
							if ( typeof resp.redirect !== 'undefined' ) {
								window.location.href = resp.redirect;
							} else {
								window.location.reload();
							}
						} else {
							enable();
							$errorNotice.html( trackingDbopLocale.SQLFailure );
							if ( undefined !== resp.msg ) {
								$errorNotice.html( $errorNotice.text() + ":<br>" + resp.msg );
								console.log( resp.msg );
							}
						}
					},
					error: function ( request ) {
						$( '.advads-spinner' ).remove();
						enable();
						console.log( request );
						alert( trackingDbopLocale.serverFail );
					}
				} );

			}
		}
	} );

	$(document).ready(function () {
        //check if reset-stats-id exists
        const urlParams = new URLSearchParams(window.location.search);
        const resetStatsId = urlParams.get("reset-stats-id");

        if (
            resetStatsId &&
            $(`#reset-stats-adID option[value="${resetStatsId}"]`).length > 0
        ) {
            $("#reset-stats-adID").val(resetStatsId);
            $("html, body").animate({
                scrollTop: $("#reset-stats-adID").offset().top,
            });
        }
    });
})( jQuery );
