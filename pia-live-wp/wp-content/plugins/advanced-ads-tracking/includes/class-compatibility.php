<?php
/**
 * The class responsible for compatibility with third-party plugins.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking;

use AdvancedAds\Framework\Utilities\Str;
use AdvancedAds\Framework\Utilities\Params;
use AdvancedAds\Framework\Interfaces\Integration_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Compatibility.
 */
class Compatibility implements Integration_Interface {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_filter( 'rocket_preload_links_exclusions', [ $this, 'exclude_linkout_from_rocket_preload' ] );
		add_filter( 'advanced-ads-compatibility-critical-inline-js', [ $this, 'critical_inline_js' ], 10, 2 );

		// Adjust Peepso placement output.
		if ( Str::contains( 'peepsoajax', Params::server( 'REQUEST_URI' ) ) && strtolower( Params::server( 'REQUEST_METHOD' ) ) === 'post' ) {
			$tracking = new Frontend\Tracking_Scripts();
			add_filter( 'advanced-ads-ad-output', [ $this, 'peepso_output' ], 10, 2 );
			add_filter( 'advanced-ads-output-wrapper-options', [ $tracking, 'add_wrapper' ], 10, 2 );
		}
	}

	/**
	 * Add advads-tracking to array not be optimized by WP Rocket, Complianz et al.
	 *
	 * @param array  $inline_js       Array with unique strings (IDs), identifying inline JavaScript.
	 * @param string $frontend_prefix The frontend_prefix option setting.
	 *
	 * @return array
	 */
	public function critical_inline_js( $inline_js, $frontend_prefix ): array {
		$inline_js[] = sprintf( 'id="%stracking"', $frontend_prefix );

		return $inline_js;
	}

	/**
	 * Add the linkout link base to be excluded from WP Rocket's link preloading.
	 *
	 * @param array $links Array with existing links/fragments.
	 *
	 * @return array
	 */
	public function exclude_linkout_from_rocket_preload( $links ): array {
		// RegEx for excluding all links starting with the link-base prefix.
		$links[] = sprintf( '/%s/.+', Helpers::get_link_base() );

		return $links;
	}

	/**
	 * Place markers on the Peepso placement output
	 *
	 * @param string $output the ad output.
	 * @param Ad     $ad     the ad object.
	 *
	 * @return string
	 */
	public function peepso_output( $output, $ad ) {
		ob_start();
		?>
		<script>
			document.dispatchEvent(
				new CustomEvent(
					'advads_track_async',
					{
						detail: {
							ad: <?php echo esc_js( $ad->get_id() ); ?>,
							bid: <?php echo esc_js( get_current_blog_id() ); ?>,
						}
					}
				)
			);
		</script>
		<?php
		$output .= ob_get_clean();
		return $output;
	}
}
