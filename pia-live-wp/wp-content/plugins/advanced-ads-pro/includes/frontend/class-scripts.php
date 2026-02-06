<?php
/**
 * Scripts template file.
 * Brief description of the styles in this file
 *
 * @since   3.0.4
 * @package AdvancedAds\Pro
 * @author  Advanced Ads <info@wpadvancedads.com>
 */

namespace AdvancedAds\Pro\Frontend;

use AdvancedAds\Framework\Interfaces\Integration_Interface;
use AdvancedAds\Utilities\Conditional;

defined( 'ABSPATH' ) || exit;

/**
 * Scripts class.
 */
class Scripts implements Integration_Interface {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'wp_head', [ $this, 'add_cfp_queue' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	/**
	 * Add CFP queue.
	 *
	 * @return void
	 */
	public function add_cfp_queue(): void {
		// Do not enqueue on AMP pages.
		if ( Conditional::is_amp() ) {
			return;
		}

		?>
		<script type="text/javascript">
			var advadsCfpQueue = [];
			var advadsCfpAd = function( adID ) {
				if ( 'undefined' === typeof advadsProCfp ) {
					advadsCfpQueue.push( adID )
				} else {
					advadsProCfp.addElement( adID )
				}
			}
		</script>
		<?php
	}

	/**
	 * Enqueue scripts.
	 *
	 * @return void
	 */
	public function enqueue_scripts(): void {
		// Do not enqueue on AMP pages.
		if ( Conditional::is_amp() ) {
			return;
		}

		wp_advads_pro()->registry->enqueue_script( 'main' );
	}
}
