<?php

declare( strict_types=1 );

namespace SkautisIntegration\General;

use SkautisIntegration\Auth\Connect_And_Disconnect_WP_Account;
use SkautisIntegration\Auth\Skautis_Gateway;
use SkautisIntegration\Auth\Skautis_Login;
use SkautisIntegration\Auth\WP_Login_Logout;
use SkautisIntegration\Utils\Helpers;

final class Actions {

	const LOGIN_ACTION                      = 'login';
	const LOGOUT_CONFIRM_ACTION             = 'logout/confirm';
	const CONNECT_ACTION                    = 'connect';
	const CONNECT_WP_USER_TO_SKAUTIS_ACTION = 'connect/users';
	const DISCONNECT_ACTION                 = 'disconnect';

	private $skautisGateway;
	private $skautisLogin;
	private $wpLoginLogout;
	private $connectWpAccount;
	private $frontendDirUrl = '';

	public function __construct( Skautis_Login $skautisLogin, WP_Login_Logout $wpLoginLogout, Connect_And_Disconnect_WP_Account $connectWpAccount, Skautis_Gateway $skautisGateway ) {
		$this->skautisGateway   = $skautisGateway;
		$this->skautisLogin     = $skautisLogin;
		$this->wpLoginLogout    = $wpLoginLogout;
		$this->connectWpAccount = $connectWpAccount;
		$this->frontendDirUrl   = plugin_dir_url( __FILE__ ) . 'public/';
		$this->init_hooks();
	}

	private function init_hooks() {
		add_action( 'init', array( $this, 'registerAuthRewriteRules' ) );
		add_action( 'query_vars', array( $this, 'registerAuthQueryVars' ) );

		add_action( 'init', array( $this, 'flushRewriteRulesIfNecessary' ) );

		add_action( 'pre_get_posts', array( $this, 'authActionsRouter' ) );

		add_action( 'plugins_loaded', array( $this, 'authInProcess' ) );
		add_filter( 'allowed_redirect_hosts', array( $this, 'addRedirectHosts' ) );
	}

	public function addRedirectHosts( $hosts ) {
		$hosts[] = 'test-is.skaut.cz';
		$hosts[] = 'is.skaut.cz';
		return $hosts;
	}

	public function registerAuthRewriteRules() {
		add_rewrite_rule( '^skautis/auth/(.*?)$', 'index.php?skautis_auth=$matches[1]', 'top' );
		$loginPageUrl = get_option( SKAUTISINTEGRATION_NAME . '_login_page_url' );
		if ( $loginPageUrl ) {
			add_rewrite_rule( '^' . $loginPageUrl . '$', 'index.php?skautis_login=1', 'top' );
		}
	}

	public function registerAuthQueryVars( array $vars = array() ): array {
		$vars[] = 'skautis_auth';

		return $vars;
	}

	public function flushRewriteRulesIfNecessary() {
		if ( get_option( 'skautis_rewrite_rules_need_to_flush' ) ) {
			flush_rewrite_rules();
			delete_option( 'skautis_rewrite_rules_need_to_flush' );
		}
	}

	public function authInProcess() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['skautIS_Token'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		do_action( SKAUTISINTEGRATION_NAME . '_after_skautis_token_is_set', $_POST );

		if ( strpos( Helpers::getCurrentUrl(), 'profile.php' ) !== false ) {
			$this->connectWpAccount->connect();
		} else {
			$this->skautisLogin->loginConfirm();
		}
	}

	public function authActionsRouter( \WP_Query $wpQuery ) {
		if ( ! $wpQuery->get( 'skautis_auth' ) ) {
			return $wpQuery;
		}

		if ( ! $this->skautisGateway->isInitialized() ) {
			if ( ( get_option( 'skautis_integration_appid_type' ) === 'prod' && ! get_option( 'skautis_integration_appid_prod' ) ) ||
				( get_option( 'skautis_integration_appid_type' ) === 'test' && ! get_option( 'skautis_integration_appid_test' ) ) ) {
				if ( Helpers::userIsSkautisManager() ) {
					/* translators: 1: Start of link to the settings 2: End of link to the settings */
					wp_die( sprintf( esc_html__( 'Pro správné fungování pluginu skautIS integrace, je potřeba %1$snastavit APP ID%2$s', 'skautis-integration' ), '<a href="' . esc_url( admin_url( 'admin.php?page=' . SKAUTISINTEGRATION_NAME ) ) . '">', '</a>' ), esc_html__( 'Chyba v konfiguraci pluginu', 'skautis-integration' ) );
				} else {
					wp_safe_redirect( get_home_url(), 302 );
					exit;
				}
			}
		}

		$action = $wpQuery->get( 'skautis_auth' );

		$actions = array(
			self::LOGIN_ACTION                      => array( $this->skautisLogin, 'login' ),
			self::LOGOUT_CONFIRM_ACTION             => array( $this->wpLoginLogout, 'logout' ),
			self::CONNECT_ACTION                    => array( $this->connectWpAccount, 'connect' ),
			self::CONNECT_WP_USER_TO_SKAUTIS_ACTION => array( $this->connectWpAccount, 'connectWpUserToSkautis' ),
			self::DISCONNECT_ACTION                 => array( $this->connectWpAccount, 'disconnect' ),
		);

		$actions = apply_filters( SKAUTISINTEGRATION_NAME . '_frontend_actions_router', $actions );

		if ( isset( $actions[ $action ] ) ) {
			return call_user_func( $actions[ $action ] );
		} else {
			throw new \Exception( 'skautIS Auth action "' . esc_html( $action ) . '" is not defined' );
		}
	}

}
