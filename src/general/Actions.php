<?php

namespace SkautisIntegration\General;

use SkautisIntegration\Auth\ConnectAndDisconnectWpAccount;
use SkautisIntegration\Auth\SkautisLogin;
use SkautisIntegration\Auth\WpLoginLogout;
use SkautisIntegration\Utils\Helpers;

final class Actions {

	const LOGIN_ACTION                      = 'login';
	const LOGOUT_CONFIRM_ACTION             = 'logout/confirm';
	const CONNECT_ACTION                    = 'connect';
	const CONNECT_WP_USER_TO_SKAUTIS_ACTION = 'connect/users';
	const DISCONNECT_ACTION                 = 'disconnect';

	private $skautisLogin;
	private $wpLoginLogout;
	private $connectWpAccount;
	private $frontendDirUrl = '';

	public function __construct( SkautisLogin $skautisLogin, WpLoginLogout $wpLoginLogout, ConnectAndDisconnectWpAccount $connectWpAccount ) {
		$this->skautisLogin     = $skautisLogin;
		$this->wpLoginLogout    = $wpLoginLogout;
		$this->connectWpAccount = $connectWpAccount;
		$this->frontendDirUrl   = plugin_dir_url( __FILE__ ) . 'public/';
		$this->initHooks();
	}

	private function initHooks() {
		add_action( 'init', [ $this, 'registerAuthRewriteRules' ] );
		add_action( 'query_vars', [ $this, 'registerAuthQueryVars' ] );

		add_action( 'init', [ $this, 'flushRewriteRulesIfNecessary' ] );

		add_action( 'pre_get_posts', [ $this, 'authActionsRouter' ] );

		add_action( 'plugins_loaded', [ $this, 'authInProcess' ] );
	}

	public function registerAuthRewriteRules() {
		add_rewrite_rule( '^skautis/auth/(.*?)$', 'index.php?skautis_auth=$matches[1]', 'top' );
		if ( $loginPageUrl = (string) get_option( SKAUTISINTEGRATION_NAME . '_login_page_url' ) ) {
			add_rewrite_rule( '^' . $loginPageUrl . '$', 'index.php?skautis_login=1', 'top' );
		}
	}

	public function registerAuthQueryVars( array $vars = [] ) {
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
		if ( ! isset( $_POST['skautIS_Token'] ) ) {
			return;
		}

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
		$action = $wpQuery->get( 'skautis_auth' );

		$actions = [
			self::LOGIN_ACTION                      => [ $this->skautisLogin, 'login' ],
			self::LOGOUT_CONFIRM_ACTION             => [ $this->wpLoginLogout, 'logout' ],
			self::CONNECT_ACTION                    => [ $this->connectWpAccount, 'connect' ],
			self::CONNECT_WP_USER_TO_SKAUTIS_ACTION => [ $this->connectWpAccount, 'connectWpUserToSkautis' ],
			self::DISCONNECT_ACTION                 => [ $this->connectWpAccount, 'disconnect' ]
		];

		$actions = apply_filters( SKAUTISINTEGRATION_NAME . '_frontend_actions_router', $actions );

		if ( isset( $actions[ $action ] ) ) {
			return call_user_func( $actions[ $action ] );
		} else {
			throw new \Exception( 'SkautIS Auth action "' . esc_html( $action ) . '" is not defined' );
		}

	}

}
