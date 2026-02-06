<?php

namespace RebelCode\Aggregator\Plus\V4Migration;

use RebelCode\Aggregator\Core\Utils\Bools;
use RebelCode\Aggregator\Core\Logger;

class V4PlusSettingsMigrator {

	private array $ftpSettings;

	public function __construct( array $ftpSettings ) {
		$this->ftpSettings = $ftpSettings;
	}

	public function migrate( array $settings ): array {
		try {
			$settings['injectContentInFeeds'] = Bools::normalize( $this->ftpSettings['add_content_in_feed'] ?? false );
			// Logger::debug( "Settings in plus has migrated." ); // Removed debug log
		} catch ( \Exception $e ) {
			Logger::error( sprintf(
				"Error migrating plus settings: %s",
				$e->getMessage()
			) );
			// Optionally, return $settings without changes or handle more gracefully
		}
		return $settings;
	}

}
