<?php
/**
 * Frontend Analytics Tracking.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking\Frontend;

use AdvancedAds\Framework\Interfaces\Integration_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Frontend Analytics Tracking.
 */
class Analytics_Tracking implements Integration_Interface {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'wp_head', [ $this, 'do_head' ] );
		add_action( 'wp_footer', [ $this, 'do_footer' ], PHP_INT_MAX - 1 );
	}

	/**
	 * Print Google Analytics related javascript in <head />
	 *
	 * @return void
	 */
	public function do_head(): void {
		?>
		<script type="text/javascript">
			if ( typeof advadsGATracking === 'undefined' ) {
				window.advadsGATracking = {
					delayedAds: {},
					deferedAds: {}
				};
			}
		</script>
		<?php
	}

	/**
	 * Print Google Analytics related javascript within the 'wp_footer' action
	 *
	 * @return void
	 */
	public function do_footer(): void {
		$ad_targets = apply_filters( 'advanced-ads-get-ad-targets', [] );

		if ( ! empty( $ad_targets ) ) {
			if ( is_singular() ) {
				$post       = get_post();
				$context    = [
					'postID'   => $post->ID,
					'postSlug' => $post->post_name,
				];
				$categories = get_the_category( $post->ID );
				$cats_slugs = [];
				foreach ( $categories as $cat ) {
					$cats_slugs[] = $cat->slug;
				}
				$cats            = implode( ',', $cats_slugs );
				$context['cats'] = $cats;
			}
			?>
			<script type="text/javascript">
				if ( typeof window.advadsGATracking === 'undefined' ) {
					window.advadsGATracking = {};
				}
				<?php if ( is_singular() ) : ?>
				advadsGATracking.postContext = <?php echo wp_json_encode( $context ); ?>;
				<?php endif; ?>
			</script>
			<?php
		}
	}
}
