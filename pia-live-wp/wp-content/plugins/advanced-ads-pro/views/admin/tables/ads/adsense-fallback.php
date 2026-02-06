<?php
/**
 * AdSense fallback setting markup
 *
 * @package AdvancedAds\Pro
 * @author  Advanced Ads <info@wpadvancedads.com>
 *
 * @var bool          $is_global              whether it's the global or ad level fallback
 * @var string        $fallback               the saved fallback item for the setting that is being displayed.
 * @var array         $item                   list of available fallback options.
 * @var string        $global_fallback        global fallback item.
 * @var Ad|Group|bool $global_fallback_object global fallback entity instance. `false` if there's none.
 * @var array         $cache_busting          cache busting module's options.
 */

use AdvancedAds\Abstracts\Ad;
use AdvancedAds\Abstracts\Group;
?>
<?php if ( ! $is_global ) : ?>
	<label class="label"><?php esc_html_e( 'Fallback', 'advanced-ads-pro' ); ?></label>
<?php endif; ?>
<div>
	<select name="<?php echo esc_attr( $is_global ? GADSENSE_OPT_NAME . '[adsense_fallback]' : 'advanced_ad[adsense_fallback]' ); ?>">
		<?php if ( ! $is_global ) : ?>
			<option value="default" <?php selected( $fallback, 'default' ); ?>><?php esc_html_e( 'Default', 'advanced-ads-pro' ); ?></option>
		<?php endif; ?>
		<option value="none" <?php selected( $fallback, 'none' ); ?>><?php esc_html_e( 'None', 'advanced-ads-pro' ); ?></option>
		<?php if ( ! empty( $items['groups'] ) ) : ?>
			<optgroup label="<?php esc_attr_e( 'Ad groups', 'advanced-ads-pro' ); ?>">
				<?php foreach ( $items['groups'] as $group ) : ?>
					<option value="group_<?php echo esc_attr( $group->get_id() ); ?>" <?php selected( $fallback, 'group_' . $group->get_id() ); ?>>
						<?php echo esc_html( $group->get_title() ); ?>
					</option>
				<?php endforeach; ?>
			</optgroup>
		<?php endif; ?>
		<optgroup label="<?php esc_attr_e( 'Ads', 'advanced-ads-pro' ); ?>">
			<?php foreach ( $items['ads'] as $ad ) : ?>
				<option value="ad_<?php echo esc_attr( $ad->get_id() ); ?>" <?php selected( $fallback, 'ad_' . $ad->get_id() ); ?>>
					<?php echo esc_html( $ad->get_title() ); ?>
				</option>
			<?php endforeach; ?>
		</optgroup>
	</select>
	<?php if ( ! $is_global ) : ?>
		<span class="advads-help">
					<span class="advads-tooltip"><?php esc_html_e( 'The selected item will be displayed when an AdSense ad is unavailable, ensuring your ad space remains filled.', 'advanced-ads-pro' ); ?></span>
				</span>
	<?php endif; ?>
	<?php if ( ! $is_global ) : ?>
		<?php if ( ! $global_fallback_object ) : ?>
			<?php esc_html_e( 'No default fallback ad selected. Choose one in the AdSense settings.', 'advanced-ads-pro' ); ?>
		<?php else : ?>
			<p>
				<?php
				printf(
					/* translators: group or ad title. */
					esc_html__( 'The default fallback is "%s". You can change this in the AdSense settings.', 'advanced-ads-pro' ),
					esc_html( $global_fallback_object->get_title() )
				);
				?>
			</p>
		<?php endif; ?>
	<?php else : ?>
		<p class="description">
			<?php esc_html_e( 'The selected item will be displayed when an AdSense ad is unavailable, ensuring your ad space remains filled.', 'advanced-ads-pro' ); ?>
		</p>
	<?php endif; ?>
	<?php if ( empty( $cache_busting['enabled'] ) ) : ?>
		<div class="notice advads-notice is-dismissible inline">
			<p>
				<?php
				printf(
					'%1$s <a href="%2$s">%3$s</a>',
					esc_html__( 'The AdSense fallback feature requires the ad to be assigned to a placement with enabled Cache Busting.', 'advanced-ads-pro' ),
					esc_url( admin_url( 'admin.php?page=advanced-ads-settings#top#pro' ) ),
					esc_html__( 'Activate now', 'advanced-ads-pro' )
				);
				?>
			</p>
		</div>
	<?php endif; ?>
</div>
<?php if ( ! $is_global ) : ?>
	<hr>
<?php endif; ?>
