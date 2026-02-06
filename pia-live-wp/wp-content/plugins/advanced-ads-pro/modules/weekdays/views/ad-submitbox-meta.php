<?php
/**
 * Specific days markup for the "Publish" metabox
 *
 * @package AdvancedAds\Pro
 * @author  Advanced Ads <info@wpadvancedads.com>
 *
 * @var bool      $enabled     whether the feature is enabled for the current ad.
 * @var array     $day_indexes day indices.
 * @var WP_Locale $wp_locale   the global instance of WP_Locale.
 * @var string    $time_zone   name of the timezone for the WP installation.
 */

?>
<div id="advanced-ads-weekdays" class="misc-pub-section">
	<label onclick="advads_toggle_box( '#advanced-ads-weekdays-enable', '#advanced-ads-weekdays .inner' )">
		<input type="checkbox" id="advanced-ads-weekdays-enable" name="advanced_ad[weekdays][enabled]" value="1"
			<?php checked( $enabled, 1 ); ?>/>
		<?php esc_html_e( 'Set specific days', 'advanced-ads-pro' ); ?>
	</label>
	<div class="inner" <?php echo ! $enabled ? 'style="display:none;"' : ''; ?>>
		<select id="advads-pro-weekdays" name="advanced_ad[weekdays][day_indexes][]" multiple="multiple" size="7">
			<?php
			for ( $i = 1; $i <= 7; $i++ ) {
				$day_index = ( 7 === $i ) ? 0 : $i;
				printf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $day_index ),
					in_array( $day_index, $day_indexes, true ) ? ' selected="selected"' : '',
					esc_html( $wp_locale->get_weekday( $day_index ) )
				);
			}
			?>
		</select>
		<p class="description">(<?php echo esc_html( $time_zone ); ?>)</p>
	</div>
</div>
