<?php

namespace RebelCode\Aggregator\Plus;

use RebelCode\Aggregator\Pro\Source\ContentCleaner;
use RebelCode\Aggregator\Plus\Source\TaxonomyRule;
use RebelCode\Aggregator\Plus\Source\PostDateToUse;
use RebelCode\Aggregator\Plus\Source\ExcerptToUse;
use RebelCode\Aggregator\Core\Utils\Html;
use RebelCode\Aggregator\Core\Source;
use RebelCode\Aggregator\Core\RssReader\RssItem;
use RebelCode\Aggregator\Core\Logger;
use RebelCode\Aggregator\Core\Importer\IrPostBuilder;
use RebelCode\Aggregator\Basic\Conditions\ConditionSystem;
use Masterminds\HTML5;
use DateTime;
use DOMNode;
use DOMDocument;

class PlusIrPostBuilder {

	// Todo: cut the builder from plus and move AuthorMethod AuthorToUse to core.
	private IrPostBuilder $builder;
	private ConditionSystem $taxCondSys;

	public function __construct( IrPostBuilder $builder, ConditionSystem $taxCondSys ) {
		$this->builder = $builder;
		$this->taxCondSys = $taxCondSys;
	}

	public function buildDates( array $dates, Source $src ): array {
		$now = new DateTime();
		[$pubDate, $modDate] = $dates;

		$whichDate = $src->settings->whichPostDate;

		switch ( $whichDate ) {
			default:
				Logger::warning( "Invalid post date setting \"{$whichDate}\", using published date." );
				// no break 👇
			case PostDateToUse::PUBLISHED_DATE:
				$pubDate = $pubDate ?? $modDate ?? $now;
				$modDate = $modDate ?? $pubDate ?? $now;
				break;
			case PostDateToUse::IMPORT_DATE:
				$pubDate = $now;
				$modDate = $now;
				break;
		}

		return array( $pubDate, $modDate );
	}

	public function buildExcerpt( string $excerpt, string $content, Source $src ): string {
		$excerpt = trim( $excerpt );

		if (
			$excerpt === '' || ( $src->settings->whichExcerpt === ExcerptToUse::GENERATE
			&& $src->settings->genMissingExcerpt )
		) {
			$excerpt = trim( $content );
			$numWords = $src->settings->excerptGenNumWords;
			$suffix = $src->settings->excerptGenSuffix;
		} else {
			$numWords = $src->settings->excerptNumWords;
			$suffix = $src->settings->excerptSuffix;
		}

		$excerpt = wp_strip_all_tags( $excerpt );

		if ( '' === $excerpt ) {
			return '';
		}

		// Whenever there is no limit just pass the excerpt.
		if ( $numWords ) {
			$allowedTags = array_merge( Html::WP_ALLOWED_CONTENT_TAGS, array( 'img', 'figure' ) );
			$excerpt = Html::trimHtmlWords( $excerpt, $numWords, $suffix, $allowedTags );
		}

		return $excerpt;
	}

	public function buildContent( string $content, RssItem $item, Source $src ): string {
		$trim = $src->settings->trimContent;
		$numWords = $src->settings->contentNumWords;
		$suffix = $src->settings->excerptSuffix;

		if ( $trim && $numWords > 0 ) {
			$content = apply_filters( 'wpra.importer.post.content.beforeTrim', $content, $item, $src );
			$allowedTags = array_merge( Html::WP_ALLOWED_CONTENT_TAGS, array( 'img', 'figure' ) );
			$content = Html::trimHtmlWords( $content, $numWords, $suffix, $allowedTags );
			$content = apply_filters( 'wpra.importer.post.content.afterTrim', $content, $item, $src );
		}

		/** @var ContentCleaner $cleaner */
		foreach ( $src->settings->contentCleaners as $cleaner ) {
			$content = $cleaner->run( $content );
		}

		return $content;
	}

	public function removeImgFromContent( string $content, string $imgUrl ): string {
		$html = sprintf( '<div>%s</div>', $content );

		$parser = new HTML5( array( 'disable_html_ns' => true ) );
		$dom = $parser->loadHTML( $html );
		$dom = self::removeImgsFromDom( $dom, trim( $imgUrl ) );

		if ( $dom instanceof DOMDocument ) {
			return $dom->saveHTML() ?: $content;
		}

		return $content;
	}

	/** @return array<string,list<IrTerm>> */
	public function buildTerms( string $postType, RssItem $item, Source $src ): array {
		$results = array();

		/** @var TaxonomyRule[] $rules */
		foreach ( $src->settings->taxonomies as $taxonomy => $rules ) {
			if ( ! is_object_in_taxonomy( $postType, $taxonomy ) ) {
				continue;
			}

			$results[ $taxonomy ] ??= array();

			foreach ( $rules as $rule ) {
				$terms = $rule->eval( $this->taxCondSys, $taxonomy, $item );

				foreach ( $terms as $term ) {
					$results[ $taxonomy ][] = $term;
				}
			}
		}

		return $results;
	}

	private function removeImgsFromDom( DOMNode $node, string $url ): DOMNode {
		for ( $i = 0; $i < $node->childNodes->length; $i++ ) {
			$child = $node->childNodes->item( $i );

			if ( $child->nodeName === 'figure' ) {
				// Check if the figure contains an img with the specified URL
				$img = $child->getElementsByTagName( 'img' )->item( 0 );
				if ( $img ) {
					$src = $img->attributes->getNamedItem( 'src' );
					$srcSet = $img->attributes->getNamedItem( 'srcset' );

					if (
						( $src !== null && trim( $src->nodeValue ) === $url )
						||
						( $srcSet !== null && str_contains( $srcSet->nodeValue, $url ) )
					) {
						$node->removeChild( $child ); // Remove the entire figure tag
						continue;
					}
				}
			}

			// Existing img removal logic
			if ( $child->nodeName === 'img' ) {
				$src = $child->attributes->getNamedItem( 'src' );
				$srcSet = $child->attributes->getNamedItem( 'srcset' );

				if (
					( $src !== null && trim( $src->nodeValue ) === $url )
					||
					( $srcSet !== null && str_contains( $srcSet->nodeValue, $url ) )
				) {
					$node->removeChild( $child );
				}
			}

			// Recursively check child nodes
			if ( $child && $child->childNodes->length > 0 ) {
				self::removeImgsFromDom( $child, $url );
			}
		}

		return $node;
	}
}
