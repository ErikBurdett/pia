<?php

declare(strict_types=1);

namespace RebelCode\Aggregator\Plus\Source;

interface AuthorMethod {

	public const DEFAULT = 'default';
	public const CREATE = 'create';
	public const FALLBACK = 'fallback';
}
