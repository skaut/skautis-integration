<?php

use SkautisIntegration\Services\Services;
use SkautisIntegration\Modules\Register\Register;

if ( ! function_exists( "getSkautisLoginUrl" ) ) {
	function getSkautisLoginUrl() {
		return ( Services::getServicesContainer()['wpLoginLogout'] )->getLoginUrl();
	}
}

if ( ! function_exists( "getSkautisLogoutUrl" ) ) {
	function getSkautisLogoutUrl() {
		return ( Services::getServicesContainer()['wpLoginLogout'] )->getLogoutUrl();
	}
}

if ( ! function_exists( "getSkautisRegisterUrl" ) ) {
	function getSkautisRegisterUrl() {
		if ( Services::getServicesContainer()['modulesManager']->isModuleActivated( Register::getId() ) ) {
			return ( Services::getServicesContainer()[ Register::getId() ] )->getWpRegister()->getRegisterUrl();
		} else {
			return '';
		}
	}
}

if ( ! function_exists( "isUserLoggedInSkautis" ) ) {
	function isUserLoggedInSkautis() {
		return ( Services::getServicesContainer()['skautisLogin'] )->isUserLoggedInSkautis();
	}
}