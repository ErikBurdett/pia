<?php

namespace RebelCode\Aggregator\Plus\Templates;

use RebelCode\Aggregator\Core\IrPost;
use RebelCode\Aggregator\Core\Source;

class TokenList {

	/** @var list<string> */
	public array $tokens;

	/** @param list<string> $tokens */
	public function __construct( array $tokens ) {
		$this->tokens = $tokens;
	}

	public function render( IrPost $post, Source $src ): string {
	}
}
