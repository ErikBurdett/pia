<?php
/**
 * Underscore.js template
 *
 * @package fusion-builder
 * @since 2.0
 */

?>
<script type="text/html" id="tmpl-fusion_tagline_box-shortcode">

<style type="text/css">
	.reading-box-container-{{ cid }} .element-bottomshadow:before,.reading-box-container-{{ cid }} .element-bottomshadow:after{opacity:{{ values.shadowopacity }};}
</style>

<div {{{ _.fusionGetAttributes( attr ) }}}>
	<div {{{ _.fusionGetAttributes( attrReadingBox ) }}}>
		<# if ( '' !== values.description || '' !== values.element_content ) { #>

			<# if ( '' !== values.link && '' !== values.button && 'center' !== values.content_alignment ) { #>
				<a {{{ _.fusionGetAttributes( desktopAttrButton ) }}}><span {{{ _.fusionGetAttributes( buttonSpanAttr ) }}}>{{{ values.button }}}</span></a>
			<# } #>

			<# if ( '' !== values.title ) { #>
				<h2 {{{ _.fusionGetAttributes( titleAttr ) }}}>{{{ values.title }}}</h2>
			<# } #>

			<# if ( '' !== values.description ) { #>
				<div {{{ _.fusionGetAttributes( descriptionAttr ) }}}>{{{ values.description }}}</div>
			<# } #>

			<# if ( '' !== values.element_content ) { #>
				<div {{{ _.fusionGetAttributes( contentAttr ) }}}>{{{ FusionPageBuilderApp.renderContent( values.element_content, cid, false ) }}}</div>
			<# } #>

			<div class="fusion-clearfix"></div>

		<# } else if ( 'center' === values.content_alignment ) { #>
			<# if ( '' !== values.title ) { #>
				<h2 {{{ _.fusionGetAttributes( titleAttr ) }}}>{{{ values.title }}}</h2>
			<# } #>

		<# } else { #>
			<div class="fusion-reading-box-flex">
				<# if ( 'left' === values.content_alignment ) { #>
					<# if ( '' !== values.title ) { #>
						<h2 {{{ _.fusionGetAttributes( titleAttr ) }}}>{{{ values.title }}}</h2>
					<# } #>

					<# if ( '' !== values.link && '' !== values.button && 'center' !== values.content_alignment ) { #>
						<a {{{ _.fusionGetAttributes( desktopAttrButton ) }}}><span {{{ _.fusionGetAttributes( buttonSpanAttr ) }}}>{{{ values.button }}}</span></a>
					<# } #>
				<# } else { #>
					<# if ( '' !== values.link && '' !== values.button && 'center' !== values.content_alignment ) { #>
						<a {{{ _.fusionGetAttributes( desktopAttrButton ) }}}><span {{{ _.fusionGetAttributes( buttonSpanAttr ) }}}>{{{ values.button }}}</span></a>
					<# } #>

					<# if ( '' !== values.title ) { #>
						<h2 {{{ _.fusionGetAttributes( titleAttr ) }}}>{{{ values.title }}}</h2>
					<# } #>
				<# } #>
			</div>
		<# } #>

		<# if ( '' !== values.link && '' !== values.button ) { #>
			<a {{{ _.fusionGetAttributes( mobileAttrButton ) }}}><span {{{ _.fusionGetAttributes( buttonSpanAttr ) }}}>{{{ values.button }}}</span></a>
		<# } #>
	</div>

	<# if ( 'yes' === values.shadow ) { #>
		<svg style="opacity:{{{ values.shadowopacity }}};" xmlns="http://www.w3.org/2000/svg" version="1.1" width="100%" viewBox="0 0 600 28" preserveAspectRatio="none"><g clip-path="url(#a)"><mask id="b" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="0" y="0" width="600" height="28"><path d="M0 0h600v28H0V0Z" fill="#fff"/></mask><g filter="url(#c)" mask="url(#b)"><path d="M16.439-18.667h567.123v30.8S438.961-8.4 300-8.4C161.04-8.4 16.438 12.133 16.438 12.133v-30.8Z" fill="#000"/></g></g><defs><clipPath id="a"><path fill="#fff" d="M0 0h600v28H0z"/></clipPath><filter id="c" x="5.438" y="-29.667" width="589.123" height="52.8" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feBlend in="SourceGraphic" in2="BackgroundImageFix" result="shape"/><feGaussianBlur stdDeviation="5.5" result="effect1_foregroundBlur_3983_183"/></filter></defs></svg>
	<# } #>
</div>

</script>
