<?php
/**
 * Underscore.js template.
 *
 * @package fusion-builder
 */

?>
<script type="text/template" id="fusion-builder-block-module-text-path-preview-template">
	<h4 class="fusion_module_title"><span class="fusion-module-icon {{ fusionAllElements[element_type].icon }}"></span>{{ fusionAllElements[element_type].name }}</h4>
	<# const pathType = 'undefined' !== typeof fusionAllElements[element_type].params.path_type.value[ params.path_type ] ? fusionAllElements[element_type].params.path_type.value[ params.path_type ] : params.path_type; #>
	<?php
	/* translators: The path type. */
	printf( esc_html__( 'Path Type = %s', 'fusion-builder' ), '{{ pathType }}' );
	?>
	<br />
	<?php
	/* translators: The path text. */
	printf( esc_html__( 'Text = %s', 'fusion-builder' ), '{{ params.element_content }}' );
	?>
</script>
