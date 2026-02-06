<?php

namespace RebelCode\Aggregator\Basic\Layouts;

use RebelCode\Aggregator\Core\IrPost;
use RebelCode\Aggregator\Core\Display\LayoutTrait;
use RebelCode\Aggregator\Core\Display\LayoutInterface;
use RebelCode\Aggregator\Core\Display\DisplayState;

class EtLayout implements LayoutInterface {

	use LayoutTrait;

	public function getStyleId(): ?string {
		return 'wpra-et-layout-css';
	}

	public function getScriptId(): ?string {
		return 'wpra-displays';
	}

	/** @param iterable<IrPost> $posts */
	public function render( iterable $posts, DisplayState $state ): string {
		$listClass = '';
		if ( $this->ds->enableBullets ) {
			$listClass = 'wpra-item-et--bullets wpra-item-et--' . $this->ds->bulletStyle;
		}

		if ( $this->ds->enableBullets && $this->ds->bulletStyle === 'numbers' ) {
			$listType = 'ol';
		} else {
			$listType = 'ul';
		}

		$listStart = ( $state->page - 1 ) * $this->ds->numItems + 1;
		$listItems = $this->renderItems( $posts, fn ( IrPost $post ) => $this->item( $post ) );

		return <<<HTML
            <div class="wp-rss-aggregator wpra-et-template {$this->ds->htmlClass}" data-page="{$state->page}">
                <{$listType} class="rss-aggregator wpra-et-legacy wpra-item-et {$listClass}" start="{$listStart}">
                    {$listItems}
                </{$listType}>
            </div>
        HTML;
	}

	protected function item( IrPost $post ): string {
		$thumbPlace = $this->getLegacyPlacement();

		$beforeTitle = '';
		$closeDivs = '';
		if ( $thumbPlace === 'item-side' || $thumbPlace === 'item-top' ) {
			$thumbnail = $this->thumbnail( $post );
			$beforeTitle = <<<HTML
                <div class="feed-item-content {$thumbPlace}">
                {$thumbnail}
                <div class="feed-item-text-content {$thumbPlace}">
            HTML;
			$closeDivs = '</div></div>';
		}

		$beforeExcerpt = '';
		if ( $thumbPlace === 'excerpt-side' ) {
			$beforeExcerpt = $this->thumbnail( $post );
		}

		$excerpt = '';
		if ( $this->ds->enableExcerpts ) {
			if ( $thumbPlace === 'excerpt-text' ) {
				$thumbnail = $this->thumbnail( $post );
			} else {
				$thumbnail = '';
			}

			$excerpt = <<<HTML
                <div class="thumbnail-excerpt wprss-feed-excerpt">
                    {$thumbnail}
                    {$this->renderExcerpt($post)}
                </div>
            HTML;
		}

		return <<<HTML
            <li class="feed-item {$this->ds->htmlClass}">
                {$beforeTitle}

                {$this->renderTitle($post)}

                <div class="thumbnail-excerpt wprss-feed-thumbnail">
                    {$beforeExcerpt}
                    {$excerpt}
                </div>

                {$this->renderAudioPlayer($post)}

                <div class="wprss-feed-meta">
                    {$this->renderSource($post)}
                    {$this->renderDate($post)}
                    {$this->renderAuthor($post)}
                </div>

                {$closeDivs}
            </li>
        HTML;
	}

	protected function thumbnail( IrPost $post ): string {
		if ( ! $this->ds->enableImages ) {
			return '';
		}

		$thumbnail = $post->ftImage ? $post->ftImage->url : '';
		$canShowDef = $this->ds->fallbackToSrcImage;

		if ( empty( $thumbnail ) && ! $canShowDef ) {
			return '';
		}

		$imgTag = get_the_post_thumbnail( $post->postId, array( $this->ds->imageWidth, $this->ds->imageHeight ) );

		if ( $this->ds->linkImages ) {
			return $this->renderLink( $imgTag, $this->getItemUrl( $post ) );
		} else {
			return $imgTag;
		}
	}

	protected function getLegacyPlacement(): string {
		switch ( $this->ds->etStyle ) {
			case 'news':
				return 'excerpt-side';
			case 'wrapped':
				return 'excerpt-text';
			case 'magazine':
				return 'item-side';
			case 'blog':
				return 'item-top';
		}

		return '';
	}
}
