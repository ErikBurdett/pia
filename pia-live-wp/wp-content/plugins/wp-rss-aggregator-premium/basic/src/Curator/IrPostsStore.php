<?php

declare(strict_types=1);

namespace RebelCode\Aggregator\Basic\Curator;

use Throwable;
use RebelCode\Aggregator\Core\Utils\Time;
use RebelCode\Aggregator\Core\Utils\Result;
use RebelCode\Aggregator\Core\Utils\Arrays;
use RebelCode\Aggregator\Core\IrPost\IrTerm;
use RebelCode\Aggregator\Core\IrPost\IrImage;
use RebelCode\Aggregator\Core\IrPost\IrAuthor;
use RebelCode\Aggregator\Core\IrPost;
use RebelCode\Aggregator\Core\Exception\NotFoundException;
use RebelCode\Aggregator\Core\Database;
use Exception;

class IrPostsStore {

	public const ID = 'id';
	public const GUID = 'guid';
	public const SOURCES = 'sources';
	public const URL = 'url';
	public const TYPE = 'type';
	public const STATUS = 'status';
	public const FORMAT = 'format';
	public const SLUG = 'slug';
	public const TITLE = 'title';
	public const EXCERPT = 'excerpt';
	public const CONTENT = 'content';
	public const AUTHOR = 'author';
	public const PUBLISHED_DATE = 'published';
	public const MODIFIED_DATE = 'modified';
	public const IMPORTED_DATE = 'imported';
	public const IMAGES = 'images';
	public const FT_IMAGE = 'ft_image';
	public const TERMS = 'terms';
	public const COMMENTS = 'comments';
	public const META = 'meta';
	public const PARENT = 'parent';

	private Database $db;
	protected string $table;

	public function __construct( Database $db, string $table ) {
		$this->db = $db;
		$this->table = $table;
	}

	/*
	 * Inserts a new IR post.
	 *
	 * @param IrPost $post The IR post to insert.
	 * @return Result<IrPost>
	 */
	public function insert( IrPost $post ): Result {
		try {
			$post->id = null;
			$row = $this->irPostToRow( $post );
			$formats = $this->getColumnFormats();
			unset( $row['id'], $formats['id'] );

			$id = $this->db->insert( $this->table, $row, $formats );

			if ( is_int( $id ) ) {
				$newPost = clone $post;
				$newPost->id = $id;
				return Result::Ok( $newPost );
			} else {
				return Result::Err( 'Failed to get the inserted IR post ID.' );
			}
		} catch ( Throwable $t ) {
			return Result::Err( $t );
		}
	}

	/**
	 * Updates an IR post in its entirety.
	 *
	 * @param IrPost $post The IR post to update.
	 * @return Result<IrPost>
	 */
	public function replace( IrPost $post ): Result {
		if ( ! $post->id ) {
			return Result::Err( __( 'Cannot replace IR post with no ID', 'wp-rss-aggregator-premium' ) );
		}

		$data = $this->irPostToRow( $post );
		$formats = $this->getColumnFormats();

		try {
			$this->db->replace( $this->table, $data, $formats );

			return Result::Ok( $post );
		} catch ( Throwable $err ) {
			return Result::Err( $err );
		}
	}

	/**
	 * Saves an IR post. Inserts if the post has no ID, or replaces if it does.
	 *
	 * @param IrPost $post The IR post to save.
	 * @return Result<IrPost> The saved IR post.
	 */
	public function save( IrPost $post ): Result {
		if ( empty( $post->guid ) ) {
			return Result::Ok( $post );
		}

		try {
			$row = $this->db->getRow(
				"SELECT * FROM {$this->table} WHERE `guid` = %s LIMIT 1",
				array( $post->guid )
			);

			if ( ! is_array( $row ) || empty( $row ) ) {
				return $this->insert( $post );
			}

			$id = (int) ( $row['id'] ?? 0 );
			if ( ! $id ) {
				return Result::Err( 'Found existing IR post, but it has no ID.' );
			}

			$newPost = clone $post;
			$newPost->id = $id;
			return $this->replace( $newPost );
		} catch ( Throwable $t ) {
			return Result::Err( $t );
		}
	}

