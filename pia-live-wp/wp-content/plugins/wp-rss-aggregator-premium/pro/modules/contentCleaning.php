<?php

namespace RebelCode\Aggregator\Core;

use DomainException;
use RebelCode\Aggregator\Pro\Source\ContentCleaner;

wpra()->addModule(
	'contentCleaning',
	array(),
	function () {
		add_filter(
			'wpra.source.settings.patch.contentCleaners',
			function ( $cleaners ) {
				if ( ! is_array( $cleaners ) ) {
					throw new DomainException( 'Invalid value for `contentCleaners` in source settings' );
				}

				$result = array();
				foreach ( $cleaners as $cleaner ) {
					$result[] = ContentCleaner::fromArray( $cleaner );
				}
				return $result;
			}
		);
	}
);
