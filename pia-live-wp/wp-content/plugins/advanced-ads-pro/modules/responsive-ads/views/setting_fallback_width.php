<?php
/**
 * Responsive Ads fallback browser width template
 *
 * @var int $width Fallback width.
 *
 * @package    Advanced_Ads_Pro\Module
 * @subpackage Responsive_Ads
 * @author     Advanced Ads <info@wpadvancedads.com>
 */


$setting_name = Advanced_Ads_Pro::OPTION_KEY . '[responsive-ads][fallback-width]';
?>
<input type="number" name="<?php echo esc_attr( $setting_name ); ?>" value="<?php echo esc_attr( $width ); ?>" class="small-text" /> px.
<a href="https://wpadvancedads.com/manual/display-ads-by-browser-width/?utm_source=advanced-ads&utm_medium=link&utm_campaign=settings-fallback-with#Fallback_width" target="_blank" class="advads-manual-link"><?php esc_html_e( 'Manual', 'advanced-ads-pro' ); ?></a>
