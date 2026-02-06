<?php
/**
 * An underscore.js template.
 *
 * @package fusion-builder
 */

?>
<script type="text/template" id="fusion-builder-app-template">
	<div id="fusion-loader"><span class="fusion-builder-loader"></span><span class="awb-studio-import-status"></span></div>
	<div id="content-error" title="{{{ fusionBuilderText.content_error_title }}}" style="display:none;">
		<p>{{{ fusionBuilderText.content_error_description }}}</p>
	</div>
	<div id="fusion_builder_controls">

		<ul id="fusion-page-builder-tabs">
			<li><a href="javascript:void(0)" class="fusion-builder-button">{{ fusionBuilderText.builder }}</a></li>
			<li><a href="#" class="fusion-builder-library-dialog">{{ fusionBuilderText.library }}</a></li>
		</ul>

		<div class="fusion-page-builder-controls">
			<a href="#" class="fusion-builder-layout-buttons fusion-builder-layout-buttons-toggle-containers" title="{{ fusionBuilderText.toggle_all_sections }}"><span class="dashicons-before dashicons-arrow-down"></span></a>
			<a href="#" class="fusion-builder-layout-buttons fusion-builder-layout-code-fields" title="{{ fusionBuilderText.code_fields }}"><span class="fusiona-file-code-o"></span></a>
			<a href="#" class="fusion-builder-layout-buttons fusion-builder-layout-custom-css <?php echo esc_attr( $has_custom_css ); ?>" title="{{ fusionBuilderText.custom_css }}"><span class="fusiona-code"></span></a>
			<?php if ( current_user_can( apply_filters( 'awb_role_manager_access_capability', 'edit_posts', 'avada_library', 'backed_builder_edit' ) ) ) : ?>
				<a href="#" class="fusion-builder-layout-buttons fusion-builder-template-buttons-save" title="{{ fusionBuilderText.save_page_layout }}"><span class="fusiona-drive"></span></a>
			<?php endif; ?>
			<a href="#" class="fusion-builder-layout-buttons fusion-builder-layout-buttons-clear" title="{{ fusionBuilderText.delete_page_layout }}"><span class="fusiona-trash-o"></span></a>
			<a href="javascript:void(0)" class="fusion-builder-layout-buttons fusion-builder-layout-buttons-history" title="{{ fusionBuilderText.history }}">
				<span class="dashicons dashicons-backup"></span>
				<ul class="fusion-builder-history-list">
					<li class="fusion-empty-history fusion-history-active-state" data-state-id="1"><span class="dashicons dashicons-arrow-right-alt2"></span>{{ fusionBuilderText.empty }}</li>
				</ul>
			</a>
		</div>

		<?php
		if ( class_exists( 'Avada' ) ) {
			$sections = [];
			require_once wp_normalize_path( Avada::$template_dir_path . '/includes/metaboxes/tabs/tab_code_fields.php' );
			if ( function_exists( 'avada_page_options_tab_code_fields' ) ) {
				$sections = call_user_func( 'avada_page_options_tab_code_fields', [] );
			}

			if ( isset( $sections['code_fields']['fields'] ) ) {
				?>
				<div class="awb-po-code-fields">
					<div class="awb-po-code-fields-wrapper">

							<?php foreach ( $sections['code_fields']['fields'] as $name => $field ) { ?>
								<div class="awb-po-code-field awb-<?php echo esc_attr( str_replace( '_', '-', $name ) ); ?>">
									<label for="<?php echo esc_attr( Fusion_Data_PostMeta::ROOT . '[' . $field['id'] . ']' ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
									<textarea class="awb-code" placeholder="<?php echo esc_attr( $field['description'] ); ?>" id="<?php echo esc_attr( $field['id'] ); ?>" name="<?php echo esc_attr( Fusion_Data_PostMeta::ROOT . '[' . $field['id'] . ']' ); ?>" ><?php echo esc_html( fusion_data()->post_meta( $post->ID )->get( $name ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></textarea>
								</div>
							<?php } ?>

					</div>
				</div>
			<?php } ?>
		<?php } ?>

		<div class="fusion-custom-css">
			<?php
			$echo_custom_css = '';
			if ( ! empty( $saved_custom_css ) ) {
				$echo_custom_css = $saved_custom_css;
			}
			?>
			<label for="_fusion_builder_custom_css">{{ fusionBuilderText.custom_css }}</label>
			<textarea name="_fusion_builder_custom_css" id="fusion-custom-css-field" placeholder="{{ fusionBuilderText.add_css_code_here }}"><?php echo wp_strip_all_tags( $echo_custom_css ); // phpcs:ignore WordPress.Security.EscapeOutput ?></textarea>
		</div>

	</div>

	<div id="fusion_builder_container">
		<?php do_action( 'fusion_builder_before_content' ); ?>
	</div>
	<?php do_action( 'fusion_builder_after_content' ); ?>

	<div id="fusion-builder-layouts">
		<?php Fusion_Builder_Library()->display_library_content( 'backend_builder' ); ?>
	</div>

	<div id="fusion-google-font-holder" style="display:none">
		<?php
		$echo_google_fonts  = '';
		$saved_google_fonts = get_post_meta( $post->ID, '_fusion_google_fonts', true );
		if ( ! empty( $saved_google_fonts ) ) {
			$echo_google_fonts = $saved_google_fonts;
		}
		?>
		<textarea name="_fusion_google_fonts" id="fusion-google-fonts-field"><?php echo wp_json_encode( $echo_google_fonts ); // phpcs:ignore WordPress.Security.EscapeOutput ?></textarea>
		<div id="fusion-render-holder" style="display:none"></div>
	</div>
	<div id="fusion-studio-media-map-holdder" style="display:none">
		<?php
		$echo_media_map  = '';
		$saved_media_map = get_post_meta( $post->ID, 'avada_media', true );
		if ( ! empty( $saved_media_map ) ) {
			$echo_media_map = $saved_media_map;
		}
		?>
		<textarea name="avada_media" id="fusion-studio-media-map-field"><?php echo wp_json_encode( $echo_media_map ); // phpcs:ignore WordPress.Security.EscapeOutput ?></textarea>
	</div>
</script>
