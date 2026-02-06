<?php

namespace RebelCode\Aggregator\Basic\Conditions;

interface Evaluable {

	/**
	 * Evaluates the object.
	 *
	 * @param ConditionSystem $sys The condition system, to resolve subject and
	 *        operator IDs into instances.
	 * @param mixed           $input The input value.
	 * @return mixed The evaluation result.
	 */
	public function eval( ConditionSystem $sys, $input );
}
