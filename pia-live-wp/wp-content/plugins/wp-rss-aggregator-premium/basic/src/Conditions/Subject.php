<?php

namespace RebelCode\Aggregator\Basic\Conditions;

use RebelCode\Aggregator\Core\Utils\ArraySerializable;

class Subject implements ArraySerializable {

	public string $type;
	public string $label;
	/** @var callable(mixed):mixed */
	public $getter;

	/**
	 * @param string                $type The type, used to get the supported operators in UIs.
	 * @param string                $label The name of the subject, to use in UIs.
	 * @param callable(mixed):mixed $getter A function that takes the condition
	 *        input as argument and returns the value to use for evaluation.
	 */
	public function __construct( string $type, string $label, callable $getter ) {
		$this->type = $type;
		$this->label = $label;
		$this->getter = $getter;
	}

	/**
	 * Gets the value for the subject from a condition input.
	 *
	 * @param mixed $input The condition input.
	 * @return mixed
	 */
	public function getValue( $input ) {
		return call_user_func( $this->getter, $input );
	}

	public function toArray(): array {
		return array(
			'type' => $this->type,
			'label' => $this->label,
		);
	}

	/**
	 * Creates a subject with a string type.
	 *
	 * @param string                $label The name of the subject, to use in UIs.
	 * @param callable(mixed):mixed $getter A function that takes the condition
	 *        input as argument and returns the value to use for evaluation.
	 */
	public static function string( string $label, callable $getter ): self {
		return new self( 'string', $label, $getter );
	}
}
