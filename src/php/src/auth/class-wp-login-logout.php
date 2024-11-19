<?php
/**
 * Contains the WP_Login_Logout class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Auth;

use Skautis_Integration\General\Actions;
use Skautis_Integration\Utils\Helpers;
use Skautis_Integration\Services\Services;
use Skautis_Integration\Modules\Register\Register;

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
	 *
	 * @SuppressWarnings("PHPMD.ExitExpression")
	 */
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
					if ( Services::get_modules_manager()->is_module_activated( Register::get_id() ) &&
						! user_can( $wp_user->ID, Helpers::get_skautis_manager_capability() ) &&
						false !== get_option( SKAUTIS_INTEGRATION_NAME . '_checkUserPrivilegesIfLoginBySkautis' ) ) {
						if ( '' === Services::get_modules_manager()->get_register_module()->getRulesManager()->check_if_user_passed_rules_and_get_his_role() ) {
							/* translators: 1: Start of a link to SkautIS login 2: End of the link to SkautIS login */
							wp_die( sprintf( esc_html__( 'Je nám líto, ale již nemáte oprávnění k přístupu. %1$sZkuste se znovu zaregistrovat%2$s', 'skautis-integration' ), '<a href = "' . esc_url( Services::get_modules_manager()->get_register_module()->getWpRegister()->get_register_url() ) . '">', '</a>' ), esc_html__( 'Neautorizovaný přístup', 'skautis-integration' ) );
						}
					}
				}

				if ( is_user_logged_in() && get_current_user_id() === $wp_user->ID ) {
					wp_safe_redirect( $return_url, 302 );
					die();
				}

				wp_destroy_current_session();
				wp_clear_auth_cookie();
				wp_set_current_user( $wp_user->ID, $wp_user->data->user_login );
				wp_set_auth_cookie( $wp_user->ID, true );

				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				do_action( 'wp_login', $wp_user->user_login, $wp_user );

				wp_safe_redirect( $return_url, 302 );
				die();
			}
		}

		if ( ! $dont_die_on_error ) {
			if ( Services::get_modules_manager()->is_module_activated( Register::get_id() ) ) {
				/* translators: 1: Start of a link to SkautIS login 2: End of the link to SkautIS login */
				wp_die( sprintf( esc_html__( 'Nemáte oprávnění k přístupu. %1$sZkuste se nejdříve zaregistrovat%2$s', 'skautis-integration' ), '<a href ="' . esc_url( Services::get_modules_manager()->get_register_module()->getWpRegister()->get_register_url() ) . '">', '</a>' ), esc_html__( 'Neautorizovaný přístup', 'skautis-integration' ) );
			} else {
				$this->skautis_gateway->logout();
				wp_die( esc_html__( 'Nemáte oprávnění k přístupu', 'skautis-integration' ), esc_html__( 'Neautorizovaný přístup', 'skautis-integration' ) );
			}
		}

		return false;
	}

	/**
	 * Returns the SkautIS login URL with all arguments initialized.
	 *
	 * @param string $return_url The URL to return back to after the login.
	 *
	 * @suppress PhanPluginPossiblyStaticPublicMethod
	 */
	public function get_login_url( string $return_url = '' ): string {
		if ( '' === $return_url ) {
			$return_url = Helpers::get_login_logout_redirect();
		}

		$return_url = remove_query_arg( 'loggedout', urldecode( $return_url ) );

		if ( strpos( $return_url, 'wp-login.php' ) !== false ) {
			$return_url = admin_url();
		}

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
		if ( '' === $return_url ) {
			$return_url = Helpers::get_login_logout_redirect();
		}

		$return_url = remove_query_arg( 'loggedout', urldecode( $return_url ) );

		if ( strpos( $return_url, 'wp-login.php' ) !== false ) {
			$return_url = admin_url();
		}

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
	 *
	 * @SuppressWarnings("PHPMD.ExitExpression")
	 */
	public function logout() {
		$this->skautis_gateway->logout();

		wp_logout();
		wp_set_current_user( 0 );

		$return_url = Helpers::get_login_logout_redirect();
		wp_safe_redirect( esc_url_raw( $return_url ), 302 );
		die();
	}
}
