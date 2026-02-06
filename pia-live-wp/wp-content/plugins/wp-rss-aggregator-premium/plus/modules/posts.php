<?php

namespace RebelCode\Aggregator\Core;

use RebelCode\Aggregator\Plus\Templates\TokenRenderer;
use RebelCode\Aggregator\Plus\PlusIrPostBuilder;
use RebelCode\Aggregator\Core\RssReader\RssItem;
use RebelCode\Aggregator\Core\IrPost\IrImage;
use RebelCode\Aggregator\Core\IrPost\IrAuthor;
use RebelCode\Aggregator\Basic\Conditions\ConditionSystem;

wpra()->addModule(
	'posts',
	array( 'importer', 'templates', 'taxonomies' ),
	function (
		Importer $importer,
		TokenRenderer $_,
		ConditionSystem $taxCondSys
	) {
		$builder = new PlusIrPostBuilder( $importer->postBuilder, $taxCondSys );

		add_filter(
			'wpra.importer.post.type',
			function ( string $_, RssItem $__, Source $src ) {
				return $src->settings->postType;
			},
			10,
			3
		);

		add_filter(
			'wpra.importer.post.status',
			function ( string $_, RssItem $__, Source $src ) {
				if ( 'wprss_feed_item' === $src->settings->postType ) {
					return 'publish';
				}
				return $src->settings->postStatus;
			},
			10,
			3
		);

		add_filter(
			'wpra.importer.post.format',
			function ( string $_, RssItem $__, Source $src ) {
				return $src->settings->postFormat;
			},
			10,
			3
		);

		add_filter(
			'wpra.importer.post.commentsOpen',
			function ( bool $_, RssItem $__, Source $src ) {
				return $src->settings->commentsOpen;
			},
			10,
			3
		);

		add_filter(
			'wpra.importer.post.dates',
			function ( array $dates, IrPost $_, RssItem $__, Source $src ) use ( $builder ) {
				return $builder->buildDates( $dates, $src );
			},
			10,
			4
		);

		add_filter(
			'wpra.importer.post.excerpt',
			function ( string $excerpt, $_, Source $src, IrPost $post ) use ( $builder ) {
				if ( ! $src->settings->enableExcerpt ) {
					return '';
				}

				return $builder->buildExcerpt( $excerpt, $post->content, $src );
			},
			10,
			4
		);

		add_filter(
			'wpra.importer.post.content',
			function ( string $content, RssItem $item, Source $src ) use ( $builder ) {
				return $builder->buildContent( $content, $item, $src );
			},
			11,
			3
		);

		add_filter(
			'wpra.importer.post.ftImage',
			function ( ?IrImage $ftImage, RssItem $_, Source $src, IrPost $post ) use ( $builder ) {
				if ( $src->settings->deDupeFtImage && $ftImage !== null ) {
					$post->content = $builder->removeImgFromContent( $post->content, $post->ftImage->url );
				}
				return $ftImage;
			},
			10,
			4
		);

		add_filter(
			'wpra.importer.post.terms',
			function ( array $_, RssItem $item, Source $src, IrPost $post ) use ( $builder ) {
				return $builder->buildTerms( $post->type, $item, $src );
			},
			10,
			4
		);

		add_filter(
			'wpra.importer.post.final',
			function ( ?IrPost $post, RssItem $_, Source $src ) {
				if ( $src->settings->assignFtImage && $src->settings->mustHaveFtImage && $post->ftImage === null ) {
					return null;
				}
				if ( $src->settings->mustHaveAuthor && $post->author === null ) {
					return null;
				}
				if ( $src->settings->canonicalLink ) {
					$post->meta['_yoast_wpseo_canonical'] = $post->url; // YoastSEO
					$post->meta['_seopress_robots_canonical'] = $post->url; // SEOPress
					$post->meta['_aioseop_custom_link'] = $post->url; // All-in-one SEO Pack
				}
				return $post;
			},
			10,
			3
		);

		return $builder;
	}
);
