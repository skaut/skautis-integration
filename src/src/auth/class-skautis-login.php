<?php

declare( strict_types=1 );

namespace SkautisIntegration\Auth;

use SkautisIntegration\Utils\Helpers;

final class Skautis_Login {

	private $skautis_gateway;
	private $wp_login_logout;

	public function __construct( Skautis_Gateway $skautisGateway, WP_Login_Logout $wpLoginLogout ) {
		$this->skautis_gateway = $skautisGateway;
		$this->wp_login_logout  = $wpLoginLogout;
	}

	public function is_user_logged_in_skautis(): bool {
		if ( $this->skautis_gateway->is_initialized() ) {
			return $this->skautis_gateway->get_skautis_instance()->getUser()->isLoggedIn() && $this->skautis_gateway->get_skautis_instance()->getUser()->isLoggedIn( true );
		}

		return false;
	}

	public function set_login_data_to_local_skautis_instance( array $data = array() ): bool {
		$data = apply_filters( SKAUTISINTEGRATION_NAME . '_login_data_for_skautis_instance', $data );

		if ( isset( $data['skautIS_Token'] ) ) {
			$this->skautis_gateway->get_skautis_instance()->setLoginData( $data );

			if ( ! $this->is_user_logged_in_skautis() ) {
				return false;
			}

			do_action( SKAUTISINTEGRATION_NAME . '_after_user_is_logged_in_skautis', $data );

			return true;
		}

		return false;
	}

	public function login() {
		$returnUrl = Helpers::get_login_logout_redirect();

		if ( strpos( $returnUrl, 'logoutFromSkautis' ) !== false ) {
			$this->skautis_gateway->logout();
			$returnUrl = remove_query_arg( 'logoutFromSkautis', $returnUrl );
		}

		if ( ! $this->is_user_logged_in_skautis() ) {
			wp_safe_redirect( esc_url_raw( $this->skautis_gateway->get_skautis_instance()->getLoginUrl( $returnUrl ) ), 302 );
			exit;
		}

		if ( strpos( $returnUrl, 'noWpLogin' ) !== false ) {
			$this->wp_login_logout->try_to_login_to_wp();
			wp_safe_redirect( esc_url_raw( $returnUrl ), 302 );
			exit;
		} else {
			$this->wp_login_logout->login_to_wp();
		}
	}

	public function login_confirm() {
		$returnUrl = Helpers::get_return_url();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( $this->set_login_data_to_local_skautis_instance( $_POST ) ) {
			if ( is_null( $returnUrl ) || strpos( $returnUrl, 'noWpLogin' ) === false ) {
				$this->wp_login_logout->login_to_wp();
			} elseif ( ! is_null( $returnUrl ) ) {
				$this->wp_login_logout->try_to_login_to_wp();
				wp_safe_redirect( $returnUrl, 302 );
				exit;
			}
		} elseif ( $this->is_user_logged_in_skautis() ) {
			if ( is_null( $returnUrl ) || strpos( $returnUrl, 'noWpLogin' ) === false ) {
				$this->wp_login_logout->login_to_wp();
			} elseif ( ! is_null( $returnUrl ) ) {
				$this->wp_login_logout->try_to_login_to_wp();
				wp_safe_redirect( $returnUrl, 302 );
				exit;
			}
		}
	}

	public function change_user_role_in_skautis( int $roleId ) {
		if ( $roleId > 0 ) {
			$result = $this->skautis_gateway->get_skautis_instance()->UserManagement->LoginUpdate(
				array(
					'ID'          => $this->skautis_gateway->get_skautis_instance()->getUser()->getLoginId(),
					'ID_UserRole' => $roleId,
				)
			);

			if ( ! $result || ! isset( $result->ID_Unit ) ) {
				return;
			}

			$this->skautis_gateway->get_skautis_instance()->getUser()->updateLoginData(
				$this->skautis_gateway->get_skautis_instance()->getUser()->getLoginId(),
				$roleId,
				$result->ID_Unit
			);
		}
	}

}
