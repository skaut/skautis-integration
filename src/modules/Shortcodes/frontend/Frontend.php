<?php

declare( strict_types=1 );

namespace SkautisIntegration\Modules\Shortcodes\Frontend;

use SkautisIntegration\Auth\SkautisLogin;
use SkautisIntegration\Rules\RulesManager;

final class Frontend {

	private $skautisLogin;
	private $rulesManager;

	public function __construct( SkautisLogin $skautisLogin, RulesManager $rulesManager ) {
		$this->skautisLogin = $skautisLogin;
		$this->rulesManager = $rulesManager;
		$this->initHooks();
	}

	private function initHooks() {
		add_shortcode( 'skautis', [ $this, 'processShortcode' ] );
	}

	public function processShortcode( array $atts = [], string $content = '' ): string {
		if ( isset( $atts['rules'] ) ) {
			if ( $this->skautisLogin->isUserLoggedInSkautis() &&
			     $this->rulesManager->checkIfUserPassedRules( explode( ',', $atts['rules'] ) ) ) {
				return $content;
			}
		}

		return '';
	}

}
