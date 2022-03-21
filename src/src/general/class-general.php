<?php

declare( strict_types=1 );

namespace SkautisIntegration\General;

use SkautisIntegration\Rules\Rules_Init;

final class General {

	private $actions;
	private $rulesInit;

	public function __construct( Actions $actions, Rules_Init $rulesInit ) {
		$this->actions   = $actions;
		$this->rulesInit = $rulesInit;
	}

}
