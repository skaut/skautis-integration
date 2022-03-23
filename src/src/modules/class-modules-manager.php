<?php

declare( strict_types=1 );

namespace SkautisIntegration\Modules;

use SkautisIntegration\Vendor\Pimple\Container;

final class Modules_Manager {

	private $container;
	private $modules          = array();
	private $activatedModules = array();

	public function __construct( Container $container, array $modules = array() ) {
		$this->container        = $container;
		$this->modules          = apply_filters( SKAUTISINTEGRATION_NAME . '_modules', $modules );
		$this->activatedModules = (array) get_option( 'skautis_integration_activated_modules' );
		apply_filters_ref_array( SKAUTISINTEGRATION_NAME . '_activated_modules', $this->activatedModules );
		$this->register_activated_modules( $this->modules, $this->activatedModules );
	}

	private function register_activated_modules( array $modules = array(), array $activatedModules = array() ) {
		foreach ( $modules as $moduleId => $moduleLabel ) {
			if ( in_array( $moduleId, $activatedModules, true ) ) {
				$this->container[ $moduleId ];
			}
		}
	}

	public function get_all_modules(): array {
		return $this->modules;
	}

	public function is_module_activated( string $moduleName ): bool {
		return in_array( $moduleName, $this->activatedModules, true );
	}

}
