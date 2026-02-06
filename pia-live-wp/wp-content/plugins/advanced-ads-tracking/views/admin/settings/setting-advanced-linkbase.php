<?php
/**
 * Link Cloaking URL link base setting.
 *
 * @since   2.6.0
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 *
 * @var string $linkbase Current link cloaking link base value.
 */

?>
<?php echo esc_url( home_url( '/' ) ); ?><input name="<?php echo esc_attr( $this->options_slug ); ?>[linkbase]" type="text" value="<?php echo esc_attr( $linkbase ); ?>"/>/(ad id)
<p class="description">
	<?php esc_html_e( 'Pattern of the click-tracking URL if link cloaking is used. Should not collide with any posts or pages. Use chars: a-z/-', 'advanced-ads-tracking' ); ?>
</p>