	/**
	 * Deletes IR posts from multiple sources in batches, using prepared statements.
	 *
	 * @param array $srcIds Array of source IDs to delete.
	 * @param int   $batchSize Number of IDs to process per batch (default is 1000).
	 * @return Result<int> Total number of deleted posts.
	 */
	public function deleteFromSources( array $srcIds, int $batchSize = 1000 ): Result {
		$totalDeleted = 0;

		try {
			// Split the source IDs into manageable chunks for batched deletion.
			foreach ( array_chunk( $srcIds, $batchSize ) as $batch ) {
				// Construct the placeholders for the LIKE statements.
				$placeholders = implode( ' OR ', array_fill( 0, count( $batch ), '`sources` LIKE %s' ) );

				// Prepare and execute the query with placeholders.
				$values = array_map(
					function ( $id ) {
						return "%|$id|%";
					},
					$batch
				);

				// Execute the query using $this->db->query and accumulate the total deleted count.
				$query = "DELETE FROM {$this->table} WHERE $placeholders";
				$deleted = $this->db->query( $query, $values );
				$totalDeleted += $deleted;
			}

			return Result::Ok( $totalDeleted );
		} catch ( Throwable $err ) {
			return Result::Err( $err );
		}
	}

	/**
	 * Deletes IR posts from a particular source.
	 *
	 * @param int $srcId The ID of the source.
	 * @return Result<int> The number of deleted posts.
	 */
	public function deleteFromSource( int $srcId ): Result {
		try {
			$num = $this->db->query(
				"DELETE FROM {$this->table}
                WHERE `sources` LIKE %s",
				array( "%|$srcId|%" )
			);
			return Result::Ok( $num );
		} catch ( Throwable $err ) {
			return Result::Err( $err );
		}
	}

	/**
	 * Gets a IR post by ID.
	 *
	 * @param int $id The IR post ID.
	 * @return Result<IrPost>
	 */
	public function getById( int $id ): Result {
		try {
			$row = $this->db->getRow(
				"SELECT * FROM {$this->table} WHERE `id` = %d",
				array( $id )
			);

			if ( ! is_array( $row ) ) {
				return Result::Err(
					new NotFoundException(
						sprintf( __( 'IR post with ID %d not found', 'wp-rss-aggregator-premium' ), $id )
					)
				);
			}

			return Result::Ok( $this->rowToIrPost( $row ) );
		} catch ( Throwable $err ) {
			return Result::Err( $err );
		}
	}

	/** @return Result<bool> */
	public function titleExists( string $title ): Result {
		try {
			$row = $this->db->getRow(
				"SELECT COUNT(`title`) AS `count`
                FROM {$this->table}
                WHERE `title` = %s",
				array( $title )
			);

			$row ??= array();
			$count = (int) ( $row['count'] ?? 0 );

			return Result::Ok( $count > 0 );
		} catch ( Throwable $t ) {
			return Result::Err( $t );
		}
	}

	/**
	 * Deletes all IR posts from the table.
	 *
	 * @return Result<true>
	 */
	public function deleteAll(): Result {
		try {
			$result = $this->db->query( "TRUNCATE TABLE `{$this->table}`" );
			if ( $result === false ) {
				return Result::Err( $this->db->wpdb->last_error );
			}
			return Result::Ok( true );
		} catch ( Throwable $err ) {
			return Result::Err( $err );
		}
	}

	/**
	 * Gets multiple IR posts by their IDs.
	 *
	 * @param list<int> $ids The IR post IDs.
	 * @return Result<iterable<IrPost>>
	 */
	public function getManyByIds( array $ids ): Result {
		if ( count( $ids ) === 0 ) {
			return Result::Ok( array() );
		}

		try {
			$args = array();
			$idList = $this->db->prepareList( $ids, '%d', $args );

			$results = $this->db->getResults(
				"SELECT * FROM {$this->table} WHERE `id` IN ({$idList})",
				$args
			);

			$irPosts = array_map( array( $this, 'rowToIrPost' ), $results );

			return Result::Ok( $irPosts );
		} catch ( Throwable $err ) {
			return Result::Err( $err );
		}
	}

