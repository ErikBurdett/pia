<?php
/**
 * Render Placement Bulk Edit Form
 *
 * @package AdvancedAds\Pro
 * @author  Advanced Ads <info@wpadvancedads.com>
 */

use AdvancedAds\Options;

?>
<fieldset class="inline-edit-col-right advanced-ads advads-bulk-edit">
	<div class="inline-edit-col">
		<div class="wp-clearfix">
			<label>
				<span class="title"><?php esc_html_e( 'Cache Busting', 'advanced-ads-pro' ); ?></span>
				<select name="cache_busting">
					<option value="">— <?php esc_html_e( 'No Change', 'advanced-ads-pro' ); ?> —</option>
					<option value="auto"><?php esc_html_e( 'Auto', 'advanced-ads-pro' ); ?></option>
					<option value="on"><?php esc_html_e( 'AJAX', 'advanced-ads-pro' ); ?></option>
					<option value="off"><?php esc_html_e( 'Off', 'advanced-ads-pro' ); ?></option>
				</select>
			</label>
		</div>
		<div class="wp-clearfix">
			<label>
				<span class="title"><?php esc_html_e( 'Lazy Loading', 'advanced-ads-pro' ); ?></span>
				<select name="lazy_loading">
					<option value="">— <?php esc_html_e( 'No Change', 'advanced-ads-pro' ); ?> —</option>
					<option value="enabled"><?php esc_html_e( 'Enabled', 'advanced-ads-pro' ); ?></option>
					<option value="disabled"><?php esc_html_e( 'Disabled', 'advanced-ads-pro' ); ?></option>
				</select>
			</label>
		</div>
		<div class="wp-clearfix">
			<label>
				<span class="title"><?php esc_html_e( 'Hide when empty', 'advanced-ads-pro' ); ?></span>
				<select name="cache_busting_empty">
					<option value="">— <?php esc_html_e( 'No Change', 'advanced-ads-pro' ); ?> —</option>
					<option value="1"><?php esc_html_e( 'Enabled', 'advanced-ads-pro' ); ?></option>
					<option value="2"><?php esc_html_e( 'Disabled', 'advanced-ads-pro' ); ?></option>
				</select>
				<span class="advads-help v-middle">
					<span class="advads-tooltip">
						<?php esc_html_e( 'Remove the placeholder if unfilled.', 'advanced-ads-pro' ); ?>
					</span>
				</span>
			</label>
		</div>
	</div>
</fieldset>
