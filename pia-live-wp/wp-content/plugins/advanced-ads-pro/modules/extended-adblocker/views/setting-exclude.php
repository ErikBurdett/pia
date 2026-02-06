<?php
/**
 * Ad Blocker options 'exclude'
 *
 * @package     Advanced_Ads_Pro\Module
 * @var string  $option_exclude       array index name
 * @var array   $exclude              exclude option value
 */

?>

<h4>
	<?php esc_html_e( 'Exclude', 'advanced-ads-pro' ); ?>
	<span class="advads-help">
		<span class="advads-tooltip">
			<?php esc_html_e( 'Choose which user roles to exclude from this ad blocker countermeasure.', 'advanced-ads-pro' ); ?>
		</span>
	</span>
</h4>

<div class="advads-settings-checkbox-inline">
	<?php
	foreach ( $roles as $_role => $_display_name ) :
		$checked = in_array( $_role, $exclude, true );
		?>
		<label>
			<input type="checkbox" value="<?php echo esc_attr( $_role ); ?>"
				name="<?php echo esc_attr( ADVADS_SETTINGS_ADBLOCKER . "[$option_exclude][]" ); ?>"
				<?php checked( $checked, true ); ?>>
			<?php echo esc_html( $_display_name ); ?>
		</label>
		<?php
	endforeach;
	?>
</div>
