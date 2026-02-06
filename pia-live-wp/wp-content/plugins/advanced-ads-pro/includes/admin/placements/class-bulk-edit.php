<?php
/**
 * Bulk edit for placements
 *
 * @package AdvancedAds\Pro
 * @author  Advanced Ads <info@wpadvancedads.com>
 */

namespace AdvancedAds\Pro\Admin\Placements;

use AdvancedAds\Options;
use AdvancedAds\Abstracts\Placement;
use AdvancedAds\Framework\Utilities\Params;
use AdvancedAds\Framework\Interfaces\Integration_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Placements Bulk Edit.
 */
class Bulk_Edit implements Integration_Interface {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'advanced-ads-placement-bulk-edit-fields', [ $this, 'add_bulk_edit_fields' ] );
		add_filter( 'advanced-ads-placement-bulk-edit-has-change', [ $this, 'bulk_edit_has_changes' ] );
		add_filter( 'advanced-ads-placement-bulk-edit-save', [ $this, 'save_bulk_edit' ] );
	}

	/**
	 * Add the bulk edit inputs
	 *
	 * @return void
	 */
	public function add_bulk_edit_fields(): void {
		include_once AA_PRO_ABSPATH . 'views/admin/placements/bulk-edit.php';
	}

	/**
	 * Check if bulk edit fields have changes.
	 *
	 * @param bool $has_change whether some ads have been changed.
	 *
	 * @return bool
	 */
	public function bulk_edit_has_changes( $has_change ): bool {
		$cache_busting       = Params::get( 'cache_busting' );
		$lazy_loading        = Params::get( 'lazy_loading' );
		$cache_busting_empty = Params::get( 'cache_busting_empty' );

		if ( ! empty( $cache_busting ) || ! empty( $lazy_loading ) || ! empty( $cache_busting_empty ) ) {
			$has_change = true;
		}

		return $has_change;
	}

	/**
	 * Save changes made during bulk edit
	 *
	 * @param Placement $placement current placement being saved.
	 *
	 * @return Placement
	 */
	public function save_bulk_edit( $placement ): Placement {
		$cache_busting       = Params::get( 'cache_busting' );
		$lazy_loading        = Params::get( 'lazy_loading' );
		$cache_busting_empty = Params::get( 'cache_busting_empty', false, FILTER_VALIDATE_BOOLEAN );

		if ( ! empty( $cache_busting ) ) {
			$placement->set_prop( 'cache-busting', $cache_busting );
		}

		if ( ! empty( $lazy_loading ) ) {
			$placement->set_prop( 'lazy_load', $lazy_loading );
		}

		if ( ! empty( $cache_busting_empty ) ) {
			$placement->set_prop( 'cache_busting_empty', 1 === $cache_busting_empty );
		}

		return $placement;
	}
}