	/**
	 * Gets multiple IR posts by their GUIDs.
	 *
	 * @param list<string> $guids The IR post GUIDs.
	 * @return Result<iterable<IrPost>>
	 */
	public function getManyByGuids( array $guids ): Result {
		if ( count( $guids ) === 0 ) {
			return Result::Ok( array() );
		}

		try {
			$args = array();
			$guidList = $this->db->prepareList( $guids, '%s', $args );

			$results = $this->db->getResults(
				"SELECT * FROM {$this->table} WHERE `id` IN ({$guidList})",
				$args
			);

			$irPosts = array_map( array( $this, 'rowToIrPost' ), $results );

			return Result::Ok( $irPosts );
		} catch ( Throwable $err ) {
			return Result::Err( $err );
		}
	}

	/**
	 * Gets the IR posts that were imported from a list of sources.
	 *
	 * @param list<int> $srcIds The source IDs.
	 * @param int|null  $limit The number of items to get.
	 * @param int       $page The page number.
	 * @return Result<iterable<IrPost>> The IR posts.
	 */
	public function getFromSources( array $srcIds, ?int $num = null, int $page = 1 ): Result {
		if ( empty( $srcIds ) ) {
			return Result::Ok( array() );
		}

		try {
			$args = array();
			$whereStr = $this->whereSourcesIn( $srcIds, $args );
			$pagination = $this->db->pagination( $num, $page );

			$results = $this->db->getResults(
				"SELECT * FROM {$this->table}
                WHERE {$whereStr}
                {$pagination}",
				$args
			);

			$irPosts = array_map( array( $this, 'rowToIrPost' ), $results );

			return Result::Ok( $irPosts );
		} catch ( Throwable $err ) {
			return Result::Err( $err );
		}
	}

	/**
	 * Gets a listing of IR posts.
	 *
	 * Similar to {@link IrPostStore::query()}, but accepts a filter string instead of a WHERE condition.
	 *
	 * @param string    $filter Optional search or filter string.
	 * @param list<int> $srcIds The IDs of the sources to get IR posts from.
	 * @param int|null  $num The number of items to get.
	 * @param int       $page The page number.
	 * @param string    $orderBy Optional column to sort by.
	 * @param string    $order Either "asc" or "desc"
	 * @return Result<iterable<IrPost>> The folders.
	 */
	public function getList(
		string $filter = '',
		array $srcIds = array(),
		?int $num = null,
		int $page = 1,
		string $orderBy = 'imported',
		string $order = 'desc'
	): Result {
		$args = array();
		$where = array();
		if ( $filter ) {
			$where[] = '(`title` LIKE %s OR `content` LIKE %s OR `excerpt` LIKE %s)';
			$filterArg = "%$filter%";
			array_push( $args, $filterArg, $filterArg, $filterArg );
		}
		if ( count( $srcIds ) > 0 ) {
			$where[] = $this->whereSourcesIn( $srcIds, $args );
		}

		$whereStr = empty( $where ) ? 'true' : join( ' AND ', $where );
		$pagination = $this->db->pagination( $num, $page );
		$order = $this->db->normalizeOrder( $order );
		$args[] = $orderBy;

		try {
			$results = $this->db->getResults(
				"SELECT * FROM {$this->table}
                WHERE {$whereStr}
                ORDER BY %i {$order}
                {$pagination}",
				$args,
			);

			$irPosts = array_map( array( $this, 'rowToIrPost' ), $results );

			return Result::Ok( $irPosts );
		} catch ( Throwable $err ) {
			return Result::Err( $err );
		}
	}

	/**
	 * Deletes a IR post by its ID.
	 *
	 * @param int $id The ID of the IR post to delete.
	 * @return Result<int> The number of deleted IR posts.
	 */
	public function deleteById( int $id ): Result {
		try {
			$num = $this->db->delete( $this->table, array( 'id' => $id ), array( '%d' ) );
			return Result::Ok( $num );
		} catch ( Throwable $err ) {
			return Result::Err( $err );
		}
	}

