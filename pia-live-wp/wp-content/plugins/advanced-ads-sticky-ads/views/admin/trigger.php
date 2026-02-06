<?php
/**
 * The view to render the trigger option.
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

$trigger = $options['trigger'] ?? '';
$delay   = absint( $options['delay'] ?? 0 );

?>
<ul>
	<li>
		<label>
			<input type="radio" name="<?php echo esc_attr( $option_name ); ?>[trigger]" value="" <?php checked( $trigger, '' ); ?>/><?php esc_html_e( 'right away', 'advanced-ads-sticky' ); ?>
		</label>
	</li>
	<li>
		<label>
			<input type="radio" name="<?php echo esc_attr( $option_name ); ?>[trigger]" value="effect" <?php checked( $trigger, 'effect' ); ?>/><?php esc_html_e( 'right away with effect', 'advanced-ads-sticky' ); ?>
		</label>
	</li>
	<li>
		<label>
			<input type="radio" name="<?php echo esc_attr( $option_name ); ?>[trigger]" value="timeout"<?php checked( $trigger, 'timeout' ); ?>/>
			<?php
			printf(
				/* translators: %s: number of seconds */
				__( 'after %s seconds', 'advanced-ads-sticky' ), // phpcs:ignore
				'<input type="number" name="' . esc_attr( $option_name ) . '[delay]" value="' . $delay . '"/>' // phpcs:ignore
			);
			?>
		</label>
	</li>
</ul>
<?php
$option_content = ob_get_clean();

WordPress::render_option(
	'placement-sticky-trigger',
	__( 'show the ad', 'advanced-ads-sticky' ),
	$option_content
);
