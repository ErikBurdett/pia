<?php

namespace RebelCode\Aggregator\Core;

use RebelCode\WpSdk\Wp\Style;
use RebelCode\Aggregator\Core\Store\SourcesStore;
use RebelCode\Aggregator\Core\Display\DisplaySettings;
use RebelCode\Aggregator\Basic\V4Migration\V4LayoutMigrator;
use RebelCode\Aggregator\Basic\Layouts\GridLayout;
use RebelCode\Aggregator\Basic\Layouts\EtLayout;

wpra()->addModule(
	'layouts',
	array( 'renderer' ),
	function ( Renderer $renderer ) {
		$wpra = wpra();

		$renderer
		->addLayout( 'et', fn ( DisplaySettings $ds, SourcesStore $sourcesStore ) => new EtLayout( $ds, $sourcesStore ) )
		->addLayout( 'grid', fn ( DisplaySettings $ds, SourcesStore $sourcesStore ) => new GridLayout( $ds, $sourcesStore ) );

		$etCss = apply_filters(
			'wpra.layouts.et.css',
			new Style( 'wpra-et-layout-css', WPRA_PREMIUM_ROOT_URL . '/basic/css/et-layout.css', $wpra->version, array( 'wpra-displays' ) )
		);

		$gridCss = apply_filters(
			'wpra.layouts.grid.css',
			new Style( 'wpra-grid-layout-css', WPRA_PREMIUM_ROOT_URL . '/basic/css/grid-layout.css', $wpra->version, array( 'wpra-displays' ) )
		);

		add_action(
			'init',
			function () use ( $etCss, $gridCss ) {
				$etCss->register();
				$gridCss->register();
			}
		);

		// Load the styles in the admin UI frame
		add_filter(
			'wpra.admin.frame.head',
			function ( string $output ) use ( $etCss, $gridCss ) {
				ob_start();
				wp_styles()->do_items( array( $etCss->id, $gridCss->id ) );
				return $output . ob_get_clean();
			}
		);

		$migrator = new V4LayoutMigrator();
		add_filter(
			'wpra.v4Migration.display.converted',
			function ( Display $display, array $meta ) use ( $migrator ) {
				return $migrator->migrate( $display, $meta );
			},
			10,
			2
		);
	}
);
