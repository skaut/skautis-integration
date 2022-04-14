<?php
/**
 * Contains function to be called from custom login view.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

use Skautis_Integration\Services\Services;
use Skautis_Integration\Modules\Register\Register;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
// phpcs:disable WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid

if ( ! function_exists( 'getSkautisLoginUrl' ) ) {
	/**
	 * Returns a URL for logging in to SkautIS.
	 */
	function getSkautisLoginUrl(): string {
		return Services::get_wp_login_logout()->get_login_url();
	}
}

if ( ! function_exists( 'getSkautisLogoutUrl' ) ) {
	/**
	 * Returns a URL for logging out of SkautIS.
	 */
	function getSkautisLogoutUrl(): string {
		return Services::get_wp_login_logout()->get_logout_url();
	}
}

if ( ! function_exists( 'getSkautisRegisterUrl' ) ) {
	/**
	 * Returns the Register module version of the SkautIS login URL with all arguments initialized.
	 *
	 * This version runs the module's register action after the login.
	 *
	 * @see	Register::register() The action that fires after the login.
	 */
	function getSkautisRegisterUrl(): string {
		if ( Services::get_modules_manager()->is_module_activated( Register::get_id() ) ) {
			return Services::get_module( Register::get_id() )->getWpRegister()->get_register_url();
		} else {
			return '';
		}
	}
}

if ( ! function_exists( 'isUserLoggedInSkautis' ) ) {
	/**
	 * Checks whether the current user is logged into SkautIS.
	 */
	function isUserLoggedInSkautis(): bool {
		return Services::get_skautis_login()->is_user_logged_in_skautis();
	}
}

if ( ! function_exists( 'userPassedRules' ) ) {
	/**
	 * Checks whether the current user passed plugin rules
	 */
	function userPassedRules( array $rules_ids ): bool {
		return Services::get_rules_manager()->check_if_user_passed_rules( $rules_ids );
	}
}
