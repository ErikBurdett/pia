<?php
/**
 * Markup for the statistics column
 *
 * @since   2.6.0
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 *
 * @var Ad $ad Ad instance.
 */

use AdvancedAds\Tracking\Helpers;
use AdvancedAds\Tracking\Public_Ad;
use AdvancedAds\Tracking\Utilities\Tracking;

// No tracking for Yielscale ad type.
if ( $ad->is_type( 'yieldscale' ) ) {
	return;
}

$target = Helpers::get_ad_link( $ad );

$impression_enabled = Tracking::has_ad_tracking_enabled( $ad, 'impression', 'view' );
$click_enabled      = Tracking::has_ad_tracking_enabled( $ad, 'click', 'view' );
$clickable_type     = Helpers::is_clickable_type( $ad->get_type() );

$labels = [
	'Impressions' => esc_html__( 'Impressions', 'advanced-ads-tracking' ),
	'Clicks'      => esc_html__( 'Clicks', 'advanced-ads-tracking' ),
];

$data = [
	'Impressions' => $ad->get_impressions(),
	'Clicks'      => $ad->get_clicks(),
];

// public stats.
$public      = new Public_Ad( $ad->get_id() );
$public_id   = $public->get_id();
$public_link = $public->get_url();
?>
<ul>
	<?php
	foreach ( $data as $label => $value ) :
		?>
		<li>
			<strong><?php echo esc_html( $labels[ $label ] ); ?>:</strong>&nbsp;
			<?php
			if ( ( $impression_enabled && 'Impressions' === $label ) || ( $click_enabled && $clickable_type && 'Clicks' === $label ) ) :
				echo esc_html( number_format_i18n( $value ) );
			else :
				echo esc_html__( 'disabled', 'advanced-ads-tracking' );
			endif;
			?>
		</li>
		<?php
	endforeach;
	?>
	<?php if ( $impression_enabled && $click_enabled && $clickable_type && 0 !== $ad->get_impressions() ) : ?>
		<li>
			<strong><?php esc_html_e( 'CTR', 'advanced-ads-tracking' ); ?>:</strong>&nbsp;
			<?php echo esc_html( number_format_i18n( 100 * $ad->get_clicks() / $ad->get_impressions(), 2 ) ); ?>%
		</li>
	<?php endif; ?>
	<?php if ( $target ) : ?>
		<li>
			<strong><?php esc_html_e( 'Target url', 'advanced-ads-tracking' ); ?>:</strong>&nbsp;
			<div class="target-link-div">
				<div class="target-link-text">
					<a href="<?php echo esc_url( $target ); ?>" target="_blank"><?php echo esc_html( $target ); ?></a>
				</div>
				<a href="<?php echo esc_url( $target ); ?>" target="_blank"><?php esc_html_e( 'show', 'advanced-ads-tracking' ); ?></a>
			</div>
		</li>
	<?php endif; ?>
</ul>
<?php if ( $ad->is_status( 'publish' ) ) : // avoid admin stats for non published ads. ?>
	<div class="row-actions">
		<a target="blank" href="<?php echo esc_url( Helpers::stats_url_admin_30days( $ad->get_id() ) ); ?>">
			<?php esc_html__( 'Statistics for the last 30 days', 'advanced-ads-tracking' ); ?>
		</a>
		<?php if ( ! defined( 'ADVANCED_ADS_TRACKING_NO_PUBLIC_STATS' ) && $public_id ) : ?>
			<br /><a id="ad-public-link" href="<?php echo esc_url( $public_link ); ?>" target="_blank"><?php _e( 'Shareable Link', 'advanced-ads-tracking' ); ?></a>
		<?php endif; ?>
	</div>
<?php endif; ?>
