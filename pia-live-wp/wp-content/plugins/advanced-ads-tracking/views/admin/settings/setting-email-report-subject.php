<?php
/**
 * Email report subject setting.
 *
 * @since   2.6.0
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 *
 * @var string $subject Email report subject.
 */

?>
<input type="text" class="regular-text" name="<?php echo esc_attr( $this->options_slug ); ?>[email-subject]" value="<?php echo esc_attr( $subject ); ?>" autocomplete="advads-email-subject"/>
