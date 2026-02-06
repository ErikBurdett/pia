<?php
/**
 * Public stats template.
 *
 * @since   2.6.0
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 */

defined( 'ABSPATH' ) || exit;

use AdvancedAds\Tracking\Database;
use AdvancedAds\Framework\Utilities\Params;

if ( ! isset( $ad_id ) ) {
	die;
}
$period      = Params::get( 'period', 'last30days' );
$from        = Params::get( 'from', null );
$to          = Params::get( 'to', null );
$ad          = wp_advads_get_ad( $ad_id );
$public_name = $ad->get_prop( 'tracking.public-name' );
$ad_name     = empty( $public_name ) ? $ad->get_title() : $public_name;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php echo esc_attr( get_option( 'blog_charset' ) ); ?>">
	<title><?php echo esc_html( get_bloginfo( 'name' ) ); ?> | <?php esc_html_e( 'Ad Statistics', 'advanced-ads-tracking' ); ?></title>
	<meta name="robots" content="noindex, nofollow"/>
	<script type="text/javascript">
		var AAT_SPINNER_URL = '<?php echo esc_url( AA_TRACKING_BASE_URL . 'assets/img/spinner-2x.gif' ); ?>';
	</script>
	<?php do_action( 'advanced-ads-public-stats-head' ); ?>
</head>
<body>
<div id="stats-head">
	<h1 id="stats-title"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h1>
	<div id="stats-period">
		<table style="width: 100%;">
			<tbody>
			<tr>
				<td style="width:50%;text-align:center;">
					<h3 id="ad-title">
						<?php
						/* translators: %s: ad name */
						echo esc_html( sprintf( __( 'Statistics for %s', 'advanced-ads-tracking' ), $ad_name ) );
						?>
					</h3>
				</td>
				<td style="width:50%;text-align:center;">
					<form method="get" id="period-form">
						<label><?php esc_html_e( 'Period', 'advanced-ads-tracking' ); ?>:&nbsp;</label>
						<select name="period">
							<option value="last30days" <?php selected( 'last30days', $period ); ?>><?php esc_html_e( 'last 30 days', 'advanced-ads-tracking' ); ?></option>
							<option value="lastmonth" <?php selected( 'lastmonth', $period ); ?>><?php esc_html_e( 'last month', 'advanced-ads-tracking' ); ?></option>
							<option value="last12months" <?php selected( 'last12months', $period ); ?>><?php esc_html_e( 'last 12 months', 'advanced-ads-tracking' ); ?></option>
							<option value="custom" <?php selected( 'custom', $period ); ?>><?php esc_html_e( 'custom', 'advanced-ads-tracking' ); ?></option>
						</select>
					</form>
					<?php
					$form_attr = '';
					if ( 'custom' === $period ) {
						$form_attr = 'form="period-form" required';
					}
					?>
					<input <?php echo esc_attr( $form_attr ); ?> type="text" name="from" class="stats-from<?php echo 'custom' !== $period ? ' hidden' : ''; ?>" value="<?php echo esc_attr( $from ); ?>" autocomplete="off" size="10" maxlength="10" placeholder="<?php esc_html_e( 'from', 'advanced-ads-tracking' ); ?>"/>
					<input <?php echo esc_attr( $form_attr ); ?> type="text" name="to" class="stats-to<?php echo 'custom' !== $period ? ' hidden' : ''; ?>" value="<?php echo esc_attr( $to ); ?>" autocomplete="off" size="10" maxlength="10" placeholder="<?php esc_html_e( 'to', 'advanced-ads-tracking' ); ?>"/>
					<input form="period-form" type="submit" class="button button-primary" value="<?php echo esc_attr( __( 'Load', 'advanced-ads-tracking' ) ); ?>"/>
				</td>
			</tr>
			</tbody>
		</table>
	</div>
</div>
<?php
$wptz  = Advanced_Ads_Utils::get_wp_timezone();
$today = date_create( 'now', $wptz );
$args  = [
	'ad_id'       => [ $ad_id ],
	'period'      => 'lastmonth',
	'groupby'     => 'day',
	'groupFormat' => 'Y-m-d',
	'from'        => null,
	'to'          => null,
];

if ( 'last30days' === $period ) {
	$start_ts = (int) $today->format( 'U' );
	// unlike with emails, send the current day, then the last 30 days stops at ( today - 29 days ).
	$start_ts = $start_ts - ( 29 * 24 * 60 * 60 );

	$start = date_create( '@' . $start_ts, $wptz );

	$args['period'] = 'custom';
	$args['from']   = $start->format( 'm/d/Y' );

	$end_ts = (int) $today->format( 'U' );
	$end    = date_create( '@' . $end_ts, $wptz );

	$args['to'] = $end->format( 'm/d/Y' );
}

