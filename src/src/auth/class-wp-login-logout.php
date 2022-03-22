<?php

declare( strict_types=1 );

namespace SkautisIntegration\Auth;

use SkautisIntegration\General\Actions;
use SkautisIntegration\Utils\Helpers;
use SkautisIntegration\Services\Services;
use SkautisIntegration\Modules\Register\Register;

final class WP_Login_Logout {

	private $skautisGateway;

	public function __construct( Skautis_Gateway $skautisGateway ) {
		$this->skautisGateway = $skautisGateway;
	}

	private function login_wp_user_by_skautis_user_id( int $skautisUserId, $try = false ) {
		$returnUrl = Helpers::getReturnUrl();
		if ( ! is_null( $returnUrl ) ) {
			$usersWpQuery = new \WP_User_Query(
				array(
					'number'     => 1,
					'meta_query' => array(
						array(
							'key'     => 'skautisUserId_' . $this->skautisGateway->getEnv(),
							'value'   => absint( $skautisUserId ),
							'compare' => '=',
						),
					),
				)
			);
			$users        = $usersWpQuery->get_results();

			if ( ! empty( $users )
				&& isset( $users[0] )
				&& isset( $users[0]->ID )
				&& $users[0]->ID > 0
			) {
				$wpUser = $users[0];

				if ( ! $try ) {
					if ( Services::get_services_container()['modulesManager']->isModuleActivated( Register::get_id() ) &&
						! user_can( $wpUser->ID, Helpers::getSkautisManagerCapability() ) &&
						get_option( SKAUTISINTEGRATION_NAME . '_checkUserPrivilegesIfLoginBySkautis' ) ) {
						if ( ! Services::get_services_container()[ Register::get_id() ]->getRulesManager()->checkIfUserPassedRulesAndGetHisRole() ) {
							/* translators: 1: Start of a link to SkautIS login 2: End of the link to SkautIS login */
							wp_die( sprintf( esc_html__( 'Je nám líto, ale již nemáte oprávnění k přístupu. %1$sZkuste se znovu zaregistrovat%2$s', 'skautis-integration' ), '<a href = "' . esc_url( ( Services::get_services_container()[ Register::get_id() ] )->getWpRegister()->getRegisterUrl() ) . '">', '</a>' ), esc_html__( 'Neautorizovaný přístup', 'skautis-integration' ) );
						}
					}
				}

				if ( is_user_logged_in() && get_current_user_id() === $wpUser->ID ) {
					wp_safe_redirect( $returnUrl, 302 );
					exit;
				}

				wp_destroy_current_session();
				wp_clear_auth_cookie();
				wp_set_current_user( $wpUser->ID, $wpUser->data->user_login );
				wp_set_auth_cookie( $wpUser->ID, true );

				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				do_action( 'wp_login', $wpUser->user_login, $wpUser );

				wp_safe_redirect( $returnUrl, 302 );
				exit;
			}
		}

		if ( ! $try ) {
			if ( Services::get_services_container()['modulesManager']->isModuleActivated( Register::get_id() ) ) {
				/* translators: 1: Start of a link to SkautIS login 2: End of the link to SkautIS login */
				wp_die( sprintf( esc_html__( 'Nemáte oprávnění k přístupu. %1$sZkuste se nejdříve zaregistrovat%2$s', 'skautis-integration' ), '<a href ="' . esc_url( ( Services::get_services_container()[ Register::get_id() ] )->getWpRegister()->getRegisterUrl() ) . '">', '</a>' ), esc_html__( 'Neautorizovaný přístup', 'skautis-integration' ) );
			} else {
				$this->skautisGateway->logout();
				wp_die( esc_html__( 'Nemáte oprávnění k přístupu', 'skautis-integration' ), esc_html__( 'Neautorizovaný přístup', 'skautis-integration' ) );
			}
		}

		return false;
	}

	public function get_login_url( string $returnUrl = '' ): string {
		if ( ! $returnUrl ) {
			$returnUrl = Helpers::getLoginLogoutRedirect();
		}

		$returnUrl = remove_query_arg( 'loggedout', urldecode( $returnUrl ) );

		if ( strpos( $returnUrl, 'wp-login.php' ) !== false ) {
			$returnUrl = admin_url();
		}

		$url = add_query_arg( 'ReturnUrl', rawurlencode( $returnUrl ), get_home_url( null, 'skautis/auth/' . Actions::LOGIN_ACTION ) );

		return esc_url( $url );
	}

	public function get_logout_url( string $returnUrl = '' ): string {
		if ( ! $returnUrl ) {
			$returnUrl = Helpers::getLoginLogoutRedirect();
		}

		$returnUrl = remove_query_arg( 'loggedout', urldecode( $returnUrl ) );

		if ( strpos( $returnUrl, 'wp-login.php' ) !== false ) {
			$returnUrl = admin_url();
		}

		$returnUrl = add_query_arg( SKAUTISINTEGRATION_NAME . '_logoutFromWpAndSkautis', wp_create_nonce( SKAUTISINTEGRATION_NAME . '_logoutFromWpAndSkautis' ), $returnUrl );
		$url       = add_query_arg( 'ReturnUrl', rawurlencode( $returnUrl ), get_home_url( null, 'skautis/auth/' . Actions::LOGOUT_CONFIRM_ACTION ) );

		return esc_url( $url );
	}

	public function login_to_wp() {
		$userDetail = $this->skautisGateway->getSkautisInstance()->UserManagement->UserDetail();

		if ( $userDetail && isset( $userDetail->ID ) && $userDetail->ID > 0 ) {
			$this->login_wp_user_by_skautis_user_id( $userDetail->ID );
		}
	}

	public function try_to_login_to_wp() {
		$userDetail = $this->skautisGateway->getSkautisInstance()->UserManagement->UserDetail();

		if ( $userDetail && isset( $userDetail->ID ) && $userDetail->ID > 0 ) {
			$this->login_wp_user_by_skautis_user_id( $userDetail->ID, true );
		}
	}

	public function logout() {
		$this->skautisGateway->logout();

		wp_logout();
		wp_set_current_user( 0 );

		$returnUrl = Helpers::getLoginLogoutRedirect();
		wp_safe_redirect( esc_url_raw( $returnUrl ), 302 );
		exit;
	}

}
