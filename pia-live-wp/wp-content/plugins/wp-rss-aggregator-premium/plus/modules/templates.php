<?php

namespace RebelCode\Aggregator\Core;

use RebelCode\Aggregator\Plus\Templates\TokenType;
use RebelCode\Aggregator\Plus\Templates\TokenRenderer;
use RebelCode\Aggregator\Plus\Templates\Ctx\IrPostSourceCtx;
use RebelCode\Aggregator\Core\Utils\Time;
use DateTime;

wpra()->addModule(
	'templates',
	array(),
	function () {
		$tokenTypes = apply_filters(
			'wpra.templates.tokenTypes',
			array(
				'text' => new TokenType(
					__( 'Text', 'wp-rss-aggregator-premium' ),
					function ( IrPostSourceCtx $ctx, array $args ) {
						return $args['text'] ?? '';
					}
				),
				'source_name' => new TokenType(
					__( 'Source name', 'wp-rss-aggregator-premium' ),
					function ( IrPostSourceCtx $ctx, array $args ) {
						return $ctx->src->name;
					}
				),
				'source_url' => new TokenType(
					__( 'Source URL', 'wp-rss-aggregator-premium' ),
					function ( IrPostSourceCtx $ctx, array $args ) {
						return $ctx->src->url;
					}
				),
				'post_title' => new TokenType(
					__( 'Post title', 'wp-rss-aggregator-premium' ),
					function ( IrPostSourceCtx $ctx, array $args ) {
						return $ctx->post->title;
					}
				),
				'post_url' => new TokenType(
					__( 'Post URL', 'wp-rss-aggregator-premium' ),
					function ( IrPostSourceCtx $ctx, array $args ) {
						if ( $ctx->post->postId ) {
							return get_permalink( $ctx->post->postId ) ?: '';
						}
						return '';
					}
				),
				'original_post_url' => new TokenType(
					__( 'Original Post URL', 'wp-rss-aggregator-premium' ),
					function ( IrPostSourceCtx $ctx, array $args ) {
						return $ctx->post->url;
					}
				),
				'post_publish_date' => new TokenType(
					__( 'Post date', 'wp-rss-aggregator-premium' ),
					function ( IrPostSourceCtx $ctx, array $args ) {
						$date = $ctx->post->getDate();
						$format = $args['format'] ?? Time::HUMAN_FORMAT;
						return $date ? $date->format( $format ) : '';
					}
				),
				'post_import_date' => new TokenType(
					__( 'Import date', 'wp-rss-aggregator-premium' ),
					function ( IrPostSourceCtx $ctx, array $args ) {
						$dateStr = $ctx->post->getSingleMeta( ImportedPost::IMPORT_DATE, null );
						if ( $dateStr === null ) {
							return '';
						}
						$date = Time::createAndCatch( $dateStr ) ?? new DateTime();
						$format = $args['format'] ?? Time::HUMAN_FORMAT;
						return $date->format( $format );
					}
				),
				'post_author_name' => new TokenType(
					__( 'Author name', 'wp-rss-aggregator-premium' ),
					function ( IrPostSourceCtx $ctx, array $args ) {
						$authorName = trim( $ctx->post->getSingleMeta( ImportedPost::AUTHOR_NAME, '' ) );
						if ( empty( $authorName ) && $ctx->post->author !== null ) {
							$authorName = trim( $ctx->post->author->name ?? '' );
						}
						return $authorName;
					}
				),
				'post_author_url' => new TokenType(
					__( 'Author URL', 'wp-rss-aggregator-premium' ),
					function ( IrPostSourceCtx $ctx, array $args ) {
						$authorLink = trim( $ctx->post->getSingleMeta( ImportedPost::AUTHOR_URL, '' ) );
						if ( empty( $authorLink ) && $ctx->post->author !== null ) {
							$authorLink = trim( $ctx->post->author->link ?? '' );
						}
						return $authorLink;
					}
				),
				'original_image_url' => new TokenType(
					__( 'Featured image URL', 'wp-rss-aggregator-premium' ),
					function ( IrPostSourceCtx $ctx, array $args ) {
						return $ctx->post->getSingleMeta( ImportedPost::FT_IMAGE_URL, '' );
					}
				),
				'meta' => new TokenType(
					__( 'Post meta', 'wp-rss-aggregator-premium' ),
					function ( IrPostSourceCtx $ctx, array $args ) {
						$name = $args['name'] ?? '';
						if ( empty( $name ) ) {
							return '';
						}

						if ( ! isset( $ctx->post->meta[ $name ] ) ) {
							if ( $ctx->post->postId ) {
								return get_post_meta( $ctx->post->postId, $name, true );
							} else {
								return '';
							}
						}

						$value = $ctx->post->meta[ $name ];

						// Single-value numeric arrays are treated as single values.
						if ( is_array( $value ) && count( $value ) === 1 && isset( $value[0] ) ) {
							$value = $value[0];
						}

						if ( is_scalar( $value ) ) {
							return (string) $value;
						} else {
							return json_encode( $value );
						}
					}
				),
			)
		);

		add_filter(
			'wpra.admin.frame.l10n',
			function ( array $l10n ) use ( $tokenTypes ) {
				$l10n['tokenTypes'] = array_map(
					fn ( TokenType $t ) => $t->label,
					$tokenTypes
				);
				return $l10n;
			}
		);

		return new TokenRenderer( $tokenTypes );
	}
);
