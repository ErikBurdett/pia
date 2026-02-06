<?php

namespace RebelCode\Aggregator\Basic\Source;

use RebelCode\Aggregator\Core\RssReader\RssItem;
use RebelCode\Aggregator\Core\Source;

class AutomationAction {

	public string $label;
	public $runFn;
	public $defaultFn;

	/**
	 * @param string                                     $label A human-friendly label, for use in UIs.
	 * @param callable(RssItem,Source):RssItem|null      $runFn The function to run.
	 *             If it returns null, the RSS item is skipped by the importer.
	 * @param null|callable(RssItem,Source):RssItem|null $defaultFn Optional
	 *        function to run for items that did not match an automation condition.
	 *        If it returns null, the RSS item is skipped by the importer.
	 *        By default, the RSS item given as argument is returned.
	 */
	public function __construct( string $label, callable $runFn, ?callable $defaultFn = null ) {
		$this->label = $label;
		$this->runFn = $runFn;
		$this->defaultFn = $defaultFn;
	}

	public function run( RssItem $item, Source $src ): ?RssItem {
		return call_user_func( $this->runFn, $item, $src );
	}

	public function default( RssItem $item, Source $src ): ?RssItem {
		if ( is_callable( $this->defaultFn ) ) {
			return call_user_func( $this->defaultFn, $item, $src );
		} else {
			return $item;
		}
	}
}
