<?php

namespace SkautisIntegration\General;

use SkautisIntegration\Rules\RulesInit;

final class General {

	private $actions;
	private $rulesInit;

	public function __construct( Actions $actions, RulesInit $rulesInit ) {
		$this->actions   = $actions;
		$this->rulesInit = $rulesInit;
		$this->initHooks();
	}

	private function initHooks() {

	}

}
