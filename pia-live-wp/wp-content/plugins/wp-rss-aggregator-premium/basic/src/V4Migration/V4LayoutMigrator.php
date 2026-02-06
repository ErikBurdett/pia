<?php

declare(strict_types=1);

namespace RebelCode\Aggregator\Basic\V4Migration;

use RebelCode\Aggregator\Core\Logger;
use RebelCode\Aggregator\Core\Display;

class V4LayoutMigrator {

	public function migrate( Display $display, array $meta ): Display {
		try {
			$meta = $this->normalizeMeta( $meta );
			$options = $meta['wprss_template_options'];

			$display = $this->migrateEt( $display, $options );
			$display = $this->migrateGrid( $display, $options );

			// Logger::debug( "Settings { $display->name } of basic has migrated." ); // Removed debug log
		} catch ( \Exception $e ) {
			Logger::error(
				sprintf(
					'Error migrating layout for display ID %s (Name: %s) in basic plan: %s',
					$display->id,
					$display->name,
					$e->getMessage()
				)
			);
			// Optionally, return $display without changes or handle more gracefully
		}
		return $display;
	}

	public function migrateEt( Display $display, array $options ): Display {
		$display->settings->excerptMaxWords = (int) ( $options['excerpt_max_words'] ?? 0 );
		$display->settings->enableImages = (bool) ( $options['show_image'] ?? true );
		$display->settings->enableTitles = (bool) ( $options['show_title'] ?? true );
		$display->settings->enableExcerpts = (bool) ( $options['show_excerpt'] ?? true );
		$display->settings->excerptEllipsis = isset( $options['excerpt_ending'] ) ? $options['excerpt_ending'] : '...';
		$display->settings->enableReadMore = (bool) ( $options['excerpt_more_enabled'] ?? true );
		$display->settings->readMoreText = $options['excerpt_read_more'] ?? __( 'Read more', 'wp-rss-aggregator-premium' );
		$display->settings->imageWidth = (int) ( $options['thumbnail_width'] ?? 175 );
		$display->settings->imageHeight = (int) ( $options['thumbnail_height'] ?? 175 );
		$display->settings->linkImages = (bool) ( $options['thumbnail_is_link'] ?? false );
		$display->settings->fallbackToSrcImage = ( $options['empty_thumbnail_behavior'] ?? 'true' ) === 'true';

		$thumPlacement = $options['thumbnail_placement'] ?? 'excerpt-side';
		switch ( $thumPlacement ) {
			default:
			case 'excerpt-side':
				$etStyle = 'news';
				break;
			case 'excerpt-text':
				$etStyle = 'wrapped';
				break;
			case 'item-side':
				$etStyle = 'magazine';
				break;
			case 'item-top':
				$etStyle = 'blog';
				break;
		}

		$display->settings->etStyle = $etStyle;

		return $display;
	}

	public function migrateGrid( Display $display, array $options ): Display {
		$display->settings->gridMaxColumns = (int) ( $options['columns_number'] ?? 2 );
		$display->settings->gridEnableBorders = (bool) ( $options['show_borders'] ?? true );
		$display->settings->gridUseImageAsBg = (bool) ( $options['image_is_background'] ?? false );
		$display->settings->gridItemClickable = $display->settings->gridUseImageAsBg ? false : (bool) ( $options['item_is_link'] ?? false );
		$display->settings->gridFitImages = ! ( (bool) ( $options['fill_image'] ?? false ) );
		$display->settings->gridEnableEmbeds = (bool) ( $options['videos_enabled'] ?? false );
		$display->settings->gridInfoBlocks = (bool) ( $options['info_item_block'] ?? false );
		$display->settings->gridAlignLastToBottom = (bool) ( $options['latest_to_bottom'] ?? true );
		$display->settings->gridStackInfoItems = (bool) ( $options['info_item_block'] ?? true );
		$display->settings->gridComponents = array();
		$display->settings->gridInfoComponents = array();
		$display->settings->enableSources = ! empty( $options['show_information'] ) && ( $options['source_enabled'] ?? true );
		$display->settings->enableDates = ! empty( $options['show_information'] ) && ( $options['date_enabled'] ?? true );
		$display->settings->enableAuthors = ! empty( $options['show_information'] ) && ( $options['author_enabled'] ?? false );
		$display->settings->linkImages = $display->settings->gridUseImageAsBg ? false : (bool) ( $options['thumbnail_is_link'] ?? false );

		$cardFields = (array) ( $options['card_fields_order'] ?? array(
			'image' => 0,
			'title' => 1,
			'excerpt' => 2,
			'audio' => 3,
			'information' => 4,
		) );
		asort( $cardFields );
		foreach ( $cardFields as $field => $_ ) {
			switch ( $field ) {
				case 'audio':
					$enabled = $display->settings->enableAudioPlayer;
					break;
				case 'information':
					$field = 'info';
					$enabled = (bool) ( $options['show_information'] ?? false );
					break;
				default:
					$enabled = (bool) ( $options[ 'show_' . $field ] ?? false );
					break;
			}

			$display->settings->gridComponents[] = array(
				'enabled' => $enabled,
				'type' => $field,
			);
		}

		$infoFields = (array) ( $options['information_fields_order'] ?? array(
			'date' => 0,
			'source' => 1,
			'author' => 2,
		) );
		asort( $infoFields );
		foreach ( $infoFields as $field => $_ ) {
			$display->settings->gridInfoComponents[] = array(
				'enabled' => (bool) ( $options[ "{$field}_enabled" ] ?? false ),
				'type' => $field,
			);
		}

		return $display;
	}

	private function normalizeMeta( array $meta ): array {

		$meta['wprss_template_options'] = array_merge(
			array(
				'columns_number' => 2,
				'show_borders' => true,
				'card_fields_order' => array(
					'image' => 0,
					'title' => 1,
					'excerpt' => 2,
					'audio' => 3,
					'information' => 4,
				),
				'information_fields_order' => array(
					'date' => 0,
					'source' => 1,
					'author' => 2,
				),
				'item_is_link' => false,
				'excerpt_max_words' => 0,
				'excerpt_ending' => '...',
				'show_image' => true,
				'thumbnail_is_link' => false,
				'show_title' => true,
				'show_excerpt' => true,
				'excerpt_more_enabled' => true,
				'excerpt_read_more' => __( 'read more', 'wp-rss-aggregator-premium' ),
				'show_information' => true,
				'info_item_block' => false,
				'image_is_background' => false,
				'latest_to_bottom' => true,
				'videos_enabled' => false,
				'fill_image' => false,
				'thumbnail_placement' => 'excerpt-side',
				'thumbnail_width' => 175,
				'thumbnail_height' => 150,
				'empty_thumbnail_behavior' => 'true',
			),
			$meta['wprss_template_options'] ?? array(),
		);

		return $meta;
	}
}
