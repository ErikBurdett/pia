<?php
/**
 * Show the warnings.
 *
 * @since   2.6.0
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 *
 * @var array $warnings Array holding admin notices/warnings.
 */

?>
<ul id="tracking-ads-box-notices" class="advads-metabox-notices">
<?php foreach ( $warnings as $_warning ) : ?>
	<li <?php echo isset( $_warning['class'] ) ? 'class="' . esc_attr( $_warning['class'] ) . '"' : ''; ?>>
	<?php
	echo wp_kses(
		$_warning['text'],
		[
			'a' => [
				'href'   => [],
				'target' => [],
			],
		]
	);
	?>
	</li>
<?php endforeach; ?>
</ul>
