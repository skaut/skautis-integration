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
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		if ( isset( $_GET['ReturnUrl'] ) && $_GET['ReturnUrl'] ) {
			Helpers::validateNonceFromUrl( esc_url_raw( wp_unslash( $_GET['ReturnUrl'] ) ), SKAUTISINTEGRATION_NAME . '_connectWpAccountWithSkautis' );

			update_user_meta( $wpUserId, 'skautisUserId_' . $this->skautisGateway->getEnv(), absint( $skautisUserId ) );

			wp_safe_redirect( esc_url_raw( wp_unslash( $_GET['ReturnUrl'] ) ), 302 );
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
			$url       = add_query_arg( 'ReturnUrl', urlencode( $returnUrl ), get_home_url( null, 'skautis/auth/' . Actions::DISCONNECT_ACTION ) );

			echo '
			<a href="' . esc_url( $url ) . '"
			   class="button">' . esc_html__( 'Unlink the account from skautIS', 'skautis-integration' ) . '</a>
			';
		} elseif ( get_current_screen()->id == 'profile' ) {
			$returnUrl = add_query_arg( SKAUTISINTEGRATION_NAME . '_connectWpAccountWithSkautis', wp_create_nonce( SKAUTISINTEGRATION_NAME . '_connectWpAccountWithSkautis' ), Helpers::getCurrentUrl() );
			$url       = add_query_arg( 'ReturnUrl', urlencode( $returnUrl ), get_home_url( null, 'skautis/auth/' . Actions::CONNECT_ACTION ) );

			echo '
			<a href="' . esc_url( $url ) . '"
			   class="button">' . esc_html__( 'Link this account to skautIS', 'skautis-integration' ) . '</a>
			';
		}
	}

	public function connect() {
		if ( ! $this->skautisLogin->isUserLoggedInSkautis() ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( ! $this->skautisLogin->setLoginDataToLocalSkautisInstance( $_POST ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
				if ( isset( $_GET['ReturnUrl'] ) && $_GET['ReturnUrl'] ) {
					$returnUrl = esc_url_raw( wp_unslash( $_GET['ReturnUrl'] ) );
				} else {
					$returnUrl = Helpers::getCurrentUrl();
				}
				wp_redirect( esc_url_raw( $this->skautisGateway->getSkautisInstance()->getLoginUrl( $returnUrl ) ), 302 );
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

		if ( ! isset( $_GET['wpUserId'], $_GET['skautisUserId'] ) ) {
			return;
		}

		$wpUserId      = absint( $_GET['wpUserId'] );
		$skautisUserId = absint( $_GET['skautisUserId'] );

		if ( $wpUserId > 0 && $skautisUserId > 0 ) {
			$this->setSkautisUserIdToWpAccount( $wpUserId, $skautisUserId );
		}
	}

	public function getConnectWpUserToSkautisUrl(): string {
		$returnUrl = Helpers::getCurrentUrl();
		$returnUrl = add_query_arg( SKAUTISINTEGRATION_NAME . '_connectWpAccountWithSkautis', wp_create_nonce( SKAUTISINTEGRATION_NAME . '_connectWpAccountWithSkautis' ), $returnUrl );
		$url       = add_query_arg( 'ReturnUrl', urlencode( $returnUrl ), get_home_url( null, 'skautis/auth/' . Actions::CONNECT_WP_USER_TO_SKAUTIS_ACTION ) );

		return esc_url( $url );
	}

	public function disconnect() {
		if ( is_user_logged_in() ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			if ( isset( $_GET['ReturnUrl'] ) && $_GET['ReturnUrl'] ) {
				Helpers::validateNonceFromUrl( esc_url_raw( wp_unslash( $_GET['ReturnUrl'] ) ), SKAUTISINTEGRATION_NAME . '_disconnectWpAccountFromSkautis' );

				if ( strpos( esc_url_raw( wp_unslash( $_GET['ReturnUrl'] ) ), 'profile.php' ) !== false ) {
					delete_user_meta( get_current_user_id(), 'skautisUserId_' . $this->skautisGateway->getEnv() );
				} elseif ( ( strpos( esc_url_raw( wp_unslash( $_GET['ReturnUrl'] ) ), 'user-edit_php' ) !== false ||
							  strpos( esc_url_raw( wp_unslash( $_GET['ReturnUrl'] ) ), 'user-edit.php' ) !== false ) &&
							strpos( esc_url_raw( wp_unslash( $_GET['ReturnUrl'] ) ), 'user_id=' ) !== false ) {
					if ( ! preg_match( '~user_id=(\d+)~', esc_url_raw( wp_unslash( $_GET['ReturnUrl'] ) ), $result ) ) {
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

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		if ( isset( $_GET['ReturnUrl'] ) && $_GET['ReturnUrl'] ) {
			wp_safe_redirect( esc_url_raw( wp_unslash( $_GET['ReturnUrl'] ) ), 302 );
			exit;
		} else {
			wp_safe_redirect( get_home_url(), 302 );
			exit;
		}
	}

}
