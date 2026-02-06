<?php
/**
 * The view to render the effects option.
 *
 * @package AdvancedAds\StickyAds
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   1.9.0
 *
 * @var array  $data        Placement data.
 * @var array  $options     Placement sticky options.
 * @var string $option_name Option name.
 */

use AdvancedAds\Utilities\WordPress;

ob_start();

$effect   = $options['effect'] ?? 'show';
$duration = absint( $options['duration'] ?? 0 );
?>
<label>
	<input type="radio" name="<?php echo esc_attr( $option_name ); ?>[effect]" value="show" <?php checked( $effect, 'show' ); ?>/><?php esc_html_e( 'Show', 'advanced-ads-sticky' ); ?>
</label>

<label>
	<input type="radio" name="<?php echo esc_attr( $option_name ); ?>[effect]" value="fadein" <?php checked( $effect, 'fadein' ); ?>/><?php esc_html_e( 'Fade in', 'advanced-ads-sticky' ); ?>
</label>

<label>
	<input type="radio" name="<?php echo esc_attr( $option_name ); ?>[effect]" value="slidedown" <?php checked( $effect, 'slidedown' ); ?>/><?php esc_html_e( 'Slide in', 'advanced-ads-sticky' ); ?>
</label>

<p class="description">
	<?php esc_html_e( 'Type of effect when the ad is being displayed', 'advanced-ads-sticky' ); ?>
</p>
<br/>
<input type="number" name="<?php echo esc_attr( $option_name ); ?>[duration]" value="<?php echo esc_attr( $duration ); ?>"/>
<p class="description">
	<?php esc_html_e( 'Duration of the effect (in milliseconds).', 'advanced-ads-sticky' ); ?>
</p>
<?php
$option_content = ob_get_clean();

WordPress::render_option(
	'placement-sticky-effect',
	__( 'effect', 'advanced-ads-sticky' ),
	$option_content
);
