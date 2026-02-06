<?php

namespace RebelCode\Aggregator\Core;

use RebelCode\Aggregator\Core\RssReader\RssItem;

wpra()->addModule(
	'media',
	array(),
	function () {
		add_action(
			'before_delete_post',
			function ( int $postId ) {
				$src = get_post_meta( $postId, ImportedPost::SOURCE );
				if ( empty( $src ) ) {
					return; // not an imported post
				}

				// legacy filter from the v4 "Feed to Post" add-on
				$delete = apply_filters( 'wprss_ftp_delete_attachments_with_posts', true, $postId );

				if ( ! $delete ) {
					return;
				}

				$mediaList = get_children(
					array(
						'post_parent' => $postId,
						'post_type' => 'attachment',
						'post_mime_type' => 'image',
					)
				);

				foreach ( $mediaList as $media ) {
					wp_delete_post( $media->ID );
				}
			}
		);

		add_filter(
			'wpra.importer.post',
			function ( IrPost $post, RssItem $item, Source $src ) {
				if ( 'wprss_feed_item' === $src->settings->postType || ! $src->settings->downloadImages ) {
					$post->images = array();
				}

				return $post;
			},
			10,
			3
		);
	}
);
