<?php
/**
 * Ad Blocker overlay options 'stylings'
 *
 * @package     Advanced_Ads_Pro\Module
 * @var string  $option_dismiss       array index name
 * @var string  $option_container     array index name
 * @var string  $option_background    array index name
 * @var string  $container_style      css
 * @var string  $background_style     css
 */

?>

<h4>
	<?php esc_html_e( 'Container styling', 'advanced-ads-pro' ); ?>
	<span class="advads-help">
		<span class="advads-tooltip">
			<?php esc_html_e( 'Insert CSS to customize the overlay container layout.', 'advanced-ads-pro' ); ?>
		</span>
	</span>
</h4>
<input class="width-100"
	type="text"
	name="<?php echo esc_attr( ADVADS_SETTINGS_ADBLOCKER . "[overlay][$option_container]" ); ?>"
	value="<?php echo esc_attr( $container_style ); ?>"
	placeholder="e.g. background-color: salmon; border-radius: 10px; padding: 20px;">
<?php if ( ! empty( $container_style ) ) : ?>
<p class="description"><?php esc_html_e( 'Empty and save to revert to defaults.', 'advanced-ads-pro' ); ?></p>
<?php endif; ?>

<h4>
	<?php esc_html_e( 'Background styling', 'advanced-ads-pro' ); ?>
	<span class="advads-help">
		<span class="advads-tooltip">
			<?php esc_html_e( 'Insert CSS to customize the background of the overlay.', 'advanced-ads-pro' ); ?>
		</span>
	</span>
</h4>
<input class="width-100"
	type="text"
	name="<?php echo esc_attr( ADVADS_SETTINGS_ADBLOCKER . "[overlay][$option_background]" ); ?>"
	value="<?php echo esc_attr( $background_style ); ?>"
	placeholder="e.g. background-color: rgba(0, 0, 0, 0.9);">
<?php if ( ! empty( $background_style ) ) : ?>
<p class="description"><?php esc_html_e( 'Empty and save to revert to defaults.', 'advanced-ads-pro' ); ?></p>
<?php endif; ?>
