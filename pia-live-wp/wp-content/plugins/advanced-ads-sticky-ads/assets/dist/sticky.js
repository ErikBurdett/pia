document.addEventListener( 'advanced_ads_privacy', function ( e ) {
	( 'accepted' !== e.detail.state && 'not_needed' !== e.detail.state ) ||
		window.advanced_ads_sticky_settings.placements.forEach( function ( e ) {
			document
				.querySelectorAll(
					'script[type="text/plain"][data-tcf="waiting-for-consent"][data-placement="' +
						e +
						'"]'
				)
				.forEach( advads.privacy.decode_ad );
		} );
} ),
	document.addEventListener( 'aagam_empty_slot', function ( e ) {
		const t = document.getElementById( e.detail );
		if ( ! t ) return;
		const n = t.closest(
			'.' +
				document.body.classList.value
					.split( 'aa-prefix-' )[ 1 ]
					.split( ' ' )[ 0 ] +
				'sticky'
		);
		n && advads.close( '#' + n.id );
	} ),
	jQuery( document ).ready( function ( e ) {
		let t,
			n = null,
			s = e( window ).width();
		function o() {
			n && clearTimeout( n ),
				( n = setTimeout( function () {
					const n = e( window ).width();
					s !== n &&
						( ( s = n ),
						'undefined' != typeof advanced_ads_sticky_items &&
							e.each(
								advanced_ads_sticky_items,
								function ( n, s ) {
									( t = e( '#' + n ) ),
										t.prop( 'style', s.initial_css ),
										s.modifying_func();
								}
							) );
				}, 1e3 ) );
		}
		if (
			( ( 'undefined' != typeof advanced_ads_responsive &&
				parseInt( advanced_ads_responsive.reload_on_resize, 10 ) ) ||
				jQuery( window ).on( 'resize', o ),
			'undefined' == typeof advanced_ads_sticky_settings ||
				! advanced_ads_sticky_settings.check_position_fixed )
		)
			return;
		let i = '';
		const c = e( document.body ).is( '.admin-bar' )
			? e( '#wpadminbar' ).height()
			: 0;
		function d( t ) {
			! 1 === i &&
				( e( window ).off( 'resize', o ),
				( t =
					t ||
					jQuery( '.' + advanced_ads_sticky_settings.sticky_class ) ),
				setTimeout( function () {
					t.each( function ( t, n ) {
						const s = e( n );
						window.advanced_ads_sticky_items[ s.attr( 'id' ) ]
							.can_convert_to_abs
							? s.css( 'position', 'absolute' )
							: s
									.css( 'position', '' )
									.css( 'top', '' )
									.css( 'right', '' )
									.css( 'bottom', '' )
									.css( 'left', '' )
									.css( 'margin-left', '' )
									.css( 'transform', 'none' )
									.css( '-webkit-transform', 'none' )
									.css( '-moz-transform', 'none' )
									.css( '-ms-transform', 'none' );
					} );
				} ) );
		}
		navigator.userAgent.indexOf( 'Opera Mini' ) > -1
			? ( ( i = ! 1 ), d() )
			: e( window ).scroll( function t() {
					clearTimeout( e.data( this, 'scrollTimer' ) ),
						e.data(
							this,
							'scrollTimer',
							setTimeout( function () {
								e( document ).scrollTop() <= c ||
									( '' === i &&
										( ( i = ( function () {
											const e = document.body;
											if (
												document.createElement &&
												e &&
												e.appendChild &&
												e.removeChild
											) {
												const t =
													document.createElement(
														'div'
													);
												if ( ! t.getBoundingClientRect )
													return null;
												( t.innerHTML = 'x' ),
													( t.style.cssText =
														'position:fixed;top:100px;' ),
													e.appendChild( t );
												const n = e.style.height,
													s = e.scrollTop;
												let o = parseInt(
													document.documentElement.getBoundingClientRect()
														.top,
													10
												);
												( o = o > 0 ? o : 0 ),
													( e.style.height =
														'3000px' ),
													( e.scrollTop = 500 );
												const i = parseInt(
													t.getBoundingClientRect()
														.top,
													10
												);
												e.style.height = n;
												const c = i - o === 100;
												return (
													e.removeChild( t ),
													( e.scrollTop = s ),
													c
												);
											}
											return null;
										} )() ),
										clearTimeout(
											e.data( this, 'scrollTimer' )
										),
										e( window ).off( 'scroll', t ) ),
									d() );
							}, 100 )
						);
			  } ),
			'object' == typeof advanced_ads_pro &&
				null !== advanced_ads_pro &&
				advanced_ads_pro.postscribeObservers.add( function ( e ) {
					if ( 'postscribe_done' === e.event && e.ref && e.ad ) {
						const t = jQuery( e.ref ).children(
							'.' + advanced_ads_sticky_settings.sticky_class
						);
						t.length && d( t );
					}
				} );
	} );
