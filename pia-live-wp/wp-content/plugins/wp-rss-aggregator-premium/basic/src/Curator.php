<?php

namespace RebelCode\Aggregator\Basic;

use RebelCode\Aggregator\Core\Importer;
use RebelCode\Aggregator\Core\IrPost;
use RebelCode\Aggregator\Core\Logger;
use RebelCode\Aggregator\Core\RejectedItem;
use RebelCode\Aggregator\Core\Utils\Result;
use RebelCode\Aggregator\Basic\Curator\IrPostsStore;

class Curator {

	public IrPostsStore $irPosts;
	public Importer $importer;

	public function __construct( IrPostsStore $irPosts, Importer $importer ) {
		$this->irPosts = $irPosts;
		$this->importer = $importer;
	}

	/**
	 * Approves an IR post, creating a WordPress post and deleting the IR post
	 *
	 * from the database.
	 *
	 * @return Result<IrPost> The approved post.
	 */
	public function approve( IrPost $post ): Result {
		$res = $this->importer->createWpPost( $post );
		if ( $res->isErr() ) {
			Logger::error( $res->error() );
			return $res;
		}

		if ( $post->id === null ) {
			return Result::Ok( $post );
		}

		$res = $this->irPosts->deleteById( $post->id );
		if ( $res->isErr() ) {
			return $res;
		}

		return Result::Ok( $post );
	}

	/**
	 * Approves IR posts by their IDs.
	 *
	 * @param list<int> $ids The IDs of the posts to approve.
	 * @return Result<IrPost[]> The approved posts.
	 */
	public function approveManyByIds( array $ids ): Result {
		$res = $this->irPosts->getManyByIds( $ids );
		if ( $res->isErr() ) {
			return $res;
		}

		$approved = array();
		foreach ( $res->get() as $post ) {
			$res = $this->approve( $post );
			if ( $res->isErr() ) {
				return $res;
			}
			$approved[] = $res->get();
		}

		return Result::Ok( $approved );
	}

	/**
	 * Rejects an IR post, adding its GUID to the reject list and deleting the
	 * post from the database.
	 *
	 * @param IrPost $post The post to reject.
	 * @return Result<IrPost> The post that was rejected.
	 */
	public function reject( IrPost $post ): Result {
		$note = sprintf(
			_x( '(Rejected curation) %s', 'The recorded note when a pending post is rejected. %s = post title', 'wp-rss-aggregator-premium' ),
			$post->title
		);

		$rejItem = new RejectedItem( $post->guid, null, $note );
		$res = $this->importer->rejectList->add( $rejItem );
		if ( $res->isErr() ) {
			return $res;
		}

		if ( $post->id === null ) {
			return Result::Ok( $post );
		}

		$res = $this->irPosts->deleteById( $post->id );
		if ( $res->isErr() ) {
			return $res;
		}

		return Result::Ok( $post );
	}

	/**
	 * Rejects IR posts by their IDs.
	 *
	 * @param list<int> $ids The IDs of the posts to reject.
	 * @return Result<IrPost[]> The rejected posts.
	 */
	public function rejectManyByIds( array $ids ): Result {
		$res = $this->irPosts->getManyByIds( $ids );
		if ( $res->isErr() ) {
			return $res;
		}

		$rejected = array();
		foreach ( $res->get() as $post ) {
			$res = $this->reject( $post );
			if ( $res->isErr() ) {
				return $res;
			}
			$rejected[] = $res->get();
		}

		return Result::Ok( $rejected );
	}
}
