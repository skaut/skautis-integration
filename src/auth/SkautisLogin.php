<?php

namespace SkautisIntegration\Auth;

use SkautisIntegration\Utils\Helpers;

final class SkautisLogin {

	private $skautisGateway;
	private $wpLoginLogout;

	public function __construct( SkautisGateway $skautisGateway, WpLoginLogout $wpLoginLogout ) {
		$this->skautisGateway = $skautisGateway;
		$this->wpLoginLogout  = $wpLoginLogout;
	}

	public function isUserLoggedInSkautis() {
		if ( $this->skautisGateway->isInitialized() ) {
			return $this->skautisGateway->getSkautisInstance()->getUser()->isLoggedIn() && $this->skautisGateway->getSkautisInstance()->getUser()->isLoggedIn( true );
		}

		return false;
	}

	public function setLoginDataToLocalSkautisInstance( array $data = [] ) {
		$data = apply_filters( SKAUTISINTEGRATION_NAME . '_login_data_for_skautis_instance', $data );

		if ( isset( $data['skautIS_Token'] ) ) {
			$this->skautisGateway->getSkautisInstance()->setLoginData( $data );

			if ( ! $this->isUserLoggedInSkautis() ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					throw new \Exception( __( 'Přihlášení přes skautIS se nezdařilo', 'skautis-integration' ) );
				}

				return false;
			}

			do_action( SKAUTISINTEGRATION_NAME . '_after_user_is_logged_in_skautis', $data );

			return true;
		}

		return false;
	}

	public function login() {
		if ( ! $this->isUserLoggedInSkautis() ) {
			if ( isset( $_GET['ReturnUrl'] ) && $_GET['ReturnUrl'] ) {
				$returnUrl = $_GET['ReturnUrl'];
			} else {
				$returnUrl = Helpers::getCurrentUrl();
			}
			wp_redirect( esc_url_raw( $this->skautisGateway->getSkautisInstance()->getLoginUrl( $returnUrl ) ), 302 );
			exit;
		}

		$this->wpLoginLogout->loginToWp();
	}

	public function loginConfirm() {
		if ( $this->setLoginDataToLocalSkautisInstance( $_POST ) ) {
			$this->wpLoginLogout->loginToWp();
		} else if ( $this->isUserLoggedInSkautis() ) {
			$this->wpLoginLogout->loginToWp();
		}
	}

}
