<?php

declare( strict_types=1 );

namespace SkautisIntegration\Modules;

use Pimple\Container;

final class ModulesManager {

	private $container;
	private $modules = [];
	private $activatedModules = [];

	public function __construct( Container $container, array $modules = [] ) {
		$this->container        = $container;
		$this->modules          = apply_filters( SKAUTISINTEGRATION_NAME . '_modules', $modules );
		$this->activatedModules = (array) get_option( 'skautis_integration_activated_modules' );
		apply_filters_ref_array( SKAUTISINTEGRATION_NAME . '_activated_modules', $this->activatedModules );
		$this->registerActivatedModules( $this->modules, $this->activatedModules );
	}

	private function registerActivatedModules( array $modules = [], array $activatedModules = [] ) {
		foreach ( $modules as $moduleId => $moduleLabel ) {
			if ( in_array( $moduleId, $activatedModules ) ) {
				$this->container[ $moduleId ];
			}
		}
	}

	public function getAllModules(): array {
		return $this->modules;
	}

	public function isModuleActivated( string $moduleName ): bool {
		return in_array( $moduleName, $this->activatedModules );
	}

}