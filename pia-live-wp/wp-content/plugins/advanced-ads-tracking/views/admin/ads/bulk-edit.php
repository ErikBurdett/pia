<?php
/**
 * Bulk edit fields
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

define( 'NO_CHANGE', __( '— No Change —', 'advanced-ads-tracking' ) );
?>

<fieldset class="inline-edit-col-right advads-bulk-edit">
	<div class="advads-bulk-edit-grid">
		<label>
			<span class="title"><?php echo esc_html_e( 'Tracking', 'advanced-ads-tracking' ); ?></span>
			<select name="tracking_method">
				<option value="-1"><?php echo esc_html( NO_CHANGE ); ?></option>
				<option value="default"><?php esc_html_e( 'default (impressions & clicks)', 'advanced-ads-tracking' ); ?></option>
				<option value="disabled"><?php esc_html_e( 'disabled', 'advanced-ads-tracking' ); ?></option>
				<option value="clicks"><?php esc_html_e( 'clicks only', 'advanced-ads-tracking' ); ?></option>
				<option value="impressions"><?php esc_html_e( 'impressions only', 'advanced-ads-tracking' ); ?></option>
				<option value="enabled"><?php esc_html_e( 'impressions & clicks', 'advanced-ads-tracking' ); ?></option>
			</select>
		</label>

		<label>
			<span class="title"><?php esc_html_e( 'Target URL', 'advanced-ads-tracking' ); ?></span>
			<input type="text" name="target_url" value="" placeholder="<?php echo esc_html( NO_CHANGE ); ?>">
		</label>

		<label>
			<span class="title"><?php echo esc_html_e( 'Cloak link', 'advanced-ads-tracking' ); ?></span>
			<select name="cloak_link">
				<option value="-1"><?php echo esc_html( NO_CHANGE ); ?></option>
				<option value="on"><?php echo esc_html_e( 'Enabled', 'advanced-ads-tracking' ); ?></option>
				<option value="off"><?php echo esc_html_e( 'Disabled', 'advanced-ads-tracking' ); ?></option>
			</select>
		</label>

		<label>
			<span class="title"><?php echo esc_html_e( 'Target window', 'advanced-ads-tracking' ); ?></span>
			<select name="target_window">
				<option value="-1"><?php echo esc_html( NO_CHANGE ); ?></option>
				<option value="default"><?php echo esc_html_e( 'default', 'advanced-ads-tracking' ); ?></option>
				<option value="same"><?php echo esc_html_e( 'same window', 'advanced-ads-tracking' ); ?></option>
				<option value="new"><?php echo esc_html_e( 'new window', 'advanced-ads-tracking' ); ?></option>
			</select>
		</label>

		<label>
			<span class="title"><?php echo esc_html_e( 'Add "nofollow"', 'advanced-ads-tracking' ); ?></span>
			<select name="nofollow">
				<option value="-1"><?php echo esc_html( NO_CHANGE ); ?></option>
				<option value="default"><?php echo esc_html_e( 'default', 'advanced-ads-tracking' ); ?></option>
				<option value="1"><?php echo esc_html_e( 'yes', 'advanced-ads-tracking' ); ?></option>
				<option value="0"><?php echo esc_html_e( 'no', 'advanced-ads-tracking' ); ?></option>
			</select>
		</label>

		<label>
			<span class="title"><?php echo esc_html_e( 'Add "sponsored"', 'advanced-ads-tracking' ); ?></span>
			<select name="sponsored">
				<option value="-1"><?php echo esc_html( NO_CHANGE ); ?></option>
				<option value="default"><?php echo esc_html_e( 'default', 'advanced-ads-tracking' ); ?></option>
				<option value="1"><?php echo esc_html_e( 'yes', 'advanced-ads-tracking' ); ?></option>
				<option value="0"><?php echo esc_html_e( 'no', 'advanced-ads-tracking' ); ?></option>
			</select>
		</label>

		<label>
			<span class="title"><?php esc_html_e( 'Report recipient', 'advanced-ads-tracking' ); ?></span>
			<input type="text" name="report_recipient" value="" placeholder="<?php echo esc_html( NO_CHANGE ); ?>">
		</label>
	</div>
</fieldset>
