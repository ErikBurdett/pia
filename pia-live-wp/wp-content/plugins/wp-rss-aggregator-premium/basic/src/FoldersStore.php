<?php

declare(strict_types=1);

namespace RebelCode\Aggregator\Basic;

use Throwable;
use RebelCode\Aggregator\Core\Utils\Result;
use RebelCode\Aggregator\Core\Utils\Arrays;
use RebelCode\Aggregator\Core\Database;

class FoldersStore {

	public const NAME = 'name';
	public const SOURCE_ID = 'source_id';

	private Database $db;
	private string $table;
	private string $folderSourcesTable;
	private string $sourcesTable;

	public function __construct( Database $db, string $table, $folder_sources_table ) {
		$this->db = $db;
		$this->table = $table;
		$this->folderSourcesTable = $folder_sources_table;
		$this->sourcesTable = $this->db->tableName( 'sources' );
	}

	/**
	 * Gets the names of a specific list of folders, by their IDs.
	 *
	 * @param list<int> $ids The IDs of the folders.
	 * @return Result<array<int,string>> A mapping of IDs to names.
	 */
	public function getNames( array $ids, ?int $blogId = null ): Result {
		if ( empty( $ids ) ) {
			return Result::Ok( array() );
		}

		if ( $blogId !== null ) {
			switch_to_blog( $blogId );
		}

		try {
			$table = $this->db->tableName( 'folders' );
			$args = array();
			$idList = $this->db->prepareList( $ids, '%d', $args );

			// Fetch folders by ID
			$results = $this->db->getResults(
				"SELECT id, name FROM {$table} WHERE id IN ({$idList})",
				$args
			);

			$names = array();
			foreach ( $results as $row ) {
				$names[ $row['id'] ] = $row['name'] ?? '';
			}

			// Handle missing folders
			foreach ( $ids as $id ) {
				if ( ! isset( $names[ $id ] ) ) {
					$names[ $id ] = sprintf( __( 'Folder #%d [Missing]', 'wp-rss-aggregator-premium' ), $id );
				}
			}

			return Result::Ok( $names );
		} catch ( Throwable $err ) {
			return Result::Err( $err );
		} finally {
			if ( $blogId !== null ) {
				restore_current_blog();
			}
		}
	}

	/**
	 * @param string    $search Optional string to search by name.
	 * @param list<int> $srcIds Optional list of source IDs to limit the results to.
	 * @param int|null  $num Optional number of results per page.
	 * @param int       $page The page number.
	 * @param string    $order Either 'ASC' or 'DESC'. Results are sorted by name.
	 * @return Result<iterable<Folder>>
	 */
	public function getList( string $search = '', array $srcIds = array(), ?int $num = null, int $page = 1, string $order = 'ASC' ): Result {
		$search = trim( $search );
		$order = $this->db->normalizeOrder( $order );
		$pagination = $this->db->pagination( $num, $page );

		$where = array();
		$args = array();

		if ( strlen( $search ) > 0 ) {
			$where[] = 'f.`name` LIKE %s';
			$args[] = "%$search%";
		}

		if ( count( $srcIds ) > 0 ) {
			$srcIdList = $this->db->prepareList( $srcIds, '%d', $args );
			$where[] = "fs.`source_id` IN ({$srcIdList})";
		}

		$whereStr = empty( $where ) ? '1=1' : implode( ' AND ', $where );

		try {
			$rows = $this->db->getResults(
				"SELECT f.`id`, f.`name`, f.`slug`, GROUP_CONCAT(fs.`source_id`) AS `source_ids`
				 FROM {$this->table} AS f
				 LEFT JOIN {$this->folderSourcesTable} AS fs ON f.`id` = fs.`folder_id`
				 WHERE {$whereStr}
				 GROUP BY f.`id`, f.`name`
				 ORDER BY f.`name` {$order}
				 {$pagination}",
				$args
			);

			$folders = Arrays::gmap(
				$rows,
				function ( array $row ) {
					$name = $row['name'] ?? '';
					$slug = $row['slug'] ?? '';
					$sSourceIds = $row['source_ids'] ?? '';
					$iSourceIds = array_map( 'intval', explode( ',', $sSourceIds ) );
					return new Folder( (int) $row['id'], $name, $slug, $iSourceIds );
				}
			);

			return Result::Ok( $folders );
		} catch ( Throwable $err ) {
			return Result::Err( $err );
		}
	}

