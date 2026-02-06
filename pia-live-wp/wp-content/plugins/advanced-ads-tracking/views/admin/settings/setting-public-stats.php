<?php
/**
 * Markup for public stats page slug setting
 *
 * @since   2.6.0
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 *
 * @var string $public_stats_slug page slug for public stats page.
 * @var string $nonce             wp_nonce for action 'advads-tracking-public-stats'.
 */

?>
<?php echo esc_url( site_url() ); ?>/<input id="public-stat-base" name="<?php echo esc_attr( $this->options_slug ); ?>[public-stats-slug]" type="text" value="<?php echo esc_attr( $public_stats_slug ); ?>" autocomplete="advads-stats-slug"/>/<span id="public-stats-spinner32" style="display:inline-block;vertical-align:middle;margin-left:0.5em;"></span><br/>
<p id="public-stat-notice" style="font-style:italic;"></p>
<script>
	var advadsTrackingAjaxNonce = '<?php echo esc_attr( $nonce ); ?>';
</script>
