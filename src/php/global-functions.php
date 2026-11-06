<?php
/**
 * Contains function to be called from custom login view.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Skautis_Integration\Services\Services;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
// phpcs:disable WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid

if ( ! function_exists( 'getSkautisLoginUrl' ) ) {
	/**
	 * Returns a URL for logging in to skautIS.
	 */
	function getSkautisLoginUrl(): string {
		return Services::get_wp_login_logout()->get_login_url();
	}
}

if ( ! function_exists( 'getSkautisLogoutUrl' ) ) {
	/**
	 * Returns a URL for logging out of skautIS.
	 */
	function getSkautisLogoutUrl(): string {
		return Services::get_wp_login_logout()->get_logout_url();
	}
}

if ( ! function_exists( 'getSkautisRegisterUrl' ) ) {
	/**
	 * Returns the registration version of the skautIS login URL with all arguments initialized.
	 *
	 * This version runs the register action after the login. Returns an empty string when the registration is disabled.
	 *
	 * @see Register::register() The action that fires after the login.
	 */
	function getSkautisRegisterUrl(): string {
		if ( Services::is_registration_enabled() ) {
			return Services::get_register()->get_wp_register()->get_register_url();
		} else {
			return '';
		}
	}
}

if ( ! function_exists( 'isUserLoggedInSkautis' ) ) {
	/**
	 * Checks whether the current user is logged into skautIS.
	 */
	function isUserLoggedInSkautis(): bool {
		return Services::get_skautis_login()->is_user_logged_in_skautis();
	}
}

if ( ! function_exists( 'userPassedRules' ) ) {
	/**
	 * Checks whether the current user passed plugin rules
	 *
	 * @param array<array{skautis-integration_rules: string}> $rules_ids A list of IDs of rules or rule groups to check.
	 */
	function userPassedRules( array $rules_ids ): bool {
		return Services::get_rules_manager()->check_if_user_passed_rules( $rules_ids );
	}
}
