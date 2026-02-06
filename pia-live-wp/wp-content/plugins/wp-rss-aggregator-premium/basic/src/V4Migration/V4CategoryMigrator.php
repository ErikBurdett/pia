<?php

declare(strict_types=1);

namespace RebelCode\Aggregator\Basic\V4Migration;

use RebelCode\Aggregator\Core\Utils\Result;
use RebelCode\Aggregator\Core\Logger;
use RebelCode\Aggregator\Basic\FoldersStore;
use RebelCode\Aggregator\Basic\Folder;

class V4CategoryMigrator {

	private FoldersStore $folders;

	public function __construct( FoldersStore $folders ) {
		$this->folders = $folders;
	}

	/** @return Result<int> The number of created folders. */
	public function migrate( int $v4Id, int $v5Id ): Result {
		try {
			if ( ! taxonomy_exists( 'wprss_category' ) ) {
				register_taxonomy( 'wprss_category', array( 'wprss_feed', 'wprss_feed_item' ) );
			}

			$terms = wp_get_post_terms( $v4Id, 'wprss_category' );

			if ( is_wp_error( $terms ) ) {
				Logger::error( sprintf(
					"Error fetching terms for V4 ID %d (V5 ID: %d) in basic category migration: %s",
					$v4Id,
					$v5Id,
					$terms->get_error_message()
				) );
				return Result::Err( $terms );
			}

			if ( empty( $terms ) ) {
				// Logger::warning( "No categories found for V4 ID {$v4Id} (V5 ID: {$v5Id}) in basic." ); // Optional: change to debug or remove
				return Result::Ok( 0 );
			}

			if ( is_array( $terms ) ) {
				$folders = array();
				foreach ( $terms as $term ) {
					$folders[] = $term->name;
					$this->folders->update( $term->slug, new Folder( 0, $term->name, $term->slug, array( $v5Id ) ), true );
				}
			}

			// Logger::debug( "Category for V4 ID {$v4Id} and V5 ID {$v5Id} in basic has migrated." ); // Removed debug log
		} catch ( \Exception $e ) {
			Logger::error( sprintf(
				"Error migrating category for V4 ID %d (V5 ID: %d) in basic plan: %s",
				$v4Id,
				$v5Id,
				$e->getMessage()
			) );
			return Result::Err( $e );
		}

		return Result::Ok( 0 );
	}
}