	/**
	 * Deletes multiple IR posts by their IDs.
	 *
	 * @param list<int> $ids The IDs of the IR posts to delete.
	 * @return Result<int> The number of deleted IR posts.
	 */
	public function deleteManyByIds( array $ids ): Result {
		if ( count( $ids ) === 0 ) {
			return Result::Ok( array() );
		}

		try {
			$args = array();
			$idList = $this->db->prepareList( $ids, '%d', $args );

			$num = $this->db->query(
				"DELETE FROM {$this->table}
                WHERE `id` IN ({$idList})",
				$args
			);

			return Result::Ok( $num );
		} catch ( Throwable $err ) {
			return Result::Err( $err );
		}
	}

	/** @return Result<int> */
	public function getCount( array $srcIds = array() ): Result {
		$where = array();
		$args = array();

		if ( count( $srcIds ) > 0 ) {
			$where[] = $this->whereSourcesIn( $srcIds, $args );
		}

		$whereStr = empty( $where ) ? 'true' : join( ' AND ', $where );

		try {
			$row = $this->db->getRow(
				"SELECT COUNT(*) AS `count`
				FROM {$this->table}
				WHERE {$whereStr}",
				$args,
			);
			$row ??= array();

			$count = (int) ( $row['count'] ?? 0 );
			return Result::Ok( $count );
		} catch ( Throwable $t ) {
			return Result::Err( $t );
		}
	}

	/**
	 * Creates a table row array for an IR post.
	 *
	 * @param IrPost $irPost The IR post to create a table row for.
	 * @return array<string,mixed> The table row array.
	 */
	public function irPostToRow( IrPost $irPost ): array {
		return array(
			self::ID => $irPost->id,
			self::GUID => $irPost->guid,
			self::URL => $irPost->url,
			self::SOURCES => '|' . implode( '|', $irPost->sources ) . '|',
			self::TYPE => $irPost->type,
			self::STATUS => $irPost->status,
			self::FORMAT => $irPost->format,
			self::SLUG => $irPost->slug,
			self::TITLE => $irPost->title,
			self::CONTENT => $irPost->content,
			self::EXCERPT => $irPost->excerpt,
			self::AUTHOR => $irPost->author ? json_encode( $irPost->author ) : null,
			self::PUBLISHED_DATE => $irPost->datePublished ? $irPost->datePublished->format( DATE_ATOM ) : null,
			self::MODIFIED_DATE => $irPost->dateModified ? $irPost->dateModified->format( DATE_ATOM ) : null,
			self::IMPORTED_DATE => ( new \DateTime() )->format( DATE_ATOM ),
			self::IMAGES => json_encode( $irPost->images ),
			self::FT_IMAGE => $irPost->ftImage ? json_encode( $irPost->ftImage ) : null,
			self::TERMS => json_encode( $irPost->terms ),
			self::META => json_encode( $irPost->meta ),
			self::COMMENTS => $irPost->commentsOpen ? 'open' : 'closed',
			self::PARENT => $irPost->parentId,
		);
	}

