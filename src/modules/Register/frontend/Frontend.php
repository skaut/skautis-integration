<?php

namespace SkautisIntegration\Modules\Register\Frontend;

final class Frontend {

	private $loginForm;

	public function __construct( LoginForm $loginForm ) {
		$this->loginForm      = $loginForm;
		$this->initHooks();
	}

	private function initHooks() {

	}

}
