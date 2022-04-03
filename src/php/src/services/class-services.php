<?php

declare( strict_types=1 );

namespace Skautis_Integration\Services;

use Skautis_Integration\Vendor\Pimple\Container;
use Skautis_Integration\Admin\Users_Management;
use Skautis_Integration\Auth\Skautis_Gateway;
use Skautis_Integration\Auth\Skautis_Login;
use Skautis_Integration\Auth\WP_Login_Logout;
use Skautis_Integration\Auth\Connect_And_Disconnect_WP_Account;
use Skautis_Integration\General\General;
use Skautis_Integration\General\Actions;
use Skautis_Integration\Frontend\Frontend;
use Skautis_Integration\Frontend\Login_Form;
use Skautis_Integration\Admin\Admin;
use Skautis_Integration\Admin\Settings;
use Skautis_Integration\Admin\Users;
use Skautis_Integration\Modules\Module;
use Skautis_Integration\Modules\Modules_Manager;
use Skautis_Integration\Modules\Register\Register;
use Skautis_Integration\Modules\Shortcodes\Shortcodes;
use Skautis_Integration\Modules\Visibility\Visibility;
use Skautis_Integration\Repository\Users as Repository_Users;
use Skautis_Integration\Rules\Revisions;
use Skautis_Integration\Rules\Rules_Init;
use Skautis_Integration\Rules\Rules_Manager;
use Skautis_Integration\Utils\Role_Changer;

class Services {

	protected static $services = null;

	/**
	 * All the plugin modules.
	 *
	 * @var array<Module>
	 */
	private static $modules = array();

	/**
	 * A Skautis_Gateway service instance.
	 *
	 * @var Skautis_Gateway|null
	 */
	private static $skautis_gateway = null;

	/**
	 * A WP_Login_Logout service instance.
	 *
	 * Depends on $skautis_gateway.
	 *
	 * @var WP_Login_Logout|null
	 */
	private static $wp_login_logout = null;

	/**
	 * A Skautis_Login service instance.
	 *
	 * Depends on $skautis_gateway and $wp_login_logout.
	 *
	 * @var Skautis_Login|null
	 */
	private static $skautis_login = null;

	/**
	 * A Connect_And_Disconnect_WP_Account service instance.
	 *
	 * Depends on $skautis_gateway and $skautis_login.
	 *
	 * @var Connect_And_Disconnect_WP_Account|null
	 */
	private static $connect_and_disconnect_wp_account = null;

	/**
	 * An Actions service instance.
	 *
	 * Depends on $skautis_login, $wp_login_logout, $connect_and_disconnect_wp_account and $skautis_gateway.
	 *
	 * @var Actions|null
	 */
	private static $actions = null;

	/**
	 * A Revisions service instance.
	 *
	 * @var Revisions|null
	 */
	private static $revisions = null;

	/**
	 * A Rules_Init service instance.
	 *
	 * Depends on $revisions.
	 *
	 * @var Rules_Init|null
	 */
	private static $rules_init = null;

	/**
	 * A General service instance.
	 *
	 * Depends on $revisions.
	 *
	 * @var General|null
	 */
	private static $general = null;

	/**
	 * A Repository_Users service instance.
	 *
	 * Depends on $skautis_gateway.
	 *
	 * @var Repository_Users|null
	 */
	private static $repository_users = null;

	/**
	 * A Rules_Manager service instance.
	 *
	 * Depends on $skautis_gateway and $wp_login_logout.
	 *
	 * @var Rules_Manager|null
	 */
	private static $rules_manager = null;

	protected static function init() {
		self::$services = new Container();
		self::register_services();
	}

