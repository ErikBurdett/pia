<?php

namespace RebelCode\Aggregator\Basic\Conditions;

use DomainException;
use RebelCode\Aggregator\Core\Utils\ArraySerializable;

class Expression implements ArraySerializable, Evaluable {

	public string $subjectId;
	public string $operatorId;
	/** @var array<string,mixed> */
	public array $args;

	/**
	 * @param string              $subjectId The ID of the subject.
	 * @param string              $operatorId The ID of the operator.
	 * @param array<string,mixed> $args The operator arguments.
	 */
	public function __construct( string $subjectId, string $operatorId, array $args = array() ) {
		$this->subjectId = $subjectId;
		$this->operatorId = $operatorId;
		$this->args = $args;
	}

	public function eval( ConditionSystem $sys, $input ) {
		$subject = $sys->getSubject( $this->subjectId );
		$operator = $sys->getOperator( $this->operatorId );

		$value = $subject->getValue( $input );

		$args = array();
		foreach ( $operator->params as $key => $param ) {
			$args[ $key ] = $this->args[ $key ] ?? $param->default;
		}

		return $operator->eval( $value, $args );
	}

	public function toArray(): array {
		return array(
			'subjectId' => $this->subjectId,
			'operatorId' => $this->operatorId,
			'args' => $this->args,
		);
	}

	/** @param array<string,mixed> $array */
	public static function fromArray( array $array ): self {
		$subject = $array['subjectId'] ?? null;
		$operator = $array['operatorId'] ?? null;
		$args = $array['args'] ?? null;

		if ( ! is_string( $subject ) ) {
			throw new DomainException( 'Invalid "subjectId" in condition array' );
		}

		if ( ! is_string( $operator ) ) {
			throw new DomainException( 'Invalid "operatorId" in condition array' );
		}

		if ( ! is_array( $args ) ) {
			throw new DomainException( 'Invalid "args" in condition array' );
		}

		return new self( $subject, $operator, $args );
	}
}
