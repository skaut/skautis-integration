<?php

namespace SkautisIntegration\Services;

use SkautisIntegration\Admin\UsersManagement;
use SkautisIntegration\Auth\SkautisGateway;
use SkautisIntegration\Auth\SkautisLogin;
use SkautisIntegration\Auth\WpLoginLogout;
use SkautisIntegration\Auth\ConnectAndDisconnectWpAccount;
use SkautisIntegration\General\General;
use SkautisIntegration\General\Actions;
use SkautisIntegration\Frontend\Frontend;
use SkautisIntegration\Frontend\LoginForm;
use SkautisIntegration\Admin\Admin;
use SkautisIntegration\Admin\Settings;
use SkautisIntegration\Admin\Users;
use SkautisIntegration\Modules\ModulesManager;
use SkautisIntegration\Modules\Register\Register;
use SkautisIntegration\Rules\Revisions;
use SkautisIntegration\Rules\RulesInit;
use SkautisIntegration\Rules\RulesManager;

class Services {
	protected static $services = null;

	private static function init() {
		self::$services = new \Pimple\Container();
		self::registerServices();
	}

	private static function registerServices() {
		self::$services['skautisGateway'] = function ( \Pimple\Container $container ) {
			return new SkautisGateway();
		};

		self::$services['skautisLogin'] = function ( \Pimple\Container $container ) {
			return new SkautisLogin( $container['skautisGateway'], $container['wpLoginLogout'] );
		};

		self::$services['wpLoginLogout'] = function ( \Pimple\Container $container ) {
			return new WpLoginLogout( $container['skautisGateway'] );
		};

		self::$services['connectAndDisconnectWpAccount'] = function ( \Pimple\Container $container ) {
			return new ConnectAndDisconnectWpAccount( $container['skautisGateway'], $container['skautisLogin'] );
		};

		self::$services['rules_revisions'] = function ( \Pimple\Container $container ) {
			return new Revisions();
		};

		self::$services['rules_init'] = function ( \Pimple\Container $container ) {
			return new RulesInit( $container['rules_revisions'] );
		};

		self::$services['rules_manager'] = function ( \Pimple\Container $container ) {
			return new RulesManager( $container['skautisGateway'], $container['wpLoginLogout'] );
		};

		self::$services['general'] = function ( \Pimple\Container $container ) {
			return new General( $container['general_actions'], $container['rules_init'] );
		};

		self::$services['general_actions'] = function ( \Pimple\Container $container ) {
			return new Actions( $container['skautisLogin'], $container['wpLoginLogout'], $container['connectAndDisconnectWpAccount'] );
		};

		self::$services['frontend'] = function ( \Pimple\Container $container ) {
			return new Frontend( $container['frontend_loginForm'], $container['wpLoginLogout'], $container['skautisGateway'] );
		};

		self::$services['frontend_loginForm'] = function ( \Pimple\Container $container ) {
			return new LoginForm( $container['wpLoginLogout'] );
		};

		self::$services['admin'] = function ( \Pimple\Container $container ) {
			return new Admin( $container['admin_settings'], $container['admin_users'], $container['rules_manager'], $container['admin_usersManagement'], $container['wpLoginLogout'], $container['skautisGateway'] );
		};

		self::$services['admin_settings'] = function ( \Pimple\Container $container ) {
			return new Settings( $container['modulesManager'] );
		};

		self::$services['admin_users'] = function ( \Pimple\Container $container ) {
			return new Users( $container['connectAndDisconnectWpAccount'] );
		};

		self::$services['admin_usersManagement'] = function ( \Pimple\Container $container ) {
			return new UsersManagement( $container['skautisGateway'], $container['wpLoginLogout'], $container['skautisLogin'], $container['connectAndDisconnectWpAccount'] );
		};

		// Modules
		self::$services['modulesManager'] = function ( \Pimple\Container $container ) {
			return new ModulesManager( $container, [ // for hard modules activation/deactivation look to modules/ModulesManager WP filters
			                                         Register::getId() => Register::getLabel()
			] );
		};

		self::$services[ Register::getId() ] = function ( \Pimple\Container $container ) {
			return new Register( $container['skautisGateway'], $container['skautisLogin'], $container['wpLoginLogout'], $container['rules_manager'] );
		};
	}

	public static function getServicesContainer() {
		if ( self::$services === null ) {
			self::init();
		}

		return self::$services;
	}
}