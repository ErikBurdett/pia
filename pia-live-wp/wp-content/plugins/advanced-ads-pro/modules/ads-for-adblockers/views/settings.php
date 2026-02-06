<?php
/**
 * Render 'Ads For Adblocker' settings
 *
 * @package AdvancedAds\Pro
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.26.0
 *
 * @var array $options             Advanced Ads Pro options.
 * @var bool  $module_enabled      if the ads-for-adblockers module is enabled.
 * @var string $cb_dashicon_class  CSS class for Cache Busting on or off.
 * @var string $ab_dashicon_class  CSS class for Ad block disguise on or off.
 */

?>
<input name="<?php echo esc_attr( ADVADS_SETTINGS_ADBLOCKER ); ?>[ads-for-adblockers][enabled]" id="advanced-ads-pro-ads-for-adblockers-enabled" type="checkbox" value="1" class="advads-has-sub-settings" <?php checked( $module_enabled ); ?> />
<label for="advanced-ads-pro-ads-for-adblockers-enabled" class="description">
	<?php esc_html_e( 'Activate module.', 'advanced-ads-pro' ); ?>
</label>
<a href="https://wpadvancedads.com/manual/ad-blockers/?utm_source=advanced-ads&utm_medium=link&utm_campaign=pro-ab-manual" target="_blank" class="advads-manual-link"><?php esc_html_e( 'Manual', 'advanced-ads-pro' ); ?></a>
<div class="advads-sub-settings">
	<p class="description">
		<?php
			echo wp_kses_post(
				__( 'This module requires:', 'advanced-ads-pro' )
				. '<br> <span class="dashicons ' . esc_attr( $cb_dashicon_class ) . '"></span>'
				. __( 'Cache Busting', 'advanced-ads-pro' )
				. '<br> <span class="dashicons ' . esc_attr( $ab_dashicon_class ) . '"></span>'
				. __( 'Ad block disguise', 'advanced-ads-pro' )
			);
			?>
	</p>
</div>
