<?php

declare( strict_types=1 );

namespace Skautis_Integration\General;

use Skautis_Integration\Rules\Rules_Init;

// TODO: Unused?
final class General {

	// TODO: Unused?
	private $actions;
	// TODO: Unused?
	private $rules_init;

	public function __construct( Actions $actions, Rules_Init $rules_init ) {
		$this->actions    = $actions;
		$this->rules_init = $rules_init;
	}

}