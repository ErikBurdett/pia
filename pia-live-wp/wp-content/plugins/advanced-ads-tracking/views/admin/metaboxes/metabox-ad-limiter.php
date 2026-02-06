<?php
/**
 * Limiter section for stats metabox on single ad view.
 *
 * @since   2.6.0
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 *
 * @var Ad_Limiter $limiter          Instance of limiter for this ad.
 * @var int        $impression_limit The impression limit for this ad.
 * @var int        $click_limit      The click limit for this ad.
 * @var bool       $use_clicks       Whether click tracking is active.
 * @var array      $sums             Array with `impression` and `clicks` sums.
 */

use AdvancedAds\Tracking\Helpers;

$pace         = $limiter->get_pace();
$current_hour = $limiter->get_current_hour();
$limiter->recalculate_sums();

$reset_stats_url = add_query_arg(
	'reset-stats-id',
	$post->ID,
	Helpers::get_database_tool_link()
);
?>
<span class="label"><?php esc_html_e( 'limits', 'advanced-ads-tracking' ); ?></span>
<div>
	<table id="advads-ad-stats" class="table widefat">
		<thead>
			<tr class="alternate">
				<th></th>
				<th><strong><?php esc_html_e( 'overall', 'advanced-ads-tracking' ); ?></strong></th>
				<?php if ( $limiter->has_expiration() ) : ?>
					<th><strong><?php esc_html_e( 'hourly limit', 'advanced-ads-tracking' ); ?></strong></th>
					<th><strong><?php esc_html_e( 'this hour', 'advanced-ads-tracking' ); ?></strong></th>
				<?php endif; ?>
				<th><strong><?php esc_html_e( 'limit', 'advanced-ads-tracking' ); ?></strong></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<th><strong><?php esc_html_e( 'impressions', 'advanced-ads-tracking' ); ?></strong></th>
				<td><?php echo esc_html( number_format_i18n( $sums['impressions'] ) ); ?></td>
				<?php if ( $limiter->has_expiration() ) : ?>
					<td><?php echo esc_html( number_format_i18n( $pace['pace']['impressions'] ) ); ?></td>
					<td><?php echo esc_html( number_format_i18n( $current_hour['impressions'] ) ); ?></td>
				<?php endif; ?>
				<td>
					<input name="advanced_ad[tracking][impression_limit]" type="number" value="<?php echo esc_attr( $impression_limit ); ?>"/>
				</td>
			</tr>
			<tr class="advads-tracking-click-limit-row" style="<?php echo( ! $use_clicks ? 'display: none;' : '' ); ?>">
				<th><strong><?php esc_html_e( 'clicks', 'advanced-ads-tracking' ); ?></strong></th>
				<td><?php echo esc_html( number_format_i18n( $sums['clicks'] ) ); ?></td>
				<?php if ( $limiter->has_expiration() ) : ?>
					<td><?php echo esc_html( number_format_i18n( $pace['pace']['clicks'] ) ); ?></td>
					<td><?php echo esc_html( number_format_i18n( $current_hour['clicks'] ) ); ?></td>
				<?php endif; ?>
				<td>
					<input name="advanced_ad[tracking][click_limit]" type="number" value="<?php echo esc_attr( $click_limit ); ?>"/>
				</td>
			</tr>
		</tbody>
	</table>
	<p class="description">
		<?php esc_html_e( 'Set a limit if you want to expire the ad after a specific amount of impressions or clicks.', 'advanced-ads-tracking' ); ?>
		<a href="https://wpadvancedads.com/limit-ad-impressions-or-clicks/?utm_source=advanced-ads?utm_medium=link&utm_campaign=ad-edit-ad-limits" class="advads-manual-link" target="_blank">
			<?php esc_html_e( 'Manual', 'advanced-ads-tracking' ); ?>
		</a>
	</p>
	<p><a href="<?php echo esc_url( $reset_stats_url ); ?>" target="_blank"><?php esc_html_e( 'Reset stats', 'advanced-ads-tracking' ); ?></a></p>
</div>
<hr/>
<?php
if ( empty( $impression_limit ) && empty( $click_limit ) ) {
	return;
}

$limits_type_label = __( 'impressions', 'advanced-ads-tracking' );
$limits_type       = 'impressions';

