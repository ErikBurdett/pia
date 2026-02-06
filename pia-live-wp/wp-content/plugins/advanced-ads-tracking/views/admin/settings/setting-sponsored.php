<?php
/**
 * Template for rel="sponsored" setting.
 *
 * @since   2.6.0
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 *
 * @var bool $sponsored Add sponsored to programatically created links.
 */

?>
<label>
	<input name="<?php echo esc_attr( $this->options_slug ); ?>[sponsored]" type="checkbox" <?php checked( $sponsored ); ?>/>
	<?php
	printf(
		/* translators: %s is <code>rel="sponsored"</code> */
		esc_html__( 'Add %s to programatically created links.', 'advanced-ads-tracking' ),
		'<code>rel="sponsored"</code>'
	);
	?>
	<p class="description">
		<a href="https://webmasters.googleblog.com/2019/09/evolving-nofollow-new-ways-to-identify.html" target="_blank" class="advads-external-link"><?php esc_html_e( "Read Google's recommendation on Google Webmaster Central Blog.", 'advanced-ads-tracking' ); ?></a>
	</p>
</label>
<br/>
