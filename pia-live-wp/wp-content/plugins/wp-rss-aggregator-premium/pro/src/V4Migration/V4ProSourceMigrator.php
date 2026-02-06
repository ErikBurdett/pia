<?php

namespace RebelCode\Aggregator\Pro\V4Migration;

use RebelCode\Aggregator\Pro\Source\CustomMapping;
use RebelCode\Aggregator\Pro\Source\ContentCleaner;
use RebelCode\Aggregator\Core\Utils\Bools;
use RebelCode\Aggregator\Core\Source;
use RebelCode\Aggregator\Core\Logger;

class V4ProSourceMigrator {

	private array $ftpSettings;

	public function __construct( array $ftpSettings ) {
		$this->ftpSettings = $ftpSettings;
	}

	public function migrate( Source $src, array $meta ): Source {
		try {
			// content cleaners

			$extractRules = isset( $meta['wprss_ftp_extraction_rules'] ) ? maybe_unserialize( $meta['wprss_ftp_extraction_rules'] ) : array();
		$extractTypes = isset( $meta['wprss_ftp_extraction_rules_types'] ) ? maybe_unserialize( $meta['wprss_ftp_extraction_rules_types'] ) : array();
		$src->settings->contentCleaners = $this->convertExtractionRules( $extractRules, $extractTypes );

		// custom mapping
		$cmNamespaces = isset( $meta['wprss_ftp_rss_namespaces'] ) ? maybe_unserialize( $meta['wprss_ftp_rss_namespaces'] ) : array();
		$cmTags = isset( $meta['wprss_ftp_rss_tags'] ) ? maybe_unserialize( $meta['wprss_ftp_rss_tags'] ) : array();
		$cmFields = isset( $meta['wprss_ftp_custom_fields'] ) ? maybe_unserialize( $meta['wprss_ftp_custom_fields'] ) : array();

		if ( is_array( $cmNamespaces ) && is_array( $cmTags ) && is_array( $cmFields ) ) {
			for ( $i = 0; $i < count( $cmNamespaces ); $i++ ) {
				$ns = trim( $cmNamespaces[ $i ] ?? '' );
				$tag = trim( $cmTags[ $i ] ?? '' );
				$field = trim( $cmFields[ $i ] ?? '' );

				if ( ! empty( $tag ) ) {
					$src->settings->customMapping[] = new CustomMapping(
						CustomMapping::RSS_SELECTOR,
						$field,
						"$ns:$tag"
					);
				}
			}
		}

		// full text settings

		$fullText = $meta['wprss_ftp_force_full_content'] ?? '0';
		$fullTextMode = ( $this->ftpSettings['full_text_mode'] ?? 'article' );
		$src->settings->enableFullText = Bools::normalize( $fullText );
		$src->settings->fullTextBatchMode = strtolower( $fullTextMode ) === 'feed';

			// Logger::debug( "Source {$src->id} pro is migrated." ); // Removed debug log
		} catch ( \Exception $e ) {
			Logger::error( sprintf(
				"Error migrating pro source settings for source ID %s (Name: %s): %s",
				$src->id,
				$src->name,
				$e->getMessage()
			) );
			// Optionally, return $src without changes or handle more gracefully
		}
		return $src;
	}

	/** @return ContentCleaner[] */
	private function convertExtractionRules( $rules, $types ): array {
		$typeMap = array(
			'keep' => ContentCleaner::KEEP_ELEM,
			'remove' => ContentCleaner::REMOVE_ELEM,
			'remove_keep_children' => ContentCleaner::REMOVE_KEEP_CONTENT,
			'remove_attr' => ContentCleaner::REMOVE_ATTR,
		);

		if ( ! is_array( $rules ) || ! is_array( $types ) || count( $rules ) !== count( $types ) ) {
			return array();
		}

		$combined = array_combine( $rules, $types );

		$cleaners = array();
		foreach ( $combined as $rule => $type ) {
			$type = strtolower( trim( $type ) );
			$newType = $typeMap[ $type ] ?? 'WOOOOOO';
			$selector = strtolower( trim( $rule ) );

			if ( $newType !== null && ! empty( $selector ) ) {
				$cleaners[] = new ContentCleaner( $newType, $rule );
			}
		}
		return $cleaners;
	}
}