if ( $use_clicks && ! empty( $click_limit ) ) {
	$limits_type       = 'clicks';
	$limits_type_label = __( 'clicks', 'advanced-ads-tracking' );
	if ( ! empty( $impression_limit ) ) {
		$limits_type       = 'all';
		$limits_type_label = __( 'impressions or clicks', 'advanced-ads-tracking' );
	}
}
?>
<p class="description advads-notice-inline advads-idea">
	<?php
	$click_limit_reached      = $limiter->is_click_limit_reached();
	$impression_limit_reached = $limiter->is_impression_limit_reached();
	$hourly_limit_disabled    = $limiter->is_hourly_limit_disabled();

	if ( $click_limit_reached && $impression_limit_reached ) {
		esc_html_e( 'The overall goals for impressions and clicks have been reached.', 'advanced-ads-tracking' );
	} elseif ( $click_limit_reached ) {
		esc_html_e( 'The overall goal for clicks has been reached.', 'advanced-ads-tracking' );
	} elseif ( $impression_limit_reached ) {
		esc_html_e( 'The overall goal for impressions has been reached.', 'advanced-ads-tracking' );
	} elseif ( empty( $options['expiry_date'] ) || $hourly_limit_disabled ) {
		printf(
		/* Translators: impressions, clicks, or impressions or clicks */
			esc_html__( 'The ad %s will be delivered as soon as possible.', 'advanced-ads-tracking' ),
			esc_attr( $limits_type_label )
		);

		echo ' ';

		if ( $hourly_limit_disabled ) {
			printf(
			/* Translators: ADVANCED_ADS_TRACKING_NO_HOURLY_LIMIT constant name in <code> tag */
				esc_html__( 'The constant %s is set to true.', 'advanced-ads-tracking' ),
				sprintf( '<code>%s</code>', 'ADVANCED_ADS_TRACKING_NO_HOURLY_LIMIT' )
			);
		} else {
			echo wp_kses(
				__( 'Set an expiry date in the <em>Publish</em> meta box to spread impressions over a period.', 'advanced-ads-tracking' ),
				[ 'em' => [] ]
			);
		}
	} else {
		$remaining = $limiter->get_remaining();
		// expiration set and not yet expired.
		if ( time() <= $options['expiry_date'] ) {
			if ( ! empty( $limiter->get_impressions_limit() ) && empty( $remaining['impressions'] ) ) {
				printf(
					'%s %s',
					esc_html__( 'The impressions goal for the current hour has been reached.', 'advanced-ads-tracking' ),
					esc_html__( 'Impressions will resume in the next hour', 'advanced-ads-tracking' )
				);
			} elseif ( ! empty( $limiter->get_clicks_limit() ) && empty( $remaining['clicks'] ) ) {
				printf(
					'%s %s',
					esc_html__( 'The clicks goal for the current hour has been reached.', 'advanced-ads-tracking' ),
					esc_html__( 'Impressions will resume in the next hour', 'advanced-ads-tracking' )
				);
			} else {
				if ( isset( $pace['impressions'] ) ) {
					$pace['impressions'] = number_format_i18n( $pace['impressions'] );
				}
				if ( isset( $pace['clicks'] ) ) {
					$pace['clicks'] = number_format_i18n( $pace['clicks'] );
				}

				$remaining = $limiter->get_remaining_time_string();

				if ( 'all' !== $limits_type ) {
					printf(
					/* Translators: 1: "impressions" or "clicks", 2: the remaining time string 3: the current pace */
						esc_html__( 'The %1$s are spread equally through %2$s currently with a limit of %3$d %1$s per hour.', 'advanced-ads-tracking' ),
						esc_attr( $limits_type_label ),
						esc_attr( $remaining ),
						esc_attr( number_format_i18n( $pace['pace'][ $limits_type ] ) )
					);
				} else {
					printf(
					/* Translators: 1: "impressions and clicks", 2: the remaining time string 3: the current impression pace, 4: the current click pace */
						esc_html__( 'The %1$s are spread equally through %2$s currently with a limit of %3$s impressions or %4$s clicks per hour.', 'advanced-ads-tracking' ),
						esc_attr( $limits_type_label ),
						esc_attr( $remaining ),
						esc_attr( number_format_i18n( $pace['pace']['impressions'] ) ),
						esc_attr( number_format_i18n( $pace['pace']['clicks'] ) )
					);
				}
			}
		} else {
			// Ad has already expired.
			esc_html_e( 'This ad expired already.', 'advanced-ads-tracking' );
		}
	}
	?>
</p>
<hr/>