	/** @return Result<int> */
	public function getCount(): Result {
		try {
			$row = $this->db->getRow(
				"SELECT COUNT(DISTINCT `name`) AS `count`
                FROM {$this->table}"
			);

			$row ??= array();
			$count = (int) ( $row['count'] ?? 0 );

			return Result::Ok( $count );
		} catch ( Throwable $t ) {
			return Result::Err( $t );
		}
	}

	/** @return Result<int> */
	public function insert( Folder $folder ): Result {
		try {
			$slug = sanitize_title( $folder->name );
			// Insert into `folders` table
			$this->db->insert(
				$this->db->tableName( 'folders' ),
				array(
					'name' => $folder->name,
					'slug' => $slug,
				),
				array( '%s', '%s' )
			);

			// Get the inserted `folder_id`
			$folderId = (int) $this->db->getRow(
				"SELECT id FROM {$this->db->tableName('folders')} WHERE name = %s",
				array( $folder->name )
			)['id'];

			// Insert into `folder_sources` table
			if ( ! empty( $folder->sourceIds ) ) {
				$values = array();
				$args = array();
				foreach ( $folder->sourceIds as $sourceId ) {
					$values[] = '(%d, %d)';
					$args[] = $folderId;
					$args[] = $sourceId;
				}
				$valuesStr = implode( ', ', $values );

				$this->db->query(
					"INSERT INTO {$this->db->tableName('folder_sources')} (folder_id, source_id) VALUES {$valuesStr}",
					$args
				);
			}

			return Result::Ok( $folderId );
		} catch ( Throwable $err ) {
			if ( str_contains( $err->getMessage(), 'Duplicate entry' ) ) {
				return Result::Err( esc_html__( 'A folder with this name already exists. Choose another name.', 'wp-rss-aggregator-premium' ) );
			}

			return Result::Err( $err );
		}
	}

	public function rename( string $prevName, string $newName ): Result {
		if ( $prevName === $newName ) {
			return Result::Ok( 0 );
		}

		try {
			$data = array( 'name' => $newName );
			$where = array( 'name' => $prevName );
			$format = array( 'name' => '%s' );

			$num = $this->db->update( $this->table, $data, $where, $format, $format );

			return Result::Ok( $num );
		} catch ( Throwable $t ) {
			return Result::Err( $t );
		}
	}

	/** @param list<string> $names The names of the folders to delete. */
	public function delete( array $ids ): Result {
		if ( count( $ids ) === 0 ) {
			return Result::Ok( 0 );
		}

		try {
			$args = array();
			$idList = $this->db->prepareList( $ids, '%s', $args );

			$num = $this->db->query(
				"DELETE FROM {$this->table} WHERE `id` IN ({$idList})",
				$args
			);

			return Result::Ok( $num );
		} catch ( Throwable $t ) {
			return Result::Err( $t );
		}
	}

	/**
	 * @param list<string> $namesOrIds The list of folder names or ids to query.
	 * @return Result<list<int>> All the source IDs from all the given folders.
	 */
	public function getSources( array $namesOrIds ): Result {
		if ( empty( $namesOrIds ) ) {
			return Result::Ok( array() );
		}

		try {
			$ids   = array_filter( $namesOrIds, 'is_numeric' );
			$names = array_filter( $namesOrIds, static fn( $v ) => ! is_numeric( $v ) );

			$folderIds = array();

			if ( ! empty( $names ) ) {
				$args = array();
				$nameList = $this->db->prepareList( $names, '%s', $args );

				$folderRows = $this->db->getResults(
					"SELECT `id` FROM {$this->db->tableName('folders')}
					 WHERE `name` IN ({$nameList})",
					$args
				);

				if ( $folderRows ) {
					$folderIds = array_map( static fn( $row ) => (int) $row['id'], $folderRows );
				}
			}

			if ( ! empty( $ids ) ) {
				$folderIds = array_merge( $folderIds, array_map( 'intval', $ids ) );
			}

			if ( empty( $folderIds ) ) {
				return Result::Ok( array() );
			}

			$args = array();
			$idList = $this->db->prepareList( $folderIds, '%d', $args );

			$sourceRows = $this->db->getResults(
				"SELECT `source_id` FROM {$this->db->tableName('folder_sources')}
				 WHERE `folder_id` IN ({$idList})",
				$args
			);

			$sourceIds = array_map( static fn( $row ) => (int) $row['source_id'], $sourceRows );

			return Result::Ok( $sourceIds );
		} catch ( Throwable $t ) {
			return Result::Err( $t );
		}
	}

