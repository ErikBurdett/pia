<?php
/**
 * Responsive Ads reload setting template
 *
 * @package    Advanced_Ads_Pro\Module
 * @subpackage Responsive_Ads
 * @author     Advanced Ads <info@wpadvancedads.com>
 */

$setting_name = Advanced_Ads_Pro::OPTION_KEY . '[responsive-ads][reload-ads-on-resize]';

?>
<input id="aa-pro-reload-ads-on-resize" type="checkbox" name="<?php echo esc_attr( $setting_name ); ?>" value="1"<?php checked( $reload_ads_option_enabled ); ?><?php disabled( ! $cache_busting_enabled ); ?> />
<label for="aa-pro-reload-ads-on-resize">
	<?php
	esc_html_e( 'Reload ads when the screen resizes.', 'advanced-ads-pro' );
	if ( ! $cache_busting_enabled ) {
		echo ' ';
		esc_html_e( 'You need to enable cache-busting in order to use this feature.', 'advanced-ads-pro' );
	}
	?>
</label>
<script>
	(function($) {
		const cacheSwitch = $('#advanced-ads-pro-cache-busting-enabled');
		const reloadSwitch = $('#aa-pro-reload-ads-on-resize');
		cacheSwitch.on( 'change', function() {
			reloadSwitch.prop( 'disabled', ! cacheSwitch.is(':checked') )
		})
	})(jQuery)
</script>
