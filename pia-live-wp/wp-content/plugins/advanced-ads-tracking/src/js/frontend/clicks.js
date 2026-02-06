/* eslint-disable camelcase, no-console, no-undef */

/* IE 11 add foreach fix */
if ( window.NodeList && ! NodeList.prototype.forEach ) {
	NodeList.prototype.forEach = Array.prototype.forEach;
}

/**
 * Click tracker class.
 */
window.AdvAdsClickTracker = {
	wrappers: [],
	overTarget: false,
	currentTarget: false,
	lastClick: [],
	elements: [ 'iframe', 'a.a2t-link', 'button.a2t-link' ],
	// Predefine google adsense iframes.
	targets: [
		'aswift_0',
		'aswift_1',
		'aswift_3',
		'aswift_4',
		'aswift_5',
		'aswift_6',
		'aswift_7',
		'aswift_8',
		'aswift_9',
	],

	/**
	 * Find targets from selector array and save them into global targets array.
	 */
	findTargets() {
		// Loop through wrappers array and search wrapper elements.
		window.AdvAdsClickTracker.wrappers.forEach( function ( wrapper ) {
			const wrapperElements = document.querySelectorAll( wrapper );

			// Loop through wrapper elements and find targets.
			wrapperElements.forEach( function ( wrapperElement ) {
				// If wrapper is found search for defined child elements.
				if ( wrapperElement !== null ) {
					window.AdvAdsClickTracker.elements.forEach(
						function ( element ) {
							// Merge arrays and push detected targets into the global array.
							Array.prototype.push.apply(
								window.AdvAdsClickTracker.targets,
								// Convert dom nodelist into array.
								Array.prototype.slice.call(
									wrapperElement.querySelectorAll( element )
								)
							);
						}
					);
				}
			} );
		} );
		window.AdvAdsClickTracker.targets =
			window.AdvAdsClickTracker.targets.filter(
				AdvAdsTrackingUtils.arrayUnique
			);

		this.processTargets();
	},

	/**
	 * Initiate targets.
	 */
	processTargets() {
		window.AdvAdsClickTracker.targets.forEach( function ( target ) {
			window.AdvAdsClickTracker.registerTargetHandlers( target );
		} );
	},

	/**
	 * Register mouseover and mouseout events.
	 *
	 * @param {Element} target
	 */
	registerTargetHandlers( target ) {
		target.onmouseover = this.mouseOver;
		target.onmouseout = this.mouseOut;
		// Register click on ad with ie fix.
		if ( typeof window.attachEvent !== 'undefined' ) {
			top.attachEvent( 'onblur', this.adClick );
		} else if ( typeof window.addEventListener !== 'undefined' ) {
			// Register click on ad for all other browsers.
			top.addEventListener( 'blur', this.adClick, false );
		}
	},

	/**
	 * Register click handlers for wrapper elements.
	 */
	registerWrapperHandlers() {
		let touchmoved;

		// Add auxclick event for middle mouse button clicks.
		[ 'click', 'touchend', 'auxclick' ].forEach( function ( event ) {
			document.addEventListener(
				event,
				function ( e ) {
					// Stop if click is not from left or middle moue button.
					if (
						( e.type === 'auxclick' &&
							e.which !== 2 &&
							e.which !== 1 ) ||
						touchmoved
					) {
						return;
					}

					// Check if clicked element is clickable.
					let clickable = false;
					if (
						[ 'a', 'iframe', 'button' ].indexOf(
							e.target.localName
						) !== -1
					) {
						clickable = true;
					}
					// Loop parent nodes from the target to the delegation node.
					for (
						let target = e.target;
						target && target !== this;
						target = target.parentNode
					) {
						if (
							target.parentNode !== null &&
							! clickable &&
							[ 'a', 'iframe', 'button' ].indexOf(
								target.parentNode.localName
							) !== -1
						) {
							clickable = true;
						}
						let match = false;
						// Check if clicked element is in wrappers array.
						window.AdvAdsClickTracker.wrappers.forEach(
							function ( className ) {
								if (
									target.matches
										? target.matches( className )
										: target.msMatchesSelector( className )
								) {
									// Disable tracking on notrack links and on wrappers without clickable element
									if (
										! e.target.classList.contains(
											'notrack'
										) &&
										( clickable ||
											target.querySelector( 'iframe' ) !==
												null )
									) {
										match = true;
									}
								}
							}
						);
						// If match there is an ad click.
						if ( match ) {
							// Disable clicks if current element equals the wrapper element.
							if ( this.currentTarget === e.target ) {
								return;
							}
							window.AdvAdsClickTracker.ajaxSend( e.target );
							break;
						}
					}
				},
				{ capture: true }
			);
		} );

		// Detect swipe and click on mobile devices.
		document.addEventListener(
			'touchmove',
			function () {
				touchmoved = true;
			},
			false
		);
		document.addEventListener(
			'touchstart',
			function () {
				touchmoved = false;
			},
			false
		);
	},

	/**
	 * Click on ad action.
	 */
	adClick() {
		// If mouse is over target there is an ad click.
		if ( window.AdvAdsClickTracker.overTarget ) {
			window.AdvAdsClickTracker.ajaxSend(
				window.AdvAdsClickTracker.currentTarget
			);
			top.focus();
		}
	},

	/**
	 * Handle if mouse leaves ad.
	 */
	mouseOver() {
		window.AdvAdsClickTracker.overTarget = true;
		window.AdvAdsClickTracker.currentTarget = this;
	},

	/**
	 * Handle if mouse is over ad.
	 */
	mouseOut() {
		window.AdvAdsClickTracker.overTarget = false;
		window.AdvAdsClickTracker.currentTarget = false;
		top.focus();
	},

	/**
	 * Send message to ajax handler
	 *
	 * @param {Element} element
	 */
	ajaxSend( element ) {
		let dataId = element.getAttribute(
			'data-' + AdvAdsTrackingUtils.getPrefixedAttribute( 'trackid' )
		);
		let bId = element.getAttribute(
			'data-' + AdvAdsTrackingUtils.getPrefixedAttribute( 'trackbid' )
		);
		let redirectLink = element.getAttribute(
			'data-' + AdvAdsTrackingUtils.getPrefixedAttribute( 'redirect' )
		);
		if ( dataId === null ) {
			const parent = AdvAdsTrackingUtils.findParentByClassName( element, [
				advadsTracking.targetClass,
			] );
			dataId = parent.getAttribute(
				'data-' + AdvAdsTrackingUtils.getPrefixedAttribute( 'trackid' )
			);
			bId = parent.getAttribute(
				'data-' + AdvAdsTrackingUtils.getPrefixedAttribute( 'trackbid' )
			);
			redirectLink = parent.getAttribute(
				'data-' + AdvAdsTrackingUtils.getPrefixedAttribute( 'redirect' )
			);
		}

		const ajaxHandler = advads_tracking_urls[ bId ];
		const postData = {
			action: window.advadsTracking.clickActionName,
			referrer: window.location.pathname + window.location.search,
			type: 'ajax',
			ads: [ dataId ],
			bid: bId,
		};

		// prevent simultaneous clicks on wrapper and element as well as to fast clicks in a row
		if (
			10 >
			AdvAdsTrackingUtils.getTimestamp() - this.lastClick[ dataId ]
		) {
			return false;
		}

		// If google analytics or parallel tracking is activated, track click.
		if ( AdvAdsTrackingUtils.blogUseGA( bId ) ) {
			const tracker = advancedAdsGAInstances.getInstance( bId );
			tracker.trackClick( dataId, false, false, false );
			this.lastClick[ dataId ] = AdvAdsTrackingUtils.getTimestamp();
			if ( ! advads_tracking_parallel[ bId ] ) {
				return;
			}
		}

		// don't use frontend tracking on redirect links
		if ( redirectLink ) {
			return;
		}

		// use beacon api to send the request to the webserver
		if (
			navigator.sendBeacon &&
			ajaxHandler.indexOf( 'admin-ajax.php' ) === -1
		) {
			// Deep copy of data object.
			let beaconData = JSON.parse( JSON.stringify( postData ) );
			beaconData.type = 'beacon';
			beaconData = new Blob( [ JSON.stringify( beaconData ) ], {
				type: 'application/json; charset=UTF-8',
			} );
			navigator.sendBeacon( ajaxHandler, beaconData );
		} else {
			// use synchronous ajax call
			AdvAdsTrackingUtils.post( ajaxHandler, postData, false );
		}
		this.lastClick[ dataId ] = AdvAdsTrackingUtils.getTimestamp();
	},
};

/* Define Click Tracking class  */
advanced_ads_ready( function () {
	// We can push other custom classes via variables in this array for custom user classes or changeable classes that should be watched
	window.AdvAdsClickTracker.wrappers =
		advadsTracking.targetClass !== null && advadsTracking.targetClass !== ''
			? Array( '.' + advadsTracking.targetClass, '.adsbygoogle' )
			: Array( ' ', '.adsbygoogle' );

	// If back button is pressed blur event only works after reloading the page.
	window.onpageshow = function ( event ) {
		if ( event && event.persisted ) {
			window.location.reload();
		}
	};

	// Search for targets after some delay.
	setTimeout( function () {
		window.AdvAdsClickTracker.findTargets();
	}, 1500 );

	// Register handlers for wrappers.
	window.AdvAdsClickTracker.registerWrapperHandlers();
}, 'interactive' );
