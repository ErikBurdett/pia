<?php
/**
 * Responsive Ads setting template
 *
 * @package    Advanced_Ads_Pro\Module
 * @subpackage Responsive_Ads
 * @author     Advanced Ads <info@wpadvancedads.com>
 */

$setting_name = Advanced_Ads_Pro::OPTION_KEY . '[responsive-ads][force-responsive-images]';
?>

<input type="checkbox" id="aa-pro-responsive-images-force" name="<?php echo esc_attr( $setting_name ); ?>" value="1" <?php checked( $force_responsive ); ?> />
<label for="aa-pro-responsive-images-force">
	<?php esc_html_e( 'Check this option if the size of image ads is not adjusted responsively by your theme.', 'advanced-ads-pro' ); ?>
</label>