	protected static function register_services() {
		self::$services['skautisGateway'] = function ( Container $container ) {
			return new Skautis_Gateway();
		};

		self::$services['skautisLogin'] = function ( Container $container ) {
			return new Skautis_Login( $container['skautisGateway'], $container['wpLoginLogout'] );
		};

		self::$services['wpLoginLogout'] = function ( Container $container ) {
			return new WP_Login_Logout( $container['skautisGateway'] );
		};

		self::$services['connectAndDisconnectWpAccount'] = function ( Container $container ) {
			return new Connect_And_Disconnect_WP_Account( $container['skautisGateway'], $container['skautisLogin'] );
		};

		self::$services['rules_revisions'] = function ( Container $container ) {
			return new Revisions();
		};

		self::$services['rules_init'] = function ( Container $container ) {
			return new Rules_Init( $container['rules_revisions'] );
		};

		self::$services['rules_manager'] = function ( Container $container ) {
			return new Rules_Manager( $container['skautisGateway'], $container['wpLoginLogout'] );
		};

		self::$services['general'] = function ( Container $container ) {
			return new General( $container['general_actions'], $container['rules_init'] );
		};

		self::$services['general_actions'] = function ( Container $container ) {
			return new Actions( $container['skautisLogin'], $container['wpLoginLogout'], $container['connectAndDisconnectWpAccount'], $container['skautisGateway'] );
		};

		self::$services['frontend'] = function ( Container $container ) {
			return new Frontend( $container['frontend_loginForm'], $container['wpLoginLogout'], $container['skautisGateway'] );
		};

		self::$services['frontend_loginForm'] = function ( Container $container ) {
			return new Login_Form( $container['wpLoginLogout'] );
		};

		self::$services['admin'] = function ( Container $container ) {
			return new Admin( $container['admin_settings'], $container['admin_users'], $container['rules_manager'], $container['admin_usersManagement'], $container['wpLoginLogout'], $container['skautisGateway'] );
		};

		self::$services['admin_settings'] = function ( Container $container ) {
			return new Settings( $container['skautisGateway'], $container['modulesManager'] );
		};

		self::$services['admin_users'] = function ( Container $container ) {
			return new Users( $container['connectAndDisconnectWpAccount'] );
		};

		self::$services['admin_usersManagement'] = function ( Container $container ) {
			return new Users_Management( $container['skautisGateway'], $container['wpLoginLogout'], $container['skautisLogin'], $container['connectAndDisconnectWpAccount'], $container['repository_users'], $container['utils_roleChanger'] );
		};

		self::$services['utils_roleChanger'] = function ( Container $container ) {
			return new Role_Changer( $container['skautisGateway'], $container['skautisLogin'] );
		};

		// Repositories
		self::$services['repository_users'] = function ( Container $container ) {
			return new Repository_Users( $container['skautisGateway'] );
		};

		// Modules
		self::$services['modulesManager'] = function ( Container $container ) {
			return new Modules_Manager(
				array( // for hard modules activation/deactivation look to modules/Modules_Manager WP filters
					Register::get_id()   => Register::get_label(),
					Visibility::get_id() => Visibility::get_label(),
					Shortcodes::get_id() => Shortcodes::get_label(),
				)
			);
		};

		self::$services[ Register::get_id() ] = function ( Container $container ) {
			return new Register( $container['skautisGateway'], $container['skautisLogin'], $container['wpLoginLogout'], $container['rules_manager'], $container['repository_users'] );
		};

		self::$services[ Visibility::get_id() ] = function ( Container $container ) {
			return new Visibility( $container['rules_manager'], $container['skautisLogin'], $container['wpLoginLogout'] );
		};

		self::$services[ Shortcodes::get_id() ] = function ( Container $container ) {
			return new Shortcodes( $container['rules_manager'], $container['skautisLogin'], $container['wpLoginLogout'] );
		};
	}

	public static function get_services_container(): Container {
		if ( is_null( self::$services ) ) {
			self::init();
		}

		return self::$services;
	}

	/**
	 * Gets an instance of a module.
	 *
	 * @return Module The initialized modules instance.
	 */
	public static function get_module( $module_id ) {
		if ( ! array_key_exists( $module_id, self::$modules ) ) {
			switch ( $module_id ) {
				case Register::get_id():
					self::$modules[ $module_id ] = new Register( self::get_skautis_gateway(), self::get_skautis_login(), self::get_wp_login_logout(), self::get_rules_manager(), self::get_repository_users() );
					break;
				case Shortcodes::get_id():
					self::$modules[ $module_id ] = new Shortcodes( self::get_rules_manager(), self::get_skautis_login(), self::get_wp_login_logout() );
					break;
				case Visibility::get_id():
					self::$modules[ $module_id ] = new Visibility( self::get_rules_manager(), self::get_skautis_login(), self::get_wp_login_logout() );
					break;
			}
		}
		return self::$modules[ $module_id ];
	}

