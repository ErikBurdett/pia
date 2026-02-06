<?php
/**
 * Tracking method settings.
 *
 * @since   2.6.0
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 *
 * @var string $method                  The currently selected method.
 * @var bool   $show_tcf_warning        Whether there is a conflict between TCF and Tracking method settings.
 * @var string $tcf_warning             The error/warning message.
 * @var bool   $missing_scripts_warning Show warning if tracking scripts are disabled by filter.
 * @var bool   $is_amp_nossl            True when amp plugin is installed and missing ssl encryption.
 */

use AdvancedAds\Tracking\Helpers;
?>
	<label>
		<input name="<?php echo esc_attr( $this->options_slug ); ?>[method]" type="radio" value="frontend" <?php checked( 'frontend', $method ); ?>/>
		<?php esc_attr_e( 'Frontend', 'advanced-ads-tracking' ); ?>
	</label>
	<p class="description">
		<?php esc_html_e( 'Track impressions after the ad was loaded in the frontend.', 'advanced-ads-tracking' ); ?>
	</p>

	<label><input name="<?php echo esc_attr( $this->options_slug ); ?>[method]" type="radio" value="ga" <?php checked( 'ga', $method ); ?>/><?php esc_html_e( 'Google Analytics', 'advanced-ads-tracking' ); ?></label>
	<p class="description"><?php esc_html_e( 'Track impressions and clicks in Google Analytics.', 'advanced-ads-tracking' ); ?>
		<a href="https://wpadvancedads.com/manual/ad-tracking-with-google-analytics/?utm_source=advanced-ads&utm_medium=link&utm_campaign=settings-tracking-analytics" target="_blank" class="advads-manual-link"><?php esc_html_e( 'Manual', 'advanced-ads-tracking' ); ?></a></p>
<?php if ( 'ga' !== $method && isset( $options['ga-UID'] ) && ! Helpers::is_forced_analytics() ) : ?>
	<input type="hidden" name="<?php echo esc_attr( $this->options_slug ); ?>[ga-UID]" value="<?php echo esc_attr( $options['ga-UID'] ); ?>"/>
	<?php
endif;
?>
	<label>
		<input name="<?php echo esc_attr( $this->options_slug ); ?>[method]" type="radio" value="onrequest" <?php checked( 'onrequest', $method ); ?>/>
		<?php esc_attr_e( 'Database', 'advanced-ads-tracking' ); ?> (<?php esc_attr_e( 'experienced users', 'advanced-ads-tracking' ); ?>)
	</label>
	<p class="description">
		<?php esc_html_e( 'Track impressions when the ad is requested from the database.', 'advanced-ads-tracking' ); ?>
		<?php esc_html_e( 'Should only be used by experienced users.', 'advanced-ads-tracking' ); ?>
		<a href="https://wpadvancedads.com/manual/tracking-documentation?utm_source=advanced-ads&utm_medium=link&utm_campaign=settings-tracking-methods#Tracking_Methods" target="_blank" class="advads-manual-link"><?php esc_html_e( 'Manual', 'advanced-ads-tracking' ); ?></a>
	</p>

<?php if ( $is_amp_nossl ) : ?>
	<p class="description advads-amp-warning advads-notice-inline advads-error advads-amp-nossl" data-method="<?php echo esc_attr( $method ); ?>">
		<?php esc_html_e( 'Impression tracking is not working on AMP sites without SSL encryption.', 'advanced-ads-tracking' ); ?>
	</p>
<?php endif; ?>

<?php

if ( $show_tcf_warning ) :
	?>
	<p class="advads-notice-inline advads-error">
		<?php esc_html_e( 'The selected tracking method is not compatible with the TCF 2.0 integration.', 'advanced-ads-tracking' ); ?>
		<?php esc_html_e( 'Please choose either the Frontend or Google Analytics method.', 'advanced-ads-tracking' ); ?>
	</p>
	<?php
endif;

if ( $missing_scripts_warning ) :
	?>
	<p class="advads-notice-inline advads-error">
		<?php esc_html_e( 'The advanced-ads-tracking-load-header-scripts filter is set to false which removes any tracking scripts in the frontend. Only the Database tracking method is working now.', 'advanced-ads-tracking' ); ?>
	</p>
	<?php
endif;
