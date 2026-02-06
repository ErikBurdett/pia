<?php
/**
 * Underscore.js template
 *
 * @package fusion-builder
 * @since 2.0
 */

?>
<script type="text/template" id="fusion-builder-modules-template">
	<div class="fusion-builder-modal-top-container">
		<div class="fusion-builder-modal-search">
			<label for="fusion-modal-search" class="fusiona-search"><span><?php esc_html_e( 'Search', 'fusion-builder' ); ?></span></label>
			<input type="text" id="fusion-modal-search" class="fusion-elements-filter" placeholder="{{ fusionBuilderText.search_elements }}" />
		</div>
		<ul class="fusion-tabs-menu">
			<# if ( 'undefined' !== typeof components && components.length && 0 < componentsCounter ) { #>
				<li class=""><a href="#template-elements">{{ fusionBuilderText.layout_section_elements }}</a></li>
			<# } #>
			<# if ( 'undefined' !== typeof form_components && form_components.length && 'fusion_form' === FusionApp.data.postDetails.post_type ) { #>
				<li class=""><a href="#form-elements">{{ fusionBuilderText.form_elements }}</a></li>
			<# } #>
			<li class=""><a href="#default-elements">{{ fusionBuilderText.builder_elements }}</a></li>
			<li class=""><a href="#custom-elements">{{ fusionBuilderText.library_elements }}</a></li>
			<li class=""><a href="#inner-columns">{{ fusionBuilderText.inner_columns }}</a></li>
			<# if ( '1' === fusionAppConfig.studio_status ) { #>
				<li class=""><a href="#fusion-builder-elements-studio"><i class="fusiona-avada-logo"></i> <?php esc_html_e( 'Studio', 'fusion-builder' ); ?></a></li>
			<# } #>
		</ul>
	</div>

	<# const wooBadge = '<svg style="position: absolute; top: 10px; right: 7px;" width="32.5" height="9" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 183.6 47.5" style="enable-background:new 0 0 183.6 47.5;" xml:space="preserve"><style type="text/css">.stw0{fill-rule:evenodd;clip-rule:evenodd;fill:#873EFF;}.stw1{fill-rule:evenodd;clip-rule:evenodd;}.stw2{fill:#873EFF;}.stw3{fill-rule:evenodd;clip-rule:evenodd;fill:#FFFFFF;}.st4{fill:#FFFFFF;}</style><g><path class="stw0" d="M77.4,0c-4.3,0-7.1,1.4-9.6,6.1L56.4,27.6V8.5c0-5.7-2.7-8.5-7.7-8.5s-7.1,1.7-9.6,6.5L28.3,27.6V8.7c0-6.1-2.5-8.7-8.6-8.7H7.3C2.6,0,0,2.2,0,6.2s2.5,6.4,7.1,6.4h5.1v24.1c0,6.8,4.6,10.8,11.2,10.8s9.6-2.6,12.9-8.7l7.2-13.5v11.4c0,6.7,4.4,10.8,11.1,10.8s9.2-2.3,13-8.7l16.6-28C87.8,4.7,85.3,0,77.3,0C77.3,0,77.3,0,77.4,0z"/><path class="stw0" d="M108.6,0C95,0,84.7,10.1,84.7,23.8s10.4,23.7,23.9,23.7s23.8-10.1,23.9-23.7C132.5,10.1,122.1,0,108.6,0zM108.6,32.9c-5.1,0-8.6-3.8-8.6-9.1s3.5-9.2,8.6-9.2s8.6,3.9,8.6,9.2S113.8,32.9,108.6,32.9z"/><path class="stw0" d="M159.7,0c-13.5,0-23.9,10.1-23.9,23.8s10.4,23.7,23.9,23.7s23.9-10.1,23.9-23.7S173.2,0,159.7,0z M159.7,32.9c-5.2,0-8.5-3.8-8.5-9.1s3.4-9.2,8.5-9.2s8.6,3.9,8.6,9.2S164.9,32.9,159.7,32.9z"/></g></svg>'; #>

	<div class="fusion-builder-main-settings fusion-builder-main-settings-full has-group-options">
		<div class="fusion-builder-all-elements-container">
			<div class="fusion-tabs">
				<# if ( 'undefined' !== typeof components && components.length && 0 < componentsCounter ) { #>
					<div id="template-elements" class="fusion-tab-content">
						<ul class="fusion-builder-all-modules fusion-template-components">
							<# _.each( components, function( module ) { #>
								<#
								var additionalClass = false !== module.components_per_template && FusionPageBuilderViewManager.countElementsByType( module.label ) >= module.components_per_template ? ' fusion-builder-disabled-element' : '';

								// If element is not supposed to be active on page edit type, skip.
								if ( 'object' === typeof module.templates && ! module.templates.includes( FusionApp.data.template_category ) ) {
									return false;
								}

								var components_per_template_tooltip = fusionBuilderText.template_max_use_limit + ' ' + module.components_per_template
								components_per_template_tooltip     = ( 2 > module.components_per_template ) ? components_per_template_tooltip + ' ' + fusionBuilderText.time : components_per_template_tooltip + ' ' + fusionBuilderText.times;
								components_per_template_tooltip = 'string' === typeof module.template_tooltip ? module.template_tooltip : components_per_template_tooltip;
								#>
								<li class="{{ module.label }} fusion-builder-element{{ additionalClass }}">
									<# if ( -1 !== module.title.indexOf( 'Woo' ) ) { #>
										{{{ wooBadge }}}
									<# } #>	
									<h4 class="fusion_module_title">
										<# if ( 'undefined' !== typeof fusionAllElements[module.label].icon ) { #>
											<div class="fusion-module-icon {{ fusionAllElements[module.label].icon }}"></div>
										<# } #>
										{{{ module.title }}}
									</h4>
									<# if ( false !== module.components_per_template && FusionPageBuilderViewManager.countElementsByType( module.label ) >= module.components_per_template ) { #>
										<span class="fusion-tooltip">{{ components_per_template_tooltip }}</span>
									<# } #>
									<span class="fusion_module_label">{{ module.label }}</span>
								</li>
							<# } ); #>

							<# for ( var i = 0; i < 16; i++ ) { #>
								<li class="spacer fusion-builder-element"></li>
							<# } #>
						</ul>
					</div>
				<# } #>

				<# if ( 'undefined' !== typeof form_components && form_components.length && 'fusion_form' === FusionApp.data.postDetails.post_type ) { #>
					<div id="form-elements" class="fusion-tab-content">
						<ul class="fusion-builder-all-modules fusion-form-components">
							<# _.each( form_components, function( module ) { #>
								<li class="{{ module.label }} fusion-builder-element">
									<h4 class="fusion_module_title">
										<# if ( 'undefined' !== typeof fusionAllElements[module.label].icon ) { #>
											<div class="fusion-module-icon {{ fusionAllElements[module.label].icon }}"></div>
										<# } #>
										{{{ module.title }}}
									</h4>
									<span class="fusion_module_label">{{ module.label }}</span>
								</li>
							<# } ); #>

							<# for ( var i = 0; i < 16; i++ ) { #>
								<li class="spacer fusion-builder-element"></li>
							<# } #>
						</ul>
					</div>
				<# } #>

				<div id="default-elements" class="fusion-tab-content">
					<ul class="fusion-builder-all-modules">
						<# _.each( generator_elements, function(module) { #>
							<#
							if ( 'fusion_form' === FusionApp.data.postDetails.post_type && 'fusion_form' === module.label ) {
								return;
							}
							if ( 'mega_menus' === FusionApp.data.template_category && 'fusion_menu' === module.label ) {
								return;
							}
							if ( 'post_cards' === FusionApp.data.template_category && 'fusion_post_cards' === module.label ) {
								return;
							}
							// If element is not supposed to be active on page edit type, skip.
							if ( 'object' === typeof module.templates && ! module.templates.includes( FusionApp.data.template_category ) ) {
								return false;
							}
							#>
							<# var additionalClass = ( 'undefined' !== typeof module.generator_only ) ? ' fusion-builder-element-generator' : '',
									elementTooltip = 'undefined' !== typeof module.generator_only ? fusionBuilderText.generator_elements_tooltip : '';

								if ( 'string' === typeof module.template_tooltip && false === module.components_per_template ) {
									additionalClass += ' fusion-builder-custom-tooltip-element';

									elementTooltip = module.template_tooltip;
								}

								if ( false !== module.components_per_template && FusionPageBuilderViewManager.countElementsByType( module.label ) >= module.components_per_template ) {
									additionalClass += ' fusion-builder-disabled-element';

									elementTooltip = fusionBuilderText.template_max_use_limit + ' ' + module.components_per_template
									elementTooltip = ( 2 > module.components_per_template ) ? elementTooltip + ' ' + fusionBuilderText.time : elementTooltip + ' ' + fusionBuilderText.times;
									elementTooltip = 'string' === typeof module.template_tooltip ? module.template_tooltip : elementTooltip;
								}
							#>
							<li class="{{ module.label }} fusion-builder-element{{ additionalClass }}">
								<# if ( -1 !== module.title.indexOf( 'Woo' ) ) { #>
									{{{ wooBadge }}}
								<# } #>								
								<h4 class="fusion_module_title">
									<# if ( 'undefined' !== typeof fusionAllElements[module.label].icon ) { #>
										<div class="fusion-module-icon {{ fusionAllElements[module.label].icon }}"></div>
									<# } #>
									{{{ module.title }}}
								</h4>
								<# if ( '' !== elementTooltip ) { #>
									<span class="fusion-tooltip">{{ elementTooltip }}</span>
								<# } #>
								<span class="fusion_module_label">{{ module.label }}</span>
							</li>
						<# } ); #>

						<# for ( var i = 0; i < 16; i++ ) { #>
							<li class="spacer fusion-builder-element"></li>
						<# } #>
					</ul>
				</div>

				<div id="inner-columns" class="fusion-tab-content">
					<?php echo fusion_builder_inner_column_layouts(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
				<# if ( '1' === fusionAppConfig.studio_status ) { #>
					<div id="fusion-builder-elements-studio" class="fusion-tab-content">
						<?php if ( function_exists( 'Avada' ) && Avada()->registration->is_registered() ) : ?>
							<div class="studio-wrapper">
								<aside>
									<ul></ul>
								</aside>
								<section>
									<div class="fusion-builder-element-content fusion-loader"><span class="fusion-builder-loader"></span></div>
									<ul class="studio-imports"></ul>
								</section>
								<?php AWB_Studio::studio_import_options_template(); ?>
							</div>
						<?php else : ?>
							<h2 class="awb-studio-not-reg"><?php esc_html_e( 'The product needs to be registered to access the Avada Studio.', 'fusion-builder' ); ?></h2>
						<?php endif; ?>
					</div>
				<# } #>

				<div id="custom-elements" class="fusion-tab-content"></div>
			</div>
		</div>
	</div>
</script>
