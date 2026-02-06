<?php

namespace RebelCode\Aggregator\Basic\Layouts;

use RebelCode\Aggregator\Core\IrPost;
use RebelCode\Aggregator\Core\ImportedPost;
use RebelCode\Aggregator\Core\Display\LayoutTrait;
use RebelCode\Aggregator\Core\Display\LayoutInterface;
use RebelCode\Aggregator\Core\Display\DisplayState;

class GridLayout implements LayoutInterface {

	use LayoutTrait;

	public function getStyleId(): ?string {
		return 'wpra-grid-layout-css';
	}

	public function getScriptId(): ?string {
		return 'wpra-displays';
	}

	/** @param iterable<IrPost> $posts */
	public function render( iterable $posts, DisplayState $state ): string {
		$items = $this->renderItems( $posts, fn ( IrPost $post ) => $this->item( $post, $state ) );
		$style = "--wpra-grid-max-cols: {$this->ds->gridMaxColumns};";
		$htmlClass = esc_attr( $this->ds->htmlClass );

		return <<<HTML
            <div class="wp-rss-aggregator wpra-grid-template {$htmlClass}" data-page="{$state->page}" style="{$style}">
                <div class="wpra-item-grid rss-aggregator">
                    {$items}
                </div>
            </div>
        HTML;
	}

	protected function item( IrPost $post, DisplayState $state ): string {
		$maxCols = $this->ds->gridMaxColumns ?: 1;

		$url = $this->getItemUrl( $post );
		$imgUrl = $post->ftImage ? $post->ftImage->url : '';

		$style = '';
		$className = 'wpra-grid-item';

		if ( ! $this->ds->gridFitImages ) {
			$className .= ' wpra-grid-item--fill-image';
		}

		if ( $this->ds->gridUseImageAsBg && $imgUrl ) {
			$className .= ' wpra-grid-item--image-background';
			$style = "background-image: url({$imgUrl});";
			if ( 0 !== (int) $this->ds->imageHeight ) {
				$style .= " height: {$this->ds->imageHeight}px;";
			}
		}

		if ( ! $this->ds->gridEnableBorders ) {
			$className .= ' wpra-grid-item--no-borders';
		}

		if ( $this->ds->gridAlignLastToBottom ) {
			$className .= ' wpra-grid-item--pull-last-item';
		}

		if ( $this->ds->gridItemClickable ) {
			$className .= ' wpra-grid-item--link';
			$attrs = $this->linkAttrs( $url, $className );
			$open = sprintf( '<a %s style="%s">', $attrs, $style );
			$close = '</a>';
		} else {
			$open = sprintf( '<div class="%s" style="%s">', $className, $style );
			$close = '</div>';
		}

		$inner = '';
		foreach ( $this->ds->gridComponents as $comp ) {
			if ( $comp['enabled'] ?? false ) {
				$inner .= $this->renderComp( $post, $state, $comp['type'] ?? '' );
			}
		}

		return <<<HTML
            <div class="wpra-item feed-item wpra-item--1of{$maxCols}">
                {$open}
                    <div class="wpra-grid-item__content">
                        {$inner}
                    </div>
                {$close}
            </div>
        HTML;
	}

	protected function renderComp( IrPost $post, DisplayState $state, string $comp ): string {
		switch ( $comp ) {
			case 'title':
				return $this->titleComp( $post );
			case 'excerpt':
				return $this->excerptComp( $post );
			case 'audio':
				return $this->renderAudioPlayer( $post );
			case 'image':
				return $this->imageComp( $post, $state );
			case 'info':
				return $this->infoComp( $post );
		}
		return '';
	}

	protected function titleComp( IrPost $post ): string {
		if ( ! $this->ds->enableTitles ) {
			return '';
		}

		$linkTitle = ! $this->ds->gridItemClickable;

		return <<<HTML
            <div class="wpra-grid-item__item wpra-grid-item__title">
                {$this->renderTitle($post,$linkTitle)}
            </div>
        HTML;
	}

