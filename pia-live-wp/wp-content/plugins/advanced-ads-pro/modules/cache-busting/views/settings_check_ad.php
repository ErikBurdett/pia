<?php //phpcs:ignoreFile
/**
 * Setting check ad template
 *
 * @package AdvancedAds\Pro
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.26.0
 */

?>
<div id="advads-cache-busting-check-wrap">
	<span id="advads-cache-busting-error-result" class="advads-notice-inline advads-error" style="display:none;">
		<?php
		printf(
			/* translators: %s link to manual */
			__( 'The code of this ad might not work properly with activated cache-busting. <a href="%s" target="_blank">Manual</a>', 'advanced-ads-pro' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'https://wpadvancedads.com/manual/cache-busting/#advads-passive-compatibility-warning'
		);
		?>
	</span>
	<input type="hidden" id="advads-cache-busting-possibility" name="advanced_ad[cache-busting][possible]" value="true" />
</div>

<?php if ( ! $ad->is_type( 'adsense' ) ) : ?>
<script>
jQuery( document ).ready(function() {
	if ( typeof advads_cb_check_ad_markup !== 'undefined' ){
		var ad_content = <?php echo json_encode( $ad->prepare_output( $ad ) ); ?>;
		advads_cb_check_ad_markup( ad_content );
	}
});
</script>
	<?php
endif;
