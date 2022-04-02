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

final class WP_Login_Logout {

	private $skautis_gateway;

	public function __construct( Skautis_Gateway $skautis_gateway ) {
		$this->skautis_gateway = $skautis_gateway;
	}

	private function login_wp_user_by_skautis_user_id( int $skautis_user_id, $try = false ) {
		$return_url = Helpers::get_return_url();
		if ( ! is_null( $return_url ) ) {
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

				if ( ! $try ) {
					if ( Services::get_services_container()['modulesManager']->is_module_activated( Register::get_id() ) &&
						! user_can( $wp_user->ID, Helpers::get_skautis_manager_capability() ) &&
						get_option( SKAUTIS_INTEGRATION_NAME . '_checkUserPrivilegesIfLoginBySkautis' ) ) {
						if ( ! Services::get_services_container()[ Register::get_id() ]->getRulesManager()->check_if_user_passed_rules_and_get_his_role() ) {
							/* translators: 1: Start of a link to SkautIS login 2: End of the link to SkautIS login */
							wp_die( sprintf( esc_html__( 'Je nám líto, ale již nemáte oprávnění k přístupu. %1$sZkuste se znovu zaregistrovat%2$s', 'skautis-integration' ), '<a href = "' . esc_url( ( Services::get_services_container()[ Register::get_id() ] )->getWpRegister()->get_register_url() ) . '">', '</a>' ), esc_html__( 'Neautorizovaný přístup', 'skautis-integration' ) );
						}
					}
				}

				if ( is_user_logged_in() && get_current_user_id() === $wp_user->ID ) {
					wp_safe_redirect( $return_url, 302 );
					exit;
				}

				wp_destroy_current_session();
				wp_clear_auth_cookie();
				wp_set_current_user( $wp_user->ID, $wp_user->data->user_login );
				wp_set_auth_cookie( $wp_user->ID, true );

				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				do_action( 'wp_login', $wp_user->user_login, $wp_user );

				wp_safe_redirect( $return_url, 302 );
				exit;
			}
		}

		if ( ! $try ) {
			if ( Services::get_services_container()['modulesManager']->is_module_activated( Register::get_id() ) ) {
				/* translators: 1: Start of a link to SkautIS login 2: End of the link to SkautIS login */
				wp_die( sprintf( esc_html__( 'Nemáte oprávnění k přístupu. %1$sZkuste se nejdříve zaregistrovat%2$s', 'skautis-integration' ), '<a href ="' . esc_url( ( Services::get_services_container()[ Register::get_id() ] )->getWpRegister()->get_register_url() ) . '">', '</a>' ), esc_html__( 'Neautorizovaný přístup', 'skautis-integration' ) );
			} else {
				$this->skautis_gateway->logout();
				wp_die( esc_html__( 'Nemáte oprávnění k přístupu', 'skautis-integration' ), esc_html__( 'Neautorizovaný přístup', 'skautis-integration' ) );
			}
		}

		return false;
	}

	public function get_login_url( string $return_url = '' ): string {
		if ( ! $return_url ) {
			$return_url = Helpers::get_login_logout_redirect();
		}

		$return_url = remove_query_arg( 'loggedout', urldecode( $return_url ) );

		if ( strpos( $return_url, 'wp-login.php' ) !== false ) {
			$return_url = admin_url();
		}

		$url = add_query_arg( 'ReturnUrl', rawurlencode( $return_url ), get_home_url( null, 'skautis/auth/' . Actions::LOGIN_ACTION ) );

		return esc_url( $url );
	}

	public function get_logout_url( string $return_url = '' ): string {
		if ( ! $return_url ) {
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

	public function login_to_wp() {
		$user_detail = $this->skautis_gateway->get_skautis_instance()->UserManagement->UserDetail();

		if ( $user_detail && isset( $user_detail->ID ) && $user_detail->ID > 0 ) {
			$this->login_wp_user_by_skautis_user_id( $user_detail->ID );
		}
	}

	public function try_to_login_to_wp() {
		$user_detail = $this->skautis_gateway->get_skautis_instance()->UserManagement->UserDetail();

		if ( $user_detail && isset( $user_detail->ID ) && $user_detail->ID > 0 ) {
			$this->login_wp_user_by_skautis_user_id( $user_detail->ID, true );
		}
	}

	public function logout() {
		$this->skautis_gateway->logout();

		wp_logout();
		wp_set_current_user( 0 );

		$return_url = Helpers::get_login_logout_redirect();
		wp_safe_redirect( esc_url_raw( $return_url ), 302 );
		exit;
	}

}
