<?php
/**
 * Email report recipients setting.
 *
 * @since   2.6.0
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 *
 * @var string $recipients Email report recipients.
 */

?>
<input type="text" name="<?php echo esc_attr( $this->options_slug ); ?>[email-addresses]" style="width:85%;" value="<?php echo esc_attr( $recipients ); ?>" autocomplete="email"/>
<p class="description">
	<?php esc_html_e( 'Separate multiple emails with commas', 'advanced-ads-tracking' ); ?>
</p>
