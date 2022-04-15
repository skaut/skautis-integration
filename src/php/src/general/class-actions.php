<?php
/**
 * Contains the Actions class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\General;

use Skautis_Integration\Auth\Connect_And_Disconnect_WP_Account;
use Skautis_Integration\Auth\Skautis_Gateway;
use Skautis_Integration\Auth\Skautis_Login;
use Skautis_Integration\Auth\WP_Login_Logout;
use Skautis_Integration\Utils\Helpers;

/**
 * Handles redirects to and from SkautIS.
 */
final class Actions {

	const LOGIN_ACTION                      = 'login';
	const LOGOUT_CONFIRM_ACTION             = 'logout/confirm';
	const CONNECT_ACTION                    = 'connect';
	const CONNECT_WP_USER_TO_SKAUTIS_ACTION = 'connect/users';
	const DISCONNECT_ACTION                 = 'disconnect';

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
	 * A link to the WP_Login_Logout service instance.
	 *
	 * @var WP_Login_Logout
	 */
	private $wp_login_logout;

	/**
	 * A link to the Connect_And_Disconnect_WP_Account service instance.
	 *
	 * @var Connect_And_Disconnect_WP_Account
	 */
	private $connect_wp_account;

	/**
	 * TODO: Unused?
	 *
	 * @var string
	 */
	private $frontend_dir_url = '';

	/**
	 * Constructs the service and saves all dependencies.
	 *
	 * @param Skautis_Login                     $skautis_login An injected Skautis_Login service instance.
	 * @param WP_Login_Logout                   $wp_login_logout An injected WP_Login_Logout service instance.
	 * @param Connect_And_Disconnect_WP_Account $connect_wp_account An injected Connect_And_Disconnect_WP_Account service instance.
	 * @param Skautis_Gateway                   $skautis_gateway An injected Skautis_Gateway service instance.
	 */
	public function __construct( Skautis_Login $skautis_login, WP_Login_Logout $wp_login_logout, Connect_And_Disconnect_WP_Account $connect_wp_account, Skautis_Gateway $skautis_gateway ) {
		$this->skautis_gateway    = $skautis_gateway;
		$this->skautis_login      = $skautis_login;
		$this->wp_login_logout    = $wp_login_logout;
		$this->connect_wp_account = $connect_wp_account;
		$this->frontend_dir_url   = plugin_dir_url( __FILE__ ) . 'public/';
		$this->init_hooks();
	}

	/**
	 * Intializes all hooks used by the object.
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'register_auth_rewrite_rules' ) );
		add_action( 'query_vars', array( $this, 'register_auth_query_vars' ) );

		add_action( 'init', array( $this, 'flush_rewrite_rules_if_necessary' ) );

		add_action( 'pre_get_posts', array( $this, 'auth_actions_router' ) );

		add_action( 'plugins_loaded', array( $this, 'auth_in_process' ) );
		add_filter( 'allowed_redirect_hosts', array( $this, 'add_redirect_hosts' ) );
	}

	/**
	 * Adds both test and live SkautIS to host that WordPress is allowed to redirect to.
	 *
	 * @param array<string> $hosts A list of already allowed redirect hosts.
	 */
	public function add_redirect_hosts( $hosts ) {
		$hosts[] = 'test-is.skaut.cz';
		$hosts[] = 'is.skaut.cz';
		return $hosts;
	}

	/**
	 * Registers redirect/rewrite for SkautIS authentication.
	 *
	 * @see Actions::auth_actions_router() for more details about how the redirect is used.
	 */
	public function register_auth_rewrite_rules() {
		add_rewrite_rule( '^skautis/auth/(.*?)$', 'index.php?skautis_auth=$matches[1]', 'top' );
		$login_page_url = get_option( SKAUTIS_INTEGRATION_NAME . '_login_page_url' );
		if ( $login_page_url ) {
			add_rewrite_rule( '^' . $login_page_url . '$', 'index.php?skautis_login=1', 'top' );
		}
	}

	/**
	 * Adds query variables that WordPress is allowed to use when redirecting.
	 *
	 * @param array<string> $vars A list of already allowed query variables.
	 */
	public function register_auth_query_vars( array $vars = array() ): array {
		$vars[] = 'skautis_auth';

		return $vars;
	}

	/**
	 * Makes WordPress update rewrite rules if it is needed.
	 */
	public function flush_rewrite_rules_if_necessary() {
		if ( get_option( 'skautis_rewrite_rules_need_to_flush' ) ) {
			flush_rewrite_rules();
			delete_option( 'skautis_rewrite_rules_need_to_flush' );
		}
	}

	/**
	 * Fires upon redirect back from SkautIS and based on the current page finishes either the login or account linking.
	 */
	public function auth_in_process() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['skautIS_Token'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		do_action( SKAUTIS_INTEGRATION_NAME . '_after_skautis_token_is_set', $_POST );

		// TODO: What if I login to the profile page (e. g. after a timeout...)?
		if ( strpos( Helpers::get_current_url(), 'profile.php' ) !== false ) {
			$this->connect_wp_account->connect();
		} else {
			$this->skautis_login->login_confirm();
		}
	}

	/**
	 * Fires upon redirect to SkautIS authentication and fires the correct action.
	 *
	 * @throws \Exception The requested action doesn't exist.
	 *
	 * @param \WP_Query The request query.
	 */
	public function auth_actions_router( \WP_Query $wp_query ) {
		if ( ! $wp_query->get( 'skautis_auth' ) ) {
			return $wp_query;
		}

		if ( ! $this->skautis_gateway->is_initialized() ) {
			if ( ( get_option( 'skautis_integration_appid_type' ) === 'prod' && ! get_option( 'skautis_integration_appid_prod' ) ) ||
				( get_option( 'skautis_integration_appid_type' ) === 'test' && ! get_option( 'skautis_integration_appid_test' ) ) ) {
				if ( Helpers::user_is_skautis_manager() ) {
					/* translators: 1: Start of link to the settings 2: End of link to the settings */
					wp_die( sprintf( esc_html__( 'Pro správné fungování pluginu skautIS integrace, je potřeba %1$snastavit APP ID%2$s', 'skautis-integration' ), '<a href="' . esc_url( admin_url( 'admin.php?page=' . SKAUTIS_INTEGRATION_NAME ) ) . '">', '</a>' ), esc_html__( 'Chyba v konfiguraci pluginu', 'skautis-integration' ) );
				} else {
					wp_safe_redirect( get_home_url(), 302 );
					exit;
				}
			}
		}

		$action = $wp_query->get( 'skautis_auth' );

		$actions = array(
			self::LOGIN_ACTION                      => array( $this->skautis_login, 'login' ),
			self::LOGOUT_CONFIRM_ACTION             => array( $this->wp_login_logout, 'logout' ),
			self::CONNECT_ACTION                    => array( $this->connect_wp_account, 'connect' ),
			self::CONNECT_WP_USER_TO_SKAUTIS_ACTION => array( $this->connect_wp_account, 'connect_wp_user_to_skautis' ),
			self::DISCONNECT_ACTION                 => array( $this->connect_wp_account, 'disconnect' ),
		);

		$actions = apply_filters( SKAUTIS_INTEGRATION_NAME . '_frontend_actions_router', $actions );

		if ( isset( $actions[ $action ] ) ) {
			return call_user_func( $actions[ $action ] );
		} else {
			throw new \Exception( 'skautIS Auth action "' . esc_html( $action ) . '" is not defined' );
		}
	}

}