	/**
	 * Gets the Skautis_Gateway service.
	 *
	 * @return Skautis_Gateway The initialized service object.
	 */
	private static function get_skautis_gateway() {
		if ( is_null( self::$skautis_gateway ) ) {
			self::$skautis_gateway = new Skautis_Gateway();
		}
		return self::$skautis_gateway;
	}

	/**
	 * Gets the WP_Login_Logout service.
	 *
	 * @return WP_Login_Logout The initialized service object.
	 */
	private static function get_wp_login_logout() {
		if ( is_null( self::$wp_login_logout ) ) {
			self::$wp_login_logout = new WP_Login_Logout( self::get_skautis_gateway() );
		}
		return self::$wp_login_logout;
	}

	/**
	 * Gets the Skautis_Login service.
	 *
	 * @return Skautis_Login The initialized service object.
	 */
	private static function get_skautis_login() {
		if ( is_null( self::$skautis_login ) ) {
			self::$skautis_login = new Skautis_Login( self::get_skautis_gateway(), self::get_wp_login_logout() );
		}
		return self::$skautis_login;
	}

	/**
	 * Gets the Connect_And_Disconnect_WP_Account service.
	 *
	 * @return Connect_And_Disconnect_WP_Account The initialized service object.
	 */
	private static function get_connect_and_disconnect_wp_account() {
		if ( is_null( self::$connect_and_disconnect_wp_account ) ) {
			self::$connect_and_disconnect_wp_account = new Connect_And_Disconnect_WP_Account( self::get_skautis_gateway(), self::get_skautis_login() );
		}
		return self::$connect_and_disconnect_wp_account;
	}

	/**
	 * Gets the Actions service.
	 *
	 * @return Actions The initialized service object.
	 */
	private static function get_actions() {
		if ( is_null( self::$actions ) ) {
			self::$actions = new Actions( self::get_skautis_login(), self::get_wp_login_logout(), self::get_connect_and_disconnect_wp_account(), self::get_skautis_gateway() );
		}
		return self::$actions;
	}

	/**
	 * Gets the Revisions service.
	 *
	 * @return Revisions The initialized service object.
	 */
	private static function get_revisions() {
		if ( is_null( self::$revisions ) ) {
			self::$revisions = new Revisions();
		}
		return self::$revisions;
	}

	/**
	 * Gets the Rules_Init service.
	 *
	 * @return Rules_Init The initialized service object.
	 */
	private static function get_rules_init() {
		if ( is_null( self::$rules_init ) ) {
			self::$rules_init = new Rules_Init( self::get_revisions() );
		}
		return self::$rules_init;
	}

	/**
	 * Gets the General service.
	 *
	 * @return General The initialized service object.
	 */
	public static function get_general() {
		if ( is_null( self::$general ) ) {
			self::$general = new General( self::get_actions(), self::get_rules_init() );
		}
		return self::$general;
	}

	/**
	 * Gets the Repository\Users service.
	 *
	 * @return Repository_Users The initialized service object.
	 */
	public static function get_repository_users() {
		if ( is_null( self::$repository_users ) ) {
			self::$repository_users = new Repository_Users( self::get_skautis_gateway() );
		}
		return self::$repository_users;
	}

	/**
	 * Gets the Rules_Manager service.
	 *
	 * @return Rules_Manager The initialized service object.
	 */
	public static function get_rules_manager() {
		if ( is_null( self::$rules_manager ) ) {
			self::$rules_manager = new Rules_Manager( self::get_skautis_gateway(), self::get_wp_login_logout() );
		}
		return self::$rules_manager;
	}
}
