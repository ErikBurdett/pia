<?php
/**
 * Display the time settings
 *
 * @since   2.6.0
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 *
 * @var string $time_wp WordPress time.
 * @var string $time_db database time.
 * @var string $time_utc UTC time.
 */

?>
<h2><?php esc_html_e( 'Time setup', 'advanced-ads-tracking' ); ?></h2>
<p>
	<?php
		printf(
			wp_kses(
				/* translators: %s is a URL */
				__( 'If you notice a shift between your own time and stats, please check if the highlighted time is your local time. If not, please check if your <a href="%s">time zone</a> is set correctly.', 'advanced-ads-tracking' ),
				[
					'a' => [
						'href' => [],
					],
				]
			),
			esc_url( admin_url( '/options-general.php' ) )
		);
		?>
</p>
<div class="advanaced-ads-stats-time">
	<ul>
		<li><strong><?php echo esc_html( $time_wp ); ?> (WordPress)</strong></li>
		<li><span><?php echo esc_html( $time_utc ); ?> (UTC)</span></li>
		<li><span><?php echo esc_html( $time_db ); ?> (DB)</span></li>
	</ul>
</div>
