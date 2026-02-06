<?php

namespace RebelCode\Aggregator\Core;

use RebelCode\Aggregator\Core\Utils\Strings;
use RebelCode\Aggregator\Basic\Conditions\OperatorParam;
use RebelCode\Aggregator\Basic\Conditions\Operator;
use RebelCode\Aggregator\Basic\Conditions\ConditionSystem;

wpra()->addModule(
	'conditions',
	array(),
	function () {
		$strEquals = new Operator(
			_x( 'Is', 'Condition operator', 'wp-rss-aggregator-premium' ),
			array( 'value' => OperatorParam::string( '', '', __( 'Type a value...', 'wp-rss-aggregator-premium' ) ) ),
			function ( $input, $args ) {
				return $input == $args->value;
			}
		);

		$strContainsAll = new Operator(
			_x( 'Contains all', 'Condition operator', 'wp-rss-aggregator-premium' ),
			array(
				'value' => OperatorParam::multiselect( __( 'Of the following keywords:' ), '', __( 'Type a keyword...', 'wp-rss-aggregator-premium' ) ),
				'matchCase' => OperatorParam::bool( __( 'Match case', 'wp-rss-aggregator-premium' ), false ),
				'wholeWords' => OperatorParam::bool( __( 'Whole words', 'wp-rss-aggregator-premium' ), false ),
			),
			function ( $input, $args ) {
				$phrases = (array) $args->value;
				foreach ( $phrases as $phrase ) {
					if ( ! Strings::contains( (string) $input, trim( $phrase ), $args->matchCase, $args->wholeWords ) ) {
						return false;
					}
				}
				return true;
			}
		);

		$strContainsAny = new Operator(
			_x( 'Contains any', 'Condition operator', 'wp-rss-aggregator-premium' ),
			array(
				'value' => OperatorParam::multiselect( __( 'Of the following keywords:' ), '', __( 'Type a keyword...', 'wp-rss-aggregator-premium' ), ),
				'matchCase' => OperatorParam::bool( __( 'Match case', 'wp-rss-aggregator-premium' ), false ),
				'wholeWords' => OperatorParam::bool( __( 'Whole words', 'wp-rss-aggregator-premium' ), false ),
			),
			function ( $input, $args ) {
				$phrases = (array) $args->value;
				foreach ( $phrases as $phrase ) {
					if ( Strings::contains( (string) $input, trim( $phrase ), $args->matchCase, $args->wholeWords ) ) {
						return true;
					}
				}
				return false;
			}
		);

		$operators = apply_filters(
			'wpra.conditions.operators',
			array(
				'strEquals' => $strEquals,
				'strNotEquals' => $strEquals->negate( __( 'Is not', 'wp-rss-aggregator-premium' ) ),
				'strContainsAll' => $strContainsAll,
				'strNotContainsAll' => $strContainsAll->negate( __( 'Does not contain all', 'wp-rss-aggregator-premium' ) ),
				'strContainsAny' => $strContainsAny,
				'strNotContainsAny' => $strContainsAny->negate( __( 'Does not contain any', 'wp-rss-aggregator-premium' ) ),
			)
		);

		$types = apply_filters(
			'wpra.conditions.types',
			array(
				'string' => array(
					'strEquals',
					'strNotEquals',
					'strContainsAll',
					'strNotContainsAll',
					'strContainsAny',
					'strNotContainsAny',
				),
			)
		);

		return new ConditionSystem( array(), $operators, $types );
	}
);
