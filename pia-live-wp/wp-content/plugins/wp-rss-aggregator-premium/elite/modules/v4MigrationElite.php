<?php

namespace RebelCode\Aggregator\Core;

use RebelCode\Aggregator\Elite\V4Migration\V4EliteSourceMigrator;
use RebelCode\Aggregator\Elite\V4Migration\V4EliteSettingsMigrator;

wpra()->addModule(
	'v4MigrationElite',
	array(),
	function () {
		$v4WaiSettings = get_option( 'wprss_settings_wordai', array() );
		$v4ScSettings = get_option( 'wprss_spc_settings', array() );
		$srcMigrator = new V4EliteSourceMigrator( $v4WaiSettings, $v4ScSettings );
		$settingsMigrator = new V4EliteSettingsMigrator( $v4WaiSettings, $v4ScSettings );

		add_filter( 'wpra.v4Migration.source.converted', fn ( $src ) => $srcMigrator->migrate( $src ) );
		add_filter( 'wpra.v4Migration.settings.patch', fn ( $settings ) => $settingsMigrator->migrate( $settings ) );
	}
);
