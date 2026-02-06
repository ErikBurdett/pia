<?php

namespace RebelCode\Aggregator\Core;

use RebelCode\Aggregator\Plus\PostProtector;

wpra()->addModule(
	'postProtector',
	array( 'settings', 'importer' ),
	function ( Settings $settings, Importer $importer ) {
		$enabled = $settings->register( 'protectEditedPosts' )->setDefault( false )->get();

		$protector = new PostProtector( $importer->sources );

		if ( ! $enabled ) {
			return;
		}

		add_action(
			'save_post',
			fn ( $id, $post ) => $protector->onPostSaved( $post ),
			10,
			2
		);

		add_filter(
			'wpra.importer.wpPosts.query.args',
			fn ( $args ) => $protector->filterQuery( $args ),
			10,
			3
		);

		return $protector;
	}
);
