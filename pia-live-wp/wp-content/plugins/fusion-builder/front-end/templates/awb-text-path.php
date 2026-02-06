<?php
/**
 * Underscore.js template
 *
 * @package fusion-builder
 * @since 2.0
 */

?>
<script type="text/html" id="tmpl-awb_text_path-shortcode">
	<div {{{ _.fusionGetAttributes( attr ) }}}>

		<# if ( ! _.isEmpty( customSvg ) ) { #>
			{{{customSvg.svgTag}}}
		<# } else { #>
			<svg width="{{ svgData.width }}" height="{{ svgData.height }}" viewBox="{{ svgData.viewbox }}">
		<# } #>

			<# if ( 'yes' === values.path_animation ) { #>
				<g><animateTransform {{{ _.fusionGetAttributes( pathAnimation ) }}} />

				<# if ( 'wiggle' === values.path_animation_type ) { #>
					<animateTransform {{{ _.fusionGetAttributes( pathAnimationSkewY ) }}} />
				<# } #>
			<# } #>

			<# if ( ! _.isEmpty( customSvg ) ) { #>
				<# const customPath = customSvg.svgPath.replace( '<path', '<path ' + _.fusionGetAttributes( path ) ); #>
				{{{customPath}}}</path>
			<# } else { #>
				<path {{{ _.fusionGetAttributes( path ) }}}></path>
			<# } #>
			
			<text {{{ _.fusionGetAttributes( text ) }}}>
				<textPath {{{ _.fusionGetAttributes( textPath ) }}}>
					<# if ( 'yes' === values.text_animation && 'none' !== values.text_animation_direction ) { #>
						<animate {{{ _.fusionGetAttributes( textAnimation ) }}} />
					<# } #>

					<# if ( 'yes' === values.text_animation && '1.0' !== values.text_animation_font_size_factor ) { #>
						<animate {{{ _.fusionGetAttributes( textAnimationFontSize ) }}} />
					<# } #>

					<# if ( '' !== values.link ) { #>
						<a {{{ _.fusionGetAttributes( link ) }}}>{{{ FusionPageBuilderApp.renderContent( values.element_content, cid, false ) }}}</a>
					<# } else { #>
						{{{ FusionPageBuilderApp.renderContent( values.element_content, cid, false ) }}}
					<# } #>
				</textPath>
			</text>

			<# if ( 'yes' === values.path_animation ) { #>
				</g>
			<# } #>
		</svg>
	</div>
</script>
