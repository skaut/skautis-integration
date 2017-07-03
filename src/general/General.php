<?php

declare( strict_types=1 );

namespace SkautisIntegration\General;

use SkautisIntegration\Rules\RulesInit;

final class General {

	private $actions;
	private $rulesInit;

	public function __construct( Actions $actions, RulesInit $rulesInit ) {
		$this->actions   = $actions;
		$this->rulesInit = $rulesInit;
	}

}
