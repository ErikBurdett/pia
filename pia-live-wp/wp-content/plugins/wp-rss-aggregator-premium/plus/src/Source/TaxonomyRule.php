<?php

declare(strict_types=1);

namespace RebelCode\Aggregator\Plus\Source;

use DomainException;
use Generator;
use RebelCode\Aggregator\Basic\Conditions\Condition;
use RebelCode\Aggregator\Basic\Conditions\ConditionSystem;
use RebelCode\Aggregator\Core\IrPost\IrTerm;
use RebelCode\Aggregator\Core\RssReader\RssItem;
use RebelCode\Aggregator\Core\Utils\ArraySerializable;

class TaxonomyRule implements ArraySerializable {

	public bool $assign;
	public bool $import;
	/** @var list<string> */
	public array $terms;
	public bool $useCondition;
	/** @var list<Condition> */
	public array $conditions;
	public ?string $parent;

	/**
	 * @param list<string>    $terms
	 * @param list<Condition> $conditions
	 */
	public function __construct(
		bool $import = false,
		bool $assign = false,
		array $terms = array(),
		bool $useCondition = false,
		array $conditions = array(),
		?string $parent = null
	) {
		$this->import = $import;
		$this->assign = $assign;
		$this->terms = $terms;
		$this->useCondition = $useCondition;
		$this->conditions = $conditions;
		$this->parent = $parent;
	}

	/** Evaluates the rule's condition to check whether it applies to an RSS item. */
	public function matches( ConditionSystem $sys, RssItem $item ): bool {
		if ( ! $this->useCondition ) {
			return true;
		}

		foreach ( $this->conditions as $group ) {
			if ( $group->eval( $sys, $item ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Evaluates the rule and returns the resulting terms.
	 *
	 * @param ConditionSystem $sys The condition system to use for evaluation.
	 * @param string          $tax The taxonomy slug.
	 * @param RssItem         $item The RSS item.
	 * @param bool            $importAll If true, terms will be imported even if the rule's
	 *                   import option is disabled.
	 * @return Generator<IrTerm>
	 */
	public function eval( ConditionSystem $sys, string $tax, RssItem $item, bool $importAll = false ): Generator {
		if ( ! $this->matches( $sys, $item ) ) {
			return;
		}

		$parent = null;
		if ( $this->parent ) {
			$parent = new IrTerm( $tax, $this->parent );
		}

		if ( $this->assign && count( $this->terms ) > 0 ) {
			foreach ( $this->terms as $term ) {
				yield new IrTerm( $tax, $term, $term, $parent );
			}
		}

		if ( $this->import || $importAll ) {
			foreach ( $item->getCategories() as $category ) {
				$label = $category->getLabel();
				$term = $category->getTerm() ?? $label;

				if ( $term ) {
					yield new IrTerm( $tax, $term, $label, $parent );
				}
			}

			$enclosures = $item->getEnclosures();
			foreach ( $enclosures as $enclosure ) {
				$keywords = $enclosure->getKeywords();
				if ( empty( $keywords ) ) {
					continue;
				}
				foreach ( $keywords as $keyword ) {
					yield new IrTerm( $tax, $keyword, null, $parent );
				}
			}
		}
	}

	public function toArray(): array {
		return array(
			'import' => $this->import,
			'assign' => $this->assign,
			'terms' => $this->terms,
			'useCondition' => $this->useCondition,
			'conditions' => $this->conditions,
			'parent' => $this->parent,
		);
	}

	/** @param array<string,mixed> $array */
	public static function fromArray( array $array ): self {
		$import = $array['import'] ?? false;
		$assign = $array['assign'] ?? false;
		$terms = $array['terms'] ?? null;
		$useCondition = $array['useCondition'] ?? false;
		$parent = $array['parent'] ?? null;

		if ( ! is_bool( $import ) ) {
			throw new DomainException( 'Invalid "import" in taxonomy rule array' );
		}
		if ( ! is_bool( $assign ) ) {
			throw new DomainException( 'Invalid "assign" in taxonomy rule array' );
		}
		if ( ! is_bool( $useCondition ) ) {
			throw new DomainException( 'Invalid "useCondition" in taxonomy rule array' );
		}
		if ( ! is_array( $terms ) ) {
			throw new DomainException( 'Invalid "terms" in taxonomy rule array' );
		}
		if ( ! is_array( $array['conditions'] ?? null ) ) {
			throw new DomainException( 'Invalid "conditions" in taxonomy rule array' );
		}

		$conditions = array();
		foreach ( $array['conditions'] as $subArray ) {
			$conditions[] = Condition::fromArray( $subArray );
		}

		return new self( $import, $assign, $terms, $useCondition, $conditions, $parent );
	}
}
