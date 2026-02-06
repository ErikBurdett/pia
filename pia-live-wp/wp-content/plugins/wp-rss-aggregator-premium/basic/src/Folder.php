<?php

declare(strict_types=1);

namespace RebelCode\Aggregator\Basic;

use RebelCode\Aggregator\Core\Utils\ArraySerializable;

class Folder implements ArraySerializable {

	public int $id;
	public string $name;
	public string $slug;
	public array $sourceIds = array();

	/**
	 * Constructor.
	 *
	 * @param int       $id The id index of folder.
	 * @param string    $name The name of the folder.
	 * @param list<int> $sourceIds The IDs of the sources in the folder.
	 */
	public function __construct( int $id, string $name = '', string $slug = '', array $sourceIds = array() ) {
		$this->id = $id;
		$this->name = $name;
		$this->slug = $slug;
		$this->sourceIds = $sourceIds;
	}

	/**
	 * Creates a copy of this instance with a different name.
	 *
	 * @param string $newName The new name.
	 * @return self The new instance.
	 */
	public function withName( string $newName ): self {
		$clone = clone $this;
		$clone->name = $newName;
		return $clone;
	}

	/**
	 * Creates a copy of this instance with a different list of source IDs.
	 *
	 * @param list<int> $sourceIds The new list of source IDs.
	 * @return self The new instance.
	 */
	public function withSourceIds( array $sourceIds ): self {
		$clone = clone $this;
		$clone->sourceIds = $sourceIds;
		return $clone;
	}

	/** @inheritDoc */
	public function toArray(): array {
		return array(
			'id' => $this->id,
			'name' => $this->name,
			'slug' => $this->slug,
			'sourceIds' => $this->sourceIds,
		);
	}

	/** @param array<string,mixed> $array */
	public static function fromArray( array $array ): self {
		return new self(
			$array['id'] ?? '',
			$array['name'] ?? '',
			$array['slug'] ?? '',
			$array['sourceIds'] ?? array(),
		);
	}
}
