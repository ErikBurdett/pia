<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Custom code setting markup
 *
 * @package AdvancedAds
 * @author  Advanced Ads <info@wpadvancedads.com>
 *
 * @var string                   $custom_code     the custom code.
 * @var false|array              $settings        CodeMirror setting.
 * @var AdvancedAds\Abstracts\Ad $ad              the ad being edited
 * @var array                    $privacy_options the privacy module option.
 */

?>
<script>
	/**
	 * Init Custom code UI.
	 */
	jQuery( document ).ready( () => {
		const textarea = jQuery( '#advads-custom-code-textarea' );

		const init = () => {
			const settings = <?php echo wp_json_encode( $settings ); ?>;
			if ( ! settings ) {
				console.log( 'Advanced Ads >> Custom code settings not found' );
				return;
			}

			const editor = wp.codeEditor.initialize( textarea, settings );
			editor.codemirror.doc.setValue( textarea.val() );
			editor.codemirror.doc.on( 'change', ( doc ) => {
				jQuery( '#image-privacy-warning' ).toggle( '' !== doc.getValue().trim() );
			} );
		};

		if ( ! textarea.is( ':hidden' ) ) {
			init();
		} else {
			jQuery( '#advanced-ads-toggle-custom-code-editor' ).on( 'click', ( ev ) => {
				ev.preventDefault();
				jQuery( this ).hide();
				jQuery( textarea ).slideToggle( 400, init );
			} );
		}
	} );
</script>
<hr class="advads-hide-in-wizard"/>
<label class='label advads-hide-in-wizard' for="advads-custom-code-textarea"><?php esc_html_e( 'custom code', 'advanced-ads-pro' ); ?></label>
<div id="advads-custom-code-wrap" class="advads-hide-in-wizard">
	<?php if ( ! empty( $privacy_options['enabled'] ) && ! empty( $privacy_options['consent-method'] ) && $ad->is_type( 'image' ) ) : ?>
		<div id="image-privacy-warning" class="notice advads-notice inline" <?php echo $custom_code ? '' : 'style="display:none"'; ?>>
			<p>
				<?php
				esc_html_e(
					'Custom code is preventing the ad from being displayed until the visitor consents to your ads. You can override this by enabling the option "Ignore general Privacy settings and display the ad even without consent."',
					'advanced-ads-pro'
				);
				?>
			</p>
		</div>
	<?php endif; ?>
	<?php if ( $custom_code ) : ?>
		<textarea id="advads-custom-code-textarea" name="advanced_ad[output][custom-code]"><?php echo $custom_code; // phpcs:ignore ?></textarea>
		<p class="description"><?php esc_html_e( 'Displayed after the ad content', 'advanced-ads-pro' ); ?></p>
	<?php else : ?>
		<textarea id="advads-custom-code-textarea" style="display:none" name="advanced_ad[output][custom-code]"><?php echo $custom_code; // phpcs:ignore ?></textarea>
		<a id="advanced-ads-toggle-custom-code-editor" href="#"><?php esc_html_e( 'place your own code below the ad', 'advanced-ads-pro' ); ?>.</a>
	<?php endif; ?>
	<a href="https://wpadvancedads.com/how-to-add-custom-code-in-css-and-html-to-your-ads/?utm_source=advanced-ads?utm_medium=link&utm_campaign=ad-edit-custom-code" target="_blank" class="advads-manual-link"><?php esc_html_e( 'Manual', 'advanced-ads-pro' ); ?></a>
</div>
