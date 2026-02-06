<?php

namespace RebelCode\Aggregator\Core;

use RebelCode\Aggregator\Basic\Display\DisplayFilter;
use RebelCode\Aggregator\Basic\Conditions\Subject;
use RebelCode\Aggregator\Basic\Conditions\OperatorParam;
use RebelCode\Aggregator\Basic\Conditions\Operator;
use RebelCode\Aggregator\Basic\Conditions\Expression;
use RebelCode\Aggregator\Basic\Conditions\ConditionSystem;
use RebelCode\Aggregator\Basic\Conditions\Condition;
use ArrayObject;

wpra()->addModule(
	'displayFilters',
	array(),
	function () {
		$subjects = apply_filters(
			'wpra.displayFilters.subjects',
			array(
				'title' => Subject::string(
					__( 'Title', 'wp-rss-aggregator-premium' ),
					fn () => '`post_title`',
				),
				'content' => Subject::string(
					__( 'Content', 'wp-rss-aggregator-premium' ),
					fn () => '`post_content`',
				),
				'title_content' => Subject::string(
					__( 'Title and content', 'wp-rss-aggregator-premium' ),
					fn () => 'CONCAT(`post_title`, " ", `post_content`)'
				),
			)
		);

		$operators = apply_filters(
			'wpra.displayFilters.operators',
			array(
				'strContains' => new Operator(
					_x( 'Contains', 'Condition operator', 'wp-rss-aggregator-premium' ),
					array(
						'value' => OperatorParam::string( '', '', __( 'Type a value...', 'wp-rss-aggregator-premium' ) ),
						'wholeWords' => OperatorParam::bool( __( 'Whole words', 'wp-rss-aggregator-premium' ), false ),
					),
					function ( string $col, $args ): ?string {
						$text = esc_sql( trim( $args->value ) );
						if ( empty( $text ) ) {
							return null;
						}
						if ( $args->wholeWords ) {
							return "{$col} REGEXP '[[:<:]]{$text}[[:>:]]'";
						} else {
							return "{$col} REGEXP '{$text}'";
						}
					}
				),
			)
		);

		$types = apply_filters(
			'wpra.displayFilters.types',
			array(
				'string' => array(
					'strContains',
				),
			)
		);

		$sys = new ConditionSystem( $subjects, $operators, $types );

		add_filter(
			'wpra.admin.frame.l10n',
			function ( array $l10n ) use ( $sys ) {
				$l10n['displays']['condSys'] = $sys->toArray();
				return $l10n;
			}
		);

		add_filter(
			'wpra.renderer.display.where',
			function ( array $where, ArrayObject $args, Display $display ) use ( $sys ) {
				if ( count( $display->settings->filters ) === 0 ) {
					return $where;
				}

				$result = null;

				/** @var DisplayFilter $filter */
				foreach ( $display->settings->filters as $filter ) {
					if ( ! $filter instanceof DisplayFilter ) {
						$filter = DisplayFilter::fromArray( $filter );
					}

					if ( ! $filter->enabled ) {
						continue;
					}

					$filterWhere = $filter->buildWhere( $sys, $args );

					if ( $result === null ) {
						$result = $filterWhere;
					} elseif ( $filter->showItems ) {
						$result = "($result) OR ($filterWhere)";
					} else {
						$result = "($result) AND ($filterWhere)";
					}
				}

				$where[] = $result;

				return $where;
			},
			10,
			3
		);

		// Add the 'filter' arg for shortcodes and blocks
		add_filter(
			'wpra.renderer.parseArgs',
			function ( Display $display, array $args ) {
				$filter = sanitize_text_field( $args['filter'] ?? '' );
				if ( is_array( $filter ) ) {
					$filter = trim( join( ' ', $filter ) );
				}

				if ( empty( $filter ) ) {
					return $display;
				}

				$display->settings->filters[] = new DisplayFilter(
					true,
					true,
					array(
						new Condition(
							true,
							array(
								new Expression(
									'title_content',
									'strContains',
									array(
										'value' => $filter,
									)
								),
							)
						),
					)
				);

				return $display;
			},
			10,
			2
		);

		add_filter(
			'wpra.display.settings.patch.displayFilters',
			function ( $value ) {
				if ( ! is_array( $value ) ) {
					return array();
				}
				return array_map(
					fn ( $filter ) => DisplayFilter::fromArray( $filter ),
					$value
				);
			}
		);

		return $sys;
	}
);
