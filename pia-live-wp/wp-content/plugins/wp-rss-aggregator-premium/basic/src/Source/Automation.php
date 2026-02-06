<?php

declare(strict_types=1);

namespace RebelCode\Aggregator\Basic\Source;

use DomainException;
use RebelCode\Aggregator\Basic\Conditions\Condition;
use RebelCode\Aggregator\Core\Utils\Arrays;
use RebelCode\Aggregator\Core\Utils\ArraySerializable;

class Automation implements ArraySerializable {

	public string $name;
	public bool $enabled = true;
	/** @var list<Condition> */
	public array $conditions;
	public string $actionId;

	/**
	 * @param string          $name The name of the automation.
	 * @param bool            $enabled Whether the automation is enabled or not.
	 * @param list<Condition> $conditions The condition groups.
	 * @param string          $actionId The ID of the action to run.
	 */
	public function __construct( string $name, bool $enabled, array $conditions, string $actionId ) {
		$this->name = $name;
		$this->enabled = $enabled;
		$this->conditions = $conditions;
		$this->actionId = $actionId;
	}

	public function toArray(): array {
		return array(
			'name' => $this->name,
			'enabled' => $this->enabled,
			'conditions' => Arrays::toArrayAll( $this->conditions ),
			'actionId' => $this->actionId,
		);
	}

	/** @param array<string,mixed> $array */
	public static function fromArray( array $array ): self {
		$name = $array['name'] ?? null;
		$enabled = $array['enabled'] ?? null;
		$actionId = $array['actionId'] ?? null;

		if ( ! is_string( $name ) ) {
			throw new DomainException( 'Invalid "name" in automation array' );
		}
		if ( ! is_bool( $enabled ) ) {
			throw new DomainException( 'Invalid "enabled" in automation array' );
		}
		if ( ! is_string( $actionId ) ) {
			throw new DomainException( 'Invalid "actionId" in automation array' );
		}
		if ( ! is_array( $array['conditions'] ?? null ) ) {
			throw new DomainException( 'Invalid "conditions" in automation array' );
		}

		$conditions = array();
		foreach ( $array['conditions'] as $subArray ) {
			if ( ! is_array( $subArray ) ) {
				throw new DomainException( 'Invalid group in automations array' );
			}
			$conditions[] = Condition::fromArray( $subArray );
		}

		return new self( $name, $enabled, $conditions, $actionId );
	}
}
