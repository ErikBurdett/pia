<?php
/**
 * Underscore.js template
 *
 * @package fusion-builder
 * @since 3.1
 */

?>
<script type="text/html" id="tmpl-fusion_form_turnstile-shortcode">

<# if ( '' !== FusionApp.settings.turnstile_site_key && '' !== FusionApp.settings.turnstile_secret_key ) { #>
<div class="fusion-builder-placeholder"><?php esc_html_e( 'Cloudflare Turnstile will display here.', 'fusion-builder' ); ?></div>
<# } else { #>
<div class="fusion-builder-placeholder"><?php esc_html_e( 'Cloudflare Turnstile configuration error. Please check the Global Options settings and your Turnstile account settings.', 'fusion-builder' ); ?></div>
<# } #>
</script>
