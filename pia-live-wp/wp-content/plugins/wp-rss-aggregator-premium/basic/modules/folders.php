<?php

namespace RebelCode\Aggregator\Core;

use WP_CLI;
use RebelCode\Aggregator\Core\Utils\Arrays;
use RebelCode\Aggregator\Core\Rpc\RpcServer;
use RebelCode\Aggregator\Core\Rpc\RpcClassHandler;
use RebelCode\Aggregator\Core\Cli\WpCliIo;
use RebelCode\Aggregator\Basic\V4Migration\V4CategoryMigrator;
use RebelCode\Aggregator\Basic\FoldersStore;
use RebelCode\Aggregator\Basic\Folder;
use RebelCode\Aggregator\Basic\Cli\FolderCommand;

wpra()->addModule(
	'folders',
	array( 'db', 'importer', 'renderer', 'rpc' ),
	function ( Database $db, Importer $importer, Renderer $renderer, RpcServer $rpc ) {
		$store = new FoldersStore( $db, $db->tableName( 'folders' ), $db->tableName( 'folder_sources' ) );
		$store->createTable();

		$rpc->addHandler(
			'folders',
			new RpcClassHandler(
				$store,
				array(
					Folder::class => fn ( array $array ) => Folder::fromArray( $array ),
				)
			)
		);

		$rpc->addTransform( Folder::class, fn ( Folder $f ) => $f->toArray() );

		if ( class_exists( 'WP_CLI' ) ) {
			WP_CLI::add_command( 'rss folder', new FolderCommand( new WpCliIo(), $store, $importer->sources ) );
		}

		add_filter(
			'wpra.renderer.display.sources',
			function ( array $srcIds, Display $display ) use ( $store ) {
				$fSrcIds = $store->getSources( $display->folders )->getOr( array() );

				foreach ( $fSrcIds as $id ) {
					$srcIds[] = $id;
				}

				return $srcIds;
			},
			10,
			2
		);

		add_filter(
			'wpra.rpc.rels.folders',
			function ( array $rels, iterable $folders ) use ( $renderer ) {
				$rels = array();
				$names = Arrays::map( $folders, fn ( Folder $f ) => $f->id );

				$displays = $renderer->displays->getWithFolders( $names )->getOrThrow();
				foreach ( $displays as $display ) {
					foreach ( $display->folders as $fid ) {
						$rels[ $fid ]['displays'][] = array(
							'id' => $display->id,
							'name' => $display->name,
						);
					}
				}
				unset( $displays );

				return $rels;
			},
			10,
			2
		);

		add_filter(
			'wpra.rpc.rels.sources',
			function ( array $rels, array $ids ) use ( $store ) {
				$folders = $store->getList( '', $ids )->getOrThrow();

				foreach ( $folders as $folder ) {
					foreach ( $folder->sourceIds as $srcId ) {
						$rels[ $srcId ]['folders'][] = $folder->name;
					}
				}

				return $rels;
			},
			10,
			2
		);

		$migrator = new V4CategoryMigrator( $store );
		add_filter(
			'wpra.v4Migration.source.inserted',
			function ( Source $newSource, int $v4Id ) use ( $migrator ) {
				if ( $newSource->id !== null ) {
					$migrator->migrate( $v4Id, $newSource->id, true )->getOrThrow();
				}
				return $newSource;
			},
			10,
			2
		);

		return $store;
	}
);
