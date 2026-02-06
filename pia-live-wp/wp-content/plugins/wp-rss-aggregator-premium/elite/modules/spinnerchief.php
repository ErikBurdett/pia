<?php

namespace RebelCode\Aggregator\Core;

use RebelCode\Aggregator\Elite\SpinnerChief\SpinnerChiefClient;

wpra()->addModule(
	'spinnerchief',
	array( 'settings' ),
	function ( Settings $settings ) {
		$apiKey = $settings->register( 'scApiKey' )->setDefault( '' )->get();
		$devKey = $settings->register( 'scDevKey' )->setDefault( '' )->get();

		$client = new SpinnerChiefClient( $apiKey, $devKey );

		add_filter(
			'wpra.importer.post.store',
			function ( $post, Source $src ) use ( $client ) {
				if ( null === $post ) {
					return null;
				}
				return $client->spinPost( $post, $src );
			},
			10,
			2
		);

		return $client;
	}
);
