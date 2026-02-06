<?php

namespace RebelCode\Aggregator\Basic\Conditions;

use LogicException;
use RebelCode\Aggregator\Core\Utils\ArraySerializable;
use RebelCode\Aggregator\Core\Utils\Arrays;

class ConditionSystem implements ArraySerializable {

	/** @var array<string,Subject> */
	public array $subjects;
	/** @var array<string,Operator> */
	public array $operators;
	/** @var array<string,string[]> */
	public array $types;

	/**
	 * @param array<string,Subject>  $subjects The subjects.
	 * @param array<string,Operator> $operators The operators.
	 * @param array<string,string[]> $types A map of types to operator IDs.
	 */
	public function __construct( array $subjects, array $operators, array $types ) {
		$this->subjects = $subjects;
		$this->operators = $operators;
		$this->types = $types;
	}

	/**
	 * Creates a copy with different subjects. Used to create derivations from
	 * a common system.
	 *
	 * @param array<string,Subject> $subjects
	 */
	public function withSubjects( array $subjects ): self {
		return new self( $subjects, $this->operators, $this->types );
	}

	/**
	 * Creates a copy with additional operators. Used to create derivations from
	 * a common system.
	 *
	 * @param array<string,Operator> $operators The additional operators.
	 */
	public function withAddedOperators( array $operators ): self {
		$newOperators = array_merge( $this->operators, $operators );
		return new self( $this->subjects, $newOperators, $this->types );
	}

	/**
	 * Creates a copy with additional types. Used to create derivations from a
	 * common system.
	 *
	 * @param array<string,Operator> $types The additional types.
	 */
	public function withAddedTypes( array $types ): self {
		$newTypes = array_merge_recursive( $this->types, $types );
		return new self( $this->subjects, $this->operators, $newTypes );
	}

	public function getSubject( string $id ): Subject {
		$subject = $this->subjects[ $id ] ?? null;
		if ( $subject === null ) {
			throw new LogicException( sprintf( 'Unknown subject "%s"', $id ) );
		}
		return $subject;
	}

	public function getOperator( string $id ): Operator {
		$operator = $this->operators[ $id ] ?? null;
		if ( $operator === null ) {
			throw new LogicException( sprintf( 'Unknown operator "%s"', $id ) );
		}
		return $operator;
	}

	/**
	 * Evaluates an evaluable object using this system.
	 *
	 * @param mixed $input The input value.
	 * @return mixed The evaluation result.
	 */
	public function eval( Evaluable $evaluable, $input ) {
		return $evaluable->eval( $this, $input );
	}

	public function toArray(): array {
		return array(
			'subjects' => Arrays::toArrayAll( $this->subjects ),
			'operators' => Arrays::toArrayAll( $this->operators ),
			'types' => $this->types,
		);
	}
}
