<?php
/**
 * Render the Statistics page under Advanced Ads > Statistics
 *
 * @package AdvancedAds
 * @author  Advanced Ads <info@wpadvancedads.com>
 *
 * @var string[] $periods       List of available periods in slug => translated string format.
 * @var string   $period        The chosen period slug for the current stats view.
 * @var string   $from          Start date for user defined period.
 * @var string   $to            End date for user defined period.
 * @var array    $groupbys      Group by argument for stats in slug => translated string format.
 * @var string   $groupby       Slug of the chosen group by for the current view.
 */

use AdvancedAds\Tracking\Database;
use AdvancedAds\Tracking\Db_Operations;
use AdvancedAds\Tracking\Helpers;

$all_ads          = Database::get_all_ads( 'dropdown' );
$autocomplete_src = [];

foreach ( $all_ads as $ad_id => $ad_title ) {
	$autocomplete_src[] = [
		'label' => $ad_title,
		'value' => $ad_id,
	];
}

$all_ads['length'] = count( $all_ads );

/**
 *  Ad groups
 */
$terms               = wp_advads_get_all_groups();
$groups_to_ads       = [];
$ads_to_groups       = [];
$groups_autocomplete = [];

foreach ( $terms as $ad_group ) {
	$ads_data = [];

	foreach ( wp_advads_get_ads_by_group_id( $ad_group->get_id() ) as $ad ) {
		$ads_data[ $ad->get_id() ] = [
			'ID'    => $ad->get_id(),
			'title' => $ad->get_title(),
		];
		if ( ! isset( $ads_to_groups[ $ad->get_id() ] ) ) {
			$ads_to_groups[ $ad->get_id() ] = [];
		}
		$ads_to_groups[ $ad->get_id() ][] = $ad_group->get_id();
	}

	$group_name = $ad_group->get_name();

	$groups_to_ads[ $ad_group->get_id() ] = [
		'ID'   => $ad_group->get_id(),
		'slug' => $ad_group->get_prop( 'slug' ),
		'name' => $group_name,
		'ads'  => $ads_data,
	];

	$groups_autocomplete[] = [
		'label' => $group_name,
		'value' => $ad_group->get_id(),
	];
}

$group_count             = count( $groups_to_ads );
$groups_to_ads['length'] = $group_count;

$formated_number = number_format_i18n( 12345.678, 3 );

?>
<script type="text/javascript">
	var groupsToAds      = <?php echo wp_json_encode( $groups_to_ads ); ?>;
	var adsToGroups      = <?php echo wp_json_encode( $ads_to_groups ); ?>;
	var groupAutoCompSrc = <?php echo wp_json_encode( $groups_autocomplete ); ?>;
	var numbersFormated  = "<?php echo esc_js( str_replace( '"', '\"', $formated_number ) ); ?>";
