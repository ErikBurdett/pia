<?php
/**
 * Underscore.js template
 *
 * @package fusion-builder
 * @since 2.0
 */

?>
<script type="text/html" id="tmpl-fusion_title-shortcode">
<# if ( 'rotating' === title_type ) { #>
	<div {{{ _.fusionGetAttributes( attr ) }}}>
		<{{ title_tag }} {{{ _.fusionGetAttributes( headingAttr ) }}}>
			<span class="fusion-highlighted-text-prefix">{{{before_text}}}</span>
			<# if ( 0 < rotation_text.length ) { #>
				<span {{{ _.fusionGetAttributes( animatedAttr ) }}}>
					<span class="fusion-animated-texts">
						<# _.each( rotation_text, function( text ) {
							if ( '' !==  text ) { #>
								<span {{{ _.fusionGetAttributes( rotatedAttr ) }}} >{{{text}}}</span>
							<# }
						} ); #>
					</span>
				</span>
			<# } #>
			<span class="fusion-highlighted-text-postfix">{{{after_text}}}</span>
		</{{ title_tag }}>
	</div>
<# } else if ( 'highlight' === title_type ) { #>
	<div {{{ _.fusionGetAttributes( attr ) }}}>
		<{{ title_tag }} {{{ _.fusionGetAttributes( headingAttr ) }}}>
			<span class="fusion-highlighted-text-prefix">{{{before_text}}}</span>
			<# if ( '' !== highlight_text ) { #>
				<span class="fusion-highlighted-text-wrapper">
					<span {{{ _.fusionGetAttributes( animatedAttr ) }}}>{{{highlight_text}}}</span>
					<#
						const highlightEffects = {
								circle: [ 'M344.6,40.1c0,0-293-3.4-330.7,40.3c-5.2,6-3.5,15.3,3.3,19.4c65.8,39,315.8,42.3,451.2-3 c6.3-2.1,12-6.1,16-11.4C527.9,27,242,16.1,242,16.1' ],
								underline_zigzag: [ 'M6.1,133.6c0,0,173.4-20.6,328.3-14.5c154.8,6.1,162.2,8.7,162.2,8.7s-262.6-4.9-339.2,13.9 c0,0,113.8-6.1,162.9,6.9' ],
								x: [ 'M25.8,37.1c0,0,321.2,56.7,435.5,82.3', 'M55.8,108.7c0,0,374-78.3,423.6-76.3' ],
								strikethrough: [ 'M22.2,93.2c0,0,222.1-11.3,298.8-15.8c84.2-4.9,159.1-4.7,159.1-4.7' ],
								curly: [ 'M9.4,146.9c0,0,54.4-60.2,102.1-11.6c42.3,43.1,84.3-65.7,147.3,0.9c37.6,39.7,79.8-52.6,123.8-14.4 c68.6,59.4,107.2-7,107.2-7' ],
								diagonal_bottom_left: [ 'M6.5,127.1C10.6,126.2,316.9,24.8,497,23.9' ],
								diagonal_top_left: [ 'M7.2,28.5c0,0,376.7,64.4,485.2,93.4' ],
								double: [ 'M21.7,145.7c0,0,192.2-33.7,456.3-14.6', 'M13.6,28.2c0,0,296.2-22.5,474.9-5.4' ],
								double_underline: [ 'M10.3,130.6c0,0,193.9-24.3,475.2-11.2', 'M38.9,148.9c0,0,173.8-35.3,423.3-11.8' ],
								underline: [ 'M8.1,146.2c0,0,240.6-55.6,479-13.8' ]
							},
							paths              = highlightEffects[ values.highlight_effect ];
						let style              = '';
					#>
					<# if ( 'object' === typeof paths ) { #>
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 500 150" preserveAspectRatio="none">
						<# if ( 'yes' === values.highlight_smudge_effect ) { #>
							<# style = 'style="stroke: url(#stroke-gradient-' + cid + ')"'; #>
							<defs>
								<linearGradient id="stroke-gradient-{{{cid}}}" x1="0%" y1="0%" x2="100%" y2="0%">
									<stop offset="0%" style="stop-color:{{{values.highlight_color_min}}};" />
									<stop offset="5%" style="stop-color:{{{values.highlight_color_max}}};" />
									<stop offset="100%" style="stop-color:{{{values.highlight_color_inter}}};" />
								</linearGradient>
							</defs>
						<# } #>
						<# paths.forEach( function ( current ) { #>
							<path {{{style}}} d="{{{current}}}"></path>
						<# } ); #>
						</svg>
					<# } #>
				</span>
			<# } #>
			<span class="fusion-highlighted-text-postfix">{{{after_text}}}</span>
		</{{ title_tag }}>
	</div>
