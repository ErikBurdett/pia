<?php

namespace RebelCode\Aggregator\Core;

use DomainException;
use RebelCode\Aggregator\Core\RssReader\RssItem;
use RebelCode\Aggregator\Pro\Source\CustomMapping;

wpra()->addModule(
	'customMapping',
	array(),
	function () {
		add_filter(
			'wpra.source.settings.patch.customMapping',
			function ( $mappings ) {
				if ( ! is_array( $mappings ) ) {
					throw new DomainException( 'Invalid value for `customMapping` in source settings' );
				}

				$result = array();
				foreach ( $mappings as $mapping ) {
					$result[] = CustomMapping::fromArray( $mapping );
				}
				return $result;
			}
		);

		add_filter(
			'wpra.importer.post.meta',
			function ( array $meta, IrPost $post, RssItem $item, Source $src ) {
				/** @var CustomMapping $mapping */
				foreach ( $src->settings->customMapping as $mapping ) {
					$value = $mapping->value( $item, $post );
					$meta[ $mapping->metaKey ] = $value;
				}
				return $meta;
			},
			10,
			4
		);
	}
);
