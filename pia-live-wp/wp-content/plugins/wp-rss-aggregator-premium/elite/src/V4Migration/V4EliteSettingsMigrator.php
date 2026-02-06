<?php

namespace RebelCode\Aggregator\Elite\V4Migration;

use RebelCode\Aggregator\Core\Logger;
class V4EliteSettingsMigrator {

	private array $waiSettings;
	private array $scSettings;

	public function __construct( array $waiSettings, array $scSettings ) {
		$this->waiSettings = $waiSettings;
		$this->scSettings = $scSettings;
	}

	public function migrate( array $settings ): array {
		try {
			$settings = array_merge(
				$settings,
				array(
					'wordAiEmail' => $this->waiSettings['email'] ?? '',
					'wordAiApiKey' => $this->waiSettings['api_key'] ?? '',
					'scApiKey' => $this->scSettings['api_key'] ?? '',
				)
			);

			// Logger::debug( "Settings elite has migrated." ); // Removed debug log
		} catch ( \Exception $e ) {
			Logger::error( sprintf(
				"Error migrating elite settings: %s",
				$e->getMessage()
			) );
			// Optionally, return $settings without changes or handle more gracefully
		}
		return $settings;
	}
}
