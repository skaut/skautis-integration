<?php
/**
 * Contains the Modules_Manager class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Modules;

use Skautis_Integration\Vendor\Pimple\Container;

final class Modules_Manager {

	private $container;
	private $modules           = array();
	private $activated_modules = array();

	public function __construct( Container $container, array $modules = array() ) {
		$this->container         = $container;
		$this->modules           = apply_filters( SKAUTIS_INTEGRATION_NAME . '_modules', $modules );
		$this->activated_modules = (array) get_option( 'skautis_integration_activated_modules' );
		apply_filters_ref_array( SKAUTIS_INTEGRATION_NAME . '_activated_modules', $this->activated_modules );
		$this->register_activated_modules( $this->modules, $this->activated_modules );
	}

	private function register_activated_modules( array $modules = array(), array $activated_modules = array() ) {
		foreach ( $modules as $module_id => $module_label ) {
			if ( in_array( $module_id, $activated_modules, true ) ) {
				$this->container[ $module_id ];
			}
		}
	}

	public function get_all_modules(): array {
		return $this->modules;
	}

	public function is_module_activated( string $module_name ): bool {
		return in_array( $module_name, $this->activated_modules, true );
	}

}
