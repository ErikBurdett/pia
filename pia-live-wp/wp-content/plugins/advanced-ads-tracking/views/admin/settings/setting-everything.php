<?php
/**
 * Everything tracking settings.
 *
 * @since   2.6.0
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 *
 * @var string $method Tracking method.
 */

?>
<select name="<?php echo esc_attr( $this->options_slug ); ?>[everything]">
	<option value="true" <?php selected( $method, 'true' ); ?>><?php esc_html_e( 'impressions & clicks', 'advanced-ads-tracking' ); ?></option>
	<option value="false" <?php selected( $method, 'false' ); ?>><?php esc_html_e( 'don’t track anything', 'advanced-ads-tracking' ); ?></option>
	<option value="impressions" <?php selected( $method, 'impressions' ); ?>><?php esc_html_e( 'impressions only', 'advanced-ads-tracking' ); ?></option>
	<option value="clicks" <?php selected( $method, 'clicks' ); ?>><?php esc_html_e( 'clicks only', 'advanced-ads-tracking' ); ?></option>
</select>
<p class="description"><?php esc_html_e( 'You can change this setting individually for each ad on the ad edit page.', 'advanced-ads-tracking' ); ?></p>
