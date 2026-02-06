<?php
/**
 * Render the DB management page
 *
 * @since   2.6.0
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 *
 * @var array $db_size Information about the database size.
 * @var string $nonce Nonce for AJAX calls.
 * @var string $impressions_table Table name for impressions.
 * @var string $clicks_table Table name for clicks.
 * @var string $date_format Date format for displaying dates.
 * @var array $remove_periods_args options for the "Remove old stats" tool.
 * @var array $export_periods_args options for the "Export stats" tool.
 * @var array $debug_option Set options for the debug mode.
 * @var false|WP_POST $debug_ad false or the ad that is currently debugged.
 * @var array $debug_time Information about the time left for the debug mode.
 * @var string $delete_debug_link Debug log download link.
 * @var WP_Post[] $ads_with_any_status List of ads with any status.
 * @var WP_Post[] $ads_published List of published ads.
 */

use AdvancedAds\Tracking\Helpers;
use AdvancedAds\Framework\Utilities\Params;
use AdvancedAds\Tracking\Db_Operations;
use AdvancedAds\Tracking\Debugger;

?>
<script type="text/javascript">
	var advadsTrackingDbopNonce = <?php echo wp_json_encode( $nonce ); ?>;
</script>
<div class="wrap">
	<?php if ( $db_size['first_impression'] ) : ?>
		<div class="notice error advads-notice advads-notice-inline inline">
			<p><?php esc_html_e( 'Always perform a backup of your stats tables before performing any of the operations on this page.', 'advanced-ads-tracking' ); ?></p>
		</div>
	<?php endif; ?>
	<table class="widefat">
		<thead>
		<tr>
			<th><?php esc_html_e( 'Table', 'advanced-ads-tracking' ); ?></th>
			<th><?php esc_html_e( 'Row count', 'advanced-ads-tracking' ); ?></th>
			<th><?php esc_html_e( 'Data size ( in kilobytes )', 'advanced-ads-tracking' ); ?></th>
			<th><?php esc_html_e( 'Oldest record', 'advanced-ads-tracking' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<tr class="alternate">
			<td><strong><?php esc_html_e( 'impressions', 'advanced-ads-tracking' ); ?></strong>&nbsp;(<code><?php echo esc_html( $impressions_table ); ?></code>)</td>
			<td><?php echo esc_html( $db_size['impression_row_count'] ); ?></td>
			<td><?php echo esc_html( $db_size['impression_in_kb'] ); ?></td>
			<td><code><?php echo $db_size['first_impression'] ? esc_html( date_i18n( $date_format, $db_size['first_impression'] ) ) : 'N/A'; ?></code></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'clicks', 'advanced-ads-tracking' ); ?></strong>&nbsp;(<code><?php echo esc_html( $clicks_table ); ?></code>)</td>
			<td><?php echo esc_html( $db_size['click_row_count'] ); ?></td>
			<td><?php echo esc_html( $db_size['click_in_kb'] ); ?></td>
			<td><code><?php echo ( $db_size['first_click'] ) ? esc_html( date_i18n( $date_format, $db_size['first_click'] ) ) : 'N/A'; ?></code></td>
		</tr>
		</tbody>
	</table>

	<?php if ( $db_size['first_impression'] ) : ?>
	<br/>
	<div class="form-wrap">
		<label><strong><?php esc_html_e( 'Export stats', 'advanced-ads-tracking' ); ?></strong></label>
		<div class="form-field">
			<form id="export-stats-form" action="<?php echo esc_url( Helpers::get_database_tool_link() ); ?>" method="post">
				<?php Db_Operations::period_select_inputs( $export_periods_args ); ?>
				<button class="button button-primary"><?php esc_html_e( 'download', 'advanced-ads-tracking' ); ?></button>
			</form>
			<p class="description"><?php esc_html_e( 'Export stats as CSV so you can review them later by uploading the file.', 'advanced-ads-tracking' ); ?></p>
			<p class="advads-notice-inline advads-error" id="export-period-error" style="display:none;"><?php esc_html_e( 'The period you have chosen is not consistent', 'advanced-ads-tracking' ); ?></p>
		</div>
	</div>
	<div class="form-wrap">
		<form id="remove-stats-form" action="<?php echo esc_url( Helpers::get_database_tool_link() ); ?>" method="post">
			<label><strong><?php esc_html_e( 'Remove old stats', 'advanced-ads-tracking' ); ?></strong></label>
			<div class="form-field">
				<?php Db_Operations::period_select_inputs( $remove_periods_args ); ?>
				<button class="button button-primary"><?php esc_html_e( 'remove', 'advanced-ads-tracking' ); ?></button>
		</form>
		<p class="description"><?php esc_html_e( 'Remove old stats to reduce the size of the database.', 'advanced-ads-tracking' ); ?></p>
		<p id="remove-error-notice" class="advads-notice-inline advads-error hidden"></p>
	</div>