if ( 'last12months' === $period) {
	$current_year  = (int) $today->format( 'Y' );
	$current_month = (int) $today->format( 'm' );
	$past_year     = $current_year - 1;

	$args['period']  = 'custom';
	$args['groupby'] = 'month';

	$args['from'] = $today->format( 'm/01/' . $past_year );
	$args['to']   = $today->format( 'm/d/Y' );
}

if ( 'custom' === $period ) {
	if ( empty( $from ) || empty( $to ) ) {
		return false;
	}

	$custom_from          = new DateTime( $from );
	$custom_to            = new DateTime( $to );
	$day_difference       = $custom_from->diff( $custom_to )->days;
	$is_custom_days_limit = $day_difference > 28;
	$args['groupby']      = $is_custom_days_limit ? 'month' : 'day';

	$args['period'] = 'custom';
	$args['from']   = $custom_from->format( 'm/d/Y' );
	$args['to']     = $custom_to->format( 'm/d/Y' );
}

$impr_stats   = Database::load_stats( $args, Database::get_impression_table() );
$clicks_stats = Database::load_stats( $args, Database::get_click_table() );

// If clicks only are present, fill impressions with zero.
if ( ! $impr_stats && is_array( $clicks_stats ) ) {
	$impr_stats = $clicks_stats;
	foreach ( $impr_stats as $date => $_stats ) {
		foreach ( $_stats as $key => $value ) {
			$impr_stats[ $date ][ $key ] = 0;
		}
	}
}

$impr_series   = [];
$clicks_series = [];
$first_date    = false;
$max_clicks    = 0;
$max_impr      = 0;

