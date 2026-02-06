<?php

namespace RebelCode\Aggregator\Plus\Templates;

class TokenType {

	public string $label;
	/** @var callable(mixed,array):string */
	public $renderFn;

	/** @param callable(mixed,array):string $renderFn */
	public function __construct( string $label, callable $renderFn ) {
		$this->label = $label;
		$this->renderFn = $renderFn;
	}

	/**
	 * @param mixed               $ctx The context value.
	 * @param array<string,mixed> $args The token arguments.
	 */
	public function render( $ctx, array $args ): string {
		return call_user_func( $this->renderFn, $ctx, $args );
	}
}
