<?php
/***
 * Markup for ads by hours inputs
 *
 * @package AdvancedAds\Pro
 * @author  Advanced Ads <info@wpadvancedads.com>
 *
 * @var array                               $addon_options pro add-on options.
 * @var array                               $options       ads by hour module options.
 * @var Advanced_Ads_Pro\Ads_By_Hours\admin $this          dashboard class.
 */

use AdvancedAds\Utilities\Conditional;

$cache_busting_enabled = isset( $addon_options['cache-busting']['enabled'] ) && $addon_options['cache-busting']['enabled'];

?>
<div id="advanced-ads-ads-by-hours-inputs" class="misc-pub-section">
	<label onclick="advads_toggle_box( '#advanced-ads-ads-by-hours-enabled', '#advanced-ads-ads-by-hours-inputs .inner' )">
		<input type="checkbox" value="1" name="advanced_ad[ads_by_hours][enabled]" id="advanced-ads-ads-by-hours-enabled" <?php checked( ! empty( $options['enabled'] ) ); ?>>
		<?php esc_html_e( 'Set specific hours', 'advanced-ads-pro' ); ?>
	</label>
	<?php if ( $this->module->use_browser_time() && ! $cache_busting_enabled ) : ?>
		<div class="notice advads-notice inline" style="margin:5px 1px 7px">
			<p><?php echo esc_html( $this->get_cb_warning_message() ); ?></p>
		</div>
	<?php endif; ?>
	<?php if ( ! $this->module->use_browser_time() && Conditional::has_cache_plugins() && ! $cache_busting_enabled ) : ?>
		<div class="notice advads-notice inline" style="margin:5px 1px 7px">
			<p><?php echo esc_html( $this->get_cache_plugin_warning() ); ?></p>
		</div>
	<?php endif; ?>
	<div class="inner" style="<?php echo ! empty( $options['enabled'] ) ? '' : 'display:none;'; ?>font-size:.9em;">
		<div>
			<p style="font-weight: 500;"><?php esc_html_e( 'From', 'advanced-ads-pro' ); ?></p>
			<fieldset class="advads-timestamp">
				<label>
					<select name="advanced_ad[ads_by_hours][start_hour]">
						<?php foreach ( $this->module->get_hours() as $hour ) : ?>
							<option value="<?php echo esc_attr( $hour ); ?>" <?php selected( $hour, $options['start_hour'] ); ?>><?php echo esc_html( $this->get_localized_hour( $hour ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>:
				<label>
					<select name="advanced_ad[ads_by_hours][start_min]">
						<?php foreach ( $this->module->get_minutes() as $min ) : ?>
							<option value="<?php echo esc_attr( $min ); ?>" <?php selected( $min, $options['start_min'] ); ?>><?php echo esc_html( $min ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</fieldset>
		</div>
		<div>
			<p style="font-weight: 500;"><?php esc_html_e( 'To', 'advanced-ads-pro' ); ?></p>
			<fieldset class="advads-timestamp">
				<label>
					<select name="advanced_ad[ads_by_hours][end_hour]">
						<?php foreach ( $this->module->get_hours() as $hour ) : ?>
							<option value="<?php echo esc_attr( $hour ); ?>" <?php selected( $hour, $options['end_hour'] ); ?>><?php echo esc_html( $this->get_localized_hour( $hour ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>:
				<label>
					<select name="advanced_ad[ads_by_hours][end_min]">
						<?php foreach ( $this->module->get_minutes() as $min ) : ?>
							<option value="<?php echo esc_attr( $min ); ?>" <?php selected( $min, $options['end_min'] ); ?>><?php echo esc_html( $min ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</fieldset>
		</div>
		<p class="description">(<?php echo esc_html( $this->get_time_zone_string() ); ?>)</p>
	</div>
</div>
