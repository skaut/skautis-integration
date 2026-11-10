<?php
/**
 * Contains the WP_Login_Logout class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Auth;

use PHPMD\Attribute\SuppressWarnings;
use PHPMD\Rule\Design\ExitExpression;
use Skautis_Integration\General\Actions;
use Skautis_Integration\Utils\Helpers;
use Skautis_Integration\Services\Services;

/**
 * Handles the WordPress part of logging in with SkautIS.
 */
final class WP_Login_Logout {

	/**
	 * A link to the Skautis_Gateway service instance.
	 *
	 * @var Skautis_Gateway
	 */
	private $skautis_gateway;

	/**
	 * Constructs the service and saves all dependencies.
	 *
	 * @param Skautis_Gateway $skautis_gateway An injected Skautis_Gateway service instance.
	 */
	public function __construct( Skautis_Gateway $skautis_gateway ) {
		$this->skautis_gateway = $skautis_gateway;
	}

	/**
	 * Logs a user into WordPress based on their SkautIS user ID.
	 *
	 * @param int  $skautis_user_id The SkautIS user ID.
	 * @param bool $dont_die_on_error If true, this function will not exit with an error if the login fails.
	 *
	 * @return false
	 */
	#[SuppressWarnings( ExitExpression::class )]
	private function login_wp_user_by_skautis_user_id( int $skautis_user_id, $dont_die_on_error = false ) {
		$return_url = Helpers::get_return_url();
		if ( ! is_null( $return_url ) ) {
			// TODO: Replace with a call to get_users()?
			$users_wp_query = new \WP_User_Query(
				array(
					'number'     => 1,
					'meta_query' => array(
						array(
							'key'     => 'skautisUserId_' . $this->skautis_gateway->get_env(),
							'value'   => absint( $skautis_user_id ),
							'compare' => '=',
						),
					),
				)
			);
			$users          = $users_wp_query->get_results();

			if ( ! empty( $users )
				&& isset( $users[0] )
				&& isset( $users[0]->ID )
				&& $users[0]->ID > 0
			) {
				$wp_user = $users[0];

				if ( ! $dont_die_on_error ) {
					if ( Services::is_registration_enabled() &&
						! user_can( $wp_user->ID, Helpers::get_skautis_manager_capability() ) &&
						'1' === strval( get_option( SKAUTIS_INTEGRATION_NAME . '_checkUserPrivilegesIfLoginBySkautis', '0' ) ) ) {
						if ( '' === Services::get_register()->get_rules_manager()->get_role_if_user_passed_rules() ) {
							/* translators: 1: Start of a link to SkautIS login 2: End of the link to SkautIS login */
							wp_die( sprintf( esc_html__( 'Sorry, you no longer have access. %1$sTry to register again%2$s', 'skautis-integration' ), '<a href = "' . esc_url( Services::get_register()->get_wp_register()->get_register_url() ) . '">', '</a>' ), esc_html__( 'Unauthorized access', 'skautis-integration' ) );
						}
					}
				}

				if ( is_user_logged_in() && get_current_user_id() === $wp_user->ID ) {
					wp_safe_redirect( $return_url, 302 );
					exit();
				}

				wp_destroy_current_session();
				wp_clear_auth_cookie();
				wp_set_current_user( $wp_user->ID, $wp_user->data->user_login );
				wp_set_auth_cookie( $wp_user->ID, true );

				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				do_action( 'wp_login', $wp_user->user_login, $wp_user );

				wp_safe_redirect( $return_url, 302 );
				exit();
			}
		}

		if ( ! $dont_die_on_error ) {
			if ( Services::is_registration_enabled() ) {
				/* translators: 1: Start of a link to SkautIS login 2: End of the link to SkautIS login */
				wp_die( sprintf( esc_html__( 'You do not have access. %1$sTry to register first%2$s', 'skautis-integration' ), '<a href ="' . esc_url( Services::get_register()->get_wp_register()->get_register_url() ) . '">', '</a>' ), esc_html__( 'Unauthorized access', 'skautis-integration' ) );
			} else {
				$this->skautis_gateway->logout();
				wp_die( esc_html__( 'You do not have access', 'skautis-integration' ), esc_html__( 'Unauthorized access', 'skautis-integration' ) );
			}
		}

		return false;
	}

