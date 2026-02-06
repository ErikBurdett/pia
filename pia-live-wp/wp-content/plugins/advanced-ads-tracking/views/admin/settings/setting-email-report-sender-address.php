<?php
/**
 * Email report sender address setting.
 *
 * @since   2.6.0
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 *
 * @var string $sender_address Email report sender address.
 */

?>

<input type="email" class="regular-text ltr" name="<?php echo esc_attr( $this->options_slug ); ?>[email-sender-address]" value="<?php echo esc_attr( $sender_address ); ?> " autocomplete="email"/>
