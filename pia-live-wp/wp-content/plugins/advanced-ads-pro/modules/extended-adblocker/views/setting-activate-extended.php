<?php
/**
 * Activate adblocker.
 *
 * @package     Advanced_Ads_Pro\Module
 *
 * @var string  $option_name       array index name
 * @var string  $option_exclude    array index name
 * @var boolean $checked           True, when the option is checked.
 * @var boolean $exclude_checked   True, when the option is checked.
 * @var boolean $abf_enabled       True, when the adblocker disguise is enabled.
 */

?>
<label class="advads-eadblocker-radio-button">
	<input class="advanced-ads-adblocker-eab-method" name="<?php echo esc_attr( ADVADS_SETTINGS_ADBLOCKER . '[method]' ); ?>" type="radio" value="nothing"<?php checked( $method, 'nothing' ); ?> />
	<?php esc_html_e( 'No additional actions', 'advanced-ads-pro' ); ?>
</label>

<label class="advads-eadblocker-radio-button">
	<input class="advanced-ads-adblocker-eab-method" name="<?php echo esc_attr( ADVADS_SETTINGS_ADBLOCKER . '[method]' ); ?>" type="radio" value="overlay"<?php checked( $method, 'overlay' ); ?> />
	<?php esc_html_e( 'Overlay', 'advanced-ads-pro' ); ?>
	<span class="advads-help">
		<span class="advads-tooltip" style="position: fixed; left: 628px; top: 332px;">
			<?php
			esc_html_e(
				'Show a custom overlay to users with an ad blocker enabled, prompting them to turn it off on your website.',
				'advanced-ads-pro'
			);
			?>
		</span>
	</span>
</label>

<label class="advads-eadblocker-radio-button">
	<input class="advanced-ads-adblocker-eab-method" name="<?php echo esc_attr( ADVADS_SETTINGS_ADBLOCKER . '[method]' ); ?>" type="radio" value="redirect"<?php checked( $method, 'redirect' ); ?> />
	<?php esc_html_e( 'Redirect', 'advanced-ads-pro' ); ?>
	<span class="advads-help">
		<span class="advads-tooltip" style="position: fixed; left: 628px; top: 332px;">
			<?php
			esc_html_e(
				'Automatically redirect users with ad blockers enabled to an internal page. Content access is granted after turning off the ad blocker.',
				'advanced-ads-pro'
			);
			?>
		</span>
	</span>
</label>

<p class="description advads-eab-overlay-notice">
	<?php esc_html_e( 'Activate the ad blocker disguise above to display the overlay.', 'advanced-ads-pro' ); ?>
</p>

<div id="advanced-ads-adblocker-overlay-options" <?php echo 'overlay' === $method ? '' : 'style="display: none;"'; ?>>
	<?php
		$this->render_settings_overlay_content();
		$this->render_settings_dismissible();
		$this->render_settings_styling();
	?>
</div>

<div id="advanced-ads-adblocker-redirect-options" <?php echo 'redirect' === $method ? '' : 'style="display: none;"'; ?>>
	<h4>
		<?php esc_html_e( 'Redirect URL', 'advanced-ads-pro' ); ?>
		<span class="advads-help">
			<span class="advads-tooltip">
				<?php
				esc_html_e(
					'Enter a specific page on your domain to which users with activated AdBlocker should be automatically redirected.',
					'advanced-ads-pro'
				);
				?>
			</span>
		</span>
	</h4>
	<input class="width-100" type="text" name="<?php echo esc_attr( ADVADS_SETTINGS_ADBLOCKER . '[redirect][url]' ); ?>" value="<?php echo esc_attr( $redirect_url ); ?>" />
</div>

<div id="advanced-ads-adblocker-option-exclude" <?php echo ( 'redirect' === $method || 'overlay' === $method ) ? '' : 'style="display: none;"'; ?>>
	<?php $this->render_settings_exclude(); ?>
</div>
