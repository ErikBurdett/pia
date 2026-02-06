<?php
/**
 * Responsive Ads Common.
 *
 * @package    Advanced_Ads_Pro\Module
 * @subpackage Responsive_Ads
 * @author     Advanced Ads <info@wpadvancedads.com>
 */

namespace Advanced_Ads_Pro\Module\Responsive_Ads;

use Advanced_Ads_Pro;
use AdvancedAds\Framework\Utilities\Params;

defined( 'ABSPATH' ) || exit;

/**
 * Class Common
 */
class Common {

	/**
	 * Hook into WordPress.
	 */
	public function hooks(): void {
		add_filter( 'advanced-ads-visitor-conditions', [ $this, 'visitor_conditions' ] );
	}

	/**
	 * Add visitor condition
	 *
	 * @param array $conditions Hold visitor conditions.
	 *
	 * @return array
	 */
	public function visitor_conditions( $conditions ) {
		if ( ! defined( 'ADVANCED_ADS_RESPONSIVE_DISABLE_BROWSER_WIDTH' ) ) {
			$conditions['device_width'] = [
				'label'       => esc_html__( 'browser width', 'advanced-ads-pro' ),
				'description' => esc_html__( 'Display ads based on the browser width.', 'advanced-ads-pro' ),
				'metabox'     => [ 'Advanced_Ads_Visitor_Conditions', 'metabox_number' ],
				'check'       => [ $this, 'check_browser_width' ],
			];
		}

		return $conditions;
	}

	/**
	 * Check browser width in frontend
	 *
	 * @param array $options Options of the condition.
	 *
	 * @return bool True if ad/group can be displayed.
	 */
	public function check_browser_width( $options = [] ) {
		if ( defined( 'ADVANCED_ADS_RESPONSIVE_DISABLE_BROWSER_WIDTH' ) ) {
			return true;
		}

		if ( ! isset( $options['operator'], $options['value'] ) ) {
			return true;
		}

		$browser_width = 0;
		$ads_visitor   = Params::cookie( 'advanced_ads_visitor' );
		if ( ! empty( $ads_visitor ) ) {
			$browser_width = json_decode( stripslashes( $ads_visitor ), true ); // phpcs:ignore
			$browser_width = $browser_width['browser_width'] ?? 0;
		} else {
			$responsive_options = Advanced_Ads_Pro::get_instance()->get_options();
			$browser_width      = ! empty( $responsive_options['responsive-ads']['fallback-width'] ) ? absint( $responsive_options['responsive-ads']['fallback-width'] ) : 768;
		}

		$value = absint( $options['value'] );

		$operator  = $options['operator'];
		$operators = [
			'is_equal'  => $value === $browser_width,
			'is_higher' => $value <= $browser_width,
			'is_lower'  => $value >= $browser_width,
		];

		return $operators[ $operator ] ?? true;
	}
}
