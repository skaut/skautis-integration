<?php
/**
 * Contains the WP_Register class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Modules\Register;

use Skautis_Integration\Auth\Skautis_Gateway;
use Skautis_Integration\Repository\Users as UsersRepository;
use Skautis_Integration\Utils\Helpers;

/**
 * Adds the functionality to register new WordPress users based on SkautIS.
 *
 * This class handles the WordPress part of the process.
 */
final class WP_Register {

	/**
	 * A link to the Skautis_Gateway service instance.
	 *
	 * @var Skautis_Gateway
	 */
	private $skautis_gateway;

	/**
	 * A link to the Users service instance.
	 *
	 * @var UsersRepository
	 */
	private $users_repository;

	/**
	 * Constructs the service and saves all dependencies.
	 *
	 * @param Skautis_Gateway $skautis_gateway An injected Skautis_Gateway service instance.
	 * @param UsersRepository $users_repository An injected Users service instance.
	 */
	public function __construct( Skautis_Gateway $skautis_gateway, UsersRepository $users_repository ) {
		$this->skautis_gateway  = $skautis_gateway;
		$this->users_repository = $users_repository;
	}

	/**
	 * Registers a new WordPress user and send appropriate notifications based on the module settings.
	 *
	 * @param string $user_login The WordPress username to use for the new user.
	 * @param string $user_email The new user's e-mail address.
	 *
	 * @return int The ID of the new user.
	 */
	private static function resolve_notifications_and_register_user_to_wp( string $user_login, string $user_email ) {
		remove_action( 'register_new_user', 'wp_send_new_user_notifications' );
		add_action(
			'register_new_user',
			static function ( $user_id ) {
				// TODO: Unused filter?
				$notify = apply_filters( SKAUTIS_INTEGRATION_NAME . '_modules_register_new_user_notifications', get_option( SKAUTIS_INTEGRATION_NAME . '_modules_register_notifications', 'none' ) );
				if ( 'none' !== $notify ) {
					global $wp_locale_switcher;
					if ( ! $wp_locale_switcher ) {
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
						$GLOBALS['wp_locale_switcher'] = new \WP_Locale_Switcher();
						$GLOBALS['wp_locale_switcher']->init();
					}
					wp_send_new_user_notifications( $user_id, $notify );
				}
			}
		);

		add_filter( 'sanitize_user', array( self::class, 'sanitize_username' ), 10, 3 );
		$user_id = register_new_user( $user_login, $user_email );
		remove_filter( 'sanitize_user', array( self::class, 'sanitize_username' ), 10 );

		add_action( 'register_new_user', 'wp_send_new_user_notifications' );

		if ( $user_id instanceof \WP_Error ) {
			if ( isset( $user_id->errors['username_exists'] ) || isset( $user_id->errors['email_exists'] ) ) {
				/* translators: The user's e-mail address */
				wp_die( sprintf( esc_html__( 'Vás email %s je již na webu registrován, ale není propojen se skautIS účtem.', 'skautis-integration' ), esc_html( $user_email ) ), esc_html__( 'Chyba při registraci', 'skautis-integration' ) );
			}
				/* translators: The error message */
			wp_die( sprintf( esc_html__( 'Při registraci nastala neočekávaná chyba: %s', 'skautis-integration' ), esc_html( $user_id->get_error_message() ) ), esc_html__( 'Chyba při registraci', 'skautis-integration' ) );
		}

		return $user_id;
	}

	/**
	 * Retrieves information about a user from SkautIS.
	 *
	 * This function queries SkautIS for the information needed when registering a new WordPress user.
	 *
	 * @param \stdClass $skautis_user The SkautIS user.
	 */
	private function prepare_user_data( $skautis_user ): array {
		$skautis_user_detail = $this->skautis_gateway->get_skautis_instance()->OrganizationUnit->PersonDetail(
			array(
				'ID_Login' => $this->skautis_gateway->get_skautis_instance()->getUser()->getLoginId(),
				'ID'       => $skautis_user->ID_Person,
			)
		);

		$user = array(
			'id'        => $skautis_user->ID,
			'UserName'  => $skautis_user->UserName,
			'personId'  => $skautis_user->ID_Person,
			'email'     => $skautis_user_detail->Email,
			'firstName' => $skautis_user_detail->FirstName,
			'lastName'  => $skautis_user_detail->LastName,
			'nickName'  => $skautis_user_detail->NickName,
		);

		return $user;
	}

