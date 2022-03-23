<?php

declare( strict_types=1 );

namespace SkautisIntegration\Modules\Register;

use SkautisIntegration\Auth\Skautis_Gateway;
use SkautisIntegration\Repository\Users as UsersRepository;
use SkautisIntegration\Utils\Helpers;

final class WP_Register {

	private $skautisGateway;
	private $usersRepository;

	public function __construct( Skautis_Gateway $skautisGateway, UsersRepository $usersRepository ) {
		$this->skautisGateway  = $skautisGateway;
		$this->usersRepository = $usersRepository;
	}

	private function resolve_notifications_and_register_user_to_wp( string $userLogin, string $userEmail ): int {
		remove_action( 'register_new_user', 'wp_send_new_user_notifications' );
		add_action(
			'register_new_user',
			function ( $userId ) {
				$notify = apply_filters( SKAUTISINTEGRATION_NAME . '_modules_register_newUserNotifications', get_option( SKAUTISINTEGRATION_NAME . '_modules_register_notifications', 'none' ) );
				if ( 'none' !== $notify ) {
					global $wp_locale_switcher;
					if ( ! $wp_locale_switcher ) {
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
						$GLOBALS['wp_locale_switcher'] = new \WP_Locale_Switcher();
						$GLOBALS['wp_locale_switcher']->init();
					}
					wp_send_new_user_notifications( $userId, $notify );
				}
			}
		);

		add_filter( 'sanitize_user', array( $this, 'sanitizeUsername' ), 10, 3 );
		$userId = register_new_user( $userLogin, $userEmail );
		remove_filter( 'sanitize_user', array( $this, 'sanitizeUsername' ), 10 );

		add_action( 'register_new_user', 'wp_send_new_user_notifications' );

		if ( is_wp_error( $userId ) ) {
			if ( isset( $userId->errors ) && ( isset( $userId->errors['username_exists'] ) || isset( $userId->errors['email_exists'] ) ) ) {
				/* translators: The user's e-mail address */
				wp_die( sprintf( esc_html__( 'Vás email %s je již na webu registrován, ale není propojen se skautIS účtem.', 'skautis-integration' ), esc_html( $userEmail ) ), esc_html__( 'Chyba při registraci', 'skautis-integration' ) );
			}
				/* translators: The error message */
			wp_die( sprintf( esc_html__( 'Při registraci nastala neočekávaná chyba: %s', 'skautis-integration' ), esc_html( $userId->get_error_message() ) ), esc_html__( 'Chyba při registraci', 'skautis-integration' ) );
		}

		return $userId;
	}

	private function prepare_user_data( $skautisUser ): array {
		$skautisUserDetail = $this->skautisGateway->get_skautis_instance()->OrganizationUnit->PersonDetail(
			array(
				'ID_Login' => $this->skautisGateway->get_skautis_instance()->getUser()->getLoginId(),
				'ID'       => $skautisUser->ID_Person,
			)
		);

		$user = array(
			'id'        => $skautisUser->ID,
			'UserName'  => $skautisUser->UserName,
			'personId'  => $skautisUser->ID_Person,
			'email'     => $skautisUserDetail->Email,
			'firstName' => $skautisUserDetail->FirstName,
			'lastName'  => $skautisUserDetail->LastName,
			'nickName'  => $skautisUserDetail->NickName,
		);

		return $user;
	}

	private function process_wp_user_registration( array $user, string $wpRole ): bool {
		$returnUrl = Helpers::getReturnUrl();
		if ( is_null( $returnUrl ) ) {
			return false;
		}

		Helpers::validateNonceFromUrl( $returnUrl, SKAUTISINTEGRATION_NAME . '_registerToWpBySkautis' );

		// check for skautIS User ID collision with existing users
		$usersWpQuery = new \WP_User_Query(
			array(
				'number'     => 1,
				'meta_query' => array(
					array(
						'key'     => 'skautisUserId_' . $this->skautisGateway->get_env(),
						'value'   => absint( $user['id'] ),
						'compare' => '=',
					),
				),
			)
		);
		$users        = $usersWpQuery->get_results();

		if ( ! empty( $users ) ) {
			return true;
		}

		if ( ! isset( $user['UserName'] ) || mb_strlen( $user['UserName'] ) === 0 ) {
			return false;
		}

		$username = mb_strcut( $user['UserName'], 0, 60 );

		$userId = $this->resolve_notifications_and_register_user_to_wp( $username, $user['email'] );

		if ( 0 === $userId ) {
			return false;
		}

		if ( ! add_user_meta( $userId, 'skautisUserId_' . $this->skautisGateway->get_env(), absint( $user['id'] ) ) ) {
			return false;
		}

		$firstName = $user['firstName'];
		$lastName  = $user['lastName'];
		$nickName  = $user['nickName'];
		if ( $nickName ) {
			$displayName = $nickName;
		} else {
			$nickName    = '';
			$displayName = $firstName . ' ' . $lastName;
		}

		if ( is_wp_error(
			wp_update_user(
				array(
					'ID'           => $userId,
					'first_name'   => $firstName,
					'last_name'    => $lastName,
					'nickname'     => $nickName,
					'display_name' => $displayName,
					'role'         => $wpRole,
				)
			)
		) ) {
			return false;
		}

		return true;
	}

