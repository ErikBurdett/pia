<?php
/**
 * Render performing ads widget.
 *
 * @package AdvancedAds\Tracking
 * @since   2.6.0
 *
 * @var array $stats Stats array containing clicks, impressions, and ctr.
 */

/**
 * Render no record message.
 */
function render_no_record_message() {
	?>
	<p><?php esc_html_e( 'There is no record for this period.', 'advanced-ads-tracking' ); ?></p>
	<?php
}

/**
 * Render ad performance.
 *
 * @param array  $data   Data array.
 * @param string $metric Metric label.
 */
function render_ad_performance( $data, $metric ) {
	if ( empty( $data ) ) {
		render_no_record_message();
		return;
	}

	$index = 1;
	foreach ( $data as $ad_id => $value ) {
		?>
		<div class="advads-performing-table-row">
			<div class="advads-performing-table-cell"><?php echo esc_html( $index++ ) . '.'; ?></div>
			<div class="advads-performing-table-cell">
				<?php
				$label = $metric;
				if ( 'impressions' === $metric ) {
					$label = _n( 'Impression', 'Impressions', $value, 'advanced-ads-tracking' );
				} elseif ( 'clicks' === $metric ) {
					$label = _n( 'Click', 'Clicks', $value, 'advanced-ads-tracking' );
				}

				printf( '%s %s', esc_html( $value ), esc_html( $label ) );
				?>
			</div>
			<div class="advads-performing-table-cell">
				<a href="<?php echo esc_url( get_edit_post_link( $ad_id ) ); ?>" target="_blank" title="<?php echo esc_attr( get_the_title( $ad_id ) ); ?>"><?php echo esc_html( get_the_title( $ad_id ) ); ?></a>
			</div>
		</div>
		<?php
	}
}

if ( empty( $stats['clicks'] ) && empty( $stats['impressions'] ) && empty( $stats['ctr'] ) ) {
	render_no_record_message();
	return;
}
?>

<div class="advads-performing-tabs advads-performing-tab-clicks">
	<div class="advads-performing-table">
		<?php render_ad_performance( $stats['clicks'], 'clicks' ); ?>
	</div>
</div>

<div class="advads-performing-tabs advads-performing-tab-impressions">
	<div class="advads-performing-table">
		<?php render_ad_performance( $stats['impressions'], 'impressions' ); ?>
	</div>
</div>

<div class="advads-performing-tabs advads-performing-tab-ctr">
	<div class="advads-performing-table">
		<?php render_ad_performance( $stats['ctr'], 'CTR' ); ?>
	</div>
</div>
