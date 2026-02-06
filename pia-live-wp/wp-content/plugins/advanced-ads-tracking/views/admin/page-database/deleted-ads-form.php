<?php
/**
 * Delete the debug log file.
 *
 * @since   2.6.0
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 */

use AdvancedAds\Tracking\Debugger;
use AdvancedAds\Tracking\Helpers;

if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

$log_file          = Debugger::get_debug_file_path();
$delete_debug_link = add_query_arg(
	'delete-debug-nonce',
	$_request['delete-debug-nonce'],
	Helpers::get_database_tool_link()
);
$redirect_script   = sprintf(
	'<script type="text/javascript">document.location.href = "%s";</script>',
	add_query_arg(
		'deleted_log_file',
		true,
		Helpers::get_database_tool_link()
	)
);

if ( get_filesystem_method() === 'direct' ) {
	unlink( $log_file );
	delete_option( Debugger::DEBUG_FILENAME_OPT );
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- we can't escape the URL, no user input.
	echo $redirect_script;

	return;
}

$_POST['delete-debug-nonce'] = $_request['delete-debug-nonce'];
$extra_fields                = [ 'delete-debug-nonce' ];
$method                      = '';

echo '<style type="text/css">';
include AA_TRACKING_ABSPATH . 'assets/dist/filesystem-form.css';
echo '</style>';

$creds = request_filesystem_credentials( $delete_debug_link, $method, false, false, $extra_fields );
if ( false === $creds ) {
	return;
}

if ( ! WP_Filesystem( $creds ) ) {
	// Our credentials were no good, ask the user for them again.
	request_filesystem_credentials( $delete_debug_link, $method, false, false, $extra_fields );

	return;
}

global $wp_filesystem;
if ( ! $wp_filesystem->delete( $log_file ) ) {
	esc_attr_e( 'Failing to delete the log file.', 'advanced-ads-tracking' );
} else {
	delete_option( Debugger::DEBUG_FILENAME_OPT );
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- we can't escape the URL, no user input.
	echo $redirect_script;
}
