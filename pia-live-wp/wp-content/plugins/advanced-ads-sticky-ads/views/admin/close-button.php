<?php
/**
 * The view to render the close button option.
 *
 * @package AdvancedAds\StickyAds
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   1.9.0
 *
 * @var Placement $placement   Placement object.
 * @var array     $data        Placement data.
 * @var array     $options     Placement sticky options.
 * @var string    $option_name Option name.
 */

use AdvancedAds\Utilities\WordPress;

$options               = $data['close'] ?? [];
$close_enabled         = $options['enabled'] ?? 0;
$close_where           = $options['where'] ?? 'inside';
$close_side            = $options['side'] ?? 'right';
$close_timeout_enabled = $options['timeout_enabled'] ?? false;
$close_timeout         = $options['timeout'] ?? 0;
$option_name           = 'advads[placements][options][close]';

$has_refresh_group = false;
if ( 'group' === $placement->get_item_type() ) {
	$group = $placement->get_item_object();
	if ( $group && $group->get_prop( 'options.refresh.enabled' ) ) {
		$has_refresh_group = true;
	}
}

ob_start();
?>
<p>
	<label>
		<input type="checkbox" name="<?php echo esc_attr( $option_name ); ?>[enabled]" value="1"<?php checked( $close_enabled, 1 ); ?> onclick="advads_toggle_box(this, '#advads-close-button-<?php echo esc_attr( $placement_slug ); ?>');" <?php disabled( $has_refresh_group ); ?>/>
		<?php esc_html_e( 'add close button', 'advanced-ads-sticky' ); ?>
	</label>
</p>

<?php
if ( $has_refresh_group ) {
	?>
	<p class="description">
		<?php esc_html_e( 'Note: The close button is disabled as this placement contains an ad group with a refresh interval enabled.', 'advanced-ads-sticky' ); ?>
	</p>
		<?php
}
?>

<div id="advads-close-button-<?php echo esc_attr( $placement_slug ); ?>"<?php echo ! $close_enabled ? ' style="display:none;"' : ''; ?>>
	<p>
		<?php esc_html_e( 'Position', 'advanced-ads-sticky' ); ?>
	</p>
	<label>
		<input type="radio" name="<?php echo esc_attr( $option_name ); ?>[where]" value="outside"<?php checked( $close_where, 'outside' ); ?>/>
		<?php esc_html_e( 'outside', 'advanced-ads-sticky' ); ?>
	</label>
	<label>
		<input type="radio" name="<?php echo esc_attr( $option_name ); ?>[where]" value="inside"<?php checked( $close_where, 'inside' ); ?>/>
		<?php esc_html_e( 'inside', 'advanced-ads-sticky' ); ?>
	</label>
	<br/>
	<label>
		<input type="radio" name="<?php echo esc_attr( $option_name ); ?>[side]" value="left"<?php checked( $close_side, 'left' ); ?>/>
		<?php esc_html_e( 'left', 'advanced-ads-sticky' ); ?>
	</label>
	<label>
		<input type="radio" name="<?php echo esc_attr( $option_name ); ?>[side]" value="right"<?php checked( $close_side, 'right' ); ?>/>
		<?php esc_html_e( 'right', 'advanced-ads-sticky' ); ?>
	</label>
	<br/>
	<br/>
	<p>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( $option_name ); ?>[timeout_enabled]" onclick="advads_toggle_box(this, '#advads-timeout-<?php echo esc_attr( $placement_slug ); ?>');" value="true"<?php checked( $close_timeout_enabled, 'true' ); ?>/>
			<?php esc_html_e( 'enable timeout', 'advanced-ads-sticky' ); ?>
		</label>
	</p>
	<div id="advads-timeout-<?php echo esc_attr( $placement_slug ); ?>"<?php echo ! $close_timeout_enabled ? ' style="display:none;"' : ''; ?>>
		<p>
			<?php esc_html_e( 'close the ad for …', 'advanced-ads-sticky' ); ?>
		</p>
		<input type="number" name="<?php echo esc_attr( $option_name ); ?>[timeout]" value="<?php echo absint( $close_timeout ); ?>"/>
		<span class="description">
			<?php esc_html_e( 'days, 0 = after current session', 'advanced-ads-sticky' ); ?>
		</span>
	</div>
</div>
<?php
$option_content = ob_get_clean();

WordPress::render_option(
	'placement-sticky-close-button',
	__( 'close button', 'advanced-ads-sticky' ),
	$option_content
);
