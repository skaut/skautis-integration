<?php

declare( strict_types=1 );

namespace SkautisIntegration\Auth;

use SkautisIntegration\Utils\Helpers;

final class Skautis_Login {

	private $skautisGateway;
	private $wpLoginLogout;

	public function __construct( Skautis_Gateway $skautisGateway, WP_Login_Logout $wpLoginLogout ) {
		$this->skautisGateway = $skautisGateway;
		$this->wpLoginLogout  = $wpLoginLogout;
	}

	public function is_user_logged_in_skautis(): bool {
		if ( $this->skautisGateway->isInitialized() ) {
			return $this->skautisGateway->getSkautisInstance()->getUser()->isLoggedIn() && $this->skautisGateway->getSkautisInstance()->getUser()->isLoggedIn( true );
		}

		return false;
	}

	public function setLoginDataToLocalSkautisInstance( array $data = array() ): bool {
		$data = apply_filters( SKAUTISINTEGRATION_NAME . '_login_data_for_skautis_instance', $data );

		if ( isset( $data['skautIS_Token'] ) ) {
			$this->skautisGateway->getSkautisInstance()->setLoginData( $data );

			if ( ! $this->is_user_logged_in_skautis() ) {
				return false;
			}

			do_action( SKAUTISINTEGRATION_NAME . '_after_user_is_logged_in_skautis', $data );

			return true;
		}

		return false;
	}

	public function login() {
		$returnUrl = Helpers::getLoginLogoutRedirect();

		if ( strpos( $returnUrl, 'logoutFromSkautis' ) !== false ) {
			$this->skautisGateway->logout();
			$returnUrl = remove_query_arg( 'logoutFromSkautis', $returnUrl );
		}

		if ( ! $this->is_user_logged_in_skautis() ) {
			wp_safe_redirect( esc_url_raw( $this->skautisGateway->getSkautisInstance()->getLoginUrl( $returnUrl ) ), 302 );
			exit;
		}

		if ( strpos( $returnUrl, 'noWpLogin' ) !== false ) {
			$this->wpLoginLogout->try_to_login_to_wp();
			wp_safe_redirect( esc_url_raw( $returnUrl ), 302 );
			exit;
		} else {
			$this->wpLoginLogout->login_to_wp();
		}
	}

	public function loginConfirm() {
		$returnUrl = Helpers::getReturnUrl();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( $this->setLoginDataToLocalSkautisInstance( $_POST ) ) {
			if ( is_null( $returnUrl ) || strpos( $returnUrl, 'noWpLogin' ) === false ) {
				$this->wpLoginLogout->login_to_wp();
			} elseif ( ! is_null( $returnUrl ) ) {
				$this->wpLoginLogout->try_to_login_to_wp();
				wp_safe_redirect( $returnUrl, 302 );
				exit;
			}
		} elseif ( $this->is_user_logged_in_skautis() ) {
			if ( is_null( $returnUrl ) || strpos( $returnUrl, 'noWpLogin' ) === false ) {
				$this->wpLoginLogout->login_to_wp();
			} elseif ( ! is_null( $returnUrl ) ) {
				$this->wpLoginLogout->try_to_login_to_wp();
				wp_safe_redirect( $returnUrl, 302 );
				exit;
			}
		}
	}

	public function changeUserRoleInSkautis( int $roleId ) {
		if ( $roleId > 0 ) {
			$result = $this->skautisGateway->getSkautisInstance()->UserManagement->LoginUpdate(
				array(
					'ID'          => $this->skautisGateway->getSkautisInstance()->getUser()->getLoginId(),
					'ID_UserRole' => $roleId,
				)
			);

			if ( ! $result || ! isset( $result->ID_Unit ) ) {
				return;
			}

			$this->skautisGateway->getSkautisInstance()->getUser()->updateLoginData(
				$this->skautisGateway->getSkautisInstance()->getUser()->getLoginId(),
				$roleId,
				$result->ID_Unit
			);
		}
	}

}
