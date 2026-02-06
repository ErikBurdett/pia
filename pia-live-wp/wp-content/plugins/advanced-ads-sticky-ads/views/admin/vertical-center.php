<?php
/**
 * The view to render the vertical center option.
 *
 * @package AdvancedAds\StickyAds
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   1.9.0
 *
 * @var array $data Placement data.
 */

use AdvancedAds\Utilities\WordPress;

ob_start();
?>
<label>
	<input type="checkbox" name="advads[placements][options][sticky_is_fixed]" value="1"<?php checked( $data['sticky_is_fixed'] ?? 0, 1 ); ?>/>
	<?php esc_html_e( 'fix to window position', 'advanced-ads-sticky' ); ?>
</label>
<label>
	<input type="checkbox" name="advads[placements][options][sticky_center_vertical]" value="1"<?php checked( $data['sticky_center_vertical'] ?? 0, 1 ); ?>/>
	<?php esc_html_e( 'center vertically', 'advanced-ads-sticky' ); ?>
</label>
<?php
$option_content = ob_get_clean();

WordPress::render_option(
	'placement-sticky-position',
	__( 'Position', 'advanced-ads-sticky' ),
	$option_content
);