	/**
	 * Updates a folder by syncing its name and sources.
	 *
	 * @param ?string $prevName The previous folder name (optional).
	 * @param Folder  $newFolder The new folder data.
	 * @return Result<int|Folder> The number of updated rows or new folder object.
	 */
	public function update( ?string $prevName, Folder $newFolder, $migration = false ): Result {
		$table = $this->db->tableName( 'folders' );
		$slug = sanitize_title( $newFolder->name ); // Generate new slug

		try {
			$existingFolder = null;
			if ( $prevName ) {
				$oldSlug = sanitize_title( $prevName );
				$existingFolder = $this->db->getRow(
					"SELECT id FROM {$table} WHERE slug = %s",
					array( $oldSlug )
				);
			}

			if ( $existingFolder ) {
				// Folder exists -> Update name and slug
				$folderId = (int) $existingFolder['id'];
				$data = array(
					'name' => $newFolder->name,
					'slug' => $slug,
				);
				$where = array( 'id' => $folderId );

				$num = $this->db->update( $table, $data, $where, array( '%s', '%s' ), array( '%d' ) );

				// Sync sources
				$currentSources = $this->getSourcesByFolderId( $folderId );
				$toRemove = array_diff( $currentSources, $newFolder->sourceIds );
				$toAdd = array_diff( $newFolder->sourceIds, $currentSources );

				if ( ! $migration && ! empty( $toRemove ) ) {
					$this->removeSources( $folderId, $toRemove );
				}
				if ( ! empty( $toAdd ) ) {
					$this->addSources( $folderId, $toAdd );
				}

				return Result::Ok( $num );
			} else {
				// Folder doesn't exist -> Insert new one
				return $this->insert( $newFolder );
			}
		} catch ( Throwable $t ) {
			return Result::Err( $t );
		}
	}

	/**
	 * Adds sources to a folder after removing existing ones from `$this->folderSourcesTable`.
	 *
	 * @param int       $folderId The folder ID.
	 * @param list<int> $sourceIds A list of source IDs to add.
	 * @return Result<int> Number of added sources.
	 */
	public function addSources( int $folderId, array $sourceIds ): Result {
		if ( empty( $sourceIds ) ) {
			return Result::Ok( 0 );
		}

		try {
			$args = array();
			$values = array();
			foreach ( $sourceIds as $id ) {
				array_push( $args, $folderId, $id );
				$values[] = '(%d, %d)';
			}
			$valuesStr = implode( ', ', $values );

			$num = $this->db->query(
				"INSERT INTO {$this->folderSourcesTable} (`folder_id`, `source_id`)
				VALUES {$valuesStr}",
				$args
			);

			return Result::Ok( $num );
		} catch ( Throwable $t ) {
			return Result::Err( $t );
		}
	}

	/**
	 * Removes specific sources from a folder.
	 *
	 * @param int       $folderId The folder ID.
	 * @param list<int> $sourceIds A list of source IDs to remove.
	 * @return Result<int> Number of removed sources.
	 */
	private function removeSources( int $folderId, array $sourceIds ): Result {
		if ( empty( $sourceIds ) ) {
			return Result::Ok( 0 );
		}

		try {
			$args = array( $folderId );
			$srcIdList = $this->db->prepareList( $sourceIds, '%d', $args );

			$num = $this->db->query(
				"DELETE FROM {$this->folderSourcesTable}
				WHERE `folder_id` = %d AND `source_id` IN ({$srcIdList})",
				$args
			);

			return Result::Ok( $num );
		} catch ( Throwable $t ) {
			return Result::Err( $t );
		}
	}

	/**
	 * Retrieves source IDs for a given folder ID.
	 *
	 * @param int $folderId The folder ID.
	 * @return list<int> List of source IDs.
	 */
	private function getSourcesByFolderId( int $folderId ): array {
		return $this->db->getCol(
			"SELECT source_id FROM {$this->folderSourcesTable} WHERE folder_id = %d",
			array( $folderId )
		) ?: array();
	}

