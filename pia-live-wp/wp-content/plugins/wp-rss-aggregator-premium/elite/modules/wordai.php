<?php

namespace RebelCode\Aggregator\Core;

use RebelCode\Aggregator\Elite\WordAi\WaiClient;
use RebelCode\Aggregator\Core\Rpc\RpcServer;
use RebelCode\Aggregator\Core\Rpc\RpcClassHandler;

wpra()->addModule(
	'wordai',
	array( 'settings', 'rpc' ),
	function ( Settings $settings, RpcServer $rpc ) {
		$email = $settings->register( 'wordAiEmail' )->setDefault( '' )->get();
		$apiKey = $settings->register( 'wordAiApiKey' )->setDefault( '' )->get();

		$client = new WaiClient( $email, $apiKey );

		$rpc->addHandler( 'wordai', new RpcClassHandler( $client ) );

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
