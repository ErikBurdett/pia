<?php

namespace RebelCode\Aggregator\Core;

use RebelCode\Aggregator\Plus\V4Migration\V4PlusSettingsMigrator;
use RebelCode\Aggregator\Plus\V4Migration\V4PlusSourceMigrator;

wpra()->addModule(
	'v4MigrationPlus',
	array(),
	function () {
		$ftpSettings = get_option( 'wprss_settings_ftp', array() );
		$srcMigrator = new V4PlusSourceMigrator( $ftpSettings );
		$settingsMigrator = new V4PlusSettingsMigrator( $ftpSettings );

		add_filter( 'wpra.v4Migration.source.converted', fn ( $src, $meta ) => $srcMigrator->migrate( $src, $meta ), 10, 2 );
		add_filter( 'wpra.v4Migration.settings.patch', fn ( $settings ) => $settingsMigrator->migrate( $settings ) );
	}
);
