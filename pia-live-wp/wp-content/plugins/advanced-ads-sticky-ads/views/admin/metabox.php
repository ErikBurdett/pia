<?php
/**
 * The metabox view.
 *
 * @package AdvancedAds\StickyAds
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   1.9.0
 */

?>
<p class="advads-error-message">
	<?php
	echo wp_kses_post(
		sprintf(
			/* translators: placement admin url */
			__( 'These settings are deprecated. Sticky ads are now managed through <a href="%s">placements</a>. Please convert your settings as soon as possible.', 'advanced-ads-sticky' ),
			admin_url( 'admin.php?page=advanced-ads-placements' )
		)
	);
	?>
</p>
<div>
	<label>
		<input type="checkbox" name="advanced_ad[sticky][enabled]" id="advanced_ad_sticky_type" value="1" onclick="advads_toggle_box(this, '#advads-sticky-ads');"<?php checked( $enabled, 1 ); ?>/>
		<?php esc_html_e( 'Stick ad to a specific position on the screen', 'advanced-ads-sticky' ); ?>
	</label>
	<div id="advads-sticky-ads"<?php echo ! $enabled ? ' style="display:none;"' : ''; ?>>
		<div id="advads-sticky-ads-assistant">
			<p><label><input type="radio" name="advanced_ad[sticky][type]" id="advanced_ad_sticky_type_assistant" value="assistant" <?php checked( $type, 'assistant' ); ?>/><?php esc_html_e( 'Select a position', 'advanced-ads-sticky' ); ?></label></p>
			<p class="description"><?php esc_html_e( 'Choose a position on the screen.', 'advanced-ads-sticky' ); ?></p>
			<div class="advads-sticky-assistant">
				<table>
					<tr>
						<td><input type="radio" name="advanced_ad[sticky][assistant]" title="<?php esc_html_e( 'top left', 'advanced-ads-sticky' ); ?>" value="topleft" <?php checked( $assistant, 'topleft' ); ?>/></td>
						<td><input type="radio" name="advanced_ad[sticky][assistant]" title="<?php esc_html_e( 'top center', 'advanced-ads-sticky' ); ?>" value="topcenter" <?php checked( $assistant, 'topcenter' ); ?>/></td>
						<td><input type="radio" name="advanced_ad[sticky][assistant]" title="<?php esc_html_e( 'top right', 'advanced-ads-sticky' ); ?>" value="topright" <?php checked( $assistant, 'topright' ); ?>/></td>
					</tr>
					<tr>
						<td><input type="radio" name="advanced_ad[sticky][assistant]" title="<?php esc_html_e( 'center left', 'advanced-ads-sticky' ); ?>" value="centerleft" <?php checked( $assistant, 'centerleft' ); ?>/></td>
						<td><input type="radio" name="advanced_ad[sticky][assistant]" title="<?php esc_html_e( 'center', 'advanced-ads-sticky' ); ?>" value="center" <?php checked( $assistant, 'center' ); ?>/></td>
						<td><input type="radio" name="advanced_ad[sticky][assistant]" title="<?php esc_html_e( 'center right', 'advanced-ads-sticky' ); ?>" value="centerright" <?php checked( $assistant, 'centerright' ); ?>/></td>
					</tr>
					<tr>
						<td><input type="radio" name="advanced_ad[sticky][assistant]" title="<?php esc_html_e( 'bottom left', 'advanced-ads-sticky' ); ?>" value="bottomleft" <?php checked( $assistant, 'bottomleft' ); ?>/></td>
						<td><input type="radio" name="advanced_ad[sticky][assistant]" title="<?php esc_html_e( 'bottom center', 'advanced-ads-sticky' ); ?>" value="bottomcenter" <?php checked( $assistant, 'bottomcenter' ); ?>/></td>
						<td><input type="radio" name="advanced_ad[sticky][assistant]" title="<?php esc_html_e( 'bottom right', 'advanced-ads-sticky' ); ?>" value="bottomright" <?php checked( $assistant, 'bottomright' ); ?>/></td>
					</tr>
				</table>
				<label class="description"><?php esc_html_e( 'Enter banner width to correctly center the ad.', 'advanced-ads-sticky' ); ?><br/>
					<input type="number" name="advanced_ad[sticky][position][width]" title="<?php esc_html_e( 'banner width', 'advanced-ads-sticky' ); ?>" value="<?php echo absint( $width ); ?>"/>px</label>
			</div>
		</div>
		<div id="advads-sticky-ads-absolute">
			<p><label><input type="radio" name="advanced_ad[sticky][type]" id="advanced_ad_sticky_type_absolute" value="absolute" <?php checked( $type, 'absolute' ); ?>/><?php esc_html_e( 'Define position manually', 'advanced-ads-sticky' ); ?></label></p>
			<p class="description"><?php esc_html_e( 'Use numbers in every field you want to be considered for positioning (top, left, right, bottom of the page). Leave a field empty to not set a position for this side. The number is considered to be in pixels.', 'advanced-ads-sticky' ); ?></p>
			<table class="advads-sticky-numbers">
				<tr>
					<td></td>
					<td><input type="number" name="advanced_ad[sticky][position][top]" title="<?php esc_html_e( 'top', 'advanced-ads-sticky' ); ?>" value="<?php echo absint( $top ); ?>"/></td>
					<td></td>
				</tr>
				<tr>
					<td><input type="number" name="advanced_ad[sticky][position][left]" title="<?php esc_html_e( 'left', 'advanced-ads-sticky' ); ?> "value="<?php echo absint( $left ); ?>"/></td>
					<td></td>
					<td><input type="number" name="advanced_ad[sticky][position][right]" title="<?php esc_html_e( 'right', 'advanced-ads-sticky' ); ?>" value="<?php echo absint( $right ); ?>"/></td>
				</tr>
				<tr>
					<td></td>
					<td><input type="number" name="advanced_ad[sticky][position][bottom]" title="<?php esc_html_e( 'bottom', 'advanced-ads-sticky' ); ?>" value="<?php echo absint( $bottom ); ?>"/></td>
					<td></td>
				<tr>
			</table>
		</div>
		<div class='clear'></div>
	</div>
</div>
<style>
#advads-sticky-ads > div { float: left; width: 50%; min-width: 200px; }
.advads-sticky-numbers input { width: 5em;}
.advads-sticky-assistant table, .advads-sticky-numbers { border: 1px solid #ddd; }
.advads-sticky-assistant td { width: 3em; height: 2em; text-align: center; }
#advads-sticky-ads div.clear { content: ' '; display: block; float: none; clear: both; }
</style>
<script>
	jQuery('#advanced_ad_sticky_type_assistant').change(function(){
		advads_toggle_box_enable(this, '.advads-sticky-assistant');
		advads_toggle_box_enable(document.getElementById('advanced_ad_sticky_type_absolute'), '.advads-sticky-numbers');
	})
	jQuery('#advanced_ad_sticky_type_absolute').change(function(){
		advads_toggle_box_enable(this, '.advads-sticky-numbers');
		advads_toggle_box_enable(document.getElementById('advanced_ad_sticky_type_assistant'), '.advads-sticky-assistant');
	})
	// disable/enable on load
	advads_toggle_box_enable(document.getElementById('advanced_ad_sticky_type_absolute'), '.advads-sticky-numbers');
	advads_toggle_box_enable(document.getElementById('advanced_ad_sticky_type_assistant'), '.advads-sticky-assistant');
</script>
