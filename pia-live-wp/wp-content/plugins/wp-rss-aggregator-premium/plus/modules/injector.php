<?php

namespace RebelCode\Aggregator\Core;

use WP_Post;
use RebelCode\Aggregator\Plus\Templates\TokenRenderer;
use RebelCode\Aggregator\Plus\ContentInjector;
use RebelCode\Aggregator\Core\Utils\Arrays;

wpra()->addModule(
	'injector',
	array( 'settings', 'importer', 'templates' ),
	function ( Settings $settings, Importer $importer, TokenRenderer $tokRenderer ) {
		$doFeeds = $settings->register( 'injectContentInFeeds' )->setDefault( false )->get();

		$injector = new ContentInjector( $tokRenderer, $doFeeds );
		$priority = apply_filters( 'wpra.injector.filterPriority', 10_000 );

		add_filter(
			'the_content',
			function ( string $content ) use ( $injector, $importer ) {
				global $post;
				if ( $post === null || ! $post instanceof WP_Post || empty( $post->ID ) ) {
					return $content;
				}

				if ( ! ImportedPost::isImported( $post->ID ) ) {
					return $content;
				}

				$irPost = IrPost::fromWpPost( $post );
				$irPost->content = $content;

				$srcId = $irPost->sources[0] ?? 0;
				$src = $importer->sources->getById( $srcId )->getOr( null );
				if ( $src === null ) {
					return $content;
				}

				$injector->inject(
					$irPost,
					$src,
					array(
						'postType' => $irPost->type,
						'single' => is_singular(),
						'feed' => is_feed(),
						'archive' => is_archive(),
					)
				);

				return $irPost->content;
			},
			$priority
		);

		add_filter(
			'wpra.importer.preview',
			function ( iterable $posts, Source $src ) use ( $injector ) {
				return Arrays::gmap( $posts, fn ( IrPost $post ) => $injector->attachAttribution( clone $post, $src ) );
			},
			10,
			2
		);

		add_filter(
			'wpra.importer.preview',
			function ( iterable $posts, Source $src ) use ( $injector ) {
				return Arrays::gmap( $posts, fn ( IrPost $post ) => $injector->attachAudioPlayer( clone $post, $src ) );
			},
			10,
			2
		);

		return $injector;
	}
);