	protected function excerptComp( IrPost $post ): string {
		if ( ! $this->ds->enableExcerpts ) {
			return '';
		}

		$linkExcerpt = ! $this->ds->gridItemClickable;

		return <<<HTML
            <div class="wpra-grid-item__item wpra-grid-item__excerpt">
                {$this->renderExcerpt($post,$linkExcerpt)}
            </div>
        HTML;
	}

	protected function imageComp( IrPost $post, DisplayState $state ): string {
		$url = $this->getItemUrl( $post );
		$imgUrl = $post->ftImage ? $post->ftImage->url : '';

		if ( ! $this->ds->enableImages || ! $imgUrl || $this->ds->gridUseImageAsBg ) {
			return '';
		}

		$imgWrapClass = 'wpra-grid-item__item wpra-grid-item__image';

		if ( $this->ds->linkImages && ! $this->ds->gridItemClickable ) {
			$imgWrap = 'a';
			$imgWrapAttrs = $this->linkAttrs( $url, $imgWrapClass );
		} else {
			$imgWrap = 'div';
			$imgWrapAttrs = sprintf( 'class="%s"', $imgWrapClass );
		}

		$thumbOpen = '';
		$thumbImg = '';
		$thumbEmbed = '';
		$thumbClose = '';

		$ytEmbedUrl = $post->getSingleMeta( ImportedPost::YT_EMBED_URL );
		$hasEmbed = $this->ds->gridEnableEmbeds && $ytEmbedUrl;

		if ( $this->ds->gridFitImages && ! $hasEmbed ) {
			if ( $this->ds->linkImages && ! $this->ds->gridItemClickable ) {
				$thumbOpen = sprintf( '<a %s>', $imgWrapAttrs );
				$thumbClose = '</a>';
			}

			$thumbImg = get_the_post_thumbnail( $post->postId, array( $this->ds->imageWidth, $this->ds->imageHeight ) );
		} else {
			$thumbOpen = sprintf(
				'<%s %s style="background-image: url(%s); height: %s;">',
				$imgWrap,
				$imgWrapAttrs,
				$imgUrl,
				$this->ds->imageHeight ? $this->ds->imageHeight . 'px' : 'auto'
			);
			$thumbClose = "</{$imgWrap}>";
		}

		if ( $hasEmbed ) {
			$embedHeight = $this->ds->imageHeight ?: 'auto';
			$thumbEmbed = <<<HTML
                <iframe
                    class="wpra-grid-item__video"
                    src="{$ytEmbedUrl}"
                    width="100%" height="{$embedHeight}" frameborder="0"
                    allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen>
                </iframe>
            HTML;
		}

		return $thumbOpen . $thumbImg . $thumbEmbed . $thumbClose;
	}

	protected function infoComp( IrPost $post ): string {
		$infoBlockClass = $this->ds->gridInfoBlocks ? 'block' : '';
		$stackItems = $this->ds->gridStackInfoItems ?? false;

		if ($stackItems) {
			$infoBlockClass .= ' wpra-info-items-stacked';
		}

		$infoInner = '';
		foreach ( $this->ds->gridInfoComponents as $comp ) {
			if ( $comp['enabled'] ?? false ) {
				$childOutput = $this->infoCompChild( $post, $comp['type'] ?? '' );
				if ( ! empty( $childOutput ) ) {
					if ( $stackItems ) {
						$infoInner .= "<div class=\"wpra-stacked-info-item\">{$childOutput}</div>";
					} else {
						$infoInner .= $childOutput;
					}
				}
			}
		}

		return <<<HTML
            <div class="wpra-grid-item__item wpra-grid-item__information {$infoBlockClass}">
                {$infoInner}
            </div>
        HTML;
	}

	protected function infoCompChild( IrPost $post, string $info ): string {
		switch ( $info ) {
			case 'author':
				return $this->renderAuthor( $post );
			case 'date':
				return $this->renderDate( $post, true );
			case 'source':
				$linkSrc = ! $this->ds->gridItemClickable;
				return $this->renderSource( $post, true, $linkSrc );
		}
		return '';
	}
}
