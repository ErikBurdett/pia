<?php
/**
 * Send test email button.
 *
 * @since   2.6.0
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 *
 * @var string $recipients     Email report recipients.
 * @var string $email_schedule Email report schedule.
 */

?>
<?php if ( ! empty( $recipients ) ) : ?>
	<a id="send-immediate-report" class="button button-secondary" href="#"><?php esc_html_e( 'send email', 'advanced-ads-tracking' ); ?></a><span id="send-email-spinner-spinner" style="margin:4px;display:inline-block;"></span>
	<p class="description"><?php esc_html_e( 'Send a report immediately to the listed email addresses', 'advanced-ads-tracking' ); ?>&nbsp;( <?php echo str_replace( ',', ', ', $recipients ); // phpcs:ignore ?> )</p>
	<p id="immediate-report-notice"></p>
	<p style="background-color:#00A0D2;color:#fff;padding:2px;">
		<?php
		/* Translators: 1: cron job schedule (e.g. 'daily'), 2: current timezone */
		echo esc_html( sprintf( __( 'Email will be sent %1$s at 00:15 %2$s', 'advanced-ads-tracking' ), $email_schedule, Advanced_Ads_Utils::get_timezone_name() ) );
		?>
	</p>
<?php else : ?>
	<p class="description"><?php esc_html_e( 'Add and save a recipient before sending a test email.', 'advanced-ads-tracking' ); ?></p>
	<?php
endif;
