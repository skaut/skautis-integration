<?php
/**
 * Contains the Connect_And_Disconnect_WP_Account class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Auth;

use Skautis_Integration\General\Actions;
use Skautis_Integration\Utils\Helpers;

/**
 * Handles connecting or disconnecting WordPress and SkautIS users.
 */
final class Connect_And_Disconnect_WP_Account {

	/**
	 * A link to the Skautis_Gateway service instance.
	 *
	 * @var Skautis_Gateway
	 */
	private $skautis_gateway;

	/**
	 * A link to the Skautis_Login service instance.
	 *
	 * @var Skautis_Login
	 */
	private $skautis_login;

	/**
	 * Constructs the service and saves all dependencies.
	 */
	public function __construct( Skautis_Gateway $skautis_gateway, Skautis_Login $skautis_login ) {
		$this->skautis_gateway = $skautis_gateway;
		$this->skautis_login   = $skautis_login;
	}

	/**
	 * Adds a SkautIS user id to a specified WordPress user. Respects the SkautIS environment (testing or production).
	 */
	private function set_skautis_user_id_to_wp_account( int $wp_user_id, int $skautis_user_id ) {
		$return_url = Helpers::get_return_url();
		if ( ! is_null( $return_url ) ) {
			Helpers::validate_nonce_from_url( $return_url, SKAUTIS_INTEGRATION_NAME . '_connectWpAccountWithSkautis' );

			update_user_meta( $wp_user_id, 'skautisUserId_' . $this->skautis_gateway->get_env(), absint( $skautis_user_id ) );

			wp_safe_redirect( $return_url, 302 );
			exit;
		}
	}

	/**
	 * Prints a button to either connect or disconnect the current user, or disconnect other user.
	 *
	 * It doesn't make sense to connect another user.
	 *
	 * Used when viewing own or another user's account.
	 */
	public function print_connect_and_disconnect_button( int $wp_user_id ) {
		$skautis_user_id = get_user_meta( $wp_user_id, 'skautisUserId_' . $this->skautis_gateway->get_env(), true );
		if ( $skautis_user_id ) {
			if ( ! Helpers::user_is_skautis_manager() && get_option( SKAUTIS_INTEGRATION_NAME . '_allowUsersDisconnectFromSkautis' ) !== '1' ) {
				return;
			}
			$return_url = add_query_arg( SKAUTIS_INTEGRATION_NAME . '_disconnectWpAccountFromSkautis', wp_create_nonce( SKAUTIS_INTEGRATION_NAME . '_disconnectWpAccountFromSkautis' ), Helpers::get_current_url() );
			$url        = add_query_arg( 'ReturnUrl', rawurlencode( $return_url ), get_home_url( null, 'skautis/auth/' . Actions::DISCONNECT_ACTION ) );

			echo '
			<a href="' . esc_url( $url ) . '"
			   class="button">' . esc_html__( 'Zrušit propojení účtu se skautISem', 'skautis-integration' ) . '</a>
			';
		} elseif ( get_current_screen()->id === 'profile' ) {
			$return_url = add_query_arg( SKAUTIS_INTEGRATION_NAME . '_connectWpAccountWithSkautis', wp_create_nonce( SKAUTIS_INTEGRATION_NAME . '_connectWpAccountWithSkautis' ), Helpers::get_current_url() );
			$url        = add_query_arg( 'ReturnUrl', rawurlencode( $return_url ), get_home_url( null, 'skautis/auth/' . Actions::CONNECT_ACTION ) );

			echo '
			<a href="' . esc_url( $url ) . '"
			   class="button">' . esc_html__( 'Propojit tento účet se skautISem', 'skautis-integration' ) . '</a>
			';
		}
	}

	/**
	 * Handles a call to connect the current WordPress user with SkautIS.
	 *
	 * This function checks if the user is logged into SkautIS and connects them with the current WordPress user. If the user isn't connected, they get redirected to SkautIS login first.
	 *
	 * @see Actions::auth_actions_router() for more details about how this function gets called.
	 */
	public function connect() {
		if ( ! $this->skautis_login->is_user_logged_in_skautis() ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( ! $this->skautis_login->set_login_data_to_local_skautis_instance( $_POST ) ) {
				$return_url = Helpers::get_return_url() ?? Helpers::get_current_url();
				wp_safe_redirect( esc_url_raw( $this->skautis_gateway->get_skautis_instance()->getLoginUrl( $return_url ) ), 302 );
				exit;
			}
		}

		$user_detail = $this->skautis_gateway->get_skautis_instance()->UserManagement->UserDetail();

		if ( $user_detail && isset( $user_detail->ID ) && $user_detail->ID > 0 ) {
			$this->set_skautis_user_id_to_wp_account( get_current_user_id(), $user_detail->ID );
		}
	}

