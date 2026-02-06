<?php

namespace RebelCode\Aggregator\Plus\Templates;

use DomainException;
use RebelCode\Aggregator\Core\Utils\ArraySerializable;

class Token implements ArraySerializable {

	public string $type;
	/** @var array<string,mixed> */
	public array $args;

	/** @param array<string,mixed> $args */
	public function __construct( string $type, array $args ) {
		$this->type = $type;
		$this->args = $args;
	}

	public function toArray(): array {
		return array(
			'type' => $this->type,
			'args' => $this->args,
		);
	}

	/** @param array<string,mixed> $array */
	public static function fromArray( array $array ): self {
		$type = $array['type'] ?? null;
		$args = $array['args'] ?? null;

		if ( ! is_string( $type ) ) {
			throw new DomainException( 'Invalid "type" in token array' );
		}
		if ( ! is_array( $args ) ) {
			throw new DomainException( 'Invalid "args" in token array' );
		}

		return new self( $type, $args );
	}
}
