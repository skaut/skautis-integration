<?php

declare( strict_types=1 );

namespace SkautisIntegration\General;

use SkautisIntegration\Rules\Rules_Init;

// TODO: Unused?
final class General {

	// TODO: Unused?
	private $actions;
	// TODO: Unused?
	private $rules_init;

	public function __construct( Actions $actions, Rules_Init $rulesInit ) {
		$this->actions   = $actions;
		$this->rules_init = $rulesInit;
	}

}
