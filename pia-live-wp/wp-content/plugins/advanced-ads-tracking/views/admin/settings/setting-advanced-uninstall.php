<?php
/**
 * Uninstall setting.
 *
 * @since   2.6.0
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 *
 * @var string $uninstall Uninstall option.
 */

?>
<label>
	<input type="checkbox" <?php checked( $uninstall, '1' ); ?> value="1" name="<?php echo esc_attr( $this->options_slug ); ?>[uninstall]" />
	<?php esc_html_e( 'Clean up all database entries related to tracking when removing the Tracking add-on.', 'advanced-ads-tracking' ); ?>
</label>
