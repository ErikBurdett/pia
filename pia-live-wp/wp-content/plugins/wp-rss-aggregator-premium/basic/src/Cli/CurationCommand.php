<?php

declare(strict_types=1);

namespace RebelCode\Aggregator\Basic\Cli;

use RebelCode\Aggregator\Basic\Curator;
use RebelCode\Aggregator\Basic\Curator\IrPostsStore;
use RebelCode\Aggregator\Core\Cli\BaseCommand;
use RebelCode\Aggregator\Core\Cli\CliIo;
use RebelCode\Aggregator\Core\Cli\Colors;
use RebelCode\Aggregator\Core\Cli\CliTable;
use RebelCode\Aggregator\Core\IrPost;
use RebelCode\Aggregator\Core\Source;
use RebelCode\Aggregator\Core\Store\SourcesStore;
use RebelCode\Aggregator\Core\Utils\Arrays;
use RebelCode\Aggregator\Core\Utils\Strings;
use RebelCode\Aggregator\Core\Utils\Time;
use WP_CLI;

class CurationCommand extends BaseCommand {

	private Curator $curator;
	protected IrPostsStore $irPosts;
	protected SourcesStore $sources;

	public function __construct( CliIo $io, Curator $curator, SourcesStore $sources ) {
		parent::__construct( $io );
		$this->curator = $curator;
		$this->sources = $sources;
	}

	/**
	 * Show information about an IR post.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the post to show.
	 *
	 * ## EXAMPLES
	 *
	 * wp rss curation show 123
	 *
	 * @param list<string> $args
	 */
	public function show( array $args ): void {
		$id = $this->parseIntArg( $args[0], __( '"%s" is not a valid post ID.', 'wp-rss-aggregator-premium' ) );

		$res = $this->curator->irPosts->getById( $id );

		if ( $res->isOk() ) {
			$post = $res->get();
			$pubDate = Time::toHumanFormat( $post->datePublished );
			$modDate = Time::toHumanFormat( $post->dateModified );
			$authorName = $post->author ? $post->author->name : '';

			$this->io->cprintln( $post->title, Colors::BOLD );
			$this->io->cprintf( '%(' . __( 'By:', 'wp-rss-aggregator-premium' ) . ')% ' . "$authorName\n", array( Colors::BOLD ) );
			$this->io->cprintf( '%(' . __( 'Published:', 'wp-rss-aggregator-premium' ) . ')% ' . "$pubDate\n", array( Colors::BOLD ) );
			$this->io->cprintf( '%(' . __( 'Modified:', 'wp-rss-aggregator-premium' ) . ')% ' . "$modDate\n", array( Colors::BOLD ) );
			$this->io->println( $s = '----- ' . __( 'Content', 'wp-rss-aggregator-premium' ) . ' -----' );
			$this->io->println( $post->content );
			$this->io->println( str_repeat( '-', strlen( $s ) ) );
		} else {
			$this->printCliError( $res->error() );
		}
	}

	/**
	 * List posts that are pending curation.
	 *
	 * ## OPTIONS
	 *
	 * [--search=<search>]
	 * : Search for posts that contain a string in their title or content.
	 *
	 * [--source=<source>]
	 * : Only show posts from the given source.
	 *
	 * ## EXAMPLES
	 *
	 * wp rss curation list
	 * wp rss curation list --search="Badminton"
	 * wp rss curation list --source=145
	 *
	 * @param list<string>         $args
	 * @param array<string,string> $opts
	 */
	public function list( array $args, array $opts ): void {
		$filter = $opts['search'] ?? '';
		$srcId = $opts['source'] ?? null;

		if ( $srcId !== null ) {
			$srcId = (int) $srcId;
		}

		$srcList = ( $srcId !== null ) ? array( $srcId ) : array();
		$res = $this->curator->irPosts->getList( $filter, $srcList );

		if ( $res->isErr() ) {
			$this->printCliError( $res->error() );
			return;
		}

		$posts = Arrays::fromIterable( $res->get() );

		if ( count( $posts ) === 0 ) {
			$this->io->println( 'There are no pending posts' );
			return;
		}

		$this->postsTable( $posts )->render();
	}

	/**
	 * Deletes one of more pending posts.
	 *
	 * ## OPTIONS
	 *
	 * [<ids>...]
	 * : The IDs of the posts to delete.
	 *
	 * [--from=<sources>]
	 * : A comma-separated list of source IDs.
	 *
	 * ## EXAMPLES
	 *
	 * wp rss curation delete 14 87 161
	 * wp rss curation delete --from=145
	 * wp rss curation delete --from=145,88
	 * wp rss curation delete 14 87 --from=145,88
	 *
	 * @param list<string>         $args
	 * @param array<string,string> $opts
	 */
	public function delete( array $args, array $opts ): void {
		$ids = $this->parseIntArgArray( $args, '%s is not a valid post ID.' );

		if ( count( $ids ) > 0 ) {
			$res = $this->curator->irPosts->deleteManyByIds( $ids );

			if ( $res->isOk() ) {
				$num = $res->get();
				WP_CLI::success( "Deleted {$num} pending posts." );
			} else {
				$this->printCliError( $res->error() );
			}
		}

		if ( isset( $opts['from'] ) ) {
			$srcId = $this->parseIntArg( $opts['from'], '%s is not a valid source ID.' );
			$res = $this->curator->irPosts->deleteFromSource( $srcId );

			if ( $res->isOk() ) {
				$num = $res->get();
				WP_CLI::success( "Deleted {$num} pending posts." );
			} else {
				$this->printCliError( $res->error() );
			}
		}
	}

