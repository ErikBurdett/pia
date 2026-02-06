<?php

namespace RebelCode\Aggregator\Basic\Display;

use DomainException;
use RebelCode\Aggregator\Basic\Conditions\Condition;
use RebelCode\Aggregator\Basic\Conditions\ConditionSystem;
use RebelCode\Aggregator\Core\Utils\ArraySerializable;
use RebelCode\Aggregator\Core\Utils\Arrays;

class DisplayFilter implements ArraySerializable {

	public bool $enabled = true;
	public bool $showItems = false;
	/** @var list<Condition> */
	public array $conditions;

	/**
	 * @param bool            $enabled Whether the filter is enabled.
	 * @param bool            $type True to only show matching items, false to exclude them.
	 * @param list<Condition> $conditions The conditions.
	 */
	public function __construct( bool $enabled, bool $showItems, array $conditions ) {
		$this->conditions = $conditions;
		$this->enabled = $enabled;
		$this->showItems = $showItems;
	}

	/** Evaluates the conditions to build a query WHERE condition. */
	public function buildWhere( ConditionSystem $sys ): string {
		$whereList = array();

		foreach ( $this->conditions as $condition ) {
			$exprWheres = array();

			foreach ( $condition->exprs as $expr ) {
				$exprWhere = $expr->eval( $sys, null );
				if ( $exprWhere !== null ) {
					$exprWheres[] = $exprWhere;
				}
			}

			if ( empty( $exprWheres ) ) {
				continue;
			}

			$glue = $condition->isAnd ? ' AND ' : ' OR ';
			$whereList[] = '(' . implode( $glue, $exprWheres ) . ')';
		}

		if ( empty( $whereList ) ) {
			$result = 'true';
		} else {
			$result = implode( ' OR ', $whereList );
		}

		if ( $this->showItems ) {
			return $result;
		} else {
			return "NOT ($result)";
		}
	}

	/** @return array<string,mixed> */
	public function toArray(): array {
		return array(
			'enabled' => $this->enabled,
			'showItems' => $this->showItems,
			'conditions' => Arrays::toArrayAll( $this->conditions ),
		);
	}

	/** @param array<string,mixed> $array */
	public static function fromArray( array $array ): self {
		$enabled = $array['enabled'] ?? null;
		$showItems = $array['showItems'] ?? null;

		if ( ! is_bool( $enabled ) ) {
			throw new DomainException( 'Invalid "enabled" in display filter array' );
		}
		if ( ! is_bool( $showItems ) ) {
			throw new DomainException( 'Invalid "showItems" in display filter array' );
		}
		if ( ! is_array( $array['conditions'] ?? null ) ) {
			throw new DomainException( 'Invalid "conditions" in display filter array' );
		}

		$conditions = array();
		foreach ( $array['conditions'] as $subArray ) {
			$conditions[] = Condition::fromArray( $subArray );
		}

		return new self( $enabled, $showItems, $conditions );
	}
}
