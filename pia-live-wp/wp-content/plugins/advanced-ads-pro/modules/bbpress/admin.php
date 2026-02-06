<?php
/**
 * Module backend main file
 *
 * @package AdvancedAds\Pro
 * @author  Advanced Ads <info@wpadvancedads.com>
 */

// Stop if bbPress isn't activated.
if ( ! class_exists( 'bbPress', false ) ) {
	return;
}

( new AdvancedAds\Pro\Modules\bbPress\Admin\Admin() )->hooks();
