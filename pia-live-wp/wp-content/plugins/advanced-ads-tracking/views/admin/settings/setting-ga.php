<?php
/**
 * Google Analytics settings.
 *
 * @since   2.6.0
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 *
 * @var string $uid   Google Analytics UID.
 * @var bool   $is_ga Whether Google Analytics is enabled.
 */

?>
<label>
	<?php esc_html_e( 'Your Tracking ID', 'advanced-ads-tracking' ); ?><br/>
	<input type="text" name="<?php echo esc_attr( $this->options_slug ); ?>[ga-UID]" value="<?php echo esc_attr( $uid ); ?>" <?php echo esc_attr( $is_ga ? 'required' : '' ); ?> />
</label>
<p class="description">
	<?php esc_html_e( 'One or more Google Analytics properties to which you want the data to be sent. Separate multiple properties with commas.', 'advanced-ads-tracking' ); ?>
	<br>
	<?php
	printf(
		/* translators: 1: is an example id for Universal Analytics <code>UA-123456-1</code>, 2: is an example id for GA4 '<code>G-A12BC3D456</code>' */
		esc_html__( '%1$s for Universal Analytics or %2$s for Google Analytics 4.', 'advanced-ads-tracking' ),
		'<code>UA-123456-1</code>',
		'<code>G-A12BC3D456</code>'
	);
	?>
</p>
