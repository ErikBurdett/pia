<?php

namespace RebelCode\Aggregator\Core;

use DomainException;
use RebelCode\Aggregator\Core\RssReader\RssItem;
use RebelCode\Aggregator\Basic\Conditions\ConditionSystem;
use RebelCode\Aggregator\Basic\Conditions\Subject;
use RebelCode\Aggregator\Plus\Source\TaxonomyRule;

wpra()->addModule(
	'taxonomies',
	array( 'conditions' ),
	function ( ConditionSystem $condSys ) {
		$subjects = apply_filters(
			'wpra.taxonomies.subjects',
			array(
				'title' => Subject::string(
					__( 'Title', 'wp-rss-aggregator-premium' ),
					fn ( RssItem $i ) => $i->getTitle() ?? ''
				),
				'content' => Subject::string(
					__( 'Content', 'wp-rss-aggregator-premium' ),
					fn ( RssItem $i ) => $i->getContent() ?? $i->getExcerpt() ?? ''
				),
			)
		);

		$taxCondSys = new ConditionSystem( $subjects, $condSys->operators, $condSys->types );

		add_filter(
			'wpra.admin.frame.l10n',
			function ( array $l10n ) use ( $taxCondSys ) {
				$l10n['taxonomies']['condSys'] = $taxCondSys->toArray();
				return $l10n;
			}
		);

		add_filter(
			'wpra.source.settings.patch.taxonomies',
			function ( $taxonomies ) {
				if ( ! is_array( $taxonomies ) ) {
					throw new DomainException( 'Invalid value for `taxonomies` in source settings' );
				}

				$result = array();
				foreach ( $taxonomies as $taxName => $rules ) {
					$result[ $taxName ] = array();
					foreach ( $rules as $rule ) {
						$result[ $taxName ][] = TaxonomyRule::fromArray( $rule );
					}
				}
				return $result;
			}
		);

		return $taxCondSys;
	}
);
