( () => {
	for ( const unitSelect of document.getElementsByClassName(
		'advads-option-placement-parallax-unit'
	) ) {
		unitSelect.addEventListener( 'change', ( event ) => {
			event.target
				.closest( '.advads-option-placement-parallax-height' )
				.querySelector( 'input' ).max =
				event.target.value === 'vh' ? '100' : '';
		} );
	}
} )();
