<?php

namespace SkautisIntegration\Auth;

use SkautisIntegration\General\Actions;
use SkautisIntegration\Utils\Helpers;
use SkautisIntegration\Services\Services;
use SkautisIntegration\Modules\Register\Register;

final class WpLoginLogout {

	private $skautisGateway;

	public function __construct( SkautisGateway $skautisGateway ) {
		$this->skautisGateway = $skautisGateway;
	}

	private function loginWpUserBySkautisUserId( $skautisUserId ) {

		if ( isset( $_GET['ReturnUrl'] ) && $_GET['ReturnUrl'] ) {

			Helpers::validateNonceFromUrl( $_GET['ReturnUrl'], SKAUTISINTEGRATION_NAME . '_loginToWpBySkautis' );

			$usersWpQuery = new \WP_User_Query( [
				'number'     => 1,
				'meta_query' => [
					[
						'key'     => 'skautisUserId_' . $this->skautisGateway->getEnv(),
						'value'   => absint( $skautisUserId ),
						'compare' => '='
					]
				]
			] );
			$users        = $usersWpQuery->get_results();

			if ( ! empty( $users )
			     && isset( $users[0] )
			     && isset( $users[0]->ID )
			     && $users[0]->ID > 0
			) {
				$wpUser = $users[0];

				if ( Services::getServicesContainer()['modulesManager']->isModuleActivated( Register::getId() ) &&
				     ! Helpers::userIsSkautisManager() &&
				     get_option( SKAUTISINTEGRATION_NAME . '_checkUserPrivilegesIfLoginBySkautis' ) ) {
					if ( ! Services::getServicesContainer()[ Register::getId() ]->getRulesManager()->checkIfUserPassedRulesAndGetHisRole() ) {
						wp_die( __( 'Je nám líto, ale již nemáte oprávnění k přístupu. <a href="' . ( Services::getServicesContainer()[ Register::getId() ] )->getWpRegister()->getRegisterUrl() . '">Zkuste se znovu zaregistrovat</a>', 'skautis-integration' ), __( 'Neautorizovaný přístup', 'skautis-integration' ) );
					}
				}

				if ( is_user_logged_in() && get_current_user_id() === $wpUser->ID ) {
					wp_safe_redirect( $_GET['ReturnUrl'], 302 );
					exit;
				}

				wp_destroy_current_session();
				wp_clear_auth_cookie();
				wp_set_current_user( $wpUser->ID, $wpUser->data->user_login );
				wp_set_auth_cookie( $wpUser->ID, true );

				wp_safe_redirect( $_GET['ReturnUrl'], 302 );
				exit;
			}
		}

		if ( Services::getServicesContainer()['modulesManager']->isModuleActivated( Register::getId() ) ) {
			wp_die( __( 'Nemáte oprávnění k přístupu. <a href="' . ( Services::getServicesContainer()[ Register::getId() ] )->getWpRegister()->getRegisterUrl() . '">Zkuste se nejdříve zaregistrovat</a>', 'skautis-integration' ), __( 'Neautorizovaný přístup', 'skautis-integration' ) );
		} else {
			$this->skautisGateway->logout();
			wp_die( __( 'Nemáte oprávnění k přístupu', 'skautis-integration' ), __( 'Neautorizovaný přístup', 'skautis-integration' ) );
		}

	}

	public function getLoginUrl( $returnUrl = '' ) {
		if ( ! $returnUrl ) {
			if ( isset( $_GET['redirect_to'] ) && $_GET['redirect_to'] ) {
				$returnUrl = $_GET['redirect_to'];
			} else if ( isset( $_GET['ReturnUrl'] ) && $_GET['ReturnUrl'] ) {
				$returnUrl = $_GET['ReturnUrl'];
			} else {
				$returnUrl = Helpers::getCurrentUrl();
			}
		}

		$returnUrl = remove_query_arg( 'loggedout', urldecode( $returnUrl ) );

		if ( strpos( $returnUrl, 'wp-login.php' ) !== false ) {
			$returnUrl = admin_url();
		}

		$returnUrl = add_query_arg( SKAUTISINTEGRATION_NAME . '_loginToWpBySkautis', wp_create_nonce( SKAUTISINTEGRATION_NAME . '_loginToWpBySkautis' ), $returnUrl );
		$url       = add_query_arg( 'ReturnUrl', urlencode( $returnUrl ), get_home_url( null, 'skautis/auth/' . Actions::LOGIN_ACTION ) );

		return $url;
	}

	public function getLogoutUrl( $returnUrl = '' ) {
		if ( ! $returnUrl ) {
			if ( isset( $_GET['redirect_to'] ) && $_GET['redirect_to'] ) {
				$returnUrl = $_GET['redirect_to'];
			} else if ( isset( $_GET['ReturnUrl'] ) && $_GET['ReturnUrl'] ) {
				$returnUrl = $_GET['ReturnUrl'];
			} else {
				$returnUrl = Helpers::getCurrentUrl();
			}
		}

		$returnUrl = remove_query_arg( 'loggedout', urldecode( $returnUrl ) );

		if ( strpos( $returnUrl, 'wp-login.php' ) !== false ) {
			$returnUrl = admin_url();
		}

		$returnUrl = add_query_arg( SKAUTISINTEGRATION_NAME . '_logoutFromWpAndSkautis', wp_create_nonce( SKAUTISINTEGRATION_NAME . '_logoutFromWpAndSkautis' ), $returnUrl );
		$url       = add_query_arg( 'ReturnUrl', urlencode( $returnUrl ), get_home_url( null, 'skautis/auth/' . Actions::LOGOUT_CONFIRM_ACTION ) );

		return $url;
	}

	public function loginToWp() {
		$userDetail = $this->skautisGateway->getSkautisInstance()->UserManagement->UserDetail();

		if ( $userDetail && isset( $userDetail->ID ) && $userDetail->ID > 0 ) {
			$this->loginWpUserBySkautisUserId( $userDetail->ID );
		}
	}

	public function logout() {
		$this->skautisGateway->logout();

		wp_logout();
		wp_set_current_user( 0 );

		if ( isset( $_GET['redirect_to'] ) && $_GET['redirect_to'] ) {
			$returnUrl = $_GET['redirect_to'];
		} else if ( isset( $_GET['ReturnUrl'] ) && $_GET['ReturnUrl'] ) {
			$returnUrl = $_GET['ReturnUrl'];
		} else {
			$returnUrl = Helpers::getCurrentUrl();
		}

		wp_safe_redirect( $returnUrl, 302 );
		exit;
	}

}
