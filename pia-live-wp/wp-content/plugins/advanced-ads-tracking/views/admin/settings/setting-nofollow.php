<?php
/**
 * Template for rel="nofollow" setting.
 *
 * @since   2.6.0
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 *
 * @var bool $nofollow Add nofollow to programatically created links.
 */

?>
<label>
	<input name="<?php echo esc_attr( $this->options_slug ); ?>[nofollow]" type="checkbox" <?php checked( $nofollow ); ?>/>
	<?php
	printf(
		/* translators: %s is <code>rel="nofollow"</code> */
		esc_html__( 'Add %s to programatically created links.', 'advanced-ads-tracking' ),
		'<code>rel="nofollow"</code>'
	);
	?>
</label>
<br/>