	/**
	 * Prepares the URL to return back to after the login or logout.
	 *
	 * @param string $return_url The requested return URL, or an empty string to use the redirect from the request or the current URL.
	 *
	 * @return string The return URL.
	 */
	private static function prepare_return_url( string $return_url ): string {
		$is_current_url = false;
		if ( '' === $return_url ) {
			$return_url     = Helpers::get_login_logout_redirect();
			$is_current_url = Helpers::get_current_url() === $return_url;
		}

		$return_url = remove_query_arg( 'loggedout', urldecode( $return_url ) );

		// Never return back to the login screen. is_login() also recognizes login screens moved to a custom URL.
		if ( ( $is_current_url && is_login() ) || false !== strpos( $return_url, 'wp-login.php' ) ) {
			$return_url = admin_url();
		}

		return $return_url;
	}

	/**
	 * Returns the SkautIS login URL with all arguments initialized.
	 *
	 * @param string $return_url The URL to return back to after the login.
	 *
	 * @suppress PhanPluginPossiblyStaticPublicMethod
	 */
	public function get_login_url( string $return_url = '' ): string {
		$return_url = self::prepare_return_url( $return_url );

		$url = add_query_arg( 'ReturnUrl', rawurlencode( $return_url ), get_home_url( null, 'skautis/auth/' . Actions::LOGIN_ACTION ) );

		return esc_url( $url );
	}

	/**
	 * Returns the SkautIS logout URL with all arguments and nonces initialized.
	 *
	 * @param string $return_url The URL to return back to after the logout.
	 *
	 * @suppress PhanPluginPossiblyStaticPublicMethod
	 */
	public function get_logout_url( string $return_url = '' ): string {
		$return_url = self::prepare_return_url( $return_url );

		$return_url = add_query_arg( SKAUTIS_INTEGRATION_NAME . '_logoutFromWpAndSkautis', wp_create_nonce( SKAUTIS_INTEGRATION_NAME . '_logoutFromWpAndSkautis' ), $return_url );
		$url        = add_query_arg( 'ReturnUrl', rawurlencode( $return_url ), get_home_url( null, 'skautis/auth/' . Actions::LOGOUT_CONFIRM_ACTION ) );

		return esc_url( $url );
	}

	/**
	 * Logs the current SkautIS user into WordPress.
	 *
	 * @return void
	 */
	public function login_to_wp() {
		$user_detail = $this->skautis_gateway->get_skautis_instance()->UserManagement->UserDetail();

		if ( $user_detail && isset( $user_detail->ID ) && $user_detail->ID > 0 ) {
			$this->login_wp_user_by_skautis_user_id( $user_detail->ID );
		}
	}

	/**
	 * Logs the current SkautIS user into WordPress.
	 *
	 * This version of the function doesn't produce an error if the login procedure fails.
	 *
	 * TODO: Deduplicate with login_to_wp().
	 *
	 * @return void
	 */
	public function try_to_login_to_wp() {
		$user_detail = $this->skautis_gateway->get_skautis_instance()->UserManagement->UserDetail();

		if ( $user_detail && isset( $user_detail->ID ) && $user_detail->ID > 0 ) {
			$this->login_wp_user_by_skautis_user_id( $user_detail->ID, true );
		}
	}

	/**
	 * Handles a call to log the user out of SkautIS.
	 *
	 * @see Actions::auth_actions_router() for more details about how this function gets called.
	 *
	 * @return void
	 */
	#[SuppressWarnings( ExitExpression::class )]
	public function logout() {
		// The nonce is added to the return URL by get_logout_url(); verifying it prevents logout CSRF.
		Helpers::validate_nonce_from_url( Helpers::get_return_url() ?? '', SKAUTIS_INTEGRATION_NAME . '_logoutFromWpAndSkautis' );

		$this->skautis_gateway->logout();

		wp_logout();
		wp_set_current_user( 0 );

		$return_url = Helpers::get_login_logout_redirect();
		wp_safe_redirect( esc_url_raw( $return_url ), 302 );
		exit();
	}
}
