<?php
/**
 * Initialize class.
 *
 * @package AdvancedAds\Pro
 */

if ( ! is_admin() ) {
	new Advanced_Ads_Pro_Module_Extended_Adblocker();
}