</div>
	<div class="form-wrap">
		<label><strong><?php esc_html_e( 'Reset Stats', 'advanced-ads-tracking' ); ?></strong></label>
		<div class="form-field">
			<form id="reset-stats-form" action="<?php echo esc_url( Helpers::get_database_tool_link() ); ?>" method="post">
				<select id="reset-stats-adID">
					<?php if ( ! empty( $ads_with_any_status ) ) : ?>
						<option value=""><?php esc_html_e( '(please choose the ad)', 'advanced-ads-tracking' ); ?></option>
					<?php endif; ?>
					<option value="all-ads"><?php esc_html_e( '--all ads--', 'advanced-ads-tracking' ); ?></option>
					<?php if ( ! empty( $deleted_ads['impressions'] ) || ! empty( $deleted_ads['clicks'] ) ) : ?>
						<option value="deleted-ads"><?php esc_html_e( '--deleted ads--', 'advanced-ads-tracking' ); ?></option>
					<?php endif; ?>
					<?php foreach ( $ads_with_any_status as $ad ) : ?>
						<option value="<?php echo (int) $ad->ID; ?>"><?php echo esc_html( $ad->post_title ); ?></option>
					<?php endforeach; ?>
				</select>
				<button class="button button-primary"><?php esc_html_e( 'reset', 'advanced-ads-tracking' ); ?></button>
			</form>
			<p class="description"><?php esc_html_e( 'Use this form to remove the stats for one or all ads.', 'advanced-ads-tracking' ); ?></p>
			<p id="reset-error-notice" class="advads-notice-inline advads-error hidden"></p>
		</div>
	</div>

		<?php if ( ! empty( $ads_published ) ) : ?>

	<div class="form-wrap">
		<label><strong><?php esc_html_e( 'Debug mode', 'advanced-ads-tracking' ); ?></strong></label>
		<div class="form-field">
			<?php if ( Debugger::is_debugging_forbidden() ) : ?>
				<p>
					<strong style="color:#ff5c1e;">
						<?php
						printf(
							/* translators: %s represents the string */
							esc_html__( 'Debugging is prohibited through the constant %s', 'advanced-ads-tracking' ),
							'<code>ADVANCED_ADS_TRACKING_DEBUG</code>'
						);
						?>
					</strong>
				</p>
			<?php else : ?>
				<form id="debug-mode-form" action="<?php echo esc_url( Helpers::get_database_tool_link() ); ?>" method="post">
					<?php if ( $debug_option ) : ?>
						<?php
						if ( true === $debug_option['id'] ) {
							$the_ad = __( '--all ads--', 'advanced-ads-tracking' );
						} else {
							$the_ad = '"' . $debug_ad->post_title . '"';
						}
						?>
						<div class="advads-notice notice inline">
							<p>
								<?php
								if ( empty( $debug_option['time'] ) ) {
									printf(
										wp_kses(
											/* translators: %s is name of the ad. */
											__( '<code>ADVANCED_ADS_TRACKING_DEBUG</code> constant is set: Debugging %s.', 'advanced-ads-tracking' ),
											[
												'code' => [],
											]
										),
										esc_html( $the_ad )
									);
									// the debug file can't be written.
									if ( ! Debugger::get_debug_file_handle() ) {
										echo '<br>';
										esc_html_e( "The debug log file can't be written.", 'advanced-ads-tracking' );
										printf(
											/* translators: %s is the path to WP_CONTENT_DIR */
											esc_html__( ' Please make sure the directory %s is writable', 'advanced-ads-tracking' ),
											// phpcs:ignore WordPress.Security.EscapeOutput -- the translatable part above is escaped.
											'<code>' . WP_CONTENT_DIR . '</code>'
										);
									}
								} else {
									printf(
										/* translators: 1: name of the add (or all ads), 2: amount of hours, 3: amount of minutes */
										esc_html__( 'Debugging %1$s for another %2$s and %3$s.', 'advanced-ads-tracking' ),
										esc_html( $the_ad ),
										/* translators: %d is a number for hours */
										esc_html( sprintf( _n( '%d hour', '%d hours', $debug_time['hours'], 'advanced-ads-tracking' ), $debug_time['hours'] ) ),
										/* translators: %d is a number for minutes */
										esc_html( sprintf( _n( '%d minute', '%d minutes', $debug_time['mins'], 'advanced-ads-tracking' ), $debug_time['mins'] ) )
									);
								}
								?>
							</p>
						</div>
						<?php if ( ! empty( $debug_option['time'] ) ) : ?>
							<input type="hidden" id="debug-mode-adID" value="cancel"/>
							<button class="button button-secondary"><?php esc_html_e( 'disable', 'advanced-ads-tracking' ); ?></button>
						<?php endif; ?>
					<?php else : ?>
						<select id="debug-mode-adID">
							<option value="all"><?php esc_html_e( '--all ads--', 'advanced-ads-tracking' ); ?></option>
							<?php foreach ( $ads_published as $ad ) : ?>
								<option value="<?php echo esc_attr( $ad->ID ); ?>"><?php echo esc_html( $ad->post_title ); ?></option>
							<?php endforeach; ?>
						</select>
						<button class="button button-primary"><?php esc_html_e( 'enable', 'advanced-ads-tracking' ); ?></button>
						<p class="description">
							<?php
								printf(
									/* translators: %d is the number of hours */
									esc_html__( 'Logs more information about tracked data for %d hours starting now.', 'advanced-ads-tracking' ),
									(int) Debugger::DEBUG_HOURS
								);
							?>
						</p>
					<?php endif; ?>
				</form>
			<?php endif; ?>
		</div>
	</div>
		<?php endif; ?>
<?php endif; ?>

<?php if ( ! empty( Params::get( 'deleted_log_file' ) ) ) : ?>
	<p class="advads-notice-inline advads-check">
		<?php esc_html_e( 'Successfully deleted debug log file.', 'advanced-ads-tracking' ); ?>
	</p>
<?php endif; ?>

<?php if ( file_exists( Debugger::get_debug_file_path() ) ) : ?>
	<p>
		<?php
		printf(
			/* translators: %1$s is a beginning HTML tag, %2$s is the closing one. */
			esc_html__( 'View the tracking %1$sdebug log file%2$s', 'advanced-ads-tracking' ),
			'<strong><a target="_blank" href="' . Debugger::get_debug_file_url() . '">',
			'</a></strong>'
		);
		?>
		&nbsp;|&nbsp;
		<strong><a href="<?php echo esc_url( $delete_debug_link ); ?>"><?php esc_html_e( 'delete the file', 'advanced-ads-tracking' ); ?></a></strong>
	</p>
	<?php Advanced_Ads_Filesystem::get_instance()->print_request_filesystem_credentials_modal(); ?>
<?php endif; ?>

<iframe frameborder="0" hspace="0" src="" id="stats-download-frame" style="width:1px;height:1px;"></iframe>
