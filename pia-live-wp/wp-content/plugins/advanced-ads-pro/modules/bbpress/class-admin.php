<?php
/**
 * BBPress module admin
 *
 * @package AdvancedAds\Pro\Modules\bbPress
 * @author  Advanced Ads <info@wpadvancedads.com>
 */

namespace AdvancedAds\Pro\Modules\bbPress\Admin;

use AdvancedAds\Utilities\WordPress;
use AdvancedAds\Framework\Interfaces\Integration_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Class Admin
 */
class Admin implements Integration_Interface {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'advanced-ads-placement-options-after', [ $this, 'bbpress_comment' ], 10, 2 );
		add_action( 'advanced-ads-placement-options-after', [ $this, 'bbpress_static' ], 10, 2 );
	}

	/**
	 * Add position setting for static content placement
	 *
	 * @param string    $slug      the placement slug.
	 * @param Placement $placement the placement.
	 *
	 * @return void
	 */
	public function bbpress_static( $slug, $placement ): void {
		// Early bail!!
		if ( ! $placement->is_type( 'bbPress static' ) ) {
			return;
		}

		$hooks = $this->get_bbpress_static_hooks();
		ob_start();
		?>
		<label>
			<select name="advads[placements][options][bbPress_static_hook]">
				<option>---</option>
				<?php foreach ( $hooks as $group => $positions ) : ?>
					<optgroup label="<?php echo esc_attr( $group ); ?>">
						<?php foreach ( $positions as $position ) : ?>
							<option value="<?php echo esc_attr( $position ); ?>" <?php selected( $position, $placement->get_prop( 'bbPress_static_hook' ) ); ?>><?php echo esc_html( $position ); ?></option>
						<?php endforeach; ?>
					</optgroup>
				<?php endforeach; ?>
			</select>
		</label>
		<?php
		$content = ob_get_clean();
		WordPress::render_option(
			'bbpress-static',
			__( 'Position', 'advanced-ads-pro' ),
			$content
		);
	}

	/**
	 * Add position setting for bbpress reply placement
	 *
	 * @param string    $slug      the placement slug.
	 * @param Placement $placement the placement object.
	 *
	 * @return void
	 */
	public function bbpress_comment( $slug, $placement ): void {
		// Early bail!!
		if ( ! $placement->is_type( 'bbPress comment' ) ) {
			return;
		}

		$comment_hooks = $this->get_bbpress_comment_hooks();
		ob_start();
		?>
		<label>
			<?php esc_html_e( 'Position', 'advanced-ads-pro' ); ?>&nbsp;
			<select name="advads[placements][options][bbPress_comment_hook]">
				<option>---</option>
				<?php foreach ( $comment_hooks as $group => $positions ) : ?>
					<optgroup label="<?php echo esc_attr( $group ); ?>">
						<?php foreach ( $positions as $position ) : ?>
							<option value="<?php echo esc_attr( $position ); ?>"<?php selected( $position, $placement->get_prop( 'bbPress_comment_hook' ) ); ?>><?php echo esc_html( $position ); ?></option>
						<?php endforeach; ?>
					</optgroup>
				<?php endforeach; ?>
			</select>
		</label>
		<br>
		<br>
		<label>
			<?php
			echo wp_kses(
				sprintf(
				/* translators: %s: index input field */
					__( 'Inject after %s post', 'advanced-ads-pro' ),
					sprintf(
						'<input type="number" required="required" min="1" step="1" name="advads[placements][options][pro_bbPress_comment_pages_index]" value="%d"/>',
						Max( 1, (int) $placement->get_prop( 'pro_bbPress_comment_pages_index' ) )
					)
				),
				[
					'input' => [
						'type'     => [],
						'required' => [],
						'min'      => [],
						'step'     => [],
						'name'     => [],
						'value'    => [],
					],
				]
			);
			?>
		</label>
		<br>
		<?php
		$content = ob_get_clean();
		WordPress::render_option(
			'bbpress-comment',
			__( 'Position', 'advanced-ads-pro' ),
			$content
		);
	}

	/**
	 * Get bbpress static content hooks
	 *
	 * @return array
	 */
	private function get_bbpress_static_hooks(): array {
		return [
			__( 'forum topic page', 'advanced-ads-pro' )  => [
				'template after replies loop',
				'template before replies loop',
			],
			__( 'single forum page', 'advanced-ads-pro' ) => [
				'template after single forum',
				'template before single forum',
			],
			__( 'forums page', 'advanced-ads-pro' )       => [
				'template after forums loop',
				'template before forums loop',
			],
		];
	}

	/**
	 * Get bbpress comment hooks
	 *
	 * @return array
	 */
	private function get_bbpress_comment_hooks(): array {
		return [
			__( 'forum topic page', 'advanced-ads-pro' ) => [
				'theme after reply content',
				'theme before reply content',
				'theme after reply author admin details',
			],
		];
	}
}
