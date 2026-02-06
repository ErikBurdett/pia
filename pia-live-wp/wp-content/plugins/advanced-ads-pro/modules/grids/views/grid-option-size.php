<?php
/**
 * Render group option size.
 *
 * @package AdvancedAds
 * @author  Advanced Ads <info@wpadvancedads.com>
 *
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
 */

?>
<label><select name="advads-groups[<?php echo $group->get_id(); ?>][options][grid][columns]">
	<?php for ( $i = 1; $i <= 10; $i++ ) : ?>
	<option value="<?php echo $i; ?>"<?php selected( $i, $columns ); ?>><?php echo $i; ?></option>
	<?php endfor; ?>
</select><?php esc_html_e( 'columns', 'advanced-ads-pro' ); ?> x </label><label><select name="advads-groups[<?php echo $group->get_id(); ?>][options][grid][rows]">
	<?php for ( $i = 1; $i <= 50; $i++ ) : ?>
	<option value="<?php echo $i; ?>"<?php selected( $i, $rows ); ?>><?php echo $i; ?></option>
	<?php endfor; ?>
</select><?php esc_html_e( 'rows', 'advanced-ads-pro' ); ?></label>
