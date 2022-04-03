<?php

declare( strict_types=1 );

namespace Skautis_Integration\Services;

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
	 * A Repository\Users service instance.
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

	/**
	 * A Modules_Manager service instance.
	 *
	 * @var Modules_Manager|null
	 */
	private static $modules_manager = null;

	/**
	 * An Admin\Settings service instance.
	 *
	 * Depends on $skautis_gateway and $modules_manager.
	 *
	 * @var Settings|null
	 */
	private static $admin_settings = null;

	/**
	 * An Admin\Users service instance.
	 *
	 * Depends on $connect_and_disconnect_wp_account.
	 *
	 * @var Users|null
	 */
	private static $admin_users = null;

	/**
	 * A Role_Changer service instance.
	 *
	 * Depends on $skautis_gateway and $skautis_login.
	 *
	 * @var Role_Changer|null
	 */
	private static $role_changer = null;

	/**
	 * A Users_Management service instance.
	 *
	 * Depends on $skautis_gateway, $wp_login_logout, $skautis_login, $connect_and_disconnect_wp_account, $repository_users and $role_changer.
	 *
	 * @var Users_Management|null
	 */
	private static $users_management = null;

	/**
	 * An Admin service instance.
	 *
	 * Depends on $admin_settings, $admin_users, $rules_manager, $users_management, $wp_login_logout and $skautis_gateway.
	 *
	 * @var Admin|null
	 */
	private static $admin = null;

	/**
	 * A Login_Form service instance.
	 *
	 * Depends on $wp_login_logout.
	 *
	 * @var Login_Form|null
	 */
	private static $login_form = null;

	/**
	 * A Frontend service instance.
	 *
	 * Depends on $login_form, $wp_login_logout and $skautis_gateway.
	 *
	 * @var Frontend|null
	 */
	private static $frontend = null;

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
	public static function get_wp_login_logout() {
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
	public static function get_skautis_login() {
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
	private static function get_repository_users() {
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

	/**
	 * Gets the Modules_Manager service.
	 *
	 * @return Modules_Manager The initialized service object.
	 */
	public static function get_modules_manager() {
		if ( is_null( self::$modules_manager ) ) {
			self::$modules_manager = new Modules_Manager(
				array( // for hard modules activation/deactivation look to modules/Modules_Manager WP filters
					Register::get_id()   => Register::get_label(),
					Visibility::get_id() => Visibility::get_label(),
					Shortcodes::get_id() => Shortcodes::get_label(),
				)
			);
		}
		return self::$modules_manager;
	}

	/**
	 * Gets the Admin\Settings service.
	 *
	 * @return Settings The initialized service object.
	 */
	private static function get_admin_settings() {
		if ( is_null( self::$admin_settings ) ) {
			self::$admin_settings = new Settings( self::get_skautis_gateway(), self::get_modules_manager() );
		}
		return self::$admin_settings;
	}

	/**
	 * Gets the Admin\Users service.
	 *
	 * @return Users The initialized service object.
	 */
	private static function get_admin_users() {
		if ( is_null( self::$admin_users ) ) {
			self::$admin_users = new Users( self::get_connect_and_disconnect_wp_account() );
		}
		return self::$admin_users;
	}

	/**
	 * Gets the Role_Changer service.
	 *
	 * @return Role_Changer The initialized service object.
	 */
	private static function get_role_changer() {
		if ( is_null( self::$role_changer ) ) {
			self::$role_changer = new Role_Changer( self::get_skautis_gateway(), self::get_skautis_login() );
		}
		return self::$role_changer;
	}

	/**
	 * Gets the Users_Management service.
	 *
	 * @return Users_Management The initialized service object.
	 */
	private static function get_users_management() {
		if ( is_null( self::$users_management ) ) {
			self::$users_management = new Users_Management(
				self::get_skautis_gateway(),
				self::get_wp_login_logout(),
				self::get_skautis_login(),
				self::get_connect_and_disconnect_wp_account(),
				self::get_repository_users(),
				self::get_role_changer()
			);
		}
		return self::$users_management;
	}

	/**
	 * Gets the Admin service.
	 *
	 * @return Admin The initialized service object.
	 */
	public static function get_admin() {
		if ( is_null( self::$admin ) ) {
			self::$admin = new Admin(
				self::get_admin_settings(),
				self::get_admin_users(),
				self::get_rules_manager(),
				self::get_users_management(),
				self::get_wp_login_logout(),
				self::get_skautis_gateway()
			);
		}
		return self::$admin;
	}

	/**
	 * Gets the Login_Form service.
	 *
	 * @return Login_Form The initialized service object.
	 */
	private static function get_login_form() {
		if ( is_null( self::$login_form ) ) {
			self::$login_form = new Login_Form( self::get_wp_login_logout() );
		}
		return self::$login_form;
	}

	/**
	 * Gets the Frontend service.
	 *
	 * @return Frontend The initialized service object.
	 */
	public static function get_frontend() {
		if ( is_null( self::$frontend ) ) {
			self::$frontend = new Frontend( self::get_login_form(), self::get_wp_login_logout(), self::get_skautis_gateway() );
		}
		return self::$frontend;
	}
}
