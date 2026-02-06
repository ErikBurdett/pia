<?php

namespace RebelCode\Aggregator\Elite\SpinnerChief;

use RebelCode\Aggregator\Core\Utils\Result;
use RebelCode\Aggregator\Core\Source;
use RebelCode\Aggregator\Core\Logger;
use RebelCode\Aggregator\Core\IrPost;
use RebelCode\Aggregator\Core\ImportedPost;

class SpinnerChiefClient {

	protected const TITLE_DELIM = '/n';
	public const BASE_URL = 'https://spinnerchief.com/api/paraphraser';

	protected string $apiKey;
	protected string $devKey;

	public function __construct( string $apiKey, string $devKey ) {
		$this->apiKey = trim( $apiKey );
		$this->devKey = trim( $devKey );
	}

	public function spinPost( IrPost $post, Source $src ): IrPost {
		if ( empty( $this->apiKey ) ) {
			return $post;
		}

		if ( ! $src->settings->scEnableContent && ! $src->settings->scEnableTitle ) {
			return $post;
		}

		$result = $this->spin( $post->title . static::TITLE_DELIM . $post->content );

		if ( $result->isErr() ) {
			Logger::warning( $result->error() );
			return $post;
		}

		$spunText = $result->get();

		if ( strpos( $spunText, static::TITLE_DELIM ) !== false ) {
			[$title, $content] = explode( static::TITLE_DELIM, $spunText, 2 );
		} else {
			Logger::warning( 'Delimiter not found in SpinnerChief response. Using original content.' );
			$title = $post->title;
			$content = $post->content;
		}

		if ( $src->settings->scRevisions ) {
			$post->meta[ ImportedPost::ORIG_TITLE ] = $post->title;
			$post->meta[ ImportedPost::ORIG_CONTENT ] = $post->content;
		}

		if ( $src->settings->scEnableContent ) {
			$post->content = $content;
		}

		if ( $src->settings->scEnableTitle ) {
			$post->title = $title;
		}

		return $post;
	}

	/** @return Result<string> */
	public function spin( string $text ): Result {
		$cacheKey = md5( $text );
		$cache = get_transient( "wpra_sc_cache_{$cacheKey}" );

		if ( is_array( $cache ) ) {
			$result = $cache;
		} else {
			$response = $this->request( $text );
			if ( $response->isErr() ) {
				return $response;
			}
			$result = $response->get();
			set_transient( "wpra_sc_cache_{$cacheKey}", $result, DAY_IN_SECONDS );
		}

		return Result::Ok( $result );
	}

	private function request( string $text ): Result {
		$response = wp_remote_post(
			static::BASE_URL,
			array(
				'method' => 'POST',
				'headers' => array(
					'Content-type' => 'application/x-www-form-urlencoded',
				),
				'body' => array(
					'text' => $text,
					'api_key' => $this->apiKey,
					'dev_key' => $this->devKey,
				),
				'blocking' => true,
				'timeout' => apply_filters( 'wprss_sc_api_timeout', 5 * 60 ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return Result::Err( $response->get_error_message() );
		}

		$statusCode = wp_remote_retrieve_response_code( $response );
		if ( $statusCode >= 400 ) {
			return Result::Err( wp_remote_retrieve_body( $response ) );
		}

		$json = wp_remote_retrieve_body( $response );
		$data = @json_decode( $json, true );

		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $data ) ) {
			return Result::Err( 'Invalid JSON response: ' . $json );
		}

		$code = $data['code'] ?? 200;
		$text = $data['text'] ?? null;

		if ( $code > 200 ) {
			return Result::Err( $text );
		}

		return Result::Ok( $text );
	}
}