	/**
	 * Converts a database row to an IR post.
	 *
	 * @param array<string,mixed> $row The database row.
	 * @return IrPost The IR post.
	 * @throws Exception
	 */
	public function rowToIrPost( array $row ): IrPost {
		$id = (int) ( $row[ self::ID ] ?? 0 );
		$id = $id > 0 ? $id : null;

		$guid = $row[ self::GUID ] ?? '';
		$url = $row[ self::URL ] ?? '';
		$sources = array_map( 'intval', explode( '|', substr( $row[ self::SOURCES ] ?? '||', 1, -1 ) ) );

		$irPost = new IrPost( $guid, null, $sources, $url );

		$irPost->id = $id;
		$irPost->type = $row[ self::TYPE ] ?? '';
		$irPost->status = $row[ self::STATUS ] ?? '';
		$irPost->format = $row[ self::FORMAT ] ?? '';
		$irPost->slug = $row[ self::SLUG ] ?? '';
		$irPost->title = $row[ self::TITLE ] ?? '';
		$irPost->content = $row[ self::CONTENT ] ?? '';
		$irPost->excerpt = $row[ self::EXCERPT ] ?? '';
		$irPost->commentsOpen = strtolower( $row[ self::COMMENTS ] ?? 'closed' ) === 'open';
		$irPost->parentId = (int) ( $row[ self::PARENT ] ?? 0 );

		// Decode author JSON
		$authorJson = $row[ self::AUTHOR ] ?? '{}';
		$authorData = json_decode( $authorJson, true );
		$irPost->author = is_array( $authorData ) ? IrAuthor::fromArray( $authorData ) : null;

		// Decode published date string
		$datePublishedStr = $row[ self::PUBLISHED_DATE ] ?? null;
		$irPost->datePublished = $datePublishedStr ? Time::createAndCatch( $datePublishedStr ) : null;

		// Decode modified date string
		$dateModifiedStr = $row[ self::MODIFIED_DATE ] ?? null;
		$irPost->dateModified = $dateModifiedStr ? Time::createAndCatch( $dateModifiedStr ) : null;

		// Decode images JSON
		$imagesJson = $row[ self::IMAGES ] ?? '[]';
		$imagesArray = json_decode( $imagesJson, true );
		$irPost->images = Arrays::map( $imagesArray, array( IrImage::class, 'fromArray' ) );

		// Decode featured image JSON
		$ftImagesJson = $row[ self::FT_IMAGE ] ?? 'null';
		$ftImageData = json_decode( $ftImagesJson, true );
		$irPost->ftImage = is_array( $ftImageData ) ? IrImage::fromArray( $ftImageData ) : null;

		// Decode terms JSON
		$termsJson = $row[ self::TERMS ] ?? '[]';
		$termsArray = json_decode( $termsJson, true );
		$irPost->terms = Arrays::map(
			$termsArray,
			fn ( array $terms ) => Arrays::map( $terms, array( IrTerm::class, 'fromArray' ) )
		);

		// Decode meta JSON
		$metaJson = $row[ self::META ] ?? '[]';
		$irPost->meta = json_decode( $metaJson, true );

		return $irPost;
	}

	/** @param list<int> $srcIds */
	private function whereSourcesIn( array $srcIds, array &$args = array() ): string {
		$args = array();
		$where = array();

		foreach ( $srcIds as $srcId ) {
			$args[] = "%|$srcId|%";
			$where[] = '`sources` LIKE %s';
		}

		return join( ' OR ', $where );
	}

	public function getColumnFormats(): array {
		return array(
			'id' => '%d',
			'guid' => '%s',
			'sources' => '%s',
			'url' => '%s',
			'type' => '%s',
			'status' => '%s',
			'format' => '%s',
			'slug' => '%s',
			'title' => '%s',
			'excerpt' => '%s',
			'content' => '%s',
			'author' => '%s',
			'published_date' => '%s',
			'modified_date' => '%s',
			'imported_date' => '%s',
			'images' => '%s',
			'ft_image' => '%s',
			'terms' => '%s',
			'comments' => '%s',
			'meta' => '%s',
			'parent' => '%d',
		);
	}

	public function createTable(): void {
		if ( $this->db->tableExists( $this->table ) ) {
			return;
		}

		$this->db->delta(
			"CREATE TABLE {$this->table} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                guid TEXT DEFAULT '' NOT NULL,
                sources VARCHAR(1000) DEFAULT '' NOT NULL,
                url TEXT DEFAULT '' NOT NULL,
                type VARCHAR(50) DEFAULT 'wprss_feed_item',
                status VARCHAR(20) DEFAULT 'publish',
                format VARCHAR(20) DEFAULT 'standard',
                slug VARCHAR(200) DEFAULT '',
                title TEXT DEFAULT '',
                excerpt TEXT DEFAULT '',
                content TEXT DEFAULT '',
                author TEXT DEFAULT '',
                published DATETIME DEFAULT '0000-00-00 00:00:00',
                modified DATETIME DEFAULT '0000-00-00 00:00:00',
                imported DATETIME DEFAULT '0000-00-00 00:00:00',
                images TEXT DEFAULT '',
                ft_image TEXT DEFAULT '',
                terms TEXT DEFAULT '',
                comments VARCHAR(20) DEFAULT 'open',
                meta TEXT DEFAULT '',
                parent BIGINT(20) UNSIGNED DEFAULT 0,
                PRIMARY KEY  (id)
            ) {$this->db->charsetCollate};"
		);
	}
}
