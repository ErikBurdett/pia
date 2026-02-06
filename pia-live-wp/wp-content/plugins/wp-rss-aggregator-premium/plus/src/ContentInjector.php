<?php

namespace RebelCode\Aggregator\Plus;

use RebelCode\Aggregator\Plus\Templates\TokenRenderer;
use RebelCode\Aggregator\Plus\Templates\Ctx\IrPostSourceCtx;
use RebelCode\Aggregator\Core\Source;
use RebelCode\Aggregator\Core\IrPost;
use RebelCode\Aggregator\Core\ImportedPost;

class ContentInjector {

	public TokenRenderer $tokRenderer;
	public bool $doFeeds;

	public function __construct( TokenRenderer $tokRenderer, bool $doFeeds ) {
		$this->tokRenderer = $tokRenderer;
		$this->doFeeds = $doFeeds;
	}

	/** @param array{single?:bool,feed?:bool,archive?:bool,postType?:string} $args */
	public function inject( IrPost $post, Source $src, array $args = array() ): IrPost {
		$postType = $args['postType'] ?? 'post';
		$isFeed = $args['feed'] ?? false;
		$isSingle = $args['single'] ?? false;
		$isArchive = $args['archive'] ?? false;

		// Compatibility with Newspaper theme
		if ( $postType === 'tdb_templates' ) {
			return $post;
		}

		if ( $isFeed && ! $this->doFeeds ) {
			return $post;
		}

		$ss = $src->settings;
		$ctx = new IrPostSourceCtx( $post, $src );
		$canShowAudio = $isSingle || $isFeed || $isArchive;
		$newContent = '';

		if ( $ss->enableAttribution && ( $isSingle || ! $ss->attributionSingleOnly ) && $ss->attributionPosition === 'before' ) {
			$newContent .= $this->tokRenderer->renderTemplate( $ss->attributionTemplate, $ctx );
		}

		if ( $ss->enablePreContent && ( $isSingle || ! $ss->preContentSingleOnly ) ) {
			$newContent .= $this->tokRenderer->renderTemplate( $ss->preContentTemplate, $ctx );
		}

		if ( $ss->enableAudioPlayer && $canShowAudio && $ss->audioPlayerPos === 'before' ) {
			$newContent .= $this->audioPlayer( $post );
		}

		$newContent .= $post->content;

		if ( $ss->enableAudioPlayer && $canShowAudio && $ss->audioPlayerPos === 'after' ) {
			$newContent .= $this->audioPlayer( $post );
		}

		if ( $ss->enablePostContent && ( $isSingle || ! $ss->postContentSingleOnly ) ) {
			$newContent .= $this->tokRenderer->renderTemplate( $ss->postContentTemplate, $ctx );
		}

		if ( $ss->enableAttribution && ( $isSingle || ! $ss->attributionSingleOnly ) && $ss->attributionPosition === 'after' ) {
			$newContent .= $this->tokRenderer->renderTemplate( $ss->attributionTemplate, $ctx );
		}

		$post->content = $newContent;

		return $post;
	}

	public function attachAttribution( IrPost $post, Source $src ) {
		$ss = $src->settings;

		if ( ! $ss->enableAttribution ) {
			return $post;
		}

		$ctx = new IrPostSourceCtx( $post, $src );
		$post->attribution = $this->tokRenderer->renderTemplate( $ss->attributionTemplate, $ctx );

		return $post;
	}

	public function attachAudioPlayer( IrPost $post, Source $src ) {
		$ss = $src->settings;

		if ( ! $ss->enableAudioPlayer ) {
			return $post;
		}

		$post->audio = $this->audioPlayer( $post );

		return $post;
	}

	/** @param array<string,mixed> $ctx */
	protected function audioPlayer( IrPost $post ): string {
		$audioUrl = esc_url( $post->getSingleMeta( ImportedPost::AUDIO_URL, '' ) );

		if ( empty( $audioUrl ) ) {
			return '';
		}

		return <<<HTML
        <div class="wpra-audio-player-block wprss-ftp-audio-player">
            <audio class="wpra-audio-player" controls="controls" style="width: 100%">
                <source src="{$audioUrl}" />
            </audio>
        </div>
        HTML;
	}
}
