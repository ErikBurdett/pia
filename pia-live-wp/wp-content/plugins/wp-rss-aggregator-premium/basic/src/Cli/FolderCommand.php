<?php

declare(strict_types=1);

namespace RebelCode\Aggregator\Basic\Cli;

use RebelCode\Aggregator\Core\Cli\BaseCommand;
use RebelCode\Aggregator\Core\Cli\CliIo;
use RebelCode\Aggregator\Core\Cli\CliTable;
use RebelCode\Aggregator\Core\Folder;
use RebelCode\Aggregator\Core\Source;
use RebelCode\Aggregator\Basic\FoldersStore;
use RebelCode\Aggregator\Core\Store\SourcesStore;
use RebelCode\Aggregator\Core\Utils\Arrays;
use RebelCode\Aggregator\Core\Utils\Result;
use WP_CLI;

class FolderCommand extends BaseCommand {

	protected FoldersStore $folders;
	protected SourcesStore $sources;

	public function __construct( CliIo $io, FoldersStore $folders, SourcesStore $sources ) {
		parent::__construct( $io );
		$this->folders = $folders;
		$this->sources = $sources;
	}

	/**
	 * Add sources to a folder.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : The name of the folder.
	 *
	 * [<source_id>...]
	 * : The ID of the source to add.
	 *
	 * ## EXAMPLES
	 *
	 *     wp rss folder add "My folder" 41 58 170
	 *
	 * @param list<string>         $args
	 * @param array<string,string> $opts
	 */
	public function add( array $args, array $opts ): void {
		$name = $args[0];
		$rest = array_slice( $args, 1 );
		$srcIds = $this->parseIntArgArray( $rest );

		if ( count( $srcIds ) === 0 ) {
			WP_CLI::error( __( 'No folders to add.', 'wp-rss-aggregator-premium' ) );
			return;
		}

		$result = $this->folders->addSources( $name, $srcIds );

		if ( $result->isErr() ) {
			$this->printCliError( $result->error() );
		} else {
			$num = $result->get();
			WP_CLI::success(
				sprintf(
					_n( '%d source added.', '%d sources added.', $num, 'wp-rss-aggregator-premium' ),
					$num
				)
			);
		}
	}

	/**
	 * List the sources in a folder.
	 *
	 * ## Options
	 *
	 * <name>
	 * : The name of the folder.
	 *
	 * @param list<string> $args
	 */
	public function sources( array $args ): void {
		$name = trim( $args[0] ?? '' );
		if ( strlen( $name ) === 0 ) {
			WP_CLI::error( __( 'No folder name specified.', 'wp-rss-aggregator-premium' ) );
			return;
		}

		$result = Result::pipe(
			array(
				fn () => $this->folders->getSources( $name ),
				fn ( $ids ) => $this->sources->getManyByIds( $ids ),
			)
		);

		if ( $result->isErr() ) {
			WP_CLI::error( $result->error() );
			return;
		}

		$sources = $result->get();

		foreach ( $sources as $source ) {
			$this->io->printf( '• %s', $source->name );
		}
	}

	/**
	 * List the folders with a summary of their information.
	 *
	 * ## OPTIONS
	 *
	 * [--page=<num>]
	 * : The page number to show.
	 * ---
	 * default: 1
	 * ---
	 *
	 * [--num=<num>]
	 * : The number of folders to show per page.
	 * ---
	 * default: 20
	 * ---
	 *
	 * [--search=<text>]
	 * : Search for folders by name.
	 * ---
	 * default: ''
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp rss folders list "ball"
	 *     wp rss folders list --page=2
	 *     wp rss folders list --count=50 --page=3
	 *
	 * @param list<string>         $args
	 * @param array<string,string> $opts
	 */
	public function list( array $args, array $opts ): void {
		$num = (int) ( $args[0] ?? 20 );
		$page = (int) ( $opts['page'] ?? 1 );
		$search = trim( $opts['search'] ?? '' );

		$result = $this->folders->getList( $search, array(), $num, $page );

		if ( $result->isErr() ) {
			$this->printCliException( $result->error() );
			return;
		}

		$folders = $result->get();
		self::foldersTable( $folders )->render();
	}

	/**
	 * Rename a folder.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : The name of the folder to rename.
	 *
	 * <new_name>
	 * : The new name of the folder.
	 *
	 * ## EXAMPLES
	 *
	 * wp rss folder rename "chocolate" "candy"
	 *
	 * @param list<string> $args
	 */
	public function rename( array $args ): void {
		$result = $this->folders->rename( $args[0], $args[1] );

		if ( $result->isErr() ) {
			$this->printCliError( $result->error() );
			return;
		}

		$num = $result->get();

		WP_CLI::success(
			sprintf(
				_n( '%s folder updated.', '%s sources updated.', $num, 'wp-rss-aggregator-premium' ),
				number_format_i18n( $num )
			)
		);
	}

	/**
	 * Removes one or more sources from a folder.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : The name of the folder to remove the sources from.
	 *
	 * [<sources>...]
	 * : The IDs of the sources to remove from the folder.
	 *
	 * ## EXAMPLES
	 *
	 *     wp rss folders remove Sports 18
	 *     wp rss folders remove Theatre 34 65 101
	 *
	 * @param list<string> $args
	 */
	public function remove( array $args ): void {
		$name = $args[0];
		$rest = array_slice( $args, 1 );
		$srcIds = $this->parseIntArgArray( $rest );

		$result = $this->folders->removeSources( $name, $srcIds );

		if ( $result->isErr() ) {
			$this->printCliError( $result->error() );
			return;
		}

		$num = $result->get();

		WP_CLI::success(
			sprintf(
				_n(
					'Removed %1$d source from "%2$s".',
					'Removed %1$d sources from "%2$s".',
					$num,
					'wp-rss-aggregator-premium'
				),
				$num,
				$name
			)
		);
	}

	/**
	 * Delete one or multiple folders.
	 *
	 * ## OPTIONS
	 *
	 * [<name>...]
	 * : The names of the folders to delete.
	 *
	 * ## EXAMPLES
	 *
	 *     wp aggregator folders delete Music
	 *     wp aggregator folders delete Music "Hip Hop" Dance
	 *
	 * @param list<string> $args
	 */
	public function delete( array $args ): void {
		if ( count( $args ) === 0 ) {
			WP_CLI::error( __( 'No folders to delete.', 'wp-rss-aggregator-premium' ) );
			return;
		}

		$result = $this->folders->delete( $args );

		if ( $result->isErr() ) {
			$this->printCliError( $result->error() );
			return;
		}

		$num = $result->get();
		WP_CLI::success( sprintf( _n( 'Deleted %d folder.', 'Deleted %d folders.', $num, 'wp-rss-aggregator-premium' ), $num ) );
	}

	/**
	 * Creates a CLI table for a list of folders.
	 *
	 * @param iterable<Folder> $folders The folders to display in the table.
	 */
	protected function foldersTable( iterable $folders ): CliTable {
		$rows = array();
		foreach ( $folders as $folder ) {
			$res = $this->sources->getManyByIds( $folder->sourceIds );

			if ( $res->isErr() ) {
				$sources = '{' . $res->error()->getMessage() . '}';
			} else {
				$srcNames = Arrays::map( $res->get(), fn ( Source $src ) => $src->name );
				$sources = join( ', ', $srcNames );
			}

			$rows[] = array(
				'name' => $folder->name,
				'sources' => $sources,
			);
		}

		return CliTable::create( $rows )
			->showColumns( array( 'name', 'sources' ) )
			->columnNames(
				array(
					'name' => 'Name',
					'sources' => 'Sources',
				)
			);
	}
}
