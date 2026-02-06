<?php

declare(strict_types=1);

namespace RebelCode\Aggregator\Pro\Source;

use Throwable;
use Symfony\Component\CssSelector\CssSelectorConverter;
use RebelCode\Aggregator\Core\Utils\ArraySerializable;
use RebelCode\Aggregator\Core\Logger;
use Masterminds\HTML5;
use DOMXPath;
use DOMText;
use DOMElement;
use DOMDocument;

class ContentCleaner implements ArraySerializable {

	public const REMOVE_ELEM = 'remove_elem';
	public const REMOVE_ATTR = 'remove_attr';
	public const REMOVE_KEEP_CONTENT = 'remove_keep_content';
	public const KEEP_ELEM = 'keep_elem';

	public string $type;
	public string $selector;
	private CssSelectorConverter $cssConverter;

	public function __construct( string $type, string $selector ) {
		$this->type = $type;
		$this->selector = $selector;
		$this->cssConverter = new CssSelectorConverter();
	}

	public function run( string $content ): string {
		$doc = $this->parseHtmlDoc( $content );

		switch ( $this->type ) {
			case self::REMOVE_ELEM:
				$nodes = $this->querySelector( $this->selector, $doc );
				foreach ( $nodes as $node ) {
					$node->remove();
				}
				return $doc->saveHTML() ?: $content;

			case self::REMOVE_KEEP_CONTENT:
				$nodes = $this->querySelector($this->selector, $doc);

				foreach ($nodes as $node) {
					if (!$node instanceof DOMElement) {
						continue;
					}

					$parentNode = $node->parentNode;

					if (!$parentNode) {
						$ancestor = $node;
						while ($ancestor && !$ancestor->parentNode && !$ancestor instanceof DOMDocument) {
							$ancestor = $ancestor->parentNode;
						}
						$parentNode = $ancestor->parentNode ?? null;
					}

					if (!$parentNode) {
						Logger::error( "Warning: No valid parentNode found for the node with selector: {$this->selector}" );
						continue;
					}

					try {
						$parentNode->insertBefore(new DOMText($node->textContent), $node);
						$node->remove();
					} catch (Exception $e) {
						Logger::error( $e->getMessage() );
						continue;
					}
				}

				return $doc->saveHTML() ?: $content;

			case self::KEEP_ELEM:
				case self::KEEP_ELEM:
					$allNodes = $this->querySelector('*', $doc);
					$keepNodes = $this->querySelector($this->selector, $doc);

					$keepNodesSet = [];
					foreach ($keepNodes as $node) {
						$keepNodesSet[spl_object_hash($node)] = true;

						$current = $node->parentNode;
						while ($current) {
							$keepNodesSet[spl_object_hash($current)] = true;
							$current = $current->parentNode;
						}
					}

					foreach ($allNodes as $node) {
						if (isset($keepNodesSet[spl_object_hash($node)]) || in_array($node->nodeName, ['html', 'body'])) {
							continue;
						}

						if ($node->parentNode) {
							$node->parentNode->removeChild($node);
						}
					}

					return $doc->saveHTML() ?: $content;

			case self::REMOVE_ATTR:
				$lBracket = strrpos( $this->selector, '[' );
				$rBracket = strrpos( $this->selector, ']', $lBracket ?: 0 );
				if ( $lBracket === false || $rBracket === false ) {
					return $content;
				}

				$inside = substr( $this->selector, $lBracket + 1, $rBracket - $lBracket - 1 );
				$end = strrpos( $inside, '=' ) ?: strlen( $inside );
				$attr = substr( $inside, 0, $end );
				$attr = rtrim( $attr, '^$*|' );

				$nodes = $this->querySelector( $this->selector, $doc );
				foreach ( $nodes as $node ) {
					if ( $node instanceof DOMElement ) {
						$node->removeAttribute( $attr );
					}
				}
				return $doc->saveHTML() ?: $content;
		}

		return $content;
	}

	private function parseHtmlDoc( string $html ): DOMDocument {
		$parser = new HTML5( array( 'disable_html_ns' => true ) );
		$dom = $parser->loadHTML( $html );
		return $dom;
	}

	/** @return iterable<DOMElement> */
	private function querySelector( string $selector, DOMDocument $doc ): iterable {
		$selector = trim( $selector, " ,\n\r\t\v\0" );
		if ( strlen( $selector ) === 0 ) {
			return array();
		}

		try {
			$xpath = $this->cssConverter->toXPath( $selector, '//' );
		} catch ( Throwable $t ) {
			Logger::warning(
				sprintf(
					__( 'Failed to remove unwanted content: %s', 'wp-rss-aggregator-premium' ),
					$t->getMessage()
				)
			);
			return array();
		}

		$domXpath = new DOMXPath( $doc );
		$results = $domXpath->query( $xpath );

		return $results ?: array();
	}

	public function toArray(): array {
		return array(
			'type' => $this->type,
			'selector' => $this->selector,
		);
	}

	/** @param array<string,mixed> $array */
	public static function fromArray( array $array ): self {
		$type = $array['type'] ?? '';
		$selector = $array['selector'] ?? '';

		return new self( $type, $selector );
	}
}
