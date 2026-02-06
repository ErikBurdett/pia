/**
 * Rank Math SEO Integration
 */
( function( $ ) {
	const FusionRankMath = function() {
		this.pluginName = 'fusion-rank-math-integration';

		this.hooks();
		this.events();
		this.getContent = this.getContent.bind( this );
	};

	FusionRankMath.prototype.hooks = function() {
		wp.hooks.addFilter( 'rank_math_content', this.pluginName, this.getContent );
	};

	FusionRankMath.prototype.getContent = function() {
		const content = $( '#fusion-builder-rendered-content' ).val();
		return content;
	};

	FusionRankMath.prototype.events = function() {
		$( document ).on( 'fusion-builder-content-updated', function() {
			$.ajax( {
					method: 'POST',
					url: window.fusionBuilderConfig.rest_url + 'awb/rendered_content',
					data: { content: window.fusionBuilderGetContent( 'content' ) },
					beforeSend: function ( xhr ) {
						xhr.setRequestHeader( 'X-WP-Nonce', window.fusionBuilderConfig.rest_nonce );
					}
			} ).done( function( result ) {
				$( '#fusion-builder-rendered-content' ).val( result.content );
				setTimeout( () => {
					window.rankMathEditor.refresh( 'content' );
				}, 100 );
			} );
		} );
	};

	$( document ).one( 'fusion-builder-content-updated', function() {

		// Check if rank math is active.
		if ( 'undefined' !== typeof window.rankMathEditor ) {
			new FusionRankMath();
		}
	} );

}( jQuery ) );
