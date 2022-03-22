<?php

declare( strict_types=1 );

namespace SkautisIntegration\Services;

use SkautisIntegration\Vendor\Pimple\Container;
use SkautisIntegration\Admin\Users_Management;
use SkautisIntegration\Auth\Skautis_Gateway;
use SkautisIntegration\Auth\Skautis_Login;
use SkautisIntegration\Auth\WP_Login_Logout;
use SkautisIntegration\Auth\Connect_And_Disconnect_WP_Account;
use SkautisIntegration\General\General;
use SkautisIntegration\General\Actions;
use SkautisIntegration\Frontend\Frontend;
use SkautisIntegration\Frontend\Login_Form;
use SkautisIntegration\Admin\Admin;
use SkautisIntegration\Admin\Settings;
use SkautisIntegration\Admin\Users;
use SkautisIntegration\Repository\Users as UsersRepository;
use SkautisIntegration\Modules\Modules_Manager;
use SkautisIntegration\Modules\Register\Register;
use SkautisIntegration\Modules\Shortcodes\Shortcodes;
use SkautisIntegration\Modules\Visibility\Visibility;
use SkautisIntegration\Rules\Revisions;
use SkautisIntegration\Rules\Rules_Init;
use SkautisIntegration\Rules\Rules_Manager;
use SkautisIntegration\Utils\Role_Changer;

class Services {

	protected static $services = null;

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
			return new UsersRepository( $container['skautisGateway'] );
		};

		// Modules
		self::$services['modulesManager'] = function ( Container $container ) {
			return new Modules_Manager(
				$container,
				array( // for hard modules activation/deactivation look to modules/Modules_Manager WP filters
					Register::get_id()   => Register::getLabel(),
					Visibility::get_id() => Visibility::getLabel(),
					Shortcodes::get_id() => Shortcodes::getLabel(),
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
}
