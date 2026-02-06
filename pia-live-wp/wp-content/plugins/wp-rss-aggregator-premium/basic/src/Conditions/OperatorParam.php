<?php

namespace RebelCode\Aggregator\Basic\Conditions;

class OperatorParam {

	public const STRING = 'string';
	public const NUMBER = 'number';
	public const MULTISELECT = 'multiselect';
	public const BOOL = 'bool';

	public string $label;
	public string $type;
	public $default;
	public string $placeholder;

	/**
	 * @param string $type The param type; one of the constants in this class.
	 * @param string $label A label for the param, intended for humans.
	 * @param mixed  $default The value of the param if no arg is given for it.
	 * @param string $placeholder Optional placeholder string. May be shown in
	 *        some fields in the UI, depending on the type.
	 */
	private function __construct( string $type, string $label, $default = null, string $placeholder = '' ) {
		$this->type = $type;
		$this->label = $label;
		$this->default = $default;
		$this->placeholder = $placeholder;
	}

	/** @param mixed $default */
	public static function string( string $label, $default = null, string $placeholder = '' ): self {
		return new self( self::STRING, $label, $default, $placeholder );
	}

	/** @param mixed $default */
	public static function number( string $label, $default = null, string $placeholder = '' ): self {
		return new self( self::NUMBER, $label, $default, $placeholder );
	}

	/** @param mixed $default */
	public static function bool( string $label, $default = null, string $placeholder = '' ): self {
		return new self( self::BOOL, $label, $default, $placeholder );
	}

	/** @param mixed $default */
	public static function multiselect( string $label, $default = null, string $placeholder = '' ): self {
		return new self( self::MULTISELECT, $label, $default, $placeholder );
	}
}
