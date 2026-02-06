( function () {
	/**
	 * Placements to be tested against each other
	 *
	 * @type {{}}
	 */
	const candidates = {};

	/**
	 * Update the candidate placement list
	 *
	 * @param {string} slug   placement slug.
	 * @param {number} weight test weight.
	 */
	const updateCandidates = ( slug, weight ) => {
		if ( isNaN( weight ) && 'undefined' !== typeof candidates[ slug ] ) {
			delete candidates[ slug ];
			return;
		}

		candidates[ slug ] = weight;
	};

	/**
	 * Check the visibility of the various "Save new test" buttons.
	 *
	 * @param {Node} input test weight select input.
	 */
	const updateButton = ( input ) => {
		document.querySelectorAll( '.save-new-test' ).forEach( ( el ) => {
			el.classList.add( 'hidden' );
		} );
		if ( 1 < Object.keys( candidates ).length ) {
			input
				.closest( 'td' )
				.querySelector( '.save-new-test' )
				.classList.remove( 'hidden' );
		}
	};

	/**
	 * Save a new test into the database
	 */
	const saveNewTest = () => {
		wp.ajax
			.post( 'advads_new_placement_test', {
				nonce: document
					.getElementById( 'placement-ajax-nonce' )
					.innerText.trim(),
				candidates,
			} )
			.always( function () {
				document.location.reload();
			} );
	};

	/**
	 * Update current tests data
	 */
	const updateTests = () => {
		const testsData = {};
		const inputs = document
			.getElementById( 'placement-tests' )
			.querySelectorAll( 'input,select' );
		inputs.forEach( ( input ) => {
			const name = input.name;
			if ( ! name ) {
				return;
			}

			if ( 'checkbox' === input.type && ! input.checked ) {
				return;
			}

			testsData[ name ] = input.value;
		} );

		testsData.nonce = document
			.getElementById( 'placement-ajax-nonce' )
			.innerText.trim();

		inputs.forEach( ( el ) => {
			el.disabled = true;
		} );

		wp.ajax
			.post( 'advads_update_placement_tests', testsData )
			.always( function () {
				document.location.reload();
			} );
	};

	// Test weight edited.
	document
		.getElementById( 'posts-filter' )
		.addEventListener( 'change', ( ev ) => {
			if (
				! ev.target.classList.contains( 'advads-add-to-placement-test' )
			) {
				return;
			}

			const input = ev.target;
			updateCandidates( input.dataset.slug, parseInt( input.value, 10 ) );
			updateButton( input );
		} );

	// Create a new test.
	document
		.getElementById( 'posts-filter' )
		.addEventListener( 'click', ( ev ) => {
			if ( ! ev.target.classList.contains( 'save-new-test' ) ) {
				return;
			}

			if ( ! Object.keys( candidates ).length ) {
				return;
			}

			ev.target.classList.add( 'hidden' );
			document
				.querySelectorAll( '.advads-add-to-placement-test' )
				.forEach( ( el ) => {
					el.disabled = true;
				} );

			ev.preventDefault();
			saveNewTest();
		} );

	// Update current tests.
	const updateTestsButton = document.getElementById(
		'update-placement-tests'
	);

	if ( updateTestsButton ) {
		updateTestsButton.addEventListener( 'click', ( ev ) => {
			ev.preventDefault();
			updateTests();
		} );
	}
} )();
