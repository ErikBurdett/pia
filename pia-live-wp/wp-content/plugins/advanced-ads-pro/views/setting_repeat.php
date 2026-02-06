<?php //phpcs:ignoreFile ?>
<p>
	<label>
		<input type="checkbox" name="advads[placements][options][repeat]" value="1"<?php checked( $data['repeat'] ?? 0, 1 ); ?>/><?php esc_html_e( 'repeat the position', 'advanced-ads-pro' ); ?>
	</label>
</p>
