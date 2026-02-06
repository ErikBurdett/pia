<?php // phpcs:ignore WordPress.Files.FileName


use AdvancedAds\Options;
use AdvancedAds\Constants;
use AdvancedAds\Abstracts\Ad;
use AdvancedAds\Abstracts\Group;
use AdvancedAds\Abstracts\Placement;

/**
 * Get an ad that is delivered to users for ad blocker request.
 */
class Advanced_Ads_Pro_Module_Ads_For_Adblockers {

	/**
	 * Holds unique identifiers of each chain. It allows to show only one copy of the alternative ad.
	 *
	 * @var array
	 */
	private $shown_chains = [];

	/**
	 * Constructor
	 */
	public function __construct() {
		// Early bail!!
		if ( ! Options::instance()->get( 'adblocker.ads-for-adblockers.enabled' ) ) {
			return;
		}

		add_filter( 'advanced-ads-pro-ad-needs-backend-request', [ $this, 'ad_needs_backend_request' ], 10, 3 );

		if ( wp_doing_ajax() ) {
			add_filter( 'advanced-ads-can-display-ad', [ $this, 'can_display' ], 10, 2 );
			add_filter( 'advanced-ads-ad-select-args', [ $this, 'save_chain_id' ], 10, 1 );
			add_filter( 'advanced-ads-ad-select-override-by-ad', [ $this, 'override_ad_select_by_ad' ], 10, 3 );
			add_filter( 'advanced-ads-ad-select-override-by-group', [ $this, 'override_ad_select_by_group' ], 10, 4 );
		}
	}

	/**
	 * Enable cache-busting if there is an ad for adblocker.
	 *
	 * @param string $check    The original return value.
	 * @param Ad     $ad       The ad object.
	 * @param string $fallback The fallback value.
	 *
	 * @return string The value indicating if a backend request is needed ('passive') or the fallback value.
	 */
	public function ad_needs_backend_request( $check, Ad $ad, $fallback ) {
		$ad_for_adblocker = self::get_item_for_adblocker( $ad );

		if ( ! $ad_for_adblocker ) {
			return $check;
		}

		if ( $check === $fallback ) {
			return $fallback;
		}

		// AJAX or no cache-busting if PHP is enabled for ad for adblocker.
		if ( $ad_for_adblocker->is_type( 'plain' ) && $ad_for_adblocker->is_php_allowed() ) {
			return $fallback;
		}
		return 'passive';
	}

	/**
	 * Save chain id.
	 *
	 * @param array $args Ad arguments.
	 *
	 * @return array $args
	 */
	public function save_chain_id( $args ) {
		if ( ! isset( $args['chain_id'] ) ) {
			$args['chain_id'] = wp_rand();
		}

		return $args;
	}

	/**
	 * Check if the ad can be displayed.
	 *
	 * @param bool $can_display Can the ad be displayed.
	 * @param Ad   $ad          Ad instance.
	 *
	 * @return bool
	 */
	public function can_display( $can_display, Ad $ad ) {
		if ( ! $can_display ) {
			return $can_display;
		}
		if ( in_array( $ad->get_prop( 'chain_id' ), $this->shown_chains, true ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Overrides the selected ad with a new ad based on certain conditions.
	 *
	 * @param string $overriden_ad The original selected ad.
	 * @param AD     $ad           Ad instance.
	 * @param array  $args         Additional arguments.
	 *
	 * @return string The overridden ad output.
	 */
	public function override_ad_select_by_ad( $overriden_ad, AD $ad, $args ) {
		return $this->override_ad_select( $overriden_ad, $ad, $args );
	}

	/**
	 * Overrides the selected ad with a new ad based on certain conditions.
	 *
	 * @param string     $overriden_group The original selected ad.
	 * @param Group      $group           Group instance.
	 * @param array|null $ordered_ad_ids  ordered ids of the ads that belong to the group.
	 * @param array      $args            Additional arguments.
	 *
	 * @return string The overridden ad output.
	 */
	public function override_ad_select_by_group( $overriden_group, Group $group, $ordered_ad_ids, $args ) {
		return $this->override_ad_select( $overriden_group, $group, $args );
	}

	/**
	 * Common logic for overriding the selected ad or group.
	 *
	 * @param string   $overriden_item The original selected ad or group.
	 * @param Ad|Group $entity         Ad or Group instance.
	 * @param array    $args           Additional arguments.
	 *
	 * @return string The overridden ad or group output.
	 */
	private function override_ad_select( $overriden_item, $entity, $args ) {
		if ( ! $entity->can_display() || empty( $args['adblocker_active'] ) || empty( $args['item_adblocker'] ) ) {
			return $overriden_item;
		}

		$_item = explode( '_', $args['item_adblocker'] );

		if ( ! empty( $_item[1] ) ) {
			if ( in_array( $_item[0], [ Constants::ENTITY_AD, 'id' ], true ) ) {
				$ab_ad = wp_advads_get_ad( $_item[1] );
				$ab_ad->set_parent( $entity->get_root_placement() );
				$overriden_item       = $ab_ad->output();
				$this->shown_chains[] = $args['chain_id'];
			} elseif ( Constants::ENTITY_GROUP === $_item[0] ) {
				$group = wp_advads_get_group( (int) $_item[1] );
				$group->set_parent( $entity->get_root_placement() );
				$overriden_item = $group->output();
			}
		}

		return $overriden_item;
	}

	/**
	 * Get an ad that is delivered to users with an ad blocker enabled.
	 *
	 * @param Ad|Placement $entity Ad or placement instance.
	 *
	 * @return Ad|Group|bool bool false; Ad or Group if an item for ad blocker is found.
	 */
	public static function get_item_for_adblocker( $entity ) {
		// Early bail!!
		if ( ! Options::instance()->get( 'adblocker.ads-for-adblockers.enabled' ) ) {
			return false;
		}

		$placement = is_a_placement( $entity ) ? $entity : $entity->get_root_placement();

		if ( empty( $placement ) || empty( $placement->get_prop( 'item_adblocker' ) ) ) {
			return false;
		}

		$_item = explode( '_', $placement->get_prop( 'item_adblocker' ) );
		if ( ! empty( $_item[1] ) ) {
			$item_id = absint( $_item[1] );
			if ( in_array( $_item[0], [ Constants::ENTITY_AD, 'id' ], true ) ) {
				$ad = wp_advads_get_ad( $item_id );
				if ( $ad ) {
					$ad->set_parent( $placement );
					return $ad;
				}
			} elseif ( Constants::ENTITY_GROUP === $_item[0] ) {
				$group = wp_advads_get_group( $item_id );
				if ( $group ) {
					foreach ( $group->get_ads() as $ad ) {
						$ad->set_parent( $placement );
					}

					return $group;
				}
			}
		}

		return false;
	}
}
