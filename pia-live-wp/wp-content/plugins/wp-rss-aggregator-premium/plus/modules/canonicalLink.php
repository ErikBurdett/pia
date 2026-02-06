<?php

namespace RebelCode\Aggregator\Core;

use RebelCode\Aggregator\Core\RssReader\RssItem;
use WP_Post;

wpra()->addModule(
	'canonicalLink',
	array( 'importer' ),
	function ( Importer $importer ) {
		add_filter(
			'wpra.importer.post.meta',
			function ( array $meta, ?IrPost $post, RssItem $item, Source $src ) {
				if ( $src->settings->canonicalLink ) {
					$meta['_yoast_wpseo_canonical'] = $post->url; // YoastSEO
					$meta['_seopress_robots_canonical'] = $post->url; // SEOPress
					$meta['_aioseop_custom_link'] = $post->url; // All-in-one SEO Pack
				}
				return $meta;
			},
			10,
			4
		);

		add_filter(
			'get_canonical_url',
			function ( string $url, WP_Post $post ) use ( $importer ) {
				if ( ! is_singular() ) {
					return $url;
				}

				$srcIds = get_post_meta( $post->ID, ImportedPost::SOURCE );
				$itemUrl = get_post_meta( $post->ID, ImportedPost::URL, true );

				if ( ! is_array( $srcIds ) || count( $srcIds ) === 0 ) {
					return $url;
				}

				$srcs = $importer->sources->getManyByIds( $srcIds )->getOr( array() );
				foreach ( $srcs as $src ) {
					if ( $src->settings->canonicalLink ) {
						return $itemUrl;
					}
				}

				return $url;
			},
			10,
			2
		);
	}
);
