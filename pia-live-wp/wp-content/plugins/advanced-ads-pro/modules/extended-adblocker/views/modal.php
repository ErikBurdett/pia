<?php
/**
 * Extended Adblocker Popup Modal
 *
 * @package AdvancedAds\Pro
 *
 * @var string $modal_slug               Unique slug that can be addressed by a link or button.
 * @var string $modal_content            The modal content. May contain HTML.
 * @var string $close_action             Adds close button.
 * @var string $dismiss_button_styling   Dismiss button styling.
 * @var string $container_styling        Container styling.
 * @var string $background_styling       Background styling.
 */

?>
<dialog id="modal-<?php echo esc_attr( $modal_slug ); ?>"
		class="advads-modal"
		data-modal-id="<?php echo esc_attr( $modal_slug ); ?>"
		autofocus
		style="<?php echo esc_attr( $background_styling ?? '' ); ?>">
	<div class="advads-modal-content" style="<?php echo esc_attr( $container_styling ?? '' ); ?>">
		<div class="advads-modal-body">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- modal content may contain any kind of custom html
			echo $modal_content;
			?>
			<?php if ( $close_action ) : ?>
			<div class="close-wrapper">
				<button class="advads-modal-close-action"
						onclick="document.getElementById('modal-<?php echo esc_attr( $modal_slug ); ?>').close()"
						style="<?php echo esc_attr( $dismiss_button_styling ?? '' ); ?>">
					<?php echo esc_html( $close_action ); ?>
				</button>
			</div>
			<?php endif; ?>
		</div>
	</div>
</dialog>
