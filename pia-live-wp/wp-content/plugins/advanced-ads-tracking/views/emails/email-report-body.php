<?php
/**
 * Aggregated email report template
 *
 * @since   2.6.0
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 *
 * @var string   $ad_name        ad title in the case of an individual ad report.
 * @var array    $textual_period translated string for the stats calculation period.
 * @var string   $period         stats period calculation.
 * @var string   $header_style   table header CSS.
 * @var string   $cell_style     table body CSS.
 * @var DateTime $today          Today.
 * @var string   $public_stats   link to shareable stats page in the case of an individual ad report.
 * @var array    $impr_stats     impressions data for the period.
 * @var array    $click_stats    clickss data for the period.
 * @var array    $report_args    the report arguments.
 */

use AdvancedAds\Tracking\Utilities\Email;

$impr_sum  = 0;
$click_sum = 0;
?>

<div style="margin: 0.4em auto;position:relative;width:460px;overflow:visible;">
	<h3 style="font-size:1.3em;"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h3>
	<?php if ( $ad_name ) : ?>
		<h4 style="font-size:1.2em;">
			<?php
			printf(
				/* translators: 1. ad title. 2. period for statistic calculation */
				esc_html__( '%1$s statistics for %2$s', 'advanced-ads-tracking' ),
				'<strong><em>' . esc_html( $ad_name ) . '</em></strong>',
				esc_html( $textual_period[ $period ] )
			);
			?>
		</h4>
	<?php else : ?>
		<?php /* translators: %s is a period string. */ ?>
		<h4 style="font-size:1.2em;"><?php printf( esc_html__( 'Ads statistics for %s', 'advanced-ads-tracking' ), esc_html( $textual_period[ $period ] ) ); ?></h4>
	<?php endif; ?>
	<?php do_action( 'advanced-ads-tracking-email-report-below-headline' ); ?>
	<?php if ( ! $impr_stats ) : ?>
		<p style="font-size:1.1em;"><em><?php esc_html_e( 'There is no data for the given period, yet.', 'advanced-ads-tracking' ); ?></em></p>
	<?php else : ?>
		<table style="border:1px solid;border-collapse:collapse;">
			<thead>
			<tr>
				<th style="<?php echo esc_attr( $header_style ); ?>"><?php esc_html_e( 'date', 'advanced-ads-tracking' ); ?></th>
				<th style="<?php echo esc_attr( $header_style ); ?>"><?php esc_html_e( 'impressions', 'advanced-ads-tracking' ); ?></th>
				<th style="<?php echo esc_attr( $header_style ); ?>"><?php esc_html_e( 'clicks', 'advanced-ads-tracking' ); ?></th>
				<th style="<?php echo esc_attr( $header_style ); ?>">
					<span title="<?php echo esc_attr__( 'click through rate', 'advanced-ads-tracking' ); ?>" style="cursor:help;">
						<?php esc_html_e( 'CTR', 'advanced-ads-tracking' ); ?>
					</span>
				</th>
			</tr>
			</thead>
			<tbody>
			<?php $impr_stats = array_reverse( $impr_stats ); ?>
			<?php foreach ( $impr_stats as $date => $impr ) : ?>
				<?php
				$total_impr   = ( is_array( $impr ) ) ? array_sum( $impr ) : 0;
				$total_clicks = ( isset( $click_stats[ $date ] ) && is_array( $click_stats[ $date ] ) ) ? array_sum( $click_stats[ $date ] ) : 0;
				$ctr          = 0 !== $total_impr ? number_format( 100 * $total_clicks / $total_impr, 2 ) . '%' : '0.00%';
				/**
				 *  Avoid sending the partial stats (if any at the moment the email is sent) for the current day for the "last 30 days".
				 */
				if ( 'last30days' === $period && $date === $today->format( 'Y-m-d' ) ) {
					continue;
				}
				/**
				 *  Avoid printing the 13th month (the current month) for last 12 months
				 */
				if ( 'last12months' === $period && $date === $today->format( 'Y-m-01' ) ) {
					continue;
				}
				$impr_sum  += $total_impr;
				$click_sum += $total_clicks
				?>
				<tr>
					<td style="<?php echo esc_attr( $cell_style ); ?>">
						<?php
						if ( 'last12months' === $period ) {
							echo esc_html( date_i18n( 'F Y', strtotime( $date ) ) );
						} else {
							echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $date ) ) );
						}
						?>
					</td>
					<td style="<?php echo esc_attr( $cell_style ); ?>"><?php echo esc_html( $total_impr ); ?></td>
					<td style="<?php echo esc_attr( $cell_style ); ?>"><?php echo esc_html( $total_clicks ); ?></td>
					<td style="<?php echo esc_attr( $cell_style ); ?>"><?php echo esc_html( $ctr ); ?></td>
				</tr>
			<?php endforeach; ?>
			<tr style="font-weight:600;">
				<td style="<?php echo esc_attr( $cell_style ); ?>"><?php esc_html_e( 'Total', 'advanced-ads-tracking' ); ?></td>
				<td style="<?php echo esc_attr( $cell_style ); ?>"><?php echo esc_html( $impr_sum ); ?></td>
				<td style="<?php echo esc_attr( $cell_style ); ?>"><?php echo esc_html( $click_sum ); ?></td>
				<td style="<?php echo esc_attr( $cell_style ); ?>"><?php echo 0 === $click_sum ? '0.00 %' : esc_html( number_format( 100 * $click_sum / $impr_sum, 2 ) . ' %' ); ?></td>
			</tr>
			</tbody>
		</table>
		<?php if ( $ad_name ) : ?>
			<p><a href="<?php echo esc_url( $public_stats ); ?>" target="_blank" style="font-size:1.1em;color:#1fa1d0;text-decoration:none;font-weight:bold;"><?php esc_html_e( 'View the live statistics', 'advanced-ads-tracking' ); ?></a></p>
		<?php endif; ?>
	<?php endif; ?>
	<?php if ( isset( $report_args['secondary_table'] ) ) : ?>
		<?php /* translators: %s is a period string. */ ?>
		<h4 style="font-size:1.2em;"><?php printf( esc_html__( 'Statistics per ad for %s', 'advanced-ads-tracking' ), esc_html( $textual_period[ $period ] ) ); ?></h4>
		<table style="border:1px solid;border-collapse:collapse;">
			<thead>
			<tr style="font-weight:600;">
				<th style="<?php echo esc_attr( $header_style ); ?>"><?php esc_html_e( 'Ad', 'advanced-ads-tracking' ); ?></th>
				<th style="<?php echo esc_attr( $header_style ); ?>"><?php esc_html_e( 'impressions', 'advanced-ads-tracking' ); ?></th>
				<th style="<?php echo esc_attr( $header_style ); ?>"><?php esc_html_e( 'clicks', 'advanced-ads-tracking' ); ?></th>
				<th style="<?php echo esc_attr( $header_style ); ?>">
					<span title="<?php echo esc_attr__( 'click through rate', 'advanced-ads-tracking' ); ?>" style="cursor:help;">
						<?php esc_html_e( 'CTR', 'advanced-ads-tracking' ); ?>
					</span>
				</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( Email::get_aggergated_stats_by_ad( $impr_stats, $click_stats ) as $ad_id => $stats ) : ?>
				<tr>
					<td style="<?php echo esc_attr( $cell_style ); ?>"><?php echo esc_html( $stats['name'] ); ?></td>
					<td style="<?php echo esc_attr( $cell_style ); ?>">
						<?php if ( $stats['impression_tracking'] ) : ?>
							<?php echo esc_html( $stats['impressions'] ); ?>
						<?php else : ?>
							<?php esc_html_e( 'disabled', 'advanced-ads-tracking' ); ?>
						<?php endif; ?>
					</td>
					<td style="<?php echo esc_attr( $cell_style ); ?>">
						<?php if ( $stats['click_tracking'] ) : ?>
							<?php echo esc_html( $stats['clicks'] ); ?>
						<?php else : ?>
							<?php esc_html_e( 'disabled', 'advanced-ads-tracking' ); ?>
						<?php endif; ?>
					</td>
					<td style="<?php echo esc_attr( $cell_style ); ?>"><?php echo esc_html( $stats['ctr'] ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
	<?php do_action( 'advanced-ads-tracking-email-report-below-content' ); ?>
</div>
