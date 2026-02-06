<?php

namespace RebelCode\Aggregator\Core;

use RebelCode\Aggregator\Core\Utils\Strings;
use RebelCode\Aggregator\Core\RssReader\RssItem;
use RebelCode\Aggregator\Core\RssReader\RssCategory;
use RebelCode\Aggregator\Basic\V4Migration\V4AutomationsMigrator;
use RebelCode\Aggregator\Basic\Source\AutomationSystem;
use RebelCode\Aggregator\Basic\Source\AutomationAction;
use RebelCode\Aggregator\Basic\Source\Automation;
use RebelCode\Aggregator\Basic\Conditions\Subject;
use RebelCode\Aggregator\Basic\Conditions\OperatorParam;
use RebelCode\Aggregator\Basic\Conditions\Operator;
use RebelCode\Aggregator\Basic\Conditions\ConditionSystem;
use DomainException;

wpra()->addModule(
	'automations',
	array( 'settings', 'conditions' ),
	function ( Settings $settings, ConditionSystem $condSys ) {
		$automations = $settings
		->register( 'automations' )
		->setDefault( array() )
		->middleware(
			function ( $value ) {
				$automations = array();
				foreach ( (array) $value as $subValue ) {
					if ( is_array( $subValue ) ) {
						$automations[] = Automation::fromArray( $subValue );
					} elseif ( $subValue instanceof Automation ) {
						$automations[] = $subValue;
					}
				}
				return $automations;
			}
		)
		->get();

		$subjects = apply_filters(
			'wpra.automations.subjects',
			array(
				'title' => Subject::string(
					__( 'Title', 'wp-rss-aggregator-premium' ),
					fn ( RssItem $i ) => $i->getTitle() ?? '',
				),
				'content' => Subject::string(
					__( 'Content', 'wp-rss-aggregator-premium' ),
					fn ( RssItem $i ) => $i->getContent() ?? $i->getExcerpt() ?? '',
				),
				'title_content' => Subject::string(
					__( 'Title and Content', 'wp-rss-aggregator-premium' ),
					fn ( RssItem $i ) => ( $i->getTitle() ?? '' ) . ( $i->getContent() ?? $i->getExcerpt() ?? '' ),
				),
				'tags' => new Subject(
					'tags',
					__( 'Tags/Categories', 'wp-rss-aggregator-premium' ),
					fn ( RssItem $i ) => array_map(
						function ( RssCategory $category ) {
						return $category->getLabel() ?? $category->getTerm();
						},
						$i->getCategories()
					),
				),
			)
		);

		$tagsContainAny = new Operator(
			_x( 'Contain any', 'Condition operator', 'wp-rss-aggregator-premium' ),
			array(
				'value' => OperatorParam::string( __( 'Of the following:' ), '', __( 'Type a value...', 'wp-rss-aggregator-premium' ), ),
			),
			function ( $input, $args ) {
				$items = array_filter( explode( ',', (string) $args->value ) );
				foreach ( $items as $item ) {
					if ( in_array( Strings::lower( trim( $item ) ), $input ) ) {
						return true;
					}
				}
				return false;
			}
		);

		$actions = apply_filters(
			'wpra.automations.actions',
			array(
				'import' => new AutomationAction(
					__( 'Import item', 'wp-rss-aggregator-premium' ),
					fn ( $item ) => $item,
					fn () => null,
				),
				'doNotImport' => new AutomationAction(
					__( 'Do not import', 'wp-rss-aggregator-premium' ),
					fn () => null,
					fn ( $item ) => $item
				),
			)
		);

		$sys = new AutomationSystem(
			$condSys
			->withSubjects( $subjects )
			->withAddedOperators(
				array(
					'tagsContainAny' => $tagsContainAny,
					'tagsContainNone' => $tagsContainAny->negate( _x( 'Contain none', 'Condition operator', 'wp-rss-aggregator-premium' ) ),
				)
			)
			->withAddedTypes(
				array(
					'tags' => array(
						'tagsContainAny',
						'tagsContainNone',
					),
				)
			),
			$actions,
		);

		add_filter(
			'wpra.importer.item.filter',
			function ( RssItem $item, Source $src ) use ( $automations, $sys ) {
				if ( empty( $src->settings->automations ) ) {
					return $item;
				}
				$automations = array_merge( $automations, $src->settings->automations );
				$newItem = $sys->run( $automations, $item, $src );

				if ( $newItem === null ) {
					$id = $item->getId() ?? $item->getPermalink();
					Logger::debug( "Item {$id} was rejected by an automation" );
				}

				return $newItem;
			},
			10,
			2
		);

		add_filter(
			'wpra.source.settings.patch.automations',
			function ( $automations ) {
				if ( ! is_array( $automations ) ) {
					throw new DomainException( 'Invalid value for `automations` in source settings' );
				}

				$result = array();
				foreach ( $automations as $val ) {
					$result[] = Automation::fromArray( $val );
				}

				return $result;
			}
		);

		add_filter(
			'wpra.admin.frame.l10n',
			function ( array $l10n ) use ( $sys, $actions ) {
				$l10n['automations']['condSys'] = $sys->condSys->toArray();
				$l10n['automations']['actions'] = array_map(
					fn ( AutomationAction $a ) => $a->label,
					$actions
				);
				return $l10n;
			}
		);

		$migrator = new V4AutomationsMigrator( get_option( 'wprss_settings_kf', array() ) );

		add_filter(
			'wpra.v4Migration.source.converted',
			function ( Source $src, array $meta ) use ( $migrator ) {
				return $migrator->migrateSourceAutomations( $src, $meta );
			},
			10,
			2
		);

		add_filter(
			'wpra.v4Migration.settings.patch',
			function ( array $patch ) use ( $migrator ) {
				return $migrator->migrateSettings( $patch );
			}
		);

		return $sys;
	}
);