if ( isset( $impr_stats ) && is_array( $impr_stats ) ) {
	foreach ( $impr_stats as $date => $impressions ) {
		if ( ! $first_date ) {
			$first_date = $date;
		}
		$impr = 0;
		if ( isset( $impressions[ $ad_id ] ) ) {
			$impr_series[] = [ $date, $impressions[ $ad_id ] ];
			$impr          = $impressions[ $ad_id ];
		} else {
			$impr_series[] = [ $date, 0 ];
		}
		$clicks = 0;
		if ( isset( $clicks_stats[ $date ] ) && isset( $clicks_stats[ $date ][ $ad_id ] ) ) {
			$clicks_series[] = [ $date, $clicks_stats[ $date ][ $ad_id ] ];
			$clicks          = $clicks_stats[ $date ][ $ad_id ];
		} else {
			$clicks_series[] = [ $date, 0 ];
		}
		if ( $impr > $max_impr ) {
			$max_impr = $impr;
		}
		if ( $clicks > $max_clicks ) {
			$max_clicks = $clicks;
		}
	}
}
$lines = [ $impr_series, $clicks_series ];
?>
<div id="stats-content">
	<script type="text/javascript">
		var statsGraphOptions = {
			axes:        {
				xaxis:  {
					renderer:     null,
					<?php if ( 'last12months' === $period || ( 'custom' === $period && $is_custom_days_limit ) ) : ?>
					tickOptions:  { formatString: '%b %Y' },
					<?php else : ?>
					tickOptions:  { formatString: '%b%d' },
					<?php endif; ?>
					tickInterval: '1 <?php echo $args['groupby']; // phpcs:ignore ?>',
					min:          '<?php echo $first_date; // phpcs:ignore ?>'
				},
				yaxis:  {
					min:         0,
					max: <?php echo ( (int) ( $max_impr * 1.1 / 10 ) + 1 ) * 10; ?>,
					label:       '<?php esc_html_e( 'impressions', 'advanced-ads-tracking' ); ?>',
					tickOptions: {formatString: '%\'.0f'}
				},
				y2axis: {
					min:         0,
					max: <?php echo ( (int) ( $max_clicks * 1.3 / 10 ) + 1 ) * 10; ?>,
					label:       '<?php esc_html_e( 'clicks', 'advanced-ads-tracking' ); ?>',
					tickOptions: {formatString: '%\'.0f'}
				}
			},
			grid: {
				background: '#ffffff',
				borderWidth: 2.0,
				shadow: false,
				gridLineColor: '#e5e5e5',
				drawBorder: false
			},
			highlighter: {
				show: true
			},
			cursor:      {
				show: false
			},
			title:       {
				show: true
			},
			seriesDefaults: {
				rendererOptions: {
					smooth: true
				}
			},
			series:      [
				{
					highlighter:   {
						formatString: "%s: %'.0f <?php esc_html_e( 'impressions', 'advanced-ads-tracking' ); ?>"
					},
					lineWidth:     3,
					markerOptions: {style: 'circle', size: 5},
					color:         '#1B183A',
					label:         '<?php esc_html_e( 'impressions', 'advanced-ads-tracking' ); ?>'
				}, // impressions
				{
					yaxis:         'y2axis',
					highlighter:   {
						formatString: "%s: %'.0f <?php esc_html_e( 'clicks', 'advanced-ads-tracking' ); ?>"
					},
					lineWidth:     3,
					linePattern:   'dashed',
					markerOptions: {style: 'filledSquare', size: 5},
					color:         '#0474A2',
					label:         '<?php esc_html_e( 'clicks', 'advanced-ads-tracking' ); ?>'
				} // clicks
			]
		};
		var lines             = <?php echo wp_json_encode( $lines ); ?>;
	</script>
	<?php if ( $ad->is_type( 'image' ) ) : ?>
	<div id="image-ad-preview">
		<?php $src = wp_get_attachment_image_src( $ad->get_image_id(), 'medium' ); ?>
		<img src="<?php echo esc_url( $src[0] ); ?>" alt=""/>
	</div>
	<?php endif; ?>
	<div id="public-stat-graph"></div>
	<div id="graph-legend">
		<div class="legend-item">
			<div id="impr-legend"></div>
			<span class="legend-text"><?php esc_html_e( 'impressions', 'advanced-ads-tracking' ); ?></span>
		</div>
		<div class="legend-item">
			<div id="click-legend"></div>
			<span class="legend-text"><?php esc_html_e( 'clicks', 'advanced-ads-tracking' ); ?></span>
		</div>
	</div>
	<hr/>
	<table id="public-stat-table">
		<thead>
		<th><?php esc_html_e( 'date', 'advanced-ads-tracking' ); ?></th>
		<th><?php esc_html_e( 'impressions', 'advanced-ads-tracking' ); ?></th>
		<th><?php esc_html_e( 'clicks', 'advanced-ads-tracking' ); ?></th>
		<th><?php esc_html_e( 'ctr', 'advanced-ads-tracking' ); ?></th>
		</thead>
		<tbody>
		<?php
		if ( isset( $impr_stats ) && is_array( $impr_stats ) ) :
			$impr_stats = array_reverse( $impr_stats );
			$impr_sum   = 0;
			$click_sum  = 0;
			foreach ( $impr_stats as $date => $all ) :
				?>
				<tr>
					<td>
						<?php
						$format = get_option( 'date_format' );
						if ( 'last12months' === $period || ( 'custom' === $period && $is_custom_days_limit ) ) {
							$format = 'M Y';
						}

						echo date_i18n( $format, strtotime( $date ) ); // phpcs:ignore
						?>
					</td>
					<td>
						<?php
						$impr      = ( isset( $all[ $ad_id ] ) ) ? $all[ $ad_id ] : 0;
						$impr_sum += $impr;
						echo number_format_i18n( $impr ); // phpcs:ignore
						?>
					</td>
					<td>
						<?php
						$click      = ( isset( $clicks_stats[ $date ] ) && isset( $clicks_stats[ $date ][ $ad_id ] ) ) ? $clicks_stats[ $date ][ $ad_id ] : 0;
						$click_sum += $click;
						echo number_format_i18n( $click ); // phpcs:ignore
						?>
					</td>
					<td>
						<?php
						$ctr = 0;
						if ( 0 !== (int) $impr ) {
							$ctr = $click / $impr * 100;
						}
						echo number_format( $ctr, 2 ) . ' %';
						?>
					</td>
				</tr>
			<?php endforeach; ?>
			<tr style="background-color:#f0f0f0;color:#222;font-weight:bold;">
				<td><?php esc_html_e( 'Total', 'advanced-ads-tracking' ); ?></td>
				<td><?php echo number_format_i18n( $impr_sum ); // phpcs:ignore ?></td>
				<td><?php echo number_format_i18n( $click_sum ); // phpcs:ignore ?></td>
				<td><?php echo 0 === $impr_sum ? '0' : number_format_i18n( 100 * $click_sum / $impr_sum, 2 ) . ' %'; // phpcs:ignore ?></td>
			</tr>
		<?php endif; ?>
		</tbody>
	</table>
	<hr/>
</div>
</body>
</html>
