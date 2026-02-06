<?php
/**
 * Quick edit fields
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

?>
<fieldset class="inline-edit-col-right advads-quick-edit" disabled>
	<div class="inline-edit-col">
		<div class="wp-clearfix">
			<label>
				<span class="title"><?php echo esc_html_e( 'Tracking', 'advanced-ads-tracking' ); ?></span>
				<?php // options renders from JS. ?>
				<select name="tracking_method"></select>
			</label>
		</div>

		<div class="wp-clearfix">
			<label>
				<span class="title"><?php esc_html_e( 'Target URL', 'advanced-ads-tracking' ); ?></span>
				<input type="text" name="target_url" value="">
			</label>
		</div>

		<div class="wp-clearfix">
			<label><input value="1" type="checkbox" name="cloak_link"><?php esc_html_e( 'Cloak link', 'advanced-ads-tracking' ); ?></label>
		</div>

		<div class="wp-clearfix">
			<label>
				<span class="title"><?php echo esc_html_e( 'Target window', 'advanced-ads-tracking' ); ?></span>
				<select name="target_window">
					<option value="default"><?php echo esc_html_e( 'default', 'advanced-ads-tracking' ); ?></option>
					<option value="same"><?php echo esc_html_e( 'same window', 'advanced-ads-tracking' ); ?></option>
					<option value="new"><?php echo esc_html_e( 'new window', 'advanced-ads-tracking' ); ?></option>
				</select>
			</label>
		</div>

		<div class="wp-clearfix">
			<label>
				<span class="title"><?php echo esc_html_e( 'Add "nofollow"', 'advanced-ads-tracking' ); ?></span>
				<select name="nofollow">
					<option value="default"><?php echo esc_html_e( 'default', 'advanced-ads-tracking' ); ?></option>
					<option value="1"><?php echo esc_html_e( 'yes', 'advanced-ads-tracking' ); ?></option>
					<option value="0"><?php echo esc_html_e( 'no', 'advanced-ads-tracking' ); ?></option>
				</select>
			</label>
		</div>

		<div class="wp-clearfix">
			<label>
				<span class="title"><?php echo esc_html_e( 'Add "sponsored"', 'advanced-ads-tracking' ); ?></span>
				<select name="sponsored">
					<option value="default"><?php echo esc_html_e( 'default', 'advanced-ads-tracking' ); ?></option>
					<option value="1"><?php echo esc_html_e( 'yes', 'advanced-ads-tracking' ); ?></option>
					<option value="0"><?php echo esc_html_e( 'no', 'advanced-ads-tracking' ); ?></option>
				</select>
			</label>
		</div>

		<div class="wp-clearfix">
			<label>
				<span class="title"><?php esc_html_e( 'Report recipient', 'advanced-ads-tracking' ); ?></span>
				<input type="text" name="report_recipient" value="">
			</label>
		</div>
	</div>
</fieldset>
