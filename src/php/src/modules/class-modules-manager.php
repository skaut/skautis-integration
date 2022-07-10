<?php
/**
 * Contains the Modules_Manager class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Modules;

use Skautis_Integration\Modules\Register\Register;
use Skautis_Integration\Modules\Shortcodes\Shortcodes;
use Skautis_Integration\Modules\Visibility\Visibility;
use Skautis_Integration\Services\Services;

/**
 * Manages all the plugin modules.
 */
final class Modules_Manager {

	/**
	 * A list of all available modules in the form `id => label`, where label is already localized.
	 *
	 * @var array<string, string>
	 */
	private $modules = array();

	/**
	 * A list of all active modules.
	 *
	 * @var array<string>
	 */
	private $activated_modules = array();

	/**
	 * A list of initialized module instances.
	 *
	 * @var array<string, Module>
	 */
	private $instantiated_modules = array();

	/**
	 * Constructs the service and saves all dependencies.
	 *
	 * @param array<string, string> $modules A list of all available modules in the form `id => label`, where label is already localized.
	 */
	public function __construct( array $modules = array() ) {
		$this->modules           = apply_filters( SKAUTIS_INTEGRATION_NAME . '_modules', $modules );
		$this->activated_modules = (array) get_option( 'skautis_integration_activated_modules' );
		apply_filters_ref_array( SKAUTIS_INTEGRATION_NAME . '_activated_modules', $this->activated_modules );
		$this->register_activated_modules( $this->activated_modules );
	}

	/**
	 * Initializes all activated modules.
	 *
	 * @param array<string> $activated_modules A list of all active modules.
	 *
	 * @return void
	 */
	private function register_activated_modules( array $activated_modules = array() ) {
		if ( in_array( Register::get_id(), $activated_modules, true ) ) {
			$this->get_register_module();
		}
		if ( in_array( Shortcodes::get_id(), $activated_modules, true ) ) {
			$this->get_shortcodes_module();
		}
		if ( in_array( Visibility::get_id(), $activated_modules, true ) ) {
			$this->get_visibility_module();
		}
	}

	/**
	 * Returns a list of all modules (even inactive ones) in the form `id => label`, where label is already localized.
	 *
	 * @return array<string, string> The modules
	 */
	public function get_all_modules(): array {
		return $this->modules;
	}

	/**
	 * Checks whether a module is activated.
	 *
	 * @param string $module_name The ID of the module to check.
	 */
	public function is_module_activated( string $module_name ): bool {
		return in_array( $module_name, $this->activated_modules, true );
	}

	/**
	 * Returns an instance of the Register module.
	 *
	 * @return Register The initialized module.
	 */
	public function get_register_module() {
		$module_id = Register::get_id();
		if ( ! array_key_exists( $module_id, $this->instantiated_modules ) ) {
				$this->instantiated_modules[ $module_id ] = new Register( Services::get_skautis_gateway(), Services::get_skautis_login(), Services::get_wp_login_logout(), Services::get_rules_manager(), Services::get_repository_users() );
		}
		return $this->instantiated_modules[ $module_id ];
	}

	/**
	 * Returns an instance of the Shortcodes module.
	 *
	 * @return Shortcodes The initialized module.
	 */
	public function get_shortcodes_module() {
		$module_id = Shortcodes::get_id();
		if ( ! array_key_exists( $module_id, $this->instantiated_modules ) ) {
				$this->instantiated_modules[ $module_id ] = new Shortcodes( Services::get_rules_manager(), Services::get_skautis_login(), Services::get_wp_login_logout() );
		}
		return $this->instantiated_modules[ $module_id ];
	}

	/**
	 * Returns an instance of the Visibility module.
	 *
	 * @return Visibility The initialized module.
	 */
	public function get_visibility_module() {
		$module_id = Visibility::get_id();
		if ( ! array_key_exists( $module_id, $this->instantiated_modules ) ) {
				$this->instantiated_modules[ $module_id ] = new Visibility( Services::get_rules_manager(), Services::get_skautis_login(), Services::get_wp_login_logout() );
		}
		return $this->instantiated_modules[ $module_id ];
	}
}
