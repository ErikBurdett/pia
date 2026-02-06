<?php
/**
 * Email report stats period setting.
 *
 * @since   2.6.0
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 *
 * @var string $period Email report stats period.
 */

?>
<select name="<?php echo esc_attr( $this->options_slug ); ?>[email-stats-period]">
<option value="last30days" <?php selected( $period, 'last30days' ); ?>><?php esc_html_e( 'last 30 days', 'advanced-ads-tracking' ); ?></option>
<option value="lastmonth" <?php selected( $period, 'lastmonth' ); ?>><?php esc_html_e( 'last month', 'advanced-ads-tracking' ); ?></option>
<option value="last12months" <?php selected( $period, 'last12months' ); ?>><?php esc_html_e( 'last 12 months', 'advanced-ads-tracking' ); ?></option>
</select>
<p class="description"><?php esc_html_e( 'Period used in the report', 'advanced-ads-tracking' ); ?></p>
