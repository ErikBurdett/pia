<?php

namespace RebelCode\Aggregator\Basic\Conditions;

use RebelCode\Aggregator\Core\Utils\ArraySerializable;

class Operator implements ArraySerializable {

	public string $label;
	/* @var array<string,OperatorParam> */
	public array $params;
	public $evalFn;

	/**
	 * @param string                       $label The name of the operator. Used in UIs.
	 * @param array<string,OperatorParam>  $params The list of params.
	 * @param callable(mixed,object):mixed $evalFn The eval function. Receives
	 *       the input value and the arguments object and returns the result.
	 */
	public function __construct( string $label, array $params, callable $evalFn ) {
		$this->label = $label;
		$this->params = $params;
		$this->evalFn = $evalFn;
	}

	/**
	 * @param mixed               $input The input value.
	 * @param array<string,mixed> $args A map of param names to arg values.
	 * @return mixed
	 */
	public function eval( $input, array $args ) {
		return call_user_func( $this->evalFn, $input, (object) $args );
	}

	/** Creates a negated version of the operator. */
	public function negate( string $label ): self {
		$evalFn = $this->evalFn;
		return new self(
			$label,
			$this->params,
			function ( $input, $args ) use ( $evalFn ) {
				return ! call_user_func( $evalFn, $input, $args );
			}
		);
	}

	public function toArray(): array {
		return array(
			'label' => $this->label,
			'params' => $this->params,
		);
	}
}
