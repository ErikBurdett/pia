<?php
/**
 * Render the options on the placement
 *
 * @var string   $option_prefix
 * @var string   $parallax_enabled_id
 * @var bool     $parallax_enabled
 * @var string   $placement_slug
 * @var float    $height_value
 * @var string   $height_unit
 * @var string[] $height_units
 * @var int      $offset
 */
?>

<input type="checkbox" name="<?php echo esc_attr( $option_prefix ); ?>[enabled]" <?php checked( $parallax_enabled ); ?> id="<?php echo esc_attr( $parallax_enabled_id ); ?>" class="advads-option-placement-parallax-enabled">

<label for="<?php echo esc_attr( $parallax_enabled_id ); ?>">
	<?php esc_html_e( 'Enable the parallax effect.', 'advanced-ads-pro' ); ?>
	<span> </span>

	<?php
	/* translators: Link to parallax manual */
	printf( esc_html__( 'Optimized for image ads. %s', 'advanced-ads-pro' ), '<a href="https://wpadvancedads.com/parallax-ads/?utm_source=advanced-ads&utm_medium=link&utm_campaign=pro-parallax-manual" target="_blank" class="advads-manual-link">' . esc_html__( 'Manual', 'advanced-ads-pro' ) . '</a>' );
	?>
</label>
<br>

<div class="advads-option-placement-parallax-height">
	<label>
		<?php echo esc_html_x( 'Height', 'Parallax Ad placement height', 'advanced-ads-pro' ); ?>
		<br>
		<input type="number" min="1" max="<?php echo esc_attr( $height_unit === 'vh' ? 100 : '' ); ?>" step="any" name="<?php echo esc_attr( $option_prefix ); ?>[height][value]" value="<?php echo (float) $height_value; ?>">
	</label>

	<label>
		<?php echo esc_html_x( 'Unit', 'Parallax Ad placement height unit', 'advanced-ads-pro' ); ?>
		<span class="advads-help">
			<span class="advads-tooltip">
				<?php esc_html_e( 'Choose the height of the cutout, either in pixels or relative to the viewport.', 'advanced-ads-pro' ); ?>
			</span>
		</span>
		<br>
		<select name="<?php echo esc_attr( $option_prefix ); ?>[height][unit]" class="advads-option-placement-parallax-unit">
			<?php foreach ( $height_units as $value => $option ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $height_unit ); ?>>
					<?php echo esc_html( $option ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</label>
</div>
