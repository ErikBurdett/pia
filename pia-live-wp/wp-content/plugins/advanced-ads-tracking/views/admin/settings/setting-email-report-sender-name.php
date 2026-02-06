<?php
/**
 * Email report sender name setting.
 *
 * @since   2.6.0
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 *
 * @var string $sender_name Email report sender name.
 */

?>
<input type="text" class="regular-text" name="<?php echo esc_attr( $this->options_slug ); ?>[email-sender-name]" value="<?php echo esc_attr( $sender_name ); ?>" autocomplete="advads-sender-name"/>
