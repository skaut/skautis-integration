<?php

namespace SkautisIntegration\Modules;

final class ModulesManager {

	private $container;
	private $modules = [];
	private $activatedModules = [];

	public function __construct( \Pimple\Container $container, array $modules = [] ) {
		$this->container        = $container;
		$this->modules          = apply_filters( SKAUTISINTEGRATION_NAME . '_modules', $modules );
		$this->activatedModules = (array) get_option( 'skautis_integration_activated_modules' );
		apply_filters_ref_array( SKAUTISINTEGRATION_NAME . '_activated_modules', $this->activatedModules );
		$this->registerActivatedModules();
	}

	private function registerActivatedModules() {
		foreach ( (array) $this->modules as $moduleId => $moduleLabel ) {
			if ( in_array( $moduleId, $this->activatedModules ) ) {
				$this->container[ $moduleId ];
			}
		}
	}

	public function getAllModules() {
		return $this->modules;
	}

	public function isModuleActivated( $moduleName ) {
		return in_array( $moduleName, $this->activatedModules );
	}

}