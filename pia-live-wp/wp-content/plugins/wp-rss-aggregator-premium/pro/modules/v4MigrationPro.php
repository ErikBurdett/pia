<?php

namespace RebelCode\Aggregator\Core;

use RebelCode\Aggregator\Pro\V4Migration\V4ProSourceMigrator;

wpra()->addModule(
	'v4MigrationPro',
	array(),
	function () {
		$ftpSettings = get_option( 'wprss_settings_ftp', array() );
		$srcMigrator = new V4ProSourceMigrator( $ftpSettings );

		add_filter(
			'wpra.v4Migration.source.converted',
			function ( $src, $meta ) use ( $srcMigrator ) {
				return $srcMigrator->migrate( $src, $meta );
			},
			10,
			2
		);
	}
);