	/**
	 * @param int          $sourceId The ID of the source to add folders to.
	 * @param list<string> $names The names of the folders to add.
	 * @return Result<int> The number of added folders.
	 */
	private function addToSource( int $sourceId, array $ids ): Result {
		if ( count( $ids ) === 0 ) {
			return Result::Ok( 0 );
		}

		try {
			$args = array();
			$values = array();
			foreach ( $ids as $id ) {
				array_push( $args, $id, $sourceId );
				$values[] = '(%s, %d)';
			}
			$valuesStr = implode( ', ', $values );

			$num = $this->db->query(
				"INSERT INTO {$this->folderSourcesTable} (folder_id, source_id) VALUES {$valuesStr}",
				$args
			);

			return Result::Ok( $num );
		} catch ( Throwable $t ) {
			return Result::Err( $t );
		}
	}

	/**
	 * @param int          $sourceId The ID of the source to remove folders from.
	 * @param list<string> $names The names of the folders to remove.
	 * @return Result<int> The number of removed folders.
	 */
	private function removeFromSource( int $sourceId, array $ids ): Result {
		if ( empty( $ids ) ) {
			return Result::Ok( 0 );
		}

		try {
			$args = array( $sourceId );
			$idList = $this->db->prepareList( $ids, '%s', $args );

			$num = $this->db->query(
				"DELETE FROM {$this->folderSourcesTable}
                WHERE `source_id` = %d AND `folder_id` IN ({$idList})",
				$args,
			);

			return Result::Ok( $num );
		} catch ( Throwable $t ) {
			return Result::Err( $t );
		}
	}

	/**
	 * Sets the folders for a source.
	 *
	 * @param int          $sourceId The ID of the source to set the folders to.
	 * @param list<string> $names The names of the folders for the source.
	 * @return Result<array{0:int,1:int}> The number of added folders and the
	 *         number of removed folders, in a tuple.
	 */
	public function setForSource( int $sourceId, array $ids ): Result {
		try {
			$folders = $this->getList( '', array( $sourceId ) )->get();
			$prevNames = Arrays::map( $folders, fn ( Folder $f ) => $f->id );
			$toRemove = array_diff( $prevNames, $ids );

			$numRemoved = $this->removeFromSource( $sourceId, $toRemove )->get();
			$numAdded = $this->addToSource( $sourceId, $ids )->get();

			return Result::Ok( array( $numAdded, $numRemoved ) );
		} catch ( Throwable $t ) {
			return Result::Err( $t );
		}
	}

	/** @return Result<Folder> */
	private function getByName( string $name ): Result {
		try {
			// Get folder details from `folders` table
			$folderRow = $this->db->getRow(
				"SELECT `id`, `name`, `slug` FROM {$this->db->tableName('folders')} WHERE `name` = %s",
				array( $name )
			);

			if ( ! $folderRow ) {
				return Result::Err( new \RuntimeException( "Folder '{$name}' not found" ) );
			}

			$folderId = (int) $folderRow['id'];

			// Get associated source IDs from `folder_sources` table
			$sourceRows = $this->db->getResults(
				"SELECT `source_id` FROM {$this->db->tableName('folder_sources')} WHERE `folder_id` = %d",
				array( $folderId )
			);

			$sourceIds = array_map( fn( $row ) => (int) $row['source_id'], $sourceRows );

			// Now correctly instantiate Folder
			return Result::Ok( new Folder( $folderId, $folderRow['name'], $folderRow['slug'], $sourceIds ) );
		} catch ( Throwable $t ) {
			return Result::Err( $t );
		}
	}

	public function createTable(): void {

		if ( ! $this->db->tableExists( $this->table ) ) {
			$this->db->query(
				"CREATE TABLE IF NOT EXISTS {$this->table} (
					id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
					name VARCHAR(180) NOT NULL,
					slug VARCHAR(180) NOT NULL,
					PRIMARY KEY (id),
					UNIQUE KEY unique_folder_name (name),
					UNIQUE KEY unique_folder_slug (slug)
				) {$this->db->charsetCollate};"
			);
		}

		if ( ! $this->db->tableExists( $this->folderSourcesTable ) ) {
			$this->db->query(
				"CREATE TABLE IF NOT EXISTS {$this->folderSourcesTable} (
					folder_id BIGINT UNSIGNED NOT NULL,
					source_id BIGINT UNSIGNED NOT NULL,
					PRIMARY KEY (folder_id, source_id),
					FOREIGN KEY (folder_id) REFERENCES {$this->table}(id) ON DELETE CASCADE,
					FOREIGN KEY (source_id) REFERENCES {$this->sourcesTable}(id) ON DELETE CASCADE
				) {$this->db->charsetCollate};"
			);
		}
	}
}
