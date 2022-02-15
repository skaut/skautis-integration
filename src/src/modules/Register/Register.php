<?php

declare( strict_types=1 );

namespace SkautisIntegration\Modules\Register;

use SkautisIntegration\Auth\SkautisGateway;
use SkautisIntegration\Auth\SkautisLogin;
use SkautisIntegration\Auth\WpLoginLogout;
use SkautisIntegration\Rules\RulesManager;
use SkautisIntegration\Repository\Users as UsersRepository;
use SkautisIntegration\Modules\IModule;
use SkautisIntegration\Modules\Register\Admin\Admin;
use SkautisIntegration\Modules\Register\Frontend\Frontend;
use SkautisIntegration\Modules\Register\Frontend\LoginForm;
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

	public function __construct( SkautisGateway $skautisGateway, SkautisLogin $skautisLogin, WpLoginLogout $wpLoginLogout, RulesManager $rulesManager, UsersRepository $usersRepository ) {
		$this->skautisGateway  = $skautisGateway;
		$this->skautisLogin    = $skautisLogin;
		$this->wpLoginLogout   = $wpLoginLogout;
		$this->rulesManager    = $rulesManager;
		$this->usersRepository = $usersRepository;
		$this->wpRegister      = new WpRegister( $this->skautisGateway, $this->usersRepository );
		if ( is_admin() ) {
			( new Admin( $rulesManager ) );
		} else {
			( new Frontend( new LoginForm( $this->wpRegister ) ) );
		}
		$this->initHooks();
	}

	private function initHooks() {
		add_filter( SKAUTISINTEGRATION_NAME . '_frontend_actions_router', array( $this, 'addActionsToRouter' ) );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		if ( isset( $_GET['ReturnUrl'] ) && $_GET['ReturnUrl'] ) {
			if ( Helpers::getNonceFromUrl( esc_url_raw( wp_unslash( $_GET['ReturnUrl'] ) ), SKAUTISINTEGRATION_NAME . '_registerToWpBySkautis' ) ) {
				add_action( SKAUTISINTEGRATION_NAME . '_after_skautis_token_is_set', array( $this, 'registerConfirm' ) );
			}
		}
	}

	private function loginUserAfterRegistration() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		if ( isset( $_GET['redirect_to'] ) && $_GET['redirect_to'] ) {
			$returnUrl = esc_url_raw( wp_unslash( $_GET['redirect_to'] ) );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		} elseif ( isset( $_GET['ReturnUrl'] ) && $_GET['ReturnUrl'] ) {
			$returnUrl = esc_url_raw( wp_unslash( $_GET['ReturnUrl'] ) );
		} else {
			$returnUrl = Helpers::getCurrentUrl();
		}
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

	public function getWpRegister(): WpRegister {
		return $this->wpRegister;
	}

	public function getRulesManager(): RulesManager {
		return $this->rulesManager;
	}

	public function register() {
		if ( ! $this->skautisLogin->isUserLoggedInSkautis() ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			if ( isset( $_GET['ReturnUrl'] ) && $_GET['ReturnUrl'] ) {
				$returnUrl = esc_url_raw( wp_unslash( $_GET['ReturnUrl'] ) );
			} else {
				$returnUrl = Helpers::getCurrentUrl();
			}
			wp_safe_redirect( esc_url_raw( $this->skautisGateway->getSkautisInstance()->getLoginUrl( $returnUrl ) ), 302 );
			exit;
		}

		$this->registerUser();
	}

	public function registerUser() {
		if ( $wpRole = $this->rulesManager->checkIfUserPassedRulesAndGetHisRole() ) {
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

		if ( ! empty( $_GET['ReturnUrl'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			wp_die( sprintf( esc_html__( 'Nemáte oprávnění k registraci. %1$sZkuste to znovu%2$s', 'skautis-integration' ), '<a href="' . esc_url( wp_unslash( $_GET['ReturnUrl'] ) ) . '">', '</a>' ), esc_html__( 'Neautorizovaný přístup', 'skautis-integration' ) );
		}
		wp_die( esc_html__( 'Nemáte oprávnění k registraci.', 'skautis-integration' ), esc_html__( 'Neautorizovaný přístup', 'skautis-integration' ) );
	}

	public function registerUserManually() {
		if ( ! isset( $_GET[SKAUTISINTEGRATION_NAME. '_register_user_nonce'] ) ||
			 ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET[SKAUTISINTEGRATION_NAME. '_register_user_nonce'] ) ), SKAUTISINTEGRATION_NAME. '_register_user' ) ||
			 ! $this->skautisLogin->isUserLoggedInSkautis() ||
			 ! Helpers::userIsSkautisManager() ||
			 ! current_user_can( 'create_users' ) ||
			 ! isset( $_GET['ReturnUrl'], $_GET['wpRole'], $_GET['skautisUserId'] ) ) {
			wp_die( esc_html__( 'Nemáte oprávnění k registraci nových uživatelů.', 'skautis-integration' ), esc_html__( 'Neautorizovaný přístup', 'skautis-integration' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$wpRole = wp_unslash( $_GET['wpRole'] );
		if ( ! wp_roles()->is_role( $wpRole ) ) {
			wp_die( esc_html__( 'Uživatele se nepodařilo zaregistrovat - role neexistuje.', 'skautis-integration' ), esc_html__( 'Chyba při registraci uživatele', 'skautis-integration' ) );
		}
		$skautisUserId = absint( $_GET['skautisUserId'] );

		if ( $this->wpRegister->registerToWpManually( $wpRole, $skautisUserId ) ) {
			wp_safe_redirect( esc_url_raw( wp_unslash( $_GET['ReturnUrl'] ) ), 302 );
			exit;
		} else {
			wp_die( esc_html__( 'Uživatele se nepodařilo zaregistrovat', 'skautis-integration' ), esc_html__( 'Chyba při registraci uživatele', 'skautis-integration' ) );
		}
	}

}
