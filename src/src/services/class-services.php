<?php

declare( strict_types=1 );

namespace SkautisIntegration\Services;

use SkautisIntegration\Vendor\Pimple\Container;
use SkautisIntegration\Admin\UsersManagement;
use SkautisIntegration\Auth\SkautisGateway;
use SkautisIntegration\Auth\Skautis_Login;
use SkautisIntegration\Auth\WP_Login_Logout;
use SkautisIntegration\Auth\ConnectAndDisconnectWpAccount;
use SkautisIntegration\General\General;
use SkautisIntegration\General\Actions;
use SkautisIntegration\Frontend\Frontend;
use SkautisIntegration\Frontend\LoginForm;
use SkautisIntegration\Admin\Admin;
use SkautisIntegration\Admin\Settings;
use SkautisIntegration\Admin\Users;
use SkautisIntegration\Repository\Users as UsersRepository;
use SkautisIntegration\Modules\ModulesManager;
use SkautisIntegration\Modules\Register\Register;
use SkautisIntegration\Modules\Shortcodes\Shortcodes;
use SkautisIntegration\Modules\Visibility\Visibility;
use SkautisIntegration\Rules\Revisions;
use SkautisIntegration\Rules\RulesInit;
use SkautisIntegration\Rules\Rules_Manager;
use SkautisIntegration\Utils\RoleChanger;

class Services {

	protected static $services = null;

	protected static function init() {
		self::$services = new Container();
		self::registerServices();
	}

	protected static function registerServices() {
		self::$services['skautisGateway'] = function ( Container $container ) {
			return new SkautisGateway();
		};

		self::$services['skautisLogin'] = function ( Container $container ) {
			return new Skautis_Login( $container['skautisGateway'], $container['wpLoginLogout'] );
		};

		self::$services['wpLoginLogout'] = function ( Container $container ) {
			return new WP_Login_Logout( $container['skautisGateway'] );
		};

		self::$services['connectAndDisconnectWpAccount'] = function ( Container $container ) {
			return new ConnectAndDisconnectWpAccount( $container['skautisGateway'], $container['skautisLogin'] );
		};

		self::$services['rules_revisions'] = function ( Container $container ) {
			return new Revisions();
		};

		self::$services['rules_init'] = function ( Container $container ) {
			return new RulesInit( $container['rules_revisions'] );
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
			return new LoginForm( $container['wpLoginLogout'] );
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
			return new UsersManagement( $container['skautisGateway'], $container['wpLoginLogout'], $container['skautisLogin'], $container['connectAndDisconnectWpAccount'], $container['repository_users'], $container['utils_roleChanger'] );
		};

		self::$services['utils_roleChanger'] = function ( Container $container ) {
			return new RoleChanger( $container['skautisGateway'], $container['skautisLogin'] );
		};

		// Repositories
		self::$services['repository_users'] = function ( Container $container ) {
			return new UsersRepository( $container['skautisGateway'] );
		};

		// Modules
		self::$services['modulesManager'] = function ( Container $container ) {
			return new ModulesManager(
				$container,
				array( // for hard modules activation/deactivation look to modules/ModulesManager WP filters
					Register::getId()   => Register::getLabel(),
					Visibility::getId() => Visibility::getLabel(),
					Shortcodes::getId() => Shortcodes::getLabel(),
				)
			);
		};

		self::$services[ Register::getId() ] = function ( Container $container ) {
			return new Register( $container['skautisGateway'], $container['skautisLogin'], $container['wpLoginLogout'], $container['rules_manager'], $container['repository_users'] );
		};

		self::$services[ Visibility::getId() ] = function ( Container $container ) {
			return new Visibility( $container['rules_manager'], $container['skautisLogin'], $container['wpLoginLogout'] );
		};

		self::$services[ Shortcodes::getId() ] = function ( Container $container ) {
			return new Shortcodes( $container['rules_manager'], $container['skautisLogin'], $container['wpLoginLogout'] );
		};
	}

	public static function getServicesContainer(): Container {
		if ( is_null( self::$services ) ) {
			self::init();
		}

		return self::$services;
	}
}
