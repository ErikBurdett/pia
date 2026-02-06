<?php
/**
 * The view to render the dimensions option.
 *
 * @package AdvancedAds\StickyAds
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   1.9.0
 *
 * @var array  $data        Placement data.
 * @var array  $options     Placement sticky options.
 * @var string $option_name Option name.
 */

use AdvancedAds\Utilities\WordPress;

ob_start();

?>
<p>
	<?php if ( false !== $width ) : ?>
	<label>
		<?php esc_html_e( 'width', 'advanced-ads-sticky' ); ?>
		<input type="number" value="<?php echo absint( $width ); ?>" name="advads[placements][options][placement_width]">px
	</label>
	<?php endif; ?>
	<?php if ( false !== $height ) : ?>
	&nbsp;
	<label>
		<?php esc_html_e( 'height', 'advanced-ads-sticky' ); ?>
		<input type="number" value="<?php echo absint( $height ); ?>" name="advads[placements][options][placement_height]">px
	</label>
	<?php endif; ?>
</p>
<p class="description">
	<?php esc_html_e( 'Needed in case the ad does not center correctly', 'advanced-ads-sticky' ); ?>
</p>
<?php
$option_content = ob_get_clean();

WordPress::render_option(
	'placement-sticky-dimension',
	__( 'size', 'advanced-ads-sticky' ),
	$option_content
);
