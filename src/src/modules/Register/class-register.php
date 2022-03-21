<?php

declare( strict_types=1 );

namespace SkautisIntegration\Modules\Register;

use SkautisIntegration\Auth\SkautisGateway;
use SkautisIntegration\Auth\Skautis_Login;
use SkautisIntegration\Auth\WP_Login_Logout;
use SkautisIntegration\Rules\Rules_Manager;
use SkautisIntegration\Repository\Users as UsersRepository;
use SkautisIntegration\Modules\IModule;
use SkautisIntegration\Modules\Register\Admin\Admin;
use SkautisIntegration\Modules\Register\Frontend\Frontend;
use SkautisIntegration\Modules\Register\Frontend\Login_Form;
use SkautisIntegration\Utils\Helpers;

final class Register implements IModule {

	const REGISTER_ACTION                  = 'register';
	const MANUALLY_REGISTER_WP_USER_ACTION = 'registerManually';

	public static $id = 'module_Register';

	private $skautisGateway;
	private $skautisLogin;
	private $wpLoginLogout;
	private $rulesManager;
	private $usersRepository;
	private $wpRegister;

	public function __construct( SkautisGateway $skautisGateway, Skautis_Login $skautisLogin, WP_Login_Logout $wpLoginLogout, Rules_Manager $rulesManager, UsersRepository $usersRepository ) {
		$this->skautisGateway  = $skautisGateway;
		$this->skautisLogin    = $skautisLogin;
		$this->wpLoginLogout   = $wpLoginLogout;
		$this->rulesManager    = $rulesManager;
		$this->usersRepository = $usersRepository;
		$this->wpRegister      = new WP_Register( $this->skautisGateway, $this->usersRepository );
		if ( is_admin() ) {
			( new Admin( $rulesManager ) );
		} else {
			( new Frontend( new Login_Form( $this->wpRegister ) ) );
		}
		$this->initHooks();
	}

	private function initHooks() {
		add_filter( SKAUTISINTEGRATION_NAME . '_frontend_actions_router', array( $this, 'addActionsToRouter' ) );
		$returnUrl = Helpers::getReturnUrl();
		if ( ! is_null( $returnUrl ) ) {
			if ( Helpers::getNonceFromUrl( $returnUrl, SKAUTISINTEGRATION_NAME . '_registerToWpBySkautis' ) ) {
				add_action( SKAUTISINTEGRATION_NAME . '_after_skautis_token_is_set', array( $this, 'registerConfirm' ) );
			}
		}
	}

	private function loginUserAfterRegistration() {
		$returnUrl = Helpers::getLoginLogoutRedirect();
		$returnUrl = remove_query_arg( SKAUTISINTEGRATION_NAME . '_registerToWpBySkautis', urldecode( $returnUrl ) );
		wp_safe_redirect( esc_url_raw( $this->wpLoginLogout->getLoginUrl( $returnUrl ) ), 302 );
		exit;
	}

	public function addActionsToRouter( array $actions = array() ): array {
		$actions[ self::REGISTER_ACTION ]                  = array( $this, 'register' );
		$actions[ self::MANUALLY_REGISTER_WP_USER_ACTION ] = array( $this, 'registerUserManually' );

		return $actions;
	}

	public function registerConfirm( array $data = array() ) {
		if ( $this->skautisLogin->setLoginDataToLocalSkautisInstance( $data ) ) {
			$this->registerUser();
		} elseif ( $this->skautisLogin->isUserLoggedInSkautis() ) {
			$this->registerUser();
		}
	}

	public static function getId(): string {
		return self::$id;
	}

	public static function getLabel(): string {
		return __( 'Registrace', 'skautis-integration' );
	}

	public static function getPath(): string {
		return plugin_dir_path( __FILE__ );
	}

	public static function getUrl(): string {
		return plugin_dir_url( __FILE__ );
	}

	public function getWpRegister(): WP_Register {
		return $this->wpRegister;
	}

	public function getRulesManager(): Rules_Manager {
		return $this->rulesManager;
	}

	public function register() {
		if ( ! $this->skautisLogin->isUserLoggedInSkautis() ) {
			$returnUrl = Helpers::getReturnUrl() ?? Helpers::getCurrentUrl();
			wp_safe_redirect( esc_url_raw( $this->skautisGateway->getSkautisInstance()->getLoginUrl( $returnUrl ) ), 302 );
			exit;
		}

		$this->registerUser();
	}

	public function registerUser() {
		$wpRole = $this->rulesManager->checkIfUserPassedRulesAndGetHisRole();
		if ( $wpRole ) {
			if ( $this->wpRegister->registerToWp( $wpRole ) ) {
				$this->loginUserAfterRegistration();
			}
		} else {
			$wpUserId = $this->wpRegister->checkIfUserIsAlreadyRegisteredAndGetHisUserId();
			if ( $wpUserId > 0 ) {
				if ( get_option( SKAUTISINTEGRATION_NAME . '_checkUserPrivilegesIfLoginBySkautis' ) ) {
					if ( user_can( $wpUserId, Helpers::getSkautisManagerCapability() ) ) {
						$this->loginUserAfterRegistration();
					}
				} else {
					$this->loginUserAfterRegistration();
				}
			}
		}

		$this->skautisGateway->logout();

		$returnUrl = Helpers::getReturnUrl();
		if ( ! is_null( $returnUrl ) ) {
			/* translators: 1: Start of the link back 2: End of the link back */
			wp_die( sprintf( esc_html__( 'Nemáte oprávnění k registraci. %1$sZkuste to znovu%2$s', 'skautis-integration' ), '<a href="' . esc_url( $returnUrl ) . '">', '</a>' ), esc_html__( 'Neautorizovaný přístup', 'skautis-integration' ) );
		}
		wp_die( esc_html__( 'Nemáte oprávnění k registraci.', 'skautis-integration' ), esc_html__( 'Neautorizovaný přístup', 'skautis-integration' ) );
	}

	public function registerUserManually() {
		$returnUrl = Helpers::getReturnUrl();
		if ( ! isset( $_GET[ SKAUTISINTEGRATION_NAME . '_register_user_nonce' ] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET[ SKAUTISINTEGRATION_NAME . '_register_user_nonce' ] ) ), SKAUTISINTEGRATION_NAME . '_register_user' ) ||
			! $this->skautisLogin->isUserLoggedInSkautis() ||
			! Helpers::userIsSkautisManager() ||
			! current_user_can( 'create_users' ) ||
			is_null( $returnUrl ) ||
			! isset( $_GET['wpRole'], $_GET['skautisUserId'] ) ) {
			wp_die( esc_html__( 'Nemáte oprávnění k registraci nových uživatelů.', 'skautis-integration' ), esc_html__( 'Neautorizovaný přístup', 'skautis-integration' ) );
		}

		$wpRole = sanitize_text_field( wp_unslash( $_GET['wpRole'] ) );
		if ( ! wp_roles()->is_role( $wpRole ) ) {
			wp_die( esc_html__( 'Uživatele se nepodařilo zaregistrovat - role neexistuje.', 'skautis-integration' ), esc_html__( 'Chyba při registraci uživatele', 'skautis-integration' ) );
		}
		$skautisUserId = absint( $_GET['skautisUserId'] );

		if ( $this->wpRegister->registerToWpManually( $wpRole, $skautisUserId ) ) {
			wp_safe_redirect( $returnUrl, 302 );
			exit;
		} else {
			wp_die( esc_html__( 'Uživatele se nepodařilo zaregistrovat', 'skautis-integration' ), esc_html__( 'Chyba při registraci uživatele', 'skautis-integration' ) );
		}
	}

}