	/**
	 * Registers a new WordPress user based on user data.
	 *
	 * This function performs checks around the user already being registed, user ID collisions and populates basic user info based on the user data.
	 *
	 * @see resolve_notifications_and_register_user_to_wp This function actually performs the registration.
	 *
	 * @param array{id: int, UserName: string, email: string, firstName: string, lastName: string, nickName: string} $user Information about the user.
	 * @param string                                                                                                 $wp_role The WordPress role to assign to the new user.
	 */
	private function process_wp_user_registration( array $user, string $wp_role ): bool {
		$return_url = Helpers::get_return_url();
		if ( is_null( $return_url ) ) {
			return false;
		}

		Helpers::validate_nonce_from_url( $return_url, SKAUTIS_INTEGRATION_NAME . '_registerToWpBySkautis' );

		// TODO: Replace with a call to get_users()?
		// Check for skautIS User ID collision with existing users.
		$users_wp_query = new \WP_User_Query(
			array(
				'number'     => 1,
				'meta_query' => array(
					array(
						'key'     => 'skautisUserId_' . $this->skautis_gateway->get_env(),
						'value'   => absint( $user['id'] ),
						'compare' => '=',
					),
				),
			)
		);
		$users          = $users_wp_query->get_results();

		if ( ! empty( $users ) ) {
			return true;
		}

		if ( mb_strlen( $user['UserName'] ) === 0 ) {
			return false;
		}

		$username = mb_strcut( $user['UserName'], 0, 60 );

		$user_id = self::resolve_notifications_and_register_user_to_wp( $username, $user['email'] );

		if ( 0 === $user_id || ! is_int( $user_id ) ) {
			return false;
		}

		if ( false === add_user_meta( $user_id, 'skautisUserId_' . $this->skautis_gateway->get_env(), absint( $user['id'] ) ) ) {
			return false;
		}

		$first_name = $user['firstName'];
		$last_name  = $user['lastName'];
		$nick_name  = $user['nickName'];
		if ( '' !== $nick_name ) {
			$display_name = $nick_name;
		} else {
			$nick_name    = '';
			$display_name = $first_name . ' ' . $last_name;
		}

		if ( is_wp_error(
			wp_update_user(
				array(
					'ID'           => $user_id,
					'first_name'   => $first_name,
					'last_name'    => $last_name,
					'nickname'     => $nick_name,
					'display_name' => $display_name,
					'role'         => $wp_role,
				)
			)
		) ) {
			return false;
		}

		return true;
	}

	/**
	 * Returns the WordPress user ID of the current SkautIS user, or 0 if there is no such WordPress user.
	 */
	public function check_if_user_is_already_registered_and_get_his_user_id(): int {
		$user_detail = $this->skautis_gateway->get_skautis_instance()->UserManagement->UserDetail();

		if ( is_null( $user_detail ) || ! isset( $user_detail->ID ) || ! $user_detail->ID > 0 ) {
			return 0;
		}

		// TODO: Replace with a call to get_users()?
		// Check for skautIS User ID collision with existing users.
		$users_wp_query = new \WP_User_Query(
			array(
				'number'     => 1,
				'meta_query' => array(
					array(
						'key'     => 'skautisUserId_' . $this->skautis_gateway->get_env(),
						'value'   => absint( $user_detail->ID ),
						'compare' => '=',
					),
				),
			)
		);
		$users          = $users_wp_query->get_results();

		if ( ! empty( $users ) ) {
			return $users[0]->ID;
		}

		return 0;
	}

