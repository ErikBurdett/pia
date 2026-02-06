<?php

namespace RebelCode\Aggregator\Basic\Conditions;

use DomainException;
use RebelCode\Aggregator\Core\Utils\ArraySerializable;
use RebelCode\Aggregator\Core\Utils\Arrays;

class Condition implements ArraySerializable, Evaluable {

	public bool $isAnd;
	/** @var list<Expression> */
	public array $exprs;

	/**
	 * @param bool             $isAnd True for an AND group, false for an OR group.
	 * @param list<Expression> $exprs The expressions.
	 */
	public function __construct( bool $isAnd, array $exprs = array() ) {
		$this->isAnd = $isAnd;
		$this->exprs = $exprs;
	}

	public function eval( ConditionSystem $sys, $input ): bool {
		foreach ( $this->exprs as $expr ) {
			$result = (bool) $expr->eval( $sys, $input );

			if ( $result !== $this->isAnd ) {
				return $result;
			}
		}

		return $this->isAnd;
	}

	public function toArray(): array {
		return array(
			'isAnd' => $this->isAnd,
			'exprs' => Arrays::toArrayAll( $this->exprs ),
		);
	}

	/** @param array<string,mixed> $array */
	public static function fromArray( array $array ): self {
		$isAnd = $array['isAnd'] ?? null;
		$exprsArray = $array['exprs'] ?? $array['conditions'] ?? null;

		if ( ! is_bool( $isAnd ) ) {
			throw new DomainException( 'Invalid "isAnd" in group array' );
		}

		if ( ! is_array( $exprsArray ) ) {
			throw new DomainException( 'Invalid "exprs" in group array' );
		}

		$exprs = array();
		foreach ( $exprsArray as $subArray ) {
			$exprs[] = Expression::fromArray( $subArray );
		}

		return new self( $isAnd, $exprs );
	}
}
