<?php

namespace SkautisIntegration\Modules\Register;

use SkautisIntegration\Auth\SkautisGateway;
use SkautisIntegration\Auth\SkautisLogin;
use SkautisIntegration\Auth\WpLoginLogout;
use SkautisIntegration\Rules\RulesManager;
use SkautisIntegration\Modules\IModule;
use SkautisIntegration\Modules\Register\Admin\Admin;
use SkautisIntegration\Modules\Register\Frontend\Frontend;
use SkautisIntegration\Modules\Register\Frontend\LoginForm;
use SkautisIntegration\Utils\Helpers;

class Register implements IModule {

	const REGISTER_ACTION = 'register';

	public static $id = 'module_Register';

	private $skautisGateway;
	private $skautisLogin;
	private $wpLoginLogout;
	private $rulesManager;
	private $wpRegister;

	public function __construct( SkautisGateway $skautisGateway, SkautisLogin $skautisLogin, WpLoginLogout $wpLoginLogout, RulesManager $rulesManager ) {
		$this->skautisGateway = $skautisGateway;
		$this->skautisLogin   = $skautisLogin;
		$this->wpLoginLogout  = $wpLoginLogout;
		$this->rulesManager   = $rulesManager;
		$this->wpRegister     = new WpRegister( $this->skautisGateway );
		if ( is_admin() ) {
			( new Admin( $rulesManager ) );
		} else {
			( new Frontend( new LoginForm( $this->wpRegister ) ) );
		}
		$this->initHooks();
	}

	private function initHooks() {
		add_filter( SKAUTISINTEGRATION_NAME . '_frontend_actions_router', [ $this, 'addActionsToRouter' ] );
		if ( isset( $_GET['ReturnUrl'] ) && $_GET['ReturnUrl'] ) {
			if ( Helpers::getNonceFromUrl( $_GET['ReturnUrl'], SKAUTISINTEGRATION_NAME . '_registerToWpBySkautis' ) ) {
				add_action( SKAUTISINTEGRATION_NAME . '_after_skautis_token_is_set', [ $this, 'registerConfirm' ] );
			}
		}
	}

	public function addActionsToRouter( array $actions = [] ) {
		$actions[ self::REGISTER_ACTION ] = [ $this, 'register' ];

		return $actions;
	}

	public function registerConfirm( array $data = [] ) {
		if ( $this->skautisLogin->setLoginDataToLocalSkautisInstance( $data ) ) {
			$this->registerUser();
		} else if ( $this->skautisLogin->isUserLoggedInSkautis() ) {
			$this->registerUser();
		}
	}

	public static function getId() {
		return self::$id;
	}

	public static function getLabel() {
		return __( 'Registrace', 'skautis-integration' );
	}

	public static function getPath() {
		return plugin_dir_path( __FILE__ );
	}

	public static function getUrl() {
		return plugin_dir_url( __FILE__ );
	}

	public function getWpRegister() {
		return $this->wpRegister;
	}

	public function getRulesManager() {
		return $this->rulesManager;
	}

	public function register() {
		if ( ! $this->skautisLogin->isUserLoggedInSkautis() ) {
			if ( isset( $_GET['ReturnUrl'] ) && $_GET['ReturnUrl'] ) {
				$returnUrl = $_GET['ReturnUrl'];
			} else {
				$returnUrl = Helpers::getCurrentUrl();
			}
			wp_redirect( $this->skautisGateway->getSkautisInstance()->getLoginUrl( $returnUrl ), 302 );
			exit;
		}

		$this->registerUser();
	}

	public function registerUser() {
		if ( $wpRole = $this->rulesManager->checkIfUserPassedRulesAndGetHisRole() ) {
			if ( $this->wpRegister->registerToWp( $wpRole ) ) {
				if ( isset( $_GET['redirect_to'] ) && $_GET['redirect_to'] ) {
					$returnUrl = $_GET['redirect_to'];
				} else if ( isset( $_GET['ReturnUrl'] ) && $_GET['ReturnUrl'] ) {
					$returnUrl = $_GET['ReturnUrl'];
				} else {
					$returnUrl = Helpers::getCurrentUrl();
				}
				$returnUrl = remove_query_arg( SKAUTISINTEGRATION_NAME . '_registerToWpBySkautis', urldecode( $returnUrl ) );
				wp_safe_redirect( $this->wpLoginLogout->getLoginUrl( $returnUrl ), 302 );
				exit;
			}
		}

		$this->skautisGateway->logout();

		$tryAgain = '';
		if ( ! empty( $_GET['ReturnUrl'] ) ) {
			$tryAgain = '<a href="' . esc_attr( $_GET['ReturnUrl'] ) . '">' . __( 'Zkuste to znovu', 'skautis-integration' ) . '</a>';
		}
		wp_die( sprintf( __( 'Nemáte oprávnění k registraci. %s', 'skautis-integration' ), $tryAgain ), __( 'Neautorizovaný přístup', 'skautis-integration' ) );
	}

}