<?php

namespace RebelCode\Aggregator\Pro\Source;

use RebelCode\Aggregator\Core\IrPost;
use RebelCode\Aggregator\Core\RssReader\RssItem;
use RebelCode\Aggregator\Core\RssReader\RssSelector;
use RebelCode\Aggregator\Core\Utils\ArraySerializable;

class CustomMapping implements ArraySerializable {

	public const FIXED = 'fixed';
	public const RSS_SELECTOR = 'rss_selector';
	public const WP_FILTER = 'wp_filter';

	public string $source;
	public string $metaKey;
	public string $arg;

	public function __construct( string $source, string $metaKey, string $arg ) {
		$this->source = $source;
		$this->metaKey = $metaKey;
		$this->arg = $arg;
	}

	public function map( RssItem $item, IrPost $post ): IrPost {
		$value = $this->value( $item, $post );

		if ( $value !== null ) {
			$post->meta[ $this->metaKey ] = array( $value );
		}

		return $post;
	}

	public function value( RssItem $item, IrPost $post ) {
		switch ( $this->source ) {
			case self::FIXED:
				return $this->arg;

			case self::RSS_SELECTOR:
				$selector = new RssSelector( $this->arg );
				return $selector->resolve( $item );

			case self::WP_FILTER:
				return apply_filters( $this->arg, $item, $post );
		}

		return null;
	}

	/** @return array{selector:string,metaKey:string} */
	public function toArray(): array {
		return array(
			'source' => $this->source,
			'metaKey' => $this->metaKey,
			'arg' => $this->arg,
		);
	}

	/** @param mixed[] $data */
	public static function fromArray( array $data ): self {
		$source = $data['source'] ?? '';
		$metaKey = $data['metaKey'] ?? '';
		$arg = $data['arg'] ?? '';

		return new self( $source, $metaKey, $arg );
	}
}
