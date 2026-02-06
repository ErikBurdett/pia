<?php

namespace RebelCode\Aggregator\Plus\Templates\Ctx;

use RebelCode\Aggregator\Core\IrPost;
use RebelCode\Aggregator\Core\Source;

class IrPostSourceCtx {

	public IrPost $post;
	public Source $src;

	public function __construct( IrPost $post, Source $src ) {
		$this->post = $post;
		$this->src = $src;
	}
}