	public function check_if_user_is_already_registered_and_get_his_user_id(): int {
		$userDetail = $this->skautisGateway->get_skautis_instance()->UserManagement->UserDetail();

		if ( ! $userDetail || ! isset( $userDetail->ID ) || ! $userDetail->ID > 0 ) {
			return 0;
		}

		// check for skautIS User ID collision with existing users
		$usersWpQuery = new \WP_User_Query(
			array(
				'number'     => 1,
				'meta_query' => array(
					array(
						'key'     => 'skautisUserId_' . $this->skautisGateway->get_env(),
						'value'   => absint( $userDetail->ID ),
						'compare' => '=',
					),
				),
			)
		);
		$users        = $usersWpQuery->get_results();

		if ( ! empty( $users ) ) {
			return $users[0]->ID;
		}

		return 0;
	}

	public function get_register_url(): string {
		$returnUrl = Helpers::getLoginLogoutRedirect();
		$returnUrl = remove_query_arg( 'loggedout', urldecode( $returnUrl ) );

		$returnUrl = add_query_arg( SKAUTISINTEGRATION_NAME . '_registerToWpBySkautis', wp_create_nonce( SKAUTISINTEGRATION_NAME . '_registerToWpBySkautis' ), $returnUrl );
		$url       = add_query_arg( 'ReturnUrl', rawurlencode( $returnUrl ), get_home_url( null, 'skautis/auth/' . Register::REGISTER_ACTION ) );

		return esc_url( $url );
	}

	public function register_to_wp( string $wpRole ): bool {
		$userDetail = $this->skautisGateway->get_skautis_instance()->UserManagement->UserDetail();

		if ( $userDetail && isset( $userDetail->ID ) && $userDetail->ID > 0 ) {
			$user = $this->prepare_user_data( $userDetail );

			return $this->process_wp_user_registration( $user, $wpRole );
		}

		return false;
	}

	public function get_manually_register_wp_user_url(): string {
		$returnUrl = Helpers::getLoginLogoutRedirect();
		$returnUrl = add_query_arg( SKAUTISINTEGRATION_NAME . '_registerToWpBySkautis', wp_create_nonce( SKAUTISINTEGRATION_NAME . '_registerToWpBySkautis' ), $returnUrl );
		$url       = add_query_arg( 'ReturnUrl', rawurlencode( $returnUrl ), get_home_url( null, 'skautis/auth/' . Register::MANUALLY_REGISTER_WP_USER_ACTION ) );

		return esc_url( wp_nonce_url( $url, SKAUTISINTEGRATION_NAME . '_register_user', SKAUTISINTEGRATION_NAME . '_register_user_nonce' ) );
	}

	public function register_to_wp_manually( string $wpRole, int $skautisUserId ): bool {
		$userDetail = $this->usersRepository->get_user_detail( $skautisUserId );

		return $this->process_wp_user_registration( $userDetail, $wpRole );
	}

	public function sanitizeUsername( string $username, string $rawUsername, bool $strict ): string {
		$username = wp_strip_all_tags( $rawUsername );

		// Kill octets
		$username = preg_replace( '|%([a-fA-F0-9][a-fA-F0-9])|', '', $username );

		// Kill entities
		$username = preg_replace( '/&.+?;/', '', $username );

		// If strict, reduce to ASCII, Latin and Cyrillic characters for max portability.
		if ( $strict ) {
			$username = preg_replace( '|[^a-z\p{Latin}\p{Cyrillic}0-9 _.\-@]|iu', '', $username );
		}

		$username = trim( $username );

		// Consolidate contiguous whitespace
		$username = preg_replace( '|\s+|', ' ', $username );

		return $username;
	}

}
