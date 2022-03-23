<?php

declare( strict_types=1 );

use SkautisIntegration\Services\Services;
use SkautisIntegration\Modules\Register\Register;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
// phpcs:disable WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid

if ( ! function_exists( 'getSkautisLoginUrl' ) ) {
	function getSkautisLoginUrl(): string {
		return ( Services::get_services_container()['wpLoginLogout'] )->get_login_url();
	}
}

if ( ! function_exists( 'getSkautisLogoutUrl' ) ) {
	function getSkautisLogoutUrl(): string {
		return ( Services::get_services_container()['wpLoginLogout'] )->get_logout_url();
	}
}

if ( ! function_exists( 'getSkautisRegisterUrl' ) ) {
	function getSkautisRegisterUrl(): string {
		if ( Services::get_services_container()['modulesManager']->is_module_activated( Register::get_id() ) ) {
			return ( Services::get_services_container()[ Register::get_id() ] )->getWpRegister()->get_register_url();
		} else {
			return '';
		}
	}
}

if ( ! function_exists( 'isUserLoggedInSkautis' ) ) {
	function isUserLoggedInSkautis(): bool {
		return ( Services::get_services_container()['skautisLogin'] )->is_user_logged_in_skautis();
	}
}

if ( ! function_exists( 'userPassedRules' ) ) {
	function userPassedRules( array $rulesIds ): bool {
		return ( Services::get_services_container()['rules_manager'] )->check_if_user_passed_rules( $rulesIds );
	}
}