<# } else if ( 'marquee' === title_type ) { #>
	<div {{{ _.fusionGetAttributes( attr ) }}}>
		<{{ title_tag }} {{{ _.fusionGetAttributes( headingAttr ) }}}>
			<#
			let content = 'off' !== title_link ? '<a href="#">' + FusionPageBuilderApp.renderContent( output, cid, false ) + '</a>' : FusionPageBuilderApp.renderContent( output, cid, false );
			let marquee = '';

			marquee = '<span ' + _.fusionGetAttributes( marqueeAttr ) + '>' + content + '</span>';
			content = marquee + marquee;
			#>
			{{{ content }}}
		</{{ title_tag }}>
	</div>	
<# } else if ( -1 !== style_type.indexOf( 'underline' ) || -1 !== style_type.indexOf( 'none' ) ) { #>
<div {{{ _.fusionGetAttributes( attr ) }}}>
	<{{ title_tag }} {{{ _.fusionGetAttributes( headingAttr ) }}}>
		<# if ( 'off' !== title_link ) { #>
			<a href="#"> {{{ FusionPageBuilderApp.renderContent( output, cid, false ) }}} </a>
		<# } else { #>
			{{{ FusionPageBuilderApp.renderContent( output, cid, false ) }}}
		<# } #>
	</{{ title_tag }}>
</div>
<# } else { #>
	<# if ( 'right' == content_align && ! isFlex ) { #>
<div {{{ _.fusionGetAttributes( attr ) }}}>
	<div class="title-sep-container">
		<div {{{ _.fusionGetAttributes( separatorAttr ) }}}></div>
	</div>
	<span class="awb-title-spacer"></span>
	<{{ title_tag }} {{{ _.fusionGetAttributes( headingAttr ) }}}>
	<# if ( 'off' !== title_link ) { #>
		<a href="#"> {{{ FusionPageBuilderApp.renderContent( output, cid, false ) }}} </a>
	<# } else { #>
		{{{ FusionPageBuilderApp.renderContent( output, cid, false ) }}}
	<# } #>
	</{{ title_tag }}>
</div>
	<# } else if ( 'center' == content_align || isFlex ) { #>
<div {{{ _.fusionGetAttributes( attr ) }}}>
	<#
		var leftClasses            = 'title-sep-container title-sep-container-left',
			rightClasses           = 'title-sep-container title-sep-container-right',
			additionalLeftClasses  = '',
			additionalRightClasses = '';

		_.each( ['large', 'medium', 'small' ], function( responsiveSize ) {
			if ( ! content_align_sizes[ responsiveSize ] || 'center' === content_align_sizes[ responsiveSize ] ) {
				return;
			}
			if ( 'left' == content_align_sizes[ responsiveSize ] ) {
				additionalLeftClasses += ' fusion-no-' + responsiveSize + '-visibility';
			} else {
				additionalRightClasses += ' fusion-no-' + responsiveSize + '-visibility';
			}
		} );

		leftClasses  += additionalLeftClasses;
		rightClasses += additionalRightClasses;
	#>
	<div class="{{{ leftClasses }}}">
		<div {{{ _.fusionGetAttributes( separatorAttr ) }}}></div>
	</div>
	<span class="awb-title-spacer{{{ additionalLeftClasses }}}"></span>
	<{{ title_tag }} {{{ _.fusionGetAttributes( headingAttr ) }}}>
		<# if ( 'off' !== title_link ) { #>
			<a href="#"> {{{ FusionPageBuilderApp.renderContent( output, cid, false ) }}} </a>
		<# } else { #>
			{{{ FusionPageBuilderApp.renderContent( output, cid, false ) }}}
		<# } #>
	</{{ title_tag }}>
	<span class="awb-title-spacer{{{ additionalRightClasses }}}"></span>
	<div class="{{{ rightClasses }}}">
		<div {{{ _.fusionGetAttributes( separatorAttr ) }}}></div>
	</div>
</div>
	<# } else { #>
<div {{{ _.fusionGetAttributes( attr ) }}}>
	<{{ title_tag }} {{{ _.fusionGetAttributes( headingAttr ) }}}>
		<# if ( 'off' !== title_link ) { #>
			<a href="#"> {{{ FusionPageBuilderApp.renderContent( output, cid, false ) }}} </a>
		<# } else { #>
			{{{ FusionPageBuilderApp.renderContent( output, cid, false ) }}}
		<# } #>

	</{{ title_tag }}>
	<span class="awb-title-spacer"></span>
	<div class="title-sep-container">
		<div {{{ _.fusionGetAttributes( separatorAttr ) }}}></div>
	</div>
</div>
	<# } #>
<# } #>
</script>
