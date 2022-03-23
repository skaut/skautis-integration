<?php

declare( strict_types=1 );

namespace SkautisIntegration\Modules;

use SkautisIntegration\Vendor\Pimple\Container;

final class Modules_Manager {

	private $container;
	private $modules           = array();
	private $activated_modules = array();

	public function __construct( Container $container, array $modules = array() ) {
		$this->container         = $container;
		$this->modules           = apply_filters( SKAUTISINTEGRATION_NAME . '_modules', $modules );
		$this->activated_modules = (array) get_option( 'skautis_integration_activated_modules' );
		apply_filters_ref_array( SKAUTISINTEGRATION_NAME . '_activated_modules', $this->activated_modules );
		$this->register_activated_modules( $this->modules, $this->activated_modules );
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
		return in_array( $moduleName, $this->activated_modules, true );
	}

}