</script>
<div class="wrap">
	<?php // There needs to be an empty H2 headline at the top of the page so that WordPress can properly position admin notifications. ?>
	<h2 style="display: none;"></h2>
	<?php if ( Helpers::is_tracking_method( 'ga' ) ) : ?>
		<div class="notice advads-notice">
			<p>
				<?php
				printf(
					/* translators: %1$s is the opening link tag, %2$s is the closing link tag. */
					esc_html__( 'You are currently tracking ads with Google Analytics. The statistics can be viewed only within your %1$sAnalytics account%2$s.', 'advanced-ads-tracking' ),
					'<a href="https://analytics.google.com/analytics/web/" class="advads-external-link" target="_blank">',
					'</a>'
				);
				?>
			</p>
		</div>
	<?php endif; ?>
	<div class="postbox advads-box">
		<h2 class="hndle"><?php esc_html_e( 'Filter', 'advanced-ads-tracking' ); ?>
			<?php if ( current_user_can( 'manage_options' ) ) : ?>
				<span class="advads-hndlelinks"><a href="<?php echo esc_url( Helpers::get_database_tool_link() ); ?>"><?php esc_html_e( 'Database management', 'advanced-ads-tracking' ); ?></a></span>
			<?php endif; ?>
		</h2>
		<div class="inside">
			<form action="" method="post" id="stats-form">
				<input type="hidden" id="all-ads" value="<?php echo esc_attr( implode( '-', Database::get_all_ads( 'ids' ) ) ); ?>"/>
				<table id="period-table">
					<thead style="text-align:left;">
					<th><strong><?php esc_html_e( 'Period', 'advanced-ads-tracking' ); ?></strong></th>
					<th><strong><?php esc_html_e( 'Group by:', 'advanced-ads-tracking' ); ?></strong></th>
					<th>
						<?php
						if ( current_user_can( 'manage_options' ) ) :
							?>
							<strong><?php esc_html_e( 'Data source:', 'advanced-ads-tracking' ); ?></strong><?php endif; ?></th>
					<?php if ( current_user_can( 'manage_options' ) ) : ?>
						<th style="padding-left:6em;"></th>
					<?php endif; ?>
					</thead>
					<tbody>
					<tr>
						<td>
							<fieldset class="load-from-db-fields">
								<label>
									<select name="advads-stats[period]" class="advads-stats-period">
										<?php foreach ( $periods as $period_key => $period_title ) : ?>
											<option value="<?php echo esc_attr( $period_key ); ?>" <?php selected( $period_key, $period ); ?>><?php echo esc_html( $period_title ); ?></option>
										<?php endforeach; ?>
									</select>
								</label>
								<input type="text" name="advads-stats[from]" class="advads-stats-from<?php echo 'custom' !== $period ? ' hidden' : ''; ?>" value="<?php echo esc_attr( $from ); ?>" autocomplete="off" size="10" maxlength="10" placeholder="<?php esc_html_e( 'from', 'advanced-ads-tracking' ); ?>"/>
								<input type="text" name="advads-stats[to]" class="advads-stats-to<?php echo 'custom' !== $period ? ' hidden' : ''; ?>" value="<?php echo esc_attr( $to ); ?>" autocomplete="off" size="10" maxlength="10" placeholder="<?php esc_html_e( 'to', 'advanced-ads-tracking' ); ?>"/>
								<button class="button button-primary" id="load-simple"><?php esc_html_e( 'load stats', 'advanced-ads-tracking' ); ?></button>
							</fieldset>
							<fieldset class="load-from-file-fields" style="display:none;">
								<?php
								if ( current_user_can( 'manage_options' ) ) :
									$load_from_file_period_args = [
										'period-options' => [
											'latestmonth' => esc_html__( 'latest month', 'advanced-ads-tracking' ),
											'firstmonth'  => esc_html__( 'first month', 'advanced-ads-tracking' ),
										],
										'period'         => [ 'stats-file-period', '' ],
										'from'           => [ 'stats-file-from', '' ],
										'to'             => [ 'stats-file-to', '' ],
									];
									Db_Operations::period_select_inputs( $load_from_file_period_args );
									?>
									<button class="button button-primary" disabled id="load-stats-from-file"><?php esc_html_e( 'load stats', 'advanced-ads-tracking' ); ?></button>
								<?php endif; ?>
							</fieldset>
						</td>
						<td>
							<label>
								<select name="advads-stats[groupby]">
									<?php foreach ( $groupbys as $_groupby_key => $_groupby ) : ?>
										<option value="<?php echo esc_attr( $_groupby_key ); ?>" <?php selected( $_groupby_key, $groupby ); ?>><?php echo esc_html( $_groupby[1] ); ?></option>
									<?php endforeach; ?>
								</select>
								<span class="ajax-spinner-placeholder" id="statsA-spinner"></span>
							</label>
						</td>
						<td>
							<select id="data-source" <?php echo ! current_user_can( 'manage_options' ) ? 'style="display:none;"' : ''; ?>>
								<option value="db"><?php esc_html_e( 'Database', 'advanced-ads-tracking' ); ?></option>
								<?php if ( current_user_can( 'manage_options' ) ) : ?>
									<option value="file"><?php esc_html_e( 'File', 'advanced-ads-tracking' ); ?></option>
								<?php endif; ?>
							</select>
							<?php if ( current_user_can( 'manage_options' ) ) : ?>
								<span class="load-from-file-fields" style="display:none;">
						<button class="button button-secondary" id="select-file"><?php esc_html_e( 'select file', 'advanced-ads-tracking' ); ?></button>
						<span class="ajax-spinner-placeholder" id="file-spinner"></span>
						<span class="description" id="stats-file-description"><?php esc_html_e( 'no file selected', 'advanced-ads-tracking' ); ?></span>
						<input type="hidden" id="stats-attachment-id" value=""/>
						<input type="hidden" id="stats-attachment-firstdate" value=""/>
						<input type="hidden" id="stats-attachment-lastdate" value=""/>
						<input type="hidden" id="stats-attachment-adIDs" value=""/>
					</span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td colspan="3" id="period-td"></td>
					</tr>
					<tr id="compare-tr" <?php echo ( isset( $_REQUEST['advads-stats']['period2'] ) ) ? '' : 'style="display:none;"'; ?>><?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
						<td colspan="3" style="padding-top:1.5em;">
							<strong><?php esc_html_e( 'Compare with', 'advanced-ads-tracking' ); ?></strong>
							<fieldset>
								<button class="button button-secondary donotreversedisable" id="compare-prev-btn"><?php esc_html_e( 'previous period', 'advanced-ads-tracking' ); ?></button>
								&nbsp;&nbsp;
								<button class="button button-secondary donotreversedisable" id="compare-next-btn"><?php esc_html_e( 'next period', 'advanced-ads-tracking' ); ?></button>
								<input id="compare-offset" value="0" type="hidden"/>
								<input id="compare-from-prev" value="" type="hidden"/>
								<input id="compare-to-prev" value="" type="hidden"/>
								<input id="compare-from-next" value="" type="hidden"/>
								<input id="compare-to-next" value="" type="hidden"/>
							</fieldset>
						</td>
						<?php if ( current_user_can( 'manage_options' ) ) : ?>
							<td></td>
						<?php endif; ?>
					</tr>
					</tbody>
				</table>
				<hr/>
				<div id="ad-filter-wrap" style="float: left;">
					<label><strong><?php esc_html_e( 'Filter by ad', 'advanced-ads-tracking' ); ?></strong></label><br/>
					<input id="ad-filter" class="donotreversedisable" type="text" value="" <?php echo count( $all_ads ) < 2 ? 'disabled' : ''; ?>/>
					<script type="text/javascript">
						var adTitles    = <?php echo wp_json_encode( $all_ads ); ?>;
						var adTitlesDB  = <?php echo wp_json_encode( $all_ads ); ?>;
						var autoCompSrc = <?php echo wp_json_encode( $autocomplete_src ); ?>;
					</script>
				</div>
				<div id="group-filter-wrap">
					<?php if ( $groups_to_ads['length'] > 0 ) : ?>
						<label><strong><?php esc_html_e( 'Filter by group', 'advanced-ads-tracking' ); ?></strong></label><br/>
						<input id="group-filter" class="donotreversedisable" type="text" value=""/>
					<?php endif; ?>
				</div>
				<div id="display-filter-list">
					<strong style="display: block;"><span id="filter-head"><?php esc_html_e( 'Current filters', 'advanced-ads-tracking' ); ?></span></strong>
				</div>
			</form>
			<div class="clearfix" style="overflow: hidden;"></div>
		</div>
	</div>
	<div class="postbox advads-box">
		<div class="inside">
			<div id="advads-stats-graph"></div>
			<div id="advads-graph-legend" style="display:none;">
				<div class="legend-item donotremove">
					<div id="solid-line-legend">
					</div>
					<span><?php esc_html_e( 'impressions', 'advanced-ads-tracking' ); ?></span>
				</div>
				<div class="legend-item donotremove">
					<div id="dashed-line-legend">
					</div>
					<span><?php esc_html_e( 'clicks', 'advanced-ads-tracking' ); ?></span>
				</div>
			</div>
		</div>
	</div>
	<script type="text/javascript">
		var advadsStatPageNonce = '<?php echo esc_attr( wp_create_nonce( 'advads-stats-page' ) ); ?>';
	</script>
	<div id="table-area">
		<div class="postbox advads-box">
			<h2><?php esc_html_e( 'Statistics by date', 'advanced-ads-tracking' ); ?></h2>
			<div class="inside">
				<div id="dateTable"></div>
			</div>
		</div>
		<div class="postbox advads-box">
			<h2><?php esc_html_e( 'Statistics by ad', 'advanced-ads-tracking' ); ?></h2>
			<div class="inside">
				<div id="adTable"></div>
			</div>
		</div>
		<br class="clear"/>
	</div>
	<br class="clear"/>
</div>
