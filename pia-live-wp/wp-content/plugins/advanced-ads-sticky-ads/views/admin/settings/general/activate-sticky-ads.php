<?php
/**
 * The view to render the vertical center option.
 *
 * @package AdvancedAds\StickyAds
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   1.9.0
 *
 * @var int $checked Value of 1, when the option is checked.
 */

?>
<label>
	<input name="<?php echo esc_attr( ADVADS_SLUG ); ?>[sticky][check-position-fixed]" id="advanced-ads-sticky-check-position-fixed" type="checkbox" value="1" <?php echo checked( 1, $check_position_fixed, false ); ?> />
	<?php _e( 'Activate this if you experience problems with sticky ads and/or a lot of your visitors use old mobile devices. It will check browser capability and position the ad inline after scrolling. Technically speaking: removes <em>position: fixed</em>, if not supported.', 'advanced-ads-sticky' ); // phpcs:ignore ?>
</label>
