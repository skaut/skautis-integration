<?php

declare( strict_types=1 );

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

	private function setSkautisUserIdToWpAccount( int $wpUserId, int $skautisUserId ) {
		$returnUrl = Helpers::getReturnUrl();
		if ( ! is_null( $returnUrl ) ) {
			Helpers::validateNonceFromUrl( $returnUrl, SKAUTISINTEGRATION_NAME . '_connectWpAccountWithSkautis' );

			update_user_meta( $wpUserId, 'skautisUserId_' . $this->skautisGateway->getEnv(), absint( $skautisUserId ) );

			wp_safe_redirect( $returnUrl, 302 );
			exit;
		}
	}

	public function printConnectAndDisconnectButton( int $wpUserId ) {
		$skautisUserId = get_user_meta( $wpUserId, 'skautisUserId_' . $this->skautisGateway->getEnv(), true );
		if ( $skautisUserId ) {
			if ( ! Helpers::userIsSkautisManager() && get_option( SKAUTISINTEGRATION_NAME . '_allowUsersDisconnectFromSkautis' ) !== '1' ) {
				return;
			}
			$returnUrl = add_query_arg( SKAUTISINTEGRATION_NAME . '_disconnectWpAccountFromSkautis', wp_create_nonce( SKAUTISINTEGRATION_NAME . '_disconnectWpAccountFromSkautis' ), Helpers::getCurrentUrl() );
			$url       = add_query_arg( 'ReturnUrl', rawurlencode( $returnUrl ), get_home_url( null, 'skautis/auth/' . Actions::DISCONNECT_ACTION ) );

			echo '
			<a href="' . esc_url( $url ) . '"
			   class="button">' . esc_html__( 'Zrušit propojení účtu se skautISem', 'skautis-integration' ) . '</a>
			';
		} elseif ( get_current_screen()->id === 'profile' ) {
			$returnUrl = add_query_arg( SKAUTISINTEGRATION_NAME . '_connectWpAccountWithSkautis', wp_create_nonce( SKAUTISINTEGRATION_NAME . '_connectWpAccountWithSkautis' ), Helpers::getCurrentUrl() );
			$url       = add_query_arg( 'ReturnUrl', rawurlencode( $returnUrl ), get_home_url( null, 'skautis/auth/' . Actions::CONNECT_ACTION ) );

			echo '
			<a href="' . esc_url( $url ) . '"
			   class="button">' . esc_html__( 'Propojit tento účet se skautISem', 'skautis-integration' ) . '</a>
			';
		}
	}

	public function connect() {
		if ( ! $this->skautisLogin->isUserLoggedInSkautis() ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( ! $this->skautisLogin->setLoginDataToLocalSkautisInstance( $_POST ) ) {
				$returnUrl = Helpers::getReturnUrl() ?? Helpers::getCurrentUrl();
				wp_safe_redirect( esc_url_raw( $this->skautisGateway->getSkautisInstance()->getLoginUrl( $returnUrl ) ), 302 );
				exit;
			}
		}

		$userDetail = $this->skautisGateway->getSkautisInstance()->UserManagement->UserDetail();

		if ( $userDetail && isset( $userDetail->ID ) && $userDetail->ID > 0 ) {
			$this->setSkautisUserIdToWpAccount( get_current_user_id(), $userDetail->ID );
		}
	}

	public function connectWpUserToSkautis() {
		if ( ! isset( $_GET[ SKAUTISINTEGRATION_NAME . '_connect_user_nonce' ] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET[ SKAUTISINTEGRATION_NAME . '_connect_user_nonce' ] ) ), SKAUTISINTEGRATION_NAME . '_connect_user' ) ||
			! $this->skautisLogin->isUserLoggedInSkautis() ||
			! Helpers::userIsSkautisManager() ||
			is_null( Helpers::getReturnUrl() )
		) {
			wp_die( esc_html__( 'Nemáte oprávnění k propojování uživatelů.', 'skautis-integration' ), esc_html__( 'Neautorizovaný přístup', 'skautis-integration' ) );
		}

		if ( ! isset( $_GET['wpUserId'], $_GET['skautisUserId'] ) ) {
			return;
		}

		$wpUserId      = absint( $_GET['wpUserId'] );
		$skautisUserId = absint( $_GET['skautisUserId'] );

		if ( $wpUserId > 0 && $skautisUserId > 0 ) {
			$this->setSkautisUserIdToWpAccount( $wpUserId, $skautisUserId );
		}
	}

	public function getConnectWpUserToSkautisUrl(): string
	{
		$returnUrl = Helpers::getCurrentUrl();
		$returnUrl = add_query_arg( SKAUTISINTEGRATION_NAME . '_connectWpAccountWithSkautis', wp_create_nonce( SKAUTISINTEGRATION_NAME . '_connectWpAccountWithSkautis' ), $returnUrl );
		$url       = add_query_arg( 'ReturnUrl', rawurlencode( $returnUrl ), get_home_url( null, 'skautis/auth/' . Actions::CONNECT_WP_USER_TO_SKAUTIS_ACTION ) );

		return esc_url( wp_nonce_url( $url, SKAUTISINTEGRATION_NAME . '_connect_user', SKAUTISINTEGRATION_NAME . '_connect_user_nonce' ) );
	}

	public function disconnect() {
		if ( is_user_logged_in() ) {
			$returnUrl = Helpers::getReturnUrl();
			if ( ! is_null( $returnUrl ) ) {
				Helpers::validateNonceFromUrl( $returnUrl, SKAUTISINTEGRATION_NAME . '_disconnectWpAccountFromSkautis' );

				if ( strpos( $returnUrl, 'profile.php' ) !== false ) {
					delete_user_meta( get_current_user_id(), 'skautisUserId_' . $this->skautisGateway->getEnv() );
				} elseif ( ( strpos( $returnUrl, 'user-edit_php' ) !== false ||
							strpos( $returnUrl, 'user-edit.php' ) !== false ) &&
							strpos( $returnUrl, 'user_id=' ) !== false ) {
					if ( ! preg_match( '~user_id=(\d+)~', $returnUrl, $result ) ) {
						return;
					}
					if ( is_array( $result ) && isset( $result[1] ) && $result[1] > 0 ) {
						$userId = absint( $result[1] );
						if ( Helpers::userIsSkautisManager() ) {
							delete_user_meta( $userId, 'skautisUserId_' . $this->skautisGateway->getEnv() );
						}
					}
				}
			}
		}

		$returnUrl = Helpers::getReturnUrl();
		if ( ! is_null( $returnUrl ) ) {
			wp_safe_redirect( $returnUrl, 302 );
			exit;
		} else {
			wp_safe_redirect( get_home_url(), 302 );
			exit;
		}
	}

}