	/**
	 * Connects an existing SkautIS user with an existing WordPress user.
	 *
	 * This is used to connect a WordPress user that is not the current user.
	 *
	 * @see Actions::auth_actions_router() for more details about how this function gets called.
	 */
	public function connect_wp_user_to_skautis() {
		if ( ! isset( $_GET[ SKAUTIS_INTEGRATION_NAME . '_connect_user_nonce' ] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET[ SKAUTIS_INTEGRATION_NAME . '_connect_user_nonce' ] ) ), SKAUTIS_INTEGRATION_NAME . '_connect_user' ) ||
			! $this->skautis_login->is_user_logged_in_skautis() ||
			! Helpers::user_is_skautis_manager() ||
			is_null( Helpers::get_return_url() )
		) {
			wp_die( esc_html__( 'Nemáte oprávnění k propojování uživatelů.', 'skautis-integration' ), esc_html__( 'Neautorizovaný přístup', 'skautis-integration' ) );
		}

		if ( ! isset( $_GET['wpUserId'], $_GET['skautisUserId'] ) ) {
			return;
		}

		$wp_user_id      = absint( $_GET['wpUserId'] );
		$skautis_user_id = absint( $_GET['skautisUserId'] );

		if ( $wp_user_id > 0 && $skautis_user_id > 0 ) {
			$this->set_skautis_user_id_to_wp_account( $wp_user_id, $skautis_user_id );
		}
	}

	/**
	 * Returns a link to connect an existing SkautIS user with an existing WordPress user.
	 *
	 * This link is used to connect a WordPress user that is not the current user.
	 */
	public function get_connect_wp_user_to_skautis_url(): string {
		$return_url = Helpers::get_current_url();
		$return_url = add_query_arg( SKAUTIS_INTEGRATION_NAME . '_connectWpAccountWithSkautis', wp_create_nonce( SKAUTIS_INTEGRATION_NAME . '_connectWpAccountWithSkautis' ), $return_url );
		$url        = add_query_arg( 'ReturnUrl', rawurlencode( $return_url ), get_home_url( null, 'skautis/auth/' . Actions::CONNECT_WP_USER_TO_SKAUTIS_ACTION ) );

		return esc_url( wp_nonce_url( $url, SKAUTIS_INTEGRATION_NAME . '_connect_user', SKAUTIS_INTEGRATION_NAME . '_connect_user_nonce' ) );
	}

	/**
	 * Disconnects a SkautIS user from a WordPress user.
	 *
	 * This function is used both to disconnect the current user as well as other users.
	 *
	 * TODO: Rework the horrible return URL logic to distinguish between current user and other user disconnecting.
	 *
	 * @see Actions::auth_actions_router() for more details about how this function gets called.
	 */
	public function disconnect() {
		if ( is_user_logged_in() ) {
			$return_url = Helpers::get_return_url();
			if ( ! is_null( $return_url ) ) {
				Helpers::validate_nonce_from_url( $return_url, SKAUTIS_INTEGRATION_NAME . '_disconnectWpAccountFromSkautis' );

				if ( strpos( $return_url, 'profile.php' ) !== false ) {
					delete_user_meta( get_current_user_id(), 'skautisUserId_' . $this->skautis_gateway->get_env() );
				} elseif ( ( strpos( $return_url, 'user-edit_php' ) !== false ||
							strpos( $return_url, 'user-edit.php' ) !== false ) &&
							strpos( $return_url, 'user_id=' ) !== false ) {
					if ( ! preg_match( '~user_id=(\d+)~', $return_url, $result ) ) {
						return;
					}
					if ( is_array( $result ) && isset( $result[1] ) && $result[1] > 0 ) {
						$user_id = absint( $result[1] );
						if ( Helpers::user_is_skautis_manager() ) {
							delete_user_meta( $user_id, 'skautisUserId_' . $this->skautis_gateway->get_env() );
						}
					}
				}
			}
		}

		$return_url = Helpers::get_return_url();
		if ( ! is_null( $return_url ) ) {
			wp_safe_redirect( $return_url, 302 );
			exit;
		} else {
			wp_safe_redirect( get_home_url(), 302 );
			exit;
		}
	}

}
