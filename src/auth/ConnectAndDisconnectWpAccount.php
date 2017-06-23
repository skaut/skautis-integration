<?php

namespace SkautisIntegration\Auth;

use SkautisIntegration\General\Actions;
use SkautisIntegration\Utils\Helpers;

final class ConnectAndDisconnectWpAccount {

	private $skautisGateway;
	private $skautisLogin;

	public function __construct( SkautisGateway $skautisGateway, SkautisLogin $skautisLogin ) {
		$this->skautisGateway = $skautisGateway;
		$this->skautisLogin   = $skautisLogin;
	}

	private function setSkautisUserIdToWpAccount( $wpUserId, $skautisUserId ) {
		if ( isset( $_GET['ReturnUrl'] ) && $_GET['ReturnUrl'] ) {

			Helpers::validateNonceFromUrl( $_GET['ReturnUrl'], SKAUTISINTEGRATION_NAME . '_connectWpAccountWithSkautis' );

			update_user_meta( $wpUserId, 'skautisUserId_' . $this->skautisGateway->getEnv(), absint( $skautisUserId ) );

			wp_safe_redirect( $_GET['ReturnUrl'] );
			exit;
		}
	}

	public function getConnectAndDisconnectButton( $wpUserId ) {
		$skautisUserId = get_the_author_meta( 'skautisUserId_' . $this->skautisGateway->getEnv(), $wpUserId );
		if ( get_current_screen()->id == 'profile' ) {
			if ( ! $skautisUserId ) {
				$returnUrl = add_query_arg( SKAUTISINTEGRATION_NAME . '_connectWpAccountWithSkautis', wp_create_nonce( SKAUTISINTEGRATION_NAME . '_connectWpAccountWithSkautis' ), Helpers::getCurrentUrl() );
				$url       = add_query_arg( 'ReturnUrl', urlencode( $returnUrl ), get_home_url( null, 'skautis/auth/' . Actions::CONNECT_ACTION ) );

				return '
				<a href="' . $url . '"
				   class="button">' . __( 'Propojit tento účet se SkautISem', 'skautis-integration' ) . '</a>
				';
			}
		}
		if ( $skautisUserId ) {
			if ( ! Helpers::userIsSkautisManager() && get_option( SKAUTISINTEGRATION_NAME . '_allowUsersDisconnectFromSkautis' ) !== '1' ) {
				return '';
			}
			$returnUrl = add_query_arg( SKAUTISINTEGRATION_NAME . '_disconnectWpAccountFromSkautis', wp_create_nonce( SKAUTISINTEGRATION_NAME . '_disconnectWpAccountFromSkautis' ), Helpers::getCurrentUrl() );
			$url       = add_query_arg( 'ReturnUrl', urlencode( $returnUrl ), get_home_url( null, 'skautis/auth/' . Actions::DISCONNECT_ACTION ) );

			return '
			<a href="' . $url . '"
			   class="button">' . __( 'Zrušit propojení účtu se SkautISem', 'skautis-integration' ) . '</a>
			';
		}

		return '';
	}

	public function connect() {
		if ( ! $this->skautisLogin->isUserLoggedInSkautis() ) {

			if ( ! $this->skautisLogin->setLoginDataToLocalSkautisInstance( $_POST ) ) {
				if ( isset( $_GET['ReturnUrl'] ) && $_GET['ReturnUrl'] ) {
					$returnUrl = $_GET['ReturnUrl'];
				} else {
					$returnUrl = Helpers::getCurrentUrl();
				}
				wp_redirect( $this->skautisGateway->getSkautisInstance()->getLoginUrl( $returnUrl ), 302 );
				exit;
			}
		}

		$userDetail = $this->skautisGateway->getSkautisInstance()->UserManagement->UserDetail();

		if ( $userDetail && isset( $userDetail->ID ) && $userDetail->ID > 0 ) {
			$this->setSkautisUserIdToWpAccount( get_current_user_id(), $userDetail->ID );
		}
	}

	public function connectWpUserToSkautis() {
		if ( ! $this->skautisLogin->isUserLoggedInSkautis() || ! Helpers::userIsSkautisManager() || empty( $_GET['ReturnUrl'] ) ) {
			return;
		}

		if ( strpos( $_GET['ReturnUrl'], 'wpUserId' ) === false || strpos( $_GET['ReturnUrl'], 'skautisUserId' ) === false ) {
			return;
		}

		$decodedUrl = urldecode( $_GET['ReturnUrl'] );
		$wpUserId   = 0;
		if ( preg_match( "~wpUserId=([^\&,\s,\/,\#,\%,\?]*)~", $decodedUrl, $result ) ) {
			if ( is_array( $result ) && isset( $result[1] ) && $result[1] ) {
				$wpUserId = $result[1];
			}
		}

		$result        = [];
		$skautisUserId = 0;
		if ( preg_match( "~skautisUserId=([^\&,\s,\/,\#,\%,\?]*)~", $decodedUrl, $result ) ) {
			if ( is_array( $result ) && isset( $result[1] ) && $result[1] ) {
				$skautisUserId = $result[1];
			}
		}

		if ( $wpUserId && $skautisUserId ) {
			$this->setSkautisUserIdToWpAccount( $wpUserId, $skautisUserId );
		}

	}

	public function getConnectWpUserToSkautisUrl() {
		$returnUrl = Helpers::getCurrentUrl();
		$returnUrl = add_query_arg( SKAUTISINTEGRATION_NAME . '_connectWpAccountWithSkautis', wp_create_nonce( SKAUTISINTEGRATION_NAME . '_connectWpAccountWithSkautis' ), $returnUrl );
		$url       = add_query_arg( 'ReturnUrl', urlencode( $returnUrl ), get_home_url( null, 'skautis/auth/' . Actions::CONNECT_WP_USER_TO_SKAUTIS_ACTION ) );

		return $url;
	}

	public function disconnect() {
		if ( is_user_logged_in() ) {
			if ( isset( $_GET['ReturnUrl'] ) && $_GET['ReturnUrl'] ) {

				Helpers::validateNonceFromUrl( $_GET['ReturnUrl'], SKAUTISINTEGRATION_NAME . '_disconnectWpAccountFromSkautis' );

				if ( strpos( $_GET['ReturnUrl'], 'profile.php' ) !== false ) {
					delete_user_meta( get_current_user_id(), 'skautisUserId_' . $this->skautisGateway->getEnv() );
				} else if ( ( strpos( $_GET['ReturnUrl'], 'user-edit_php' ) !== false ||
				              strpos( $_GET['ReturnUrl'], 'user-edit.php' ) !== false ) &&
				            strpos( $_GET['ReturnUrl'], 'user_id=' ) !== false ) {
					if ( ! preg_match( "~user_id=(\d+)~", $_GET['ReturnUrl'], $result ) ) {
						return;
					}
					if ( is_array( $result ) && isset( $result[1] ) && $result[1] > 0 ) {
						$userId = $result[1];
						if ( Helpers::userIsSkautisManager() ) {
							delete_user_meta( $userId, 'skautisUserId_' . $this->skautisGateway->getEnv() );
						}
					}
				}
			}
		}

		if ( isset( $_GET['ReturnUrl'] ) && $_GET['ReturnUrl'] ) {
			wp_safe_redirect( $_GET['ReturnUrl'], 302 );
			exit;
		} else {
			wp_safe_redirect( get_home_url() );
			exit;
		}

	}

}