	/**
	 * Approves one or more pending posts by their IDs.
	 *
	 * ## OPTIONS
	 *
	 * [<ids>...]
	 * : The IDs of the posts to approve.
	 *
	 * ## EXAMPLES
	 *
	 * wp rss curation approve 14 87 161
	 *
	 * @param list<string> $args
	 */
	public function approve( array $args ): void {
		$ids = $this->parseIntArgArray( $args, '%s is not a valid post ID.' );

		if ( count( $ids ) === 0 ) {
			$this->printCliError( 'No post IDs specified.' );
			return;
		}

		$res = $this->curator->approveManyByIds( $ids );

		if ( $res->isErr() ) {
			$this->printCliError( $res->error() );
			return;
		}

		$posts = $res->get();
		$num = count( $posts );
		WP_CLI::success( "Approved {$num} posts." );

		foreach ( $posts as $post ) {
			$this->io->println( ' • ' . $post->title );
		}
	}

	/**
	 * Rejects one or more pending posts by their IDs.
	 *
	 * ## OPTIONS
	 *
	 * [<ids>...]
	 * : The IDs of the posts to reject.
	 *
	 * ## EXAMPLES
	 *
	 * wp rss curation reject 14 87 161
	 *
	 * @param list<string> $args
	 */
	public function reject( array $args ): void {
		$ids = $this->parseIntArgArray( $args, '%s is not a valid post ID.' );

		if ( count( $ids ) === 0 ) {
			$this->printCliError( 'No post IDs specified.' );
			return;
		}

		$res = $this->curator->rejectManyByIds( $ids );

		if ( $res->isErr() ) {
			$this->printCliError( $res->error() );
			return;
		}

		$posts = $res->get();
		$num = count( $posts );
		WP_CLI::success( "Rejected {$num} posts." );

		foreach ( $posts as $post ) {
			$this->io->println( ' • ' . $post->title );
		}
	}

	/**
	 * Curate pending posts interactively.
	 *
	 * @param list<string> $args
	 */
	public function wizard(): void {
		$res = $this->curator->irPosts->getList();

		if ( $res->isErr() ) {
			$this->printCliError( $res->error() );
			return;
		}

		$posts = Arrays::fromIterable( $res->get() );
		$numPosts = count( $posts );

		if ( $numPosts === 0 ) {
			$this->io->println( __( 'There are no posts to curate.', 'wp-rss-aggregator-premium' ) );
			return;
		}

		foreach ( $posts as $post ) {
			$date = $post->getDate();
			$dateStr = Time::toHumanFormat( $date );

			$this->io->cprintf( "%(ID:)% %($post->id)%\n", array( Colors::BOLD, Colors::CYAN ) );
			$this->io->cprintf( "%(Title:)% %($post->title)%\n", array( Colors::BOLD, Colors::CYAN ) );
			$this->io->cprintf( "%(Date:)% %($dateStr)%\n", array( Colors::BOLD, Colors::CYAN ) );
			$this->io->cprintf( "%(Excerpt:)% %($post->excerpt)%\n\n", array( Colors::BOLD, Colors::CYAN ) );

			$choice = $this->io->ask( 'What to do? [a]ccept, [r]eject, [s]kip, [c]ancel? ' );
			$this->io->println();

			switch ( $choice ) {
				case 'a':
					$res = $this->curator->approve( $post );
					if ( $res->isErr() ) {
						$this->printCliError( $res->error() );
					}
					break;
				case 'r':
					$res = $this->curator->reject( $post );
					if ( $res->isErr() ) {
						$this->printCliError( $res->error() );
					}
					break;
				case 's':
					break;
				case 'c':
					break 2;
				default:
					$this->io->error( 'Invalid choice.' );
					break 2;
			}
		}
	}

	/**
	 * @param iterable<IrPost>  $posts
	 * @param list<string>|null $columns
	 */
	protected function postsTable( iterable $posts, ?array $columns = null ): CliTable {
		$rows = Arrays::gmap(
			$posts,
			fn ( IrPost $post ) => array(
				'id' => $post->postId,
				'title' => $post->title,
				'date' => Time::toHumanFormat( $post->getDate() ),
				'author' => $post->author ? $post->author->name : '',
				'sources' => Strings::joinMap(
					$this->sources->getManyByIds( $post->sources )->getOr( array() ),
					', ',
					fn ( Source $src ) => $src->name
				),
			)
		);

		$columns = $columns ?? array( 'id', 'title', 'date', 'author', 'sources' );

		return CliTable::create( $rows )
			->showColumns( $columns )
			->columnNames(
				array(
					'id' => 'ID',
					'title' => 'Title',
					'date' => 'Date',
					'author' => 'Author',
					'sources' => 'Sources',
				)
			);
	}
}
