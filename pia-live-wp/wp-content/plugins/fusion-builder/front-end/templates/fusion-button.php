<?php
/**
 * Underscore.js template
 *
 * @package fusion-builder
 * @since 2.0
 */

?>
<script type="text/html" id="tmpl-fusion_button-shortcode">
<#
let iconHtml           = '',
	hoverIconHtml      = '',
	iconAndHoverHtml   = '',
	buttonText         = '',
	buttonHoverText    = '',
	buttonAndHoverHtml = '',
	centered           = '';

if ( values.icon || ( 'yes' === values.enable_hover_text_icon && values.hover_icon ) ) {
	if ( values.icon ) {
		iconHtml = '<i' + _.fusionGetAttributes( iconAttr ) + '></i>';
	}

	if ( 'yes' === values.enable_hover_text_icon ) {
		if ( values.hover_icon ) {
			hoverIconHtml = '<i' + _.fusionGetAttributes( hoverIconAttr ) + '></i>';
		}

		if ( 'icon_position' === values.hover_transition ) {
			iconAndHoverHtml = iconHtml + hoverIconHtml;
		} else {
			iconAndHoverHtml = '<div class="awb-button__hover-content">' + iconHtml + hoverIconHtml + '</div>';
		}
	} else {
		iconAndHoverHtml = iconHtml;

		if ( 'icon_position' === values.hover_transition ) {
			iconAndHoverHtml += iconHtml;
		}		
	}

	if ( 'yes' === values.icon_divider ) {
		iconAndHoverHtml = '<span class="fusion-button-icon-divider button-icon-divider-' + values.icon_position + '">' + iconAndHoverHtml + '</span>';
	}
}

buttonText = '<span' + _.fusionGetAttributes( textAttr ) + '>' + FusionPageBuilderApp.renderContent( values.element_content, cid, false ) + '</span>';

if ( 'yes' === values.enable_hover_text_icon ) {
	buttonHoverText = '<span' + _.fusionGetAttributes( hoverTextAttr ) + '>' + values.hover_text + '</span>';

	centered           = '' === iconHtml &&  '' === hoverIconHtml ? ' awb-button__hover-content--centered' : '';
	buttonAndHoverHtml = '<div class="awb-button__hover-content awb-button__hover-content--default' + centered + '">' + buttonText + buttonHoverText + '</div>';

	if ( values.element_content.length < values.hover_text.length ) {
		buttonAndHoverHtml = '<div class="awb-button__hover-content awb-button__hover-content--default' + centered + '">' + buttonHoverText + buttonText + '</div>';
	} else {
		buttonAndHoverHtml = '<div class="awb-button__hover-content awb-button__hover-content--default' + centered + '">' + buttonText + buttonHoverText + '</div>';
	}	

	if ( ( values.icon || values.hover_icon ) && ! values.element_content && ! values.hover_text ) {
		iconAndHoverHtml   = '<div class="awb-button__hover-content awb-button__hover-content--default awb-button__hover-content--centered">' + iconHtml + hoverIconHtml + '</div>';
		buttonAndHoverHtml = '';
	}

	if ( values.icon && ! values.hover_icon && ! values.element_content && values.hover_text ) {
		iconAndHoverHtml   = '';
		buttonAndHoverHtml = '<div class="awb-button__hover-content awb-button__hover-content--reversed awb-button__hover-content--centered">' + buttonHoverText + iconHtml + '</div>';
	}

	if ( ! values.icon && values.hover_icon && values.element_content && ! values.hover_text ) {
		iconAndHoverHtml   = '';
		buttonAndHoverHtml = '<div class="awb-button__hover-content awb-button__hover-content--default awb-button__hover-content--centered">' + buttonText + hoverIconHtml + '</div>';						
	}
} else if ( 'text_slide_up' === values.hover_transition || 'text_slide_down' === values.hover_transition ) {
	centered = '' === iconHtml ? 'awb-button__hover-content--centered' : '';
	buttonAndHoverHtml = '<div class="awb-button-text-transition ' + centered + '">' + buttonText + buttonText + '</div>';
} else {
	buttonAndHoverHtml = buttonText;
}

innerContent = ( 'left' === values.icon_position ) ? iconAndHoverHtml + buttonAndHoverHtml : buttonAndHoverHtml + iconAndHoverHtml;
#>

<div {{{ _.fusionGetAttributes( wrapperAttr ) }}}>
	<# if ( 'undefined' !== typeof values.button_el_type && 'link' !== values.button_el_type ) { #>
		<button {{{ _.fusionGetAttributes( attr ) }}} >
			{{{ innerContent }}}
		</button>
	<# } else { #>
		<a {{{ _.fusionGetAttributes( attr ) }}} >
			{{{ innerContent }}}
	</a>
	<# } #>
</div>
</script>
