<?php

namespace RebelCode\Aggregator\Elite\WordAi;

use RebelCode\Aggregator\Core\ImportedPost;
use RebelCode\Aggregator\Core\IrPost;
use RebelCode\Aggregator\Core\Logger;
use RebelCode\Aggregator\Core\Source;
use RebelCode\Aggregator\Core\Utils\Result;
use RebelCode\Aggregator\Elite\WordAi\WaiAccountInfo;
use RebelCode\Aggregator\Elite\WordAi\WaiRewriteResult;

class WaiClient {

	protected const TITLE_DELIM = "\n";
	public const BASE_URL = 'https://wai.wordai.com/api/';

	private string $email;
	private string $apiKey;

	public function __construct( string $email, string $apiKey ) {
		$this->email = trim( $email );
		$this->apiKey = trim( $apiKey );
	}

	public function spinPost( IrPost $post, Source $src ): IrPost {
		if ( empty( $this->email ) || empty( $this->apiKey ) ) {
			return $post;
		}

		if ( ! $src->settings->waiEnableTitle && ! $src->settings->waiEnableContent ) {
			return $post;
		}

		$payload = $post->title . static::TITLE_DELIM . $post->content;

		$result = $this->spin(
			array(
				'input' => $payload,
				'rewrite_num' => $src->settings->waiSpintax ? 2 : 1,
				'uniqueness' => $src->settings->waiUniqueness,
				'return_rewrites' => $this->boolStr( ! $src->settings->waiSpintax ),
				'protect_words' => $this->boolStr( $src->settings->waiProtectWords ),
				'use_custom_synonyms' => $this->boolStr( $src->settings->waiCustomSynonyms ),
			)
		);

		if ( $result->isErr() ) {
			Logger::warning( $result->error() );
			return $post;
		}

		$data = $result->get();
		if ( $src->settings->waiSpintax ) {
			$spinText = $data->text;
		} else {
			$spinText = $data->rewrites[0];
		}

		$delimPos = strpos( $spinText, static::TITLE_DELIM );
		$title = substr( $spinText, 0, $delimPos );
		$content = substr( $spinText, $delimPos + strlen( static::TITLE_DELIM ) );

		if ( $src->settings->waiRevisions ) {
			$post->meta[ ImportedPost::ORIG_TITLE ] = $post->title;
			$post->meta[ ImportedPost::ORIG_CONTENT ] = $post->content;
		}

		if ( $src->settings->waiEnableContent ) {
			$post->content = $content;
		}

		if ( $src->settings->waiEnableTitle ) {
			$post->title = $title;
		}

		return $post;
	}

	/** @return Result<WaiAccountInfo> */
	public function getAccountInfo(
		?string $email = null,
		?string $apiKey = null
	): Result {
		$result = $this->request(
			'account',
			array(
				'email' => $email ?? $this->email,
				'key' => $apiKey ?? $this->apiKey,
			)
		);

		if ( $result->isErr() ) {
			return $result;
		}

		$data = $result->get();

		$info = new WaiAccountInfo();
		$info->status = (string) $data['status'];
		$info->allowOverages = (bool) $data['Allow Overages'];
		$info->turingLimit = (int) $data['Turing Limit'];
		$info->turingUsage = (int) $data['Turing Usage'];

		return Result::Ok( $info );
	}

	/**
	 * @param array<string,mixed> $args
	 * @return Result<WaiRewriteResult>
	 */
	public function spin( array $args = array() ): Result {
		$cacheKey = md5( serialize( $args ) );
		$cache = get_transient( "wpra_wordai_cache_{$cacheKey}" );

		if ( is_array( $cache ) ) {
			$data = $cache;
		} else {
			$response = $this->request( 'rewrite', $args );
			if ( $response->isErr() ) {
				return $response;
			}
			$data = $response->get();
			set_transient( "wpra_wordai_cache_{$cacheKey}", $data, DAY_IN_SECONDS );
		}

		$rewrite = new WaiRewriteResult();
		$rewrite->status = (string) $data['status'];
		$rewrite->text = (string) $data['text'];
		$rewrite->rewrites = (array) $data['rewrites'];

		return Result::Ok( $rewrite );
	}

	/**
	 * Generic request method for the WordAi API.
	 *
	 * @param string              $route
	 * @param array<string,mixed> $data
	 * @return Result<array<string,mixed>>
	 */
	protected function request( string $route, array $data = array() ): Result {
		$url = self::BASE_URL . $route;
		$body = array_merge(
			array(
				'email' => $this->email,
				'key' => $this->apiKey,
			),
			$data
		);

		set_time_limit( 180 );

		$response = wp_remote_post(
			$url,
			array(
				'body' => $body,
				'timeout' => apply_filters( 'wprss_wordai_api_timeout', 180 ),
				'blocking' => true,
			)
		);

		if ( is_wp_error( $response ) ) {
			return Result::Err( $response->get_error_message() );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		// Invalid credentials
		if ( $code === 401 || $code === 403 ) {
			return Result::Err(
				sprintf(
					__( 'Your WordAi email or API key might be incorrect (Status code %s)', 'wp-rss-aggregator-premium' ),
					$code
				)
			);
		}

		$body = wp_remote_retrieve_body( $response );

		// Other errors
		if ( $code >= 400 ) {
			return Result::Err(
				$body . ' ' . sprintf( __( 'Status code: %d', 'wp-rss-aggregator-premium' ), $code )
			);
		}

		$decoded = @json_decode( $body, true );

		// Invalid JSON body
		if ( ! is_array( $decoded ) ) {
			$type = is_object( $decoded ) ? get_class( $decoded ) : gettype( $decoded );
			$excerpt = substr( (string) $body, 0, 50 );
			$excerpt = strlen( (string) $body ) > 50
				? $excerpt . '...'
				: $excerpt;

			return Result::Err(
				sprintf(
					__( 'The API returned a malformed JSON response: (%1$s) %2$s', 'wp-rss-aggregator-premium' ),
					$type,
					$excerpt
				),
			);
		}

		return Result::Ok( $decoded );
	}

	protected function boolStr( bool $bool ): string {
		return $bool ? 'true' : 'false';
	}
}
