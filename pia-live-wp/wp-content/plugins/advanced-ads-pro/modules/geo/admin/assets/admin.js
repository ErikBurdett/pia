/* global advads_geo_translation */
/* eslint-disable camelcase */
window.advads_geo_admin = window.advads_geo_admin || {};

advads_geo_admin.set_mode = function ( index, mode ) {
	if ( mode === 'latlon' ) {
		jQuery( '#advads_geo_classic_' + index ).hide();
		jQuery( '#advads_geo_latlon_' + index ).show();
	} else {
		jQuery( '#advads_geo_classic_' + index ).show();
		jQuery( '#advads_geo_latlon_' + index ).hide();
		jQuery( '#advads_geo_latlon_by_city_' + index ).hide();
	}
};

advads_geo_admin.click_locname = function ( index ) {
	jQuery( '#advads_geo_latlon_by_city_' + index ).show();
	jQuery( '#advads_geo_latlon_' + index ).hide();
};

advads_geo_admin.search_loc_close = function ( index ) {
	jQuery( '#advads_geo_latlon_by_city_' + index ).hide();
	jQuery( '#advads_geo_latlon_' + index ).show();
};

advads_geo_admin.search_loc = function ( index ) {
	const city = jQuery( '#advads_geo_input_search_city_' + index ).val();

	jQuery
		.get( {
			url: 'https://nominatim.openstreetmap.org/search',
			data: { format: 'json', q: city, linkedplaces: 0, hierarchy: 0 },
			dataType: 'json',
		} )
		.done( function ( data ) {
			advads_geo_admin.receive_search_results( index, data );
		} )
		.fail( function () {
			jQuery( '#advads_geo_latlon_loading_' + index ).hide();
			const container = jQuery( '#advads_geo_latlon_results_' + index );
			const text =
				advads_geo_translation.could_not_retrieve_city +
				' ' +
				advads_geo_translation.manual_geo_search;
			container.append(
				jQuery(
					'<div class="advads-notice-inline advads-error"/>'
				).html( text )
			);
		} );
	jQuery( '#advads_geo_latlon_loading_' + index ).show();
};

advads_geo_admin.receive_search_results = function ( index, data ) {
	jQuery( '#advads_geo_latlon_results_' + index ).show();
	jQuery( '#advads_geo_latlon_loading_' + index ).hide();
	const container = jQuery( '#advads_geo_latlon_results_' + index );
	container[ 0 ].innerHTML = '';
	if ( data.length > 1 ) {
		const text = advads_geo_translation.found_results.replace(
			'%1$d',
			data.length
		);
		container.append( jQuery( '<div>' + text + '</div>' ) );
	} else if ( data.length === 0 ) {
		container.append(
			jQuery( '<div>' + advads_geo_translation.no_results + '</div>' )
		);
	}

	for ( const i in data ) {
		const itm = data[ i ];
		const elm = jQuery(
			'<div style="margin-bottom:5pt;cursor:pointer;" class="inline notice"><strong>' +
				itm.display_name +
				'</strong><font class="description">(' +
				itm.lat +
				' / ' +
				itm.lon +
				')</font></div>'
		)
			.mouseover( function () {
				jQuery( this ).addClass( 'updated' );
			} )
			.mouseout( function () {
				jQuery( this ).removeClass( 'updated' );
			} );

		elm[ 0 ].location = itm;
		elm.click( function () {
			const item = jQuery( this )[ 0 ].location;
			jQuery( '#advads_geo_input_search_city_' + index ).val(
				item.display_name
			);
			jQuery( '#advads_geo_input_lat_' + index ).val( item.lat );
			jQuery( '#advads_geo_input_lon_' + index ).val( item.lon );
			container[ 0 ].innerHTML = '';
			advads_geo_admin.search_loc_close( index );
		} );
		container.append( elm );
	}
};

