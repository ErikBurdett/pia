<?php
/**
 * Module main file
 *
 * @package AdvancedAds\Pro\Modules\BuddyPress
 * @author  Advanced Ads <info@wpadvancedads.com>
 */

// Stop if BuddyPress isn't activated.
if ( ! class_exists( 'BuddyPress', false ) ) {
	return;
}

( new AdvancedAds\Pro\Modules\BuddyPress\BuddyPress() )->hooks();
