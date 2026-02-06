<?php

namespace RebelCode\Aggregator\Elite\V4Migration;

use RebelCode\Aggregator\Core\Utils\Bools;
use RebelCode\Aggregator\Core\Source;
use RebelCode\Aggregator\Core\Logger;

class V4EliteSourceMigrator {

	private array $waiSettings;
	private array $scSettings;

	public function __construct( array $waiSettings, array $scSettings ) {
		$this->waiSettings = $waiSettings;
		$this->scSettings = $scSettings;
	}

	public function migrate( Source $src ): Source {
		try {
			$waiEnabled = $this->waiSettings['enabled'] ?? false;
			$waiSpinTitle = $this->waiSettings['spin_title'] ?? false;
			$waiRevisions = $this->waiSettings['revisions'] ?? false;
			$waiSpintax = $this->waiSettings['spintax'] ?? false;
			$waiUniqueness = $this->waiSettings['uniqueness'] ?? 'regular';
			$waiProtectWords = $this->waiSettings['protect_words'] ?? false;
			$waiCustomSynonyms = $this->waiSettings['use_custom_synonyms'] ?? false;
			$src->settings->waiEnableContent = Bools::normalize( $waiEnabled );
			$src->settings->waiEnableTitle = $src->settings->waiEnableContent && Bools::normalize( $waiSpinTitle );
			$src->settings->waiRevisions = Bools::normalize( $waiRevisions );
			$src->settings->waiSpintax = Bools::normalize( $waiSpintax );
			$src->settings->waiUniqueness = $waiUniqueness;
			$src->settings->waiProtectWords = Bools::normalize( $waiProtectWords );
			$src->settings->waiCustomSynonyms = Bools::normalize( $waiCustomSynonyms );

			$scEnabled = $this->scSettings['enabled'] ?? false;
			$scSpinTitle = $this->scSettings['spin_title'] ?? false;
			$scRevisions = $this->scSettings['revisions'] ?? false;
			$src->settings->scEnableContent = Bools::normalize( $scEnabled );
			$src->settings->scEnableTitle = $src->settings->scEnableContent && Bools::normalize( $scSpinTitle );
			$src->settings->scRevisions = Bools::normalize( $scRevisions );

			// Logger::debug( "Source {$src->id} of elite is migrated." ); // Removed debug log
		} catch ( \Exception $e ) {
			Logger::error( sprintf(
				"Error migrating elite source settings for source ID %s (Name: %s): %s",
				$src->id,
				$src->name,
				$e->getMessage()
			) );
			// Optionally, return $src without changes or handle more gracefully
		}
		return $src;

	}
}
