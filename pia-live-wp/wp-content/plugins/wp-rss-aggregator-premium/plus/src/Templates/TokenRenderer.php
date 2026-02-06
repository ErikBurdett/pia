<?php

namespace RebelCode\Aggregator\Plus\Templates;

class TokenRenderer {

	private const OPEN = '{{';
	private const CLOSE = '}}';
	private const TOKEN_REGEX = '\s*(\w+)((?:\s+\w+\s*=\s*"(?:[^"]|\\\\")*")*)\s*';
	private const ARGS_REGEX = '(\w+)\s*=\s*"((?:[^"]|\\\\")*)"';

	/** @var array<string,TokenType> */
	public array $types;

	/** @param array<string,TokenType> $tokenTypes */
	public function __construct( array $tokenTypes = array() ) {
		$this->types = $tokenTypes;
	}

	/** Adds a new token type. */
	public function addType( string $id, TokenType $type ): self {
		$this->types[ $id ] = $type;
		return $this;
	}

	/**
	 * Renders a token by its type and arguments.
	 *
	 * @param string              $type The token type.
	 * @param array<string,mixed> $args The token arguments.
	 * @param mixed               $ctx The context value.
	 */
	public function render( string $type, array $args, $ctx ): string {
		$tokType = $this->types[ $type ] ?? null;
		if ( $tokType === null ) {
			return '';
		}
		return $tokType->render( $ctx, $args );
	}

	/**
	 * Renders a token instance.
	 *
	 * @param Token $token The token instance to render.
	 * @param mixed $ctx The context value to render for.
	 */
	public function renderToken( Token $token, $ctx ): string {
		return $this->render( $token->type, $token->args, $ctx );
	}

	/**
	 * Renders a list of tokens into a single string, using a single context.
	 *
	 * @param list<Token> $tokens The tokens to render.
	 * @param mixed       $ctx The context value.
	 */
	public function renderList( array $tokens, $ctx ): string {
		$str = '';
		foreach ( $tokens as $token ) {
			$str .= $this->renderToken( $token, $ctx );
		}
		return $str;
	}

	/**
	 * Renders a string template. Strings templates are strings with tokens
	 * in this form: "Hello {{name style="bold"}}".
	 *
	 * @param string $template The template string.
	 * @param mixed  $ctx The context value.
	 */
	public function renderTemplate( string $template, $ctx ): string {
		$result = preg_replace_callback(
			'/' . preg_quote( self::OPEN, '/' ) . self::TOKEN_REGEX . preg_quote( self::CLOSE, '/' ) . '/im',
			function ( array $match ) use ( $ctx ) {
				$type = trim( $match[1] );
				$args = $this->parseArgs( trim( $match[2] ) );
				return $this->render( $type, $args, $ctx );
			},
			$template
		);

		if ( ! is_string( $result ) ) {
			return '';
		}

		return $result;
	}

	/**
	 * Parses a string of token arguments into an associative array.
	 * Example:
	 *   parseArgs('foo="bar" baz="qux"'); // ["foo" => "bar", "baz" => "qux"]
	 *
	 * @param string $argStr The arguments string.
	 * @return array<string,string> The parsed arguments.
	 */
	private function parseArgs( string $argStr ): array {
		$matches = array();
		preg_match_all( '/' . self::ARGS_REGEX . '/i', $argStr, $matches, PREG_SET_ORDER );

		$args = array();
		foreach ( $matches as $match ) {
			$name = trim( $match[1] );
			$value = trim( $match[2] );
			$args[ $name ] = $value;
		}

		return $args;
	}
}
