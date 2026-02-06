<?php
/**
 * View for the MaxMind license key setting.
 *
 * @package AdvancedAds\Pro\Modules\Geo
 * @author  Advanced Ads <info@wpadvancedads.com>
 *
 * @var string $license_key
 * @var bool   $use_filters
 */

printf(
	'<input id="advanced-ads-geo-maxmind-licence" name="%s" type="text" data-customdb="%s" value="%s" />',
	esc_attr( Advanced_Ads_Pro::OPTION_KEY . '[' . Advanced_Ads_Geo_Plugin::OPTIONS_SLUG . '][maxmind-license-key]' ),
	$use_filters ? 'true' : '',
	esc_attr( $license_key )
);
?>

<p class="description">
	<a target="_blank" class="advads-external-link" rel="noopener" href="https://support.maxmind.com/hc/en-us/articles/4407111582235-Generate-a-License-Key">
		<?php esc_attr_e( 'Manual', 'advanced-ads-pro' ); ?>
	</a>
</p>
