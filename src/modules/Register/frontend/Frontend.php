<?php

declare( strict_types=1 );

namespace SkautisIntegration\Modules\Register\Frontend;

final class Frontend {

	private $loginForm;

	public function __construct( LoginForm $loginForm ) {
		$this->loginForm = $loginForm;
	}

}