	/**
	 * Returns the Register module version of the SkautIS login URL with all arguments initialized.
	 *
	 * This version runs this module's register action after the login.
	 *
	 * @see Register::register() The action that fires after the login.
	 *
	 * @suppress PhanPluginPossiblyStaticPublicMethod
	 */
	public function get_register_url(): string {
		$return_url = Helpers::get_login_logout_redirect();
		$return_url = remove_query_arg( 'loggedout', urldecode( $return_url ) );

		$return_url = add_query_arg( SKAUTIS_INTEGRATION_NAME . '_registerToWpBySkautis', wp_create_nonce( SKAUTIS_INTEGRATION_NAME . '_registerToWpBySkautis' ), $return_url );
		$url        = add_query_arg( 'ReturnUrl', rawurlencode( $return_url ), get_home_url( null, 'skautis/auth/' . Register::REGISTER_ACTION ) );

		return esc_url( $url );
	}

	/**
	 * Registers the current SkautIS user into WordPress.
	 *
	 * @param string $wp_role The WordPress role to assign to the new user.
	 */
	public function register_to_wp( string $wp_role ): bool {
		$user_detail = $this->skautis_gateway->get_skautis_instance()->UserManagement->UserDetail();

		if ( $user_detail && isset( $user_detail->ID ) && $user_detail->ID > 0 ) {
			$user = $this->prepare_user_data( $user_detail );

			return $this->process_wp_user_registration( $user, $wp_role );
		}

		return false;
	}

	/**
	 * Returns the URL used to register an existing SkautIS user as a new WordPress user.
	 *
	 * This function is used to register other users than the current user.
	 *
	 * @suppress PhanPluginPossiblyStaticPublicMethod
	 */
	public function get_manually_register_wp_user_url(): string {
		$return_url = Helpers::get_login_logout_redirect();
		$return_url = add_query_arg( SKAUTIS_INTEGRATION_NAME . '_registerToWpBySkautis', wp_create_nonce( SKAUTIS_INTEGRATION_NAME . '_registerToWpBySkautis' ), $return_url );
		$url        = add_query_arg( 'ReturnUrl', rawurlencode( $return_url ), get_home_url( null, 'skautis/auth/' . Register::MANUALLY_REGISTER_WP_USER_ACTION ) );

		return esc_url( wp_nonce_url( $url, SKAUTIS_INTEGRATION_NAME . '_register_user', SKAUTIS_INTEGRATION_NAME . '_register_user_nonce' ) );
	}

	/**
	 * Registers a SkautIS user into WordPress.
	 *
	 * This function is used to register a user that is not the current SkautIS user.
	 *
	 * @param string $wp_role The WordPress role to assign to the new user.
	 * @param int    $skautis_user_id The ID of the SkautIS user.
	 */
	public function register_to_wp_manually( string $wp_role, int $skautis_user_id ): bool {
		$user_detail = $this->users_repository->get_user_detail( $skautis_user_id );

		return $this->process_wp_user_registration( $user_detail, $wp_role );
	}

	/**
	 * Sanitizes SkautIS username to be a valid WordPress username.
	 *
	 * @param string $username The sanitized username @unused-param.
	 * @param string $raw_username The raw username before sanitizing.
	 * @param bool   $strict Whether to limit the username to Latin, Cyrillic and a few special characters.
	 */
	public static function sanitize_username( string $username, string $raw_username, bool $strict ): string {
		$username = wp_strip_all_tags( $raw_username );

		// Kill octets.
		$username = preg_replace( '|%([a-fA-F0-9][a-fA-F0-9])|', '', $username );

		// Kill entities.
		$username = preg_replace( '/&.+?;/', '', $username );

		// If strict, reduce to ASCII, Latin and Cyrillic characters for max portability.
		if ( $strict ) {
			$username = preg_replace( '|[^a-z\p{Latin}\p{Cyrillic}0-9 _.\-@]|iu', '', $username );
		}

		$username = trim( $username );

		// Consolidate contiguous whitespace.
		$username = preg_replace( '|\s+|', ' ', $username );

		return $username;
	}

}
