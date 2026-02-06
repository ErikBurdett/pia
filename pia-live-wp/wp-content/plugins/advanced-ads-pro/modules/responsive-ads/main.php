<?php
/**
 * Responsive Ads Module bootstrap
 *
 * @package    Advanced_Ads_Pro\Module
 * @subpackage Responsive_Ads
 * @author     Advanced Ads <info@wpadvancedads.com>
 */

use Advanced_Ads_Pro\Module\Responsive_Ads\Admin;
use Advanced_Ads_Pro\Module\Responsive_Ads\Common;
use Advanced_Ads_Pro\Module\Responsive_Ads\Frontend;

defined( 'ABSPATH' ) || exit;

if ( ( new Admin() )->is_responsive_active() ) {
	$notices = get_option( 'advanced-ads-notices' );
	if ( ! array_key_exists( 'pro_responsive_migration', $notices['closed'] ?? [] ) ) {
		Advanced_Ads_Admin_Notices::get_instance()->add_to_queue( 'pro_responsive_migration' );
	}
}

if ( ( new Admin() )->is_responsive_outdated() ) {
	return;
}

/**
 * Common
 */
( new Common() )->hooks();

/**
 * Start admin.
 */
if ( is_admin() ) {
	( new Admin() )->hooks();

	return;
}

/**
 * Start frontend.
 */
( new Frontend() )->hooks();
