<?php
/**
 * Ad Blocker overlay options 'overlay again and dismiss button'
 *
 * @package     Advanced_Ads_Pro\Module
 *
 * @var string  $option_time_freq       The array index name for the overlay timing option.
 * @var string  $button_text            The option value for the dismiss button text.
 * @var string  $time_freq              The option value for the overlay timing.
 * @var boolean $hide_checked           True, when the hide dismiss button option is checked.
 * @var string  $option_dismiss_style   The array index name for the dismiss button styling option.
 * @var string  $dismiss_style          The CSS value for the dismiss button styling.
 */

?>
<h4>
	<?php esc_html_e( 'When to show overlay again?', 'advanced-ads-pro' ); ?>
	<span class="advads-help">
		<span class="advads-tooltip">
			<?php esc_html_e( 'Set the timing for the overlay to reappear after being dismissed.', 'advanced-ads-pro' ); ?>
		</span>
	</span>
</h4>

<div class="advads-settings-margin">
	<select name="<?php echo esc_attr( ADVADS_SETTINGS_ADBLOCKER . "[overlay][$option_time_freq]" ); ?>">
		<option value="everytime" <?php selected( $time_freq, 'everytime' ); ?>>
			<?php esc_html_e( 'Everytime', 'advanced-ads-pro' ); ?>
		</option>
		<option value="hour" <?php selected( $time_freq, 'hour' ); ?>>
			<?php esc_html_e( '1 hour', 'advanced-ads-pro' ); ?>
		</option>
		<option value="day" <?php selected( $time_freq, 'day' ); ?>>
			<?php esc_html_e( '1 day', 'advanced-ads-pro' ); ?>
		</option>
		<option value="week" <?php selected( $time_freq, 'week' ); ?>>
			<?php esc_html_e( '1 week', 'advanced-ads-pro' ); ?>
		</option>
		<option value="month" <?php selected( $time_freq, 'month' ); ?>>
			<?php esc_html_e( '1 month', 'advanced-ads-pro' ); ?>
		</option>
		<option value="never" <?php selected( $time_freq, 'never' ); ?>>
			<?php esc_html_e( 'Never', 'advanced-ads-pro' ); ?>
		</option>
	</select>
</div>

<h4><?php esc_html_e( 'Dismiss button', 'advanced-ads-pro' ); ?></h4>
<input id="<?php echo esc_attr( ADVADS_SETTINGS_ADBLOCKER ); ?>-dismiss-button-input"
	type="checkbox"
	value="1"
	name="<?php echo esc_attr( ADVADS_SETTINGS_ADBLOCKER . '[overlay][hide_dismiss]' ); ?>"
	<?php checked( $hide_checked, 1, true ); ?>>
<label for="<?php echo esc_attr( ADVADS_SETTINGS_ADBLOCKER ); ?>-dismiss-button-input">
	<?php esc_html_e( 'Hide dismiss button', 'advanced-ads-pro' ); ?>
	<span class="advads-help">
		<span class="advads-tooltip">
			<?php esc_html_e( 'Disabling the dismiss button significantly limits site interaction.', 'advanced-ads-pro' ); ?>
		</span>
	</span>
</label>

<div id="<?php echo esc_attr( ADVADS_SETTINGS_ADBLOCKER ); ?>-dismiss-options">
	<h4>
		<?php esc_html_e( 'Dismiss button text', 'advanced-ads-pro' ); ?>
		<span class="advads-help">
			<span class="advads-tooltip">
				<?php esc_html_e( 'Enter the text that you want to appear on the dismiss button.', 'advanced-ads-pro' ); ?>
			</span>
		</span>
	</h4>
	<input type="text"
		name="<?php echo esc_attr( ADVADS_SETTINGS_ADBLOCKER . '[overlay][dismiss_text]' ); ?>"
		value="<?php echo esc_attr( $button_text ); ?>"
		placeholder="<?php esc_html_e( 'Dismiss', 'advanced-ads-pro' ); ?>" >
	<?php if ( ! empty( $button_text ) ) : ?>
	<p class="description"><?php esc_html_e( 'Empty and save to revert to defaults.', 'advanced-ads-pro' ); ?></p>
	<?php endif; ?>

	<h4>
		<?php esc_html_e( 'Dismiss button styling', 'advanced-ads-pro' ); ?>
		<span class="advads-help">
			<span class="advads-tooltip">
				<?php esc_html_e( 'Insert CSS to customize the dismiss button layout.', 'advanced-ads-pro' ); ?>
			</span>
		</span>
	</h4>
	<input class="width-100"
		type="text"
		name="<?php echo esc_attr( ADVADS_SETTINGS_ADBLOCKER . "[overlay][$option_dismiss_style]" ); ?>"
		value="<?php echo esc_attr( $dismiss_style ); ?>"
		placeholder="e.g. background-color: black; border-radius: 20px;">
	<?php if ( ! empty( $dismiss_style ) ) : ?>
	<p class="description"><?php esc_html_e( 'Empty and save to revert to defaults.', 'advanced-ads-pro' ); ?></p>
	<?php endif; ?>
</div>