( () => {
	new MutationObserver( function ( mutations ) {
		mutations.forEach( ( mutation ) => {
			if ( mutation.type === 'childList' && mutation.addedNodes.length ) {
				for ( const resetButton of document.getElementsByClassName(
					'advads-condition-visitor-profile-reset'
				) ) {
					resetButton.addEventListener( 'click', ( e ) => {
						window.Advanced_Ads_Admin.set_cookie(
							'advanced_ads_visitor',
							'',
							0,
							window.advads_geo_translation.COOKIEPATH,
							window.advads_geo_translation.COOKIE_DOMAIN
						);

						e.target
							.closest( '.advads-condition-visitor-profile' )
							.remove();
					} );
				}
			}
		} );
	} ).observe( document, { childList: true, subtree: true } );
} )();

// show/hide settings according to status of MaxMind license key and Database
// phpcs:disable Generic.Formatting.MultipleStatementAlignment.NotSameWarning -- WP PHPCS can't handle ES5 arrow functions alignment
( () => {
	const license = document.getElementById(
		'advanced-ads-geo-maxmind-licence'
	);
	if ( ! license ) {
		return;
	}

	if ( Boolean( license.dataset.customdb ) ) {
		license.closest( 'tr' ).style.display = 'none';
		return;
	}

	const licenseMissingWarning = document.getElementById(
		'advanced-ads-geo-license-missing-warning'
	);
	const dataBaseUpdate = document.getElementById(
		'advanced-ads-geo-update-database'
	);
	const hasDatabase = dataBaseUpdate.dataset.dbExists || false;

	const licenseChange = ( event ) => {
		const hasLicense = event.target.value.trim() !== '';
		let nextSibling = license.closest( 'tr' ).nextElementSibling;

		while ( nextSibling ) {
			nextSibling.style.display =
				hasLicense || hasDatabase ? 'table-row' : 'none';
			nextSibling = nextSibling.nextElementSibling;
		}

		licenseMissingWarning.style.display = hasLicense ? 'none' : 'block';
		dataBaseUpdate.style.display = hasLicense ? 'block' : 'none';
	};
	license.addEventListener( 'keyup', licenseChange );
	license.addEventListener( 'change', licenseChange );
	license.dispatchEvent( new Event( 'change' ) );
} )();
// phpcs:enable

// manually update the database.
jQuery( ( $ ) => {
	const $downloadButton = $( '#download_geolite' );
	const $loader = $( '#advads-geo-loader' );
	const $uploadError = $( '#advads-geo-upload-error' );
	const $uploadSuccess = $( '#advads-geo-upload-success' );
	const $noDbWarning = $( '#advanced-ads-geo-no-database-warning' );

	$downloadButton.on( 'click', () => {
		$downloadButton.blur().attr( 'disabled', 'disabled' );

		$loader.show();

		// phpcs:disable Generic.WhiteSpace.ScopeIndent.IncorrectExact -- moving each chain link into a new line improves readability immensely.
		$.post( ajaxurl, {
			action: 'advads_download_geolite_database',
			license_key: $( '#advanced-ads-geo-maxmind-licence' ).val(),
			locale: $( '[name="advanced-ads-pro[geo][locale]"]' ).val(),
			nonce: window.advads_geo_translation.nonce,
		} )
			.done( function ( result ) {
				if ( ! $.isPlainObject( result ) ) {
					return;
				}

				$uploadError.hide();
				$uploadSuccess.html( result.data ).show();
				$noDbWarning.remove();
				$downloadButton.remove();
			} )
			.fail( function ( jqXHR, errorMessage ) {
				let responseText;
				try {
					responseText = jQuery.parseJSON( jqXHR.responseText );
				} catch ( e ) {
					responseText = null;
				}
				errorMessage =
					responseText && responseText.data
						? responseText.data
						: errorMessage;
				$uploadError.html( errorMessage ).show();
				$uploadSuccess.hide();
				$downloadButton.attr( 'disabled', false );
			} )
			.always( function () {
				$loader.hide();
			} );
		// phpcs:enable
	} );
} );
