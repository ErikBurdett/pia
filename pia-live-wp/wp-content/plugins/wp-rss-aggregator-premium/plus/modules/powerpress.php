<?php

namespace RebelCode\Aggregator\Core;

use RebelCode\Aggregator\Core\RssReader\RssEnclosureType;
use RebelCode\Aggregator\Core\RssReader\RssItem;
use RebelCode\Aggregator\Core\Utils\Time;

wpra()->addModule(
	'powerpress',
	array(),
	function () {
		add_filter(
			'wpra.importer.post.meta',
			function ( array $meta, IrPost $post, RssItem $item, Source $src ) {
				if ( ! $src->settings->enablePowerPress ) {
					return $meta;
				}

				foreach ( $item->getEnclosures() as $enclosure ) {
					$url = $enclosure->getUrl();
					$type = $enclosure->getType();

					if ( $url && stripos( $type, RssEnclosureType::AUDIO ) === 0 ) {
						$length = $enclosure->getLength();
						$duration = $enclosure->getDuration() ?? 0;

						$durationData = serialize(
							array(
								'duration' => Time::secondsToTimeStr( $duration ),
							)
						);

						$meta['enclosure'] = "$url\n$length\n$type\n$durationData";
						break;
					}
				}

				return $meta;
			},
			10,
			4
		);
	}
);
