<?php

namespace RebelCode\Aggregator\Basic\Source;

use RebelCode\Aggregator\Basic\Conditions\ConditionSystem;
use RebelCode\Aggregator\Basic\Source\Automation;
use RebelCode\Aggregator\Basic\Source\AutomationAction;
use RebelCode\Aggregator\Core\Logger;
use RebelCode\Aggregator\Core\RssReader\RssItem;
use RebelCode\Aggregator\Core\Source;

class AutomationSystem {

	public ConditionSystem $condSys;
	/** @var array<string,AutomationAction> */
	public array $actions;

	/** @param array<string,AutomationAction> $actions */
	public function __construct( ConditionSystem $condSys, array $actions ) {
		$this->condSys = $condSys;
		$this->actions = $actions;
	}

	/** @param list<Automation> $automations */
	public function run( array $automations, RssItem $item, Source $src ): ?RssItem {
		foreach ( $automations as $automation ) {
			if ( ! $automation->enabled ) {
				continue;
			}

			$action = $this->actions[ $automation->actionId ] ?? null;
			if ( $action === null ) {
				Logger::warning( "Unknown automation action: \"{$automation->actionId}\"" );
				continue;
			}

			if ( $this->isMatch( $automation, $item ) ) {
				$item = $action->run( $item, $src );
			} else {
				$item = $action->default( $item, $src );
			}

			if ( $item === null ) {
				return null;
			}
		}

		return $item;
	}

	public function isMatch( Automation $automation, RssItem $item ): bool {
		foreach ( $automation->conditions as $group ) {
			if ( $group->eval( $this->condSys, $item ) ) {
				return true;
			}
		}

		return false;
	}
}
