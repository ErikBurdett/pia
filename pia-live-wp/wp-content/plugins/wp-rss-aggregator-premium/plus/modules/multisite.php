<?php

namespace RebelCode\Aggregator\Core;

use RebelCode\Aggregator\Core\IrPost\IrAuthor;
wpra()->addModule(
	'multisite',
	array( 'importer' ),
	function ( Importer $importer ) {
		add_action(
			'wpra.importer.post.beforeCreate',
			function ( IrPost $post ) use ( $importer ) {
				$srcs = $importer->sources->getManyByIds( $post->sources )->getOr( array() );
				$blogId = get_current_blog_id();

				foreach ( $srcs as $src ) {
					if ( $src->settings->postSite !== null ) {
						$success = switch_to_blog( $src->settings->postSite );
						if ( $success ) {
							// Clear default author cache after switching blog
							IrAuthor::clearDefaultAuthorCache();
							$post->meta[ ImportedPost::SOURCE_BLOG_ID ] = $blogId;
						}
						break;
					}
				}
			}
		);

		add_action(
			'wpra.importer.post.afterCreate',
			function ( IrPost $post ) {
				if ( $post->postId === null ) {
					return;
				}
				if ( ! isset( $post->meta[ ImportedPost::SOURCE_BLOG_ID ] ) ) {
					return;
				}
				restore_current_blog();
				IrAuthor::clearDefaultAuthorCache();
			}
		);
	}
);
