<?php

namespace RebelCode\Aggregator\Core;

use RebelCode\Aggregator\Pro\FullTextClient;

wpra()->addModule(
	'fulltext',
	array( 'licensing' ),
	function ( Licensing $licensing ) {
		$url = apply_filters( 'wpra.fulltext.url', 'http://fulltext-premium.wprssaggregator.com' );
		$client = new FullTextClient( $url, $licensing );

		add_filter(
			'wpra.importer.post.content',
			function ( string $content, $item, Source $src, IrPost $post ) use ( $client ) {
				if ( ! $src->settings->enableFullText || $src->settings->fullTextBatchMode ) {
					return $content;
				}

				$modifiedPost = $client->maybeBuildIrPost( $post, $src );

				return $modifiedPost->content;
			},
			10,
			4
		);

		return $client;
	}
);
