<?php

declare(strict_types=1);

namespace RebelCode\Aggregator\Basic\V4Migration;

use RebelCode\Aggregator\Core\Utils\Bools;
use RebelCode\Aggregator\Core\Source;
use RebelCode\Aggregator\Core\Logger;
use RebelCode\Aggregator\Basic\Source\Automation;
use RebelCode\Aggregator\Basic\Conditions\Expression;
use RebelCode\Aggregator\Basic\Conditions\Condition;

class V4AutomationsMigrator {

	private array $kfSettings;

	public function __construct( array $kfSettings ) {
		$this->kfSettings = $kfSettings;
	}

	public function migrateSourceAutomations( Source $src, array $meta ): Source {
		try {
			$src->settings->automations = $this->convertSourceMeta( $meta );
			// Logger::debug( "Source {$src->name} in basic plan has migrated." ); // Removed debug log
		} catch ( \Exception $e ) {
			Logger::error( sprintf(
				"Error migrating source automations for source ID %s (Name: %s) in basic plan: %s",
				$src->id,
				$src->name,
				$e->getMessage()
			) );
			// Optionally, return $src without changes or handle more gracefully
		}
		return $src;
	}

	public function migrateSettings( array $patch ): array {
		try {
			$patch['automations'] = $this->convertSettings();
		} catch ( \Exception $e ) {
			Logger::error( sprintf(
				"Error migrating automation settings in basic plan: %s",
				$e->getMessage()
			) );
			// Optionally, return $patch without changes or handle more gracefully
		}
		return $patch;
	}

	public function convertSourceMeta( array $meta ): array {
		$automations = array();
		$enabled = true;

		$filterTitle = $meta['wprss_filter_title'] ?? '0';
		$filterTitle = Bools::normalize( $filterTitle ?: true );

		$filterContent = $meta['wprss_filter_content'] ?? '0';
		$filterContent = Bools::normalize( $filterContent ?: true );

		if ( $filterTitle && $filterContent ) {
			$subject = 'title_content';
		} elseif ( $filterTitle ) {
			$subject = 'title';
		} elseif ( $filterContent ) {
			$subject = 'content';
		} else {
			$subject = '';
			$enabled = false;
		}

		$kwAll  = isset( $meta['wprss_keywords'] ) ? array_filter( array_map( 'trim', explode( ',', $meta['wprss_keywords'] ) ) ) : array();
		$kwAny  = isset( $meta['wprss_keywords_any'] ) ? array_filter( array_map( 'trim', explode( ',', $meta['wprss_keywords_any'] ) ) ) : array();
		$kwNone = isset( $meta['wprss_keywords_not'] ) ? array_filter( array_map( 'trim', explode( ',', $meta['wprss_keywords_not'] ) ) ) : array();
		$exprs = array();
		if ( ! empty( $kwAll ) ) {
			$exprs[] = new Expression( $subject, 'strContainsAll', array( 'value' => $kwAll ) );
		}
		if ( ! empty( $kwAny ) ) {
			$exprs[] = new Expression( $subject, 'strContainsAny', array( 'value' => $kwAny ) );
		}
		if ( ! empty( $kwNone ) ) {
			$exprs[] = new Expression( $subject, 'strNotContainsAny', array( 'value' => $kwNone ) );
		}
		if ( count( $exprs ) > 0 ) {
			$automations[] = new Automation(
				'',
				$enabled,
				array( new Condition( true, $exprs ) ),
				'import'
			);
		}

		$tagsAny = isset( $meta['wprss_keywords_tags'] ) ? trim( $meta['wprss_keywords_tags'] ) : '';
		$tagsNone = isset( $meta['wprss_keywords_not_tags'] ) ? trim( $meta['wprss_keywords_not_tags'] ) : '';
		$tagExprs = array();
		if ( strlen( $tagsAny ) > 0 ) {
			$tagExprs[] = new Expression( 'tags', 'tagsContainAny', array( 'value' => $tagsAny ) );
		}
		if ( strlen( $tagsNone ) > 0 ) {
			$tagExprs[] = new Expression( 'tags', 'tagsContainNone', array( 'value' => $tagsNone ) );
		}
		if ( count( $tagExprs ) > 0 ) {
			$automations[] = new Automation(
				'',
				true,
				array( new Condition( true, $tagExprs ) ),
				'import'
			);
		}

		return $automations;
	}

	/** @return Automation[] */
	public function convertSettings(): array {
		$automations = array();

		$kf = $this->kfSettings;
		$enabled = true;
		$filterTitle = $kf['filter_title'] ?? '0';
		$filterTitle = Bools::normalize( $filterTitle ?: true );

		$filterContent = $kf['filter_content'] ?? '0';
		$filterContent = Bools::normalize( $filterContent ?: true );

		$kwAll  = isset( $kf['keywords'] ) ? array_filter( array_map( 'trim', explode( ',', $kf['keywords'] ) ) ) : array();
		$kwAny  = isset( $kf['keywords_any'] ) ? array_filter( array_map( 'trim', explode( ',', $kf['keywords_any'] ) ) ) : array();
		$kwNone = isset( $kf['keywords_not'] ) ? array_filter( array_map( 'trim', explode( ',', $kf['keywords_not'] ) ) ) : array();

		if ( $filterTitle && $filterContent ) {
			$subject = 'title_content';
		} elseif ( $filterTitle ) {
			$subject = 'title';
		} elseif ( $filterContent ) {
			$subject = 'content';
		} else {
			$subject = array();
			$enabled = false;
		}

		$exprs = array();
		if ( ! empty( $kwAny ) ) {
			$exprs[] = new Expression( $subject, 'strContainsAny', array( 'value' => $kwAny ) );
		}
		if ( ! empty( $kwAll ) ) {
			$exprs[] = new Expression( $subject, 'strContainsAll', array( 'value' => $kwAll ) );
		}
		if ( ! empty( $kwNone ) ) {
			$exprs[] = new Expression( $subject, 'strNotContainsAny', array( 'value' => $kwNone ) );
		}
		if ( count( $exprs ) > 0 ) {
			$automations[] = new Automation(
				'',
				$enabled,
				array( new Condition( true, $exprs ) ),
				'import'
			);
		}

		$tagsAny = isset( $kf['keywords_tags'] ) ? trim( $kf['keywords_tags'] ) : '';
		$tagsNone = isset( $kf['keywords_not_tags'] ) ? trim( $kf['keywords_not_tags'] ) : '';
		$tagExprs = array();
		if ( strlen( $tagsAny ) > 0 ) {
			$tagExprs[] = new Expression( 'tags', 'tagsContainAny', array( 'value' => $tagsAny ) );
		}
		if ( strlen( $tagsNone ) > 0 ) {
			$tagExprs[] = new Expression( 'tags', 'tagsContainNone', array( 'value' => $tagsNone ) );
		}
		if ( count( $tagExprs ) > 0 ) {
			$automations[] = new Automation(
				'',
				true,
				array( new Condition( true, $tagExprs ) ),
				'import'
			);
		}

		return $automations;
	}
}
