<?php

namespace RebelCode\Aggregator\Pro;

use RuntimeException;
use RebelCode\Aggregator\Core\Utils\Result;
use RebelCode\Aggregator\Core\Source;
use RebelCode\Aggregator\Core\Logger;
use RebelCode\Aggregator\Core\Licensing\License;
use RebelCode\Aggregator\Core\Licensing;
use RebelCode\Aggregator\Core\IrPost;

class FullTextClient {

	private string $url;
	private Licensing $licensing;

	public function __construct( string $url, Licensing $licensing ) {
		$this->url = $url;
		$this->licensing = $licensing;
	}

	/**
	 * Gets the full text for a single article.
	 *
	 * @param string $url The URL of the article.
	 * @param string $rules Optional full text rules.
	 * @return Result<string> The full text of the article.
	 */
	public function getArticleFullText( string $url, string $rules = '' ): Result {
		$requestUrl = $this->buildRequestUrl( $url, false, $rules );
		$response = wp_remote_get( $requestUrl, array( 'timeout' => 60 ) );

		if ( is_wp_error( $response ) ) {
			return Result::Err( $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body );

		if ( isset( $data->content ) ) {
			return Result::Ok( $data->content );
		}

		return Result::Err( 'Full text content not found in response' );
	}

	/**
	 * Gets the URL of the full-text version of an RSS feed.
	 *
	 * @param string $url The URL of the RSS feed.
	 * @param string $rules Optional full text rules.
	 * @return string The URL of the full-text version of the RSS feed.
	 */
	public function getFullTextFeedUrl( string $url, string $rules = '' ): string {
		return $this->buildRequestUrl( $url, true, $rules );
	}

	/**
	 * Uses the single-article API to fetch the full text for an IR Post.
	 * The full content is written into the IR Post's `content` property.
	 *
	 * @param IrPost $post The IR Post to build.
	 * @return Result<IrPost> The built IR Post.
	 */
	public function buildIrPost( IrPost $post ): Result {
		$result = $this->getArticleFullText( $post->url );

		if ( $result->isErr() ) {
			return $result;
		}

		$newContent = $result->get();

		// Sanity check
		$prevLen = strlen( $post->content );
		$newLen = strlen( $newContent );

		if ( $newLen < $prevLen ) {
			Logger::notice(
				sprintf(
					__( 'The fetched full text content is shorter (%1$d characters) than the RSS feed content (%2$d characters) for post: %3$s', 'wp-rss-aggregator-premium' ),
					$prevLen,
					$newLen,
					$post->url
				)
			);
		}

		$post->content = $newContent;

		return Result::Ok( $post );
	}

	/** Checks the source's settings to conditionally build full text for a post. */
	public function maybeBuildIrPost( IrPost $post, Source $src ): IrPost {
		$result = $this->buildIrPost( $post );

		if ( $result->isErr() ) {
			Logger::warning( 'Failed to fetch full text for post. Cause: ' . $result->error()->getMessage() );
			return $post;
		}

		return $result->get();
	}

	/**
	 * Builds a request URL.
	 *
	 * @param string $url The URL of the RSS feed or article.
	 * @param bool   $batch Set to true if the $url points to an RSS feed.
	 * @param string $rules Optional full text rules.
	 */
	protected function buildRequestUrl( string $url, bool $batch = false, string $rules = '' ): string {
		$license = $this->licensing->getLicense();
		if ( $license === null || $license->status !== License::Valid ) {
			throw new RuntimeException( __( 'A valid Pro license is required to use the full text feature', 'wp-rss-aggregator-premium' ) );
		}

		$args = array(
			'url' => $url,
			'license' => $license->key,
			'site' => network_home_url(),
		);

		$rules = trim( $rules );
		if ( strlen( $rules ) > 0 ) {
			$args['siteconfig'] = $rules;
		}

		$query = http_build_query( $args, '', '&', PHP_QUERY_RFC3986 );

		if ( $batch ) {
			$requestUrl = "$this->url/makefulltextfeed.php?$query";
		} else {
			$requestUrl = "$this->url/extract.php?$query";
		}

		return apply_filters( 'wpra.fulltext.request_url', $requestUrl, $args, $batch );
	}
}
