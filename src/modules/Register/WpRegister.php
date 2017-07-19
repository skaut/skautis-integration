<?php

declare( strict_types=1 );

namespace SkautisIntegration\Modules\Register;

use Skautis\Skautis;
use SkautisIntegration\Auth\SkautisGateway;
use SkautisIntegration\Utils\Helpers;

final class WpRegister {

	private $skautisGateway;

	public function __construct( SkautisGateway $skautisGateway ) {
		$this->skautisGateway = $skautisGateway;
	}

	private function resolveNotificationsAndRegisterUserToWp( string $userLogin, string $userEmail ): int {
		remove_action( 'register_new_user', 'wp_send_new_user_notifications' );
		add_action( 'register_new_user', function ( $userId ) {
			$notify = apply_filters( SKAUTISINTEGRATION_NAME . '_modules_register_newUserNotifications', get_option( SKAUTISINTEGRATION_NAME . '_modules_register_emailNotificationsAfterNewUserRegister' ) );
			if ( ! $notify ) {
				$notify = 'none';
			}
			if ( $notify != 'none' ) {
				global $wp_locale_switcher;
				if ( ! $wp_locale_switcher ) {
					$GLOBALS['wp_locale_switcher'] = new \WP_Locale_Switcher();
					$GLOBALS['wp_locale_switcher']->init();
				}
				wp_send_new_user_notifications( $userId, $notify );
			}
		} );

		$userId = register_new_user( $userLogin, $userEmail );

		add_action( 'register_new_user', 'wp_send_new_user_notifications' );

		if ( is_wp_error( $userId ) ) {
			if ( isset( $userId->errors ) && ( isset( $userId->errors['username_exists'] ) || isset( $userId->errors['email_exists'] ) ) ) {
				wp_die( esc_html( sprintf( __( 'Vás email %s je již na webu registrován, ale není propojen se skautIS účtem.', 'skautis-integration' ), $userEmail ) ), __( 'Chyba při registraci', 'skautis-integration' ) );
			}
			wp_die( esc_html( sprintf( __( 'Při registraci nastala neočekávaná chyba: %s', 'skautis-integration' ), $userId->get_error_message() ) ), __( 'Chyba při registraci', 'skautis-integration' ) );
		}

		return $userId;
	}

	private function processWpUserRegistration( $skautisUser, string $wpRole ): bool {
		if ( isset( $_GET['ReturnUrl'] ) && $_GET['ReturnUrl'] ) {

			Helpers::validateNonceFromUrl( $_GET['ReturnUrl'], SKAUTISINTEGRATION_NAME . '_registerToWpBySkautis' );

			// check for skautIS User ID collision with existing users
			$usersWpQuery = new \WP_User_Query( [
				'number'     => 1,
				'meta_query' => [
					[
						'key'     => 'skautisUserId_' . $this->skautisGateway->getEnv(),
						'value'   => absint( $skautisUser->ID ),
						'compare' => '='
					]
				]
			] );
			$users        = $usersWpQuery->get_results();

			if ( ! empty( $users ) ) {
				return true;
			}

			$userDetail = $this->skautisGateway->getSkautisInstance()->OrganizationUnit->PersonDetail( [
				'ID_Login' => $this->skautisGateway->getSkautisInstance()->getUser()->getLoginId(),
				'ID'       => $skautisUser->ID_Person
			] );

			$userId = $this->resolveNotificationsAndRegisterUserToWp( $userDetail->Email, $userDetail->Email );

			if ( $userId === 0 ) {
				return false;
			}

			if ( ! add_user_meta( $userId, 'skautisUserId_' . $this->skautisGateway->getEnv(), absint( $skautisUser->ID ) ) ) {
				return false;
			}

			$firstName = $userDetail->FirstName;
			$lastName  = $userDetail->LastName;
			if ( $nickName = $userDetail->NickName ) {
				$displayName = $nickName;
			} else {
				$nickName    = '';
				$displayName = $firstName . ' ' . $lastName;
			}

			if ( is_wp_error( wp_update_user( [
				'ID'           => $userId,
				'first_name'   => $firstName,
				'last_name'    => $lastName,
				'nickname'     => $nickName,
				'display_name' => $displayName,
				'role'         => $wpRole
			] ) ) ) {
				return false;
			}

			return true;
		}

		return false;
	}

	public function checkIfUserIsAlreadyRegisteredAndGetHisUserId(): int {
		$userDetail = $this->skautisGateway->getSkautisInstance()->UserManagement->UserDetail();

		if ( ! $userDetail || ! isset( $userDetail->ID ) || ! $userDetail->ID > 0 ) {
			return 0;
		}

		if ( ! isset( $_GET['ReturnUrl'] ) || ! $_GET['ReturnUrl'] ) {
			return 0;
		}

		Helpers::validateNonceFromUrl( $_GET['ReturnUrl'], SKAUTISINTEGRATION_NAME . '_registerToWpBySkautis' );

		// check for skautIS User ID collision with existing users
		$usersWpQuery = new \WP_User_Query( [
			'number'     => 1,
			'meta_query' => [
				[
					'key'     => 'skautisUserId_' . $this->skautisGateway->getEnv(),
					'value'   => absint( $userDetail->ID ),
					'compare' => '='
				]
			]
		] );
		$users        = $usersWpQuery->get_results();

		if ( ! empty( $users ) ) {
			return $users[0]->ID;
		}

		return 0;
	}

	public function getRegisterUrl(): string {
		if ( isset( $_GET['redirect_to'] ) && $_GET['redirect_to'] ) {
			$returnUrl = $_GET['redirect_to'];
		} else if ( isset( $_GET['ReturnUrl'] ) && $_GET['ReturnUrl'] ) {
			$returnUrl = $_GET['ReturnUrl'];
		} else {
			$returnUrl = Helpers::getCurrentUrl();
		}

		$returnUrl = remove_query_arg( 'loggedout', urldecode( $returnUrl ) );
		$returnUrl = remove_query_arg( SKAUTISINTEGRATION_NAME . '_loginToWpBySkautis', urldecode( $returnUrl ) );

		$returnUrl = add_query_arg( SKAUTISINTEGRATION_NAME . '_registerToWpBySkautis', wp_create_nonce( SKAUTISINTEGRATION_NAME . '_registerToWpBySkautis' ), $returnUrl );
		$url       = add_query_arg( 'ReturnUrl', urlencode( $returnUrl ), get_home_url( null, 'skautis/auth/' . Register::REGISTER_ACTION ) );

		return esc_url( $url );
	}

	public function registerToWp( string $wpRole ): bool {
		$userDetail = $this->skautisGateway->getSkautisInstance()->UserManagement->UserDetail();

		if ( $userDetail && isset( $userDetail->ID ) && $userDetail->ID > 0 ) {
			return $this->processWpUserRegistration( $userDetail, $wpRole );
		}

		return false;
	}

}
