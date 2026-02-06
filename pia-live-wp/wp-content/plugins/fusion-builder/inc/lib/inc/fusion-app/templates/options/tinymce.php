<?php
/**
 * Underscore.js template.
 *
 * @since 2.0
 * @package fusion-library
 */

?>
<textarea
	id="{{ param.param_name }}"
	class="fusion-editor-field"
	<# if ( 'undefined' !== typeof param.wpautop ) { #>
		data-wpautop="{{ param.wpautop }}"
	<# } #>	
	<# if ( param.placeholder ) { #>
		data-placeholder="{{ param.value }}"
	<# } #>
	<# if ( 'fusion_text' == atts.element_type ) { #>
		data-element="fusion_text"
	<# } #>
>{{ option_value }}</textarea>