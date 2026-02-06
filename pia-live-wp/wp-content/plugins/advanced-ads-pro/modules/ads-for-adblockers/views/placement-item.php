<?php
/**
 * Placement adblocker item dropdown
 *
 * @package AdvancedAds\Pro
 * @author  Advanced Ads <info@wpadvancedads.com>
 */

use AdvancedAds\Constants;

$groups = wp_advads_get_all_groups();
?>
<div class="advanced-ads-inputs-dependent-on-cb" <?php echo $cb_off ? 'style="display:none;"' : null; ?>>
<select id="advads-placements-item-adblocker-<?php echo esc_attr( $placement_slug ); ?>" name="advads[placements][options][item_adblocker]">
	<option value=""><?php esc_html_e( '--not selected--', 'advanced-ads-pro' ); ?></option>

	<?php if ( ! empty( $groups ) ) : ?>
	<optgroup label="<?php esc_html_e( 'Groups', 'advanced-ads-pro' ); ?>">
		<?php foreach ( $groups as $group ) : ?>
		<option value="<?php echo esc_attr( Constants::ENTITY_GROUP . '_' . $group->get_id() ); ?>"<?php selected( Constants::ENTITY_GROUP . '_' . $group->get_id(), $placement_data['item_adblocker'] ?? '' ); ?>>
			<?php echo esc_html( $group->get_name() ); ?>
		<?php endforeach; ?>
	</optgroup>
	<?php endif; ?>

	<?php if ( isset( $items['ads'] ) ) : ?>
	<optgroup label="<?php esc_html_e( 'Ads', 'advanced-ads-pro' ); ?>">
		<?php foreach ( $items['ads'] as $_item_id => $_item_title ) : ?>
		<option value="<?php echo esc_attr( $_item_id ); ?>"<?php selected( $_item_id, $placement_data['item_adblocker'] ?? '' ); ?>>
			<?php echo esc_html( $_item_title ); ?>
		</option>
		<?php endforeach; ?>
	</optgroup>
	<?php endif; ?>
</select>
	<?php if ( $messages ) : ?>
		<?php foreach ( $messages as $_message ) : ?>
			<p class="advads-notice-inline advads-error">
				<?php echo esc_html( $_message ); ?>
			</p>
		<?php endforeach; ?>
	<?php endif; ?>
</div>

<p class="advads-notice-inline advads-idea" <?php echo ! $cb_off ? 'style="display:none;"' : null; ?>>
	<?php esc_html_e( 'Works only with cache-busting enabled', 'advanced-ads-pro' ); ?>
</p>
