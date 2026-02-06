<?php

namespace RebelCode\Aggregator\Core;

use RebelCode\Aggregator\Basic\Cli\CurationCommand;
use RebelCode\Aggregator\Basic\Curator\IrPostsStore;
use RebelCode\Aggregator\Basic\Curator;
use RebelCode\Aggregator\Core\Cli\WpCliIo;
use RebelCode\Aggregator\Core\Source\ReconcileStrategy;
use RebelCode\Aggregator\Core\Rpc\RpcClassHandler;
use RebelCode\Aggregator\Core\Rpc\RpcServer;
use WP_CLI;

wpra()->addModule(
	'curator',
	array( 'db', 'importer', 'rpc' ),
	function (
		Database $db,
		Importer $importer,
		RpcServer $rpc
	) {
		$irPosts = new IrPostsStore( $db, $db->tableName( 'ir_posts' ) );
		$irPosts->createTable();

		$curator = new Curator( $irPosts, $importer );

		$rpc->addHandler( 'irPosts', new RpcClassHandler( $irPosts ) );
		$rpc->addHandler( 'curator', new RpcClassHandler( $curator ) );

		if ( class_exists( 'WP_CLI' ) ) {
			$command = new CurationCommand( new WpCliIo(), $curator, $importer->sources );
			WP_CLI::add_command( 'rss curation', $command );
		}

		// If curation is enabled, intercept the IR post before it gets converted
		// into a WordPress post and save it in the ir_posts table.
		add_filter(
			'wpra.importer.post.store',
			function ( IrPost $post, Source $src ) use ( $curator ) {
				$isWpPost = $post->postId !== null;
				$isOverwriteStrat = $src->settings->reconcileStrategy === ReconcileStrategy::OVERWRITE;
				$isUpdatingWpPost = $isOverwriteStrat && $isWpPost;

				if ( ! $src->settings->curatePosts || $isUpdatingWpPost ) {
					return $post;
				}

				$result = $curator->irPosts->save( $post );
				if ( $result->isErr() ) {
					Logger::error( $result->error() );
				}

				return null;
			},
			10,
			2
		);

		// Extend the unique title check to also check IR posts
		add_filter(
			'wpra.importer.titleExists',
			function ( bool $exists, string $title ) use ( $curator ) {
				return $exists || $curator->irPosts->titleExists( $title )->getOr( false );
			},
			10,
			2
		);

		// Extend the importer's reconciliation with IR posts
		add_filter(
			'wpra.importer.existingItemsMap',
			function ( array $map, array $guids ) use ( $curator ) {
				$result = $curator->irPosts->getManyByGuids( $guids );
				if ( $result->isErr() ) {
					Logger::error( $result->error() );
					return $map;
				}
				foreach ( $result->get() as $irPost ) {
					$map[ $irPost->guid ] = $irPost;
				}
				return $map;
			},
			10,
			2
		);

		// Show the number of pending posts in the admin menu
		add_filter(
			'wpra.admin.menu.badge',
			function ( string $badge ) use ( $curator ) {
				$numPending = $curator->irPosts->getCount()->getOr( 0 );
				if ( $numPending > 0 ) {
					$badge = (string) $numPending;
				}
				return $badge;
			}
		);

		// Add the number of pending posts to source relationships
		add_filter(
			'wpra.rpc.rels.sources',
			function ( array $rels, array $ids ) use ( $curator ) {
				$irPosts = $curator->irPosts->getFromSources( $ids )->getOrThrow();
				foreach ( $irPosts as $post ) {
					foreach ( $post->sources as $sid ) {
						$rels[ $sid ]['pending'] ??= 0;
						$rels[ $sid ]['pending']++;
					}
				}
				return $rels;
			},
			10,
			2
		);

		return $curator;
	}
);
