<?php
/**
 * Email report frequency setting.
 *
 * @since   2.6.0
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 *
 * @var string $frequency Email report frequency.
 */

?>
<label style="margin-right:2em;">
<input type="radio" name="<?php echo esc_attr( $this->options_slug ); ?>[email-sched]" value="daily" <?php checked( 'daily', $frequency ); ?> />
<?php esc_html_e( 'daily', 'advanced-ads-tracking' ); ?>
</label>
<label style="margin-right:2em;">
<input type="radio" name="<?php echo esc_attr( $this->options_slug ); ?>[email-sched]" value="weekly" <?php checked( 'weekly', $frequency ); ?> />
<?php esc_html_e( 'weekly', 'advanced-ads-tracking' ); ?>
</label>
<label style="margin-right:2em;">
<input type="radio" name="<?php echo esc_attr( $this->options_slug ); ?>[email-sched]" value="monthly" <?php checked( 'monthly', $frequency ); ?> />
<?php esc_html_e( 'monthly', 'advanced-ads-tracking' ); ?>
</label>
<p class="description"><?php esc_html_e( 'How often to send email reports', 'advanced-ads-tracking' ); ?></p>
<script type="text/template" id="advads-track-admin-spinner"><img alt="" class="ajax-spinner" src="<?php echo esc_url( admin_url( 'images/spinner.gif' ) ); ?>" /></script>
