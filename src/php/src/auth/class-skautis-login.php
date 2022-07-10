<?php
/**
 * Contains the Skautis_Login class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Auth;

use Skautis_Integration\Utils\Helpers;

/**
 * Enables the "Log in with SkautIS" functionality of the plugin.
 */
final class Skautis_Login {

	/**
	 * A link to the Skautis_Gateway service instance.
	 *
	 * @var Skautis_Gateway
	 */
	private $skautis_gateway;

	/**
	 * A link to the WP_Login_Logout service instance.
	 *
	 * @var WP_Login_Logout
	 */
	private $wp_login_logout;

	/**
	 * Constructs the service and saves all dependencies.
	 *
	 * @param Skautis_Gateway $skautis_gateway An injected Skautis_Gateway service instance.
	 * @param WP_Login_Logout $wp_login_logout An injected WP_Login_Logout service instance.
	 */
	public function __construct( Skautis_Gateway $skautis_gateway, WP_Login_Logout $wp_login_logout ) {
		$this->skautis_gateway = $skautis_gateway;
		$this->wp_login_logout = $wp_login_logout;
	}

	/**
	 * Checks whether the current user is logged into SkautIS.
	 */
	public function is_user_logged_in_skautis(): bool {
		if ( $this->skautis_gateway->is_initialized() ) {
			return $this->skautis_gateway->get_skautis_instance()->getUser()->isLoggedIn() && $this->skautis_gateway->get_skautis_instance()->getUser()->isLoggedIn( true );
		}

		return false;
	}

	/**
	 * Takes the data returned from SkautIS login and passes it to the SkautIS library.
	 *
	 * @param array $data The SkautIS login data.
	 */
	public function set_login_data_to_local_skautis_instance( array $data = array() ): bool {
		$data = apply_filters( SKAUTIS_INTEGRATION_NAME . '_login_data_for_skautis_instance', $data );

		if ( isset( $data['skautIS_Token'] ) ) {
			$this->skautis_gateway->get_skautis_instance()->setLoginData( $data );

			if ( ! $this->is_user_logged_in_skautis() ) {
				return false;
			}

			do_action( SKAUTIS_INTEGRATION_NAME . '_after_user_is_logged_in_skautis', $data );

			return true;
		}

		return false;
	}

	/**
	 * Handles a call to log the user into SkautIS.
	 *
	 * This function also handles calls to login just to SkautIS, usually the user is already logged into WordPress and is trying to access functionality that needs SkautIS info. This functionality can be triggered by the "noWpLogin" GET parameter.
	 *
	 * Also, the login procedure can be forced to log the user out before the login by the "logoutFromSkautis" GET parameter.
	 *
	 * @see Actions::auth_actions_router() for more details about how this function gets called.
	 *
	 * @return void
	 *
	 * @SuppressWarnings(PHPMD.ExitExpression)
	 */
	public function login() {
		$return_url = Helpers::get_login_logout_redirect();

		if ( strpos( $return_url, 'logoutFromSkautis' ) !== false ) {
			$this->skautis_gateway->logout();
			$return_url = remove_query_arg( 'logoutFromSkautis', $return_url );
		}

		if ( ! $this->is_user_logged_in_skautis() ) {
			wp_safe_redirect( esc_url_raw( $this->skautis_gateway->get_skautis_instance()->getLoginUrl( $return_url ) ), 302 );
			die();
		}

		if ( strpos( $return_url, 'noWpLogin' ) !== false ) {
			$this->wp_login_logout->try_to_login_to_wp();
			wp_safe_redirect( esc_url_raw( $return_url ), 302 );
			die();
		} else {
			$this->wp_login_logout->login_to_wp();
		}
	}

	/**
	 * Fires upon redirect back from SkautIS login and processes the login.
	 *
	 * @return void
	 *
	 * @SuppressWarnings(PHPMD.ExitExpression)
	 */
	public function login_confirm() {
		$return_url = Helpers::get_return_url();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( $this->set_login_data_to_local_skautis_instance( $_POST ) ) {
			if ( strpos( $return_url, 'noWpLogin' ) === false ) {
				$this->wp_login_logout->login_to_wp();
			}
			$this->wp_login_logout->try_to_login_to_wp();
			wp_safe_redirect( $return_url, 302 );
			die();
		} elseif ( $this->is_user_logged_in_skautis() ) {
			if ( strpos( $return_url, 'noWpLogin' ) === false ) {
				$this->wp_login_logout->login_to_wp();
			}
			$this->wp_login_logout->try_to_login_to_wp();
			wp_safe_redirect( $return_url, 302 );
			die();
		}
	}

	/**
	 * Changes the user's role in SkautIS.
	 *
	 * @param int $role_id The ID of the new role.
	 *
	 * @return void
	 */
	public function change_user_role_in_skautis( int $role_id ) {
		if ( $role_id > 0 ) {
			$result = $this->skautis_gateway->get_skautis_instance()->UserManagement->LoginUpdate(
				array(
					'ID'          => $this->skautis_gateway->get_skautis_instance()->getUser()->getLoginId(),
					'ID_UserRole' => $role_id,
				)
			);

			if ( is_null( $result ) || ! isset( $result->ID_Unit ) ) {
				return;
			}

			$this->skautis_gateway->get_skautis_instance()->getUser()->updateLoginData(
				$this->skautis_gateway->get_skautis_instance()->getUser()->getLoginId(),
				$role_id,
				$result->ID_Unit
			);
		}
	}

}
