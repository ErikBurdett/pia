<?php
/**
 * Render the database update form.
 *
 * @package AdvancedAds\Pro\Modules\Geo
 * @author  Advanced Ads <info@wpadvancedads.com>
 *
 * @var bool         $correct_databases
 * @var string|false $last_update
 * @var int          $next_update
 * @var bool         $use_filters
 */

if ( $correct_databases ) : ?>
<p>
	<?php esc_html_e( 'Geo Databases found.', 'advanced-ads-pro' ); ?>
</p>
<?php endif; ?>

<?php if ( $use_filters ) : ?>
<div class="advads-notice-inline advads-idea">
	<p><?php esc_html_e( 'You are currently using filter hooks to load custom database files.', 'advanced-ads-pro' ); ?></p>
</div>
	<?php
	return;
endif;
?>

<p id="advanced-ads-geo-license-missing-warning" style="display: none;">
	<span class="advads-notice-inline advads-error">
		<?php esc_html_e( 'The MaxMind license key is missing.', 'advanced-ads-pro' ); ?>
	</span>
	<?php
	printf(
		/* translators: 1: opening <a>-tag to Advanced Ads manual, 2: closing <a>-tag */
		esc_html__( 'Please read the %1$sinstallation instructions%2$s.', 'advanced-ads-pro' ),
		'<a href="https://wpadvancedads.com/manual/geo-targeting-condition/#Enabling_Geo-Targeting" target="_blank">',
		'</a>'
	);
	?>
</p>

<div id="advanced-ads-geo-update-database" <?php echo $correct_databases ? esc_attr( 'data-db-exists' ) : ''; ?>>
	<?php if ( ! $correct_databases ) : ?>
		<div id="advanced-ads-geo-no-database-warning">
			<p class="advads-notice-inline advads-error">
				<?php esc_html_e( 'Geo Databases not found.', 'advanced-ads-pro' ); ?>
			</p>
			<p>
				<?php esc_html_e( 'In order to use Geo Targeting, please download the geo location databases by clicking on the button below.', 'advanced-ads-pro' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( ! $correct_databases || $this->is_update_available() ) : ?>
		<button type="button" id="download_geolite" class="button-secondary">
			<?php esc_html_e( 'Update geo location databases', 'advanced-ads-pro' ); ?> (~66MB)
		</button>
		<span class="advads-loader" id="advads-geo-loader" style="display: none;"></span>
		<p class="advads-notice-inline advads-error hidden" id="advads-geo-upload-error"></p>
		<p class="advads-notice-inline advads-check hidden" id="advads-geo-upload-success"></p>
	<?php endif; ?>
</div>

<?php
if ( $correct_databases ) :
	if ( $last_update ) :
		?>
		<p class="advads-notice-inline advads-check">
			<?php
			printf(
			/* translators: Timestamp in the localized date_format */
				esc_html__( 'Last update: %s', 'advanced-ads-pro' ),
				esc_html( date_i18n( get_option( 'date_format' ), $last_update ) )
			);
			?>
		</p>
	<?php endif; ?>

	<p>
		<?php
		printf(
		/* translators: Timestamp in the localized date_format */
			esc_html__( 'Next possible update on %s.', 'advanced-ads-pro' ),
			esc_html( date_i18n( get_option( 'date_format' ), $next_update ) )
		);
		?>
	</p>

	<p class="description">
		<?php esc_html_e( 'The databases are updated on the first Tuesday (midnight, GMT) of each month.', 'advanced-ads-pro' ); ?>
	</p>
	<?php
endif;
