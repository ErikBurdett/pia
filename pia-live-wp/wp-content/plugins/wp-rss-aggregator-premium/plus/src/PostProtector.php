<?php

declare(strict_types=1);

namespace RebelCode\Aggregator\Plus;

use RebelCode\Aggregator\Core\ImportedPost;
use RebelCode\Aggregator\Core\Store\SourcesStore;
use WP_Post;

class PostProtector {

	public const META_KEY = '_wpra_protected';

	private SourcesStore $sources;
	private bool $isSaving = false;

	public function __construct( SourcesStore $sources ) {
		$this->sources = $sources;
	}

	/**
	 * Protects a single post.
	 *
	 * @param WP_Post|int|string $post Post object or ID.
	 * @return bool True on success, false on failure or if the post was
	 *         already protected.
	 */
	public function protect( $post ): bool {
		if ( is_numeric( $post ) ) {
			$id = (int) $post;
		} elseif ( $post instanceof WP_Post ) {
			$id = $post->ID;
		} else {
			return false;
		}

		$ret = update_post_meta( $id, self::META_KEY, '1' );

		return $ret !== false;
	}

	public function onPostSaved( WP_Post $post ): bool {
		if ( $this->isSaving ) {
			return false; // Prevents infinite loop
		}

		$srcId = get_post_meta( $post->ID, ImportedPost::SOURCE, true );
		if ( empty( $srcId ) || ! is_numeric( $srcId ) ) {
			return false;
		}

		$src = $this->sources->getById( (int) $srcId )->getOr( null );
		$postType = $src ? $src->settings->postType : '';

		if ( $post->post_type !== $postType || $post->post_status === 'auto-draft' ) {
			return false;
		}

		try {
			$this->isSaving = true;
			$this->protect( $post );
		} finally {
			$this->isSaving = false;
		}

		return true;
	}

	public function filterQuery( array $queryArgs ): array {
		$queryArgs['meta_query'][] = array(
			'key' => self::META_KEY,
			'compare' => 'NOT EXISTS',
		);

		return $queryArgs;
	}
}
