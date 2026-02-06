<?php
/**
 * Underscore.js template
 *
 * @package fusion-builder
 * @since 3.1
 */

?>
<script type="text/html" id="tmpl-fusion_form_image_select-shortcode">
{{{ outerWrapper }}}

	<fieldset class="fusion-child-element">
		<# if ( 'above' === labelPosition ) { #>
			<legend>{{{ elementLabel }}}</legend>
		<# } #>
	</fieldset>

	<# if ( 'above' !== labelPosition ) { #>
		{{{ elementLabel }}}
	<# } #>
</div>
</script>
