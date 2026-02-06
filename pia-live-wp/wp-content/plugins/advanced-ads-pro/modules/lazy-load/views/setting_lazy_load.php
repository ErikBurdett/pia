<div class="advanced-ads-inputs-dependent-on-cb" <?php if ( $cb_off ) { echo 'style="display:none;"'; } ?>>
<label title="<?php esc_html_e( 'enabled', 'advanced-ads-pro' ); ?>">
<input type="radio" name="advads[placements][options][lazy_load]" value="enabled" <?php
	checked( $checked, 'enabled' ); ?> /><?php esc_html_e( 'enabled', 'advanced-ads-pro' ); ?>
</label>
<label title="<?php esc_html_e( 'disabled', 'advanced-ads-pro' ); ?>">
<input type="radio" name="advads[placements][options][lazy_load]" value="disabled" <?php
	checked( $checked, 'disabled' ); ?> /><?php esc_html_e( 'disabled', 'advanced-ads-pro' ); ?>
</label>
</div>
<p class="advads-notice-inline advads-idea" <?php if ( ! $cb_off ) { echo 'style="display:none;"'; } ?>><?php esc_html_e( 'Works only with cache-busting enabled', 'advanced-ads-pro' ); ?></p>
