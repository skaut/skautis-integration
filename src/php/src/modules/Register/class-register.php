<?php
/**
 * Contains the Register class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Modules\Register;

use Skautis_Integration\Auth\Skautis_Gateway;
use Skautis_Integration\Auth\Skautis_Login;
use Skautis_Integration\Auth\WP_Login_Logout;
use Skautis_Integration\Rules\Rules_Manager;
use Skautis_Integration\Repository\Users as UsersRepository;
use Skautis_Integration\Modules\Module;
use Skautis_Integration\Modules\Register\Admin\Admin;
use Skautis_Integration\Modules\Register\Frontend\Login_Form;
use Skautis_Integration\Utils\Helpers;
use Skautis_Integration\Utils\Request_Parameter_Helpers;

/**
 * Adds the functionality to register new WordPress users based on SkautIS.
 */
final class Register implements Module {

	const REGISTER_ACTION                  = 'register';
	const MANUALLY_REGISTER_WP_USER_ACTION = 'registerManually';

	/**
	 * The module ID.
	 *
	 * @var string
	 */
	public static $module_id = 'module_Register';

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
	 * A link to the Rules_Manager service instance.
	 *
	 * @var Rules_Manager
	 */
	private $rules_manager;

	/**
	 * A link to the Users service instance.
	 *
	 * TODO: Unused?
	 *
	 * @var UsersRepository
	 */
	private $users_repository;

	/**
	 * A link to the WP_Register service instance.
	 *
	 * @var WP_Register
	 */
	private $wp_register;

	/**
	 * Constructs the module and saves all dependencies.
	 *
	 * @param Skautis_Gateway $skautis_gateway An injected Skautis_Gateway service instance.
	 * @param Skautis_Login   $skautis_login An injected Skautis_Login service instance.
	 * @param WP_Login_Logout $wp_login_logout An injected WP_Login_Logout service instance.
	 * @param Rules_Manager   $rules_manager An injected Rules_Manager service instance.
	 * @param UsersRepository $users_repository An injected Users service instance.
	 */
	public function __construct( Skautis_Gateway $skautis_gateway, Skautis_Login $skautis_login, WP_Login_Logout $wp_login_logout, Rules_Manager $rules_manager, UsersRepository $users_repository ) {
		$this->skautis_gateway  = $skautis_gateway;
		$this->skautis_login    = $skautis_login;
		$this->wp_login_logout  = $wp_login_logout;
		$this->rules_manager    = $rules_manager;
		$this->users_repository = $users_repository;
		$this->wp_register      = new WP_Register( $this->skautis_gateway, $this->users_repository );
		if ( is_admin() ) {
			new Admin( $this->rules_manager );
		} else {
			new Login_Form( $this->wp_register );
		}
		$this->init_hooks();
	}

	/**
	 * Intializes all hooks used by the object.
	 *
	 * @return void
	 */
	private function init_hooks() {
		add_filter( SKAUTIS_INTEGRATION_NAME . '_frontend_actions_router', array( $this, 'addActionsToRouter' ) );
		$return_url = Helpers::get_return_url();
		if ( ! is_null( $return_url ) ) {
			if ( '' !== Helpers::get_nonce_from_url( $return_url, SKAUTIS_INTEGRATION_NAME . '_registerToWpBySkautis' ) ) {
				add_action( SKAUTIS_INTEGRATION_NAME . '_after_skautis_token_is_set', array( $this, 'registerConfirm' ) );
			}
		}
	}

	/**
	 * Redirects the user to login with SkautIS.
	 *
	 * @return void
	 *
	 * @SuppressWarnings(PHPMD.ExitExpression)
	 */
	private function loginUserAfterRegistration() {
		$return_url = Helpers::get_login_logout_redirect();
		$return_url = remove_query_arg( SKAUTIS_INTEGRATION_NAME . '_registerToWpBySkautis', urldecode( $return_url ) );
		wp_safe_redirect( esc_url_raw( $this->wp_login_logout->get_login_url( $return_url ) ), 302 );
		die();
	}

	/**
	 * Adds new actions to Actions.
	 *
	 * This function modifies the behaviour of Actions to add new possible redirects using SkautIS.
	 *
	 * @see Actions::auth_actions_router() for more details about how the actions are used.
	 *
	 * @param array<string, callable> $actions A list of already registered actions.
	 *
	 * @return array<string, callable> The updated action list.
	 */
	public function addActionsToRouter( array $actions = array() ): array {
		$actions[ self::REGISTER_ACTION ]                  = array( $this, 'register' );
		$actions[ self::MANUALLY_REGISTER_WP_USER_ACTION ] = array( $this, 'registerUserManually' );

		return $actions;
	}

	/**
	 * Fires upon redirect back from SkautIS after login and handles the user login and potential registration.
	 *
	 * @param array{skautIS_Token?: string, skautIS_IDRole?: string, skautIS_IDUnit?: string, skautIS_DateLogout?: string} $data SkautIS login data.
	 *
	 * @return void
	 */
	public function registerConfirm( array $data = array() ) {
		// TODO: Why is this not one conditional?
		if ( $this->skautis_login->set_login_data_to_local_skautis_instance( $data ) ) {
			$this->registerUser();
		} elseif ( $this->skautis_login->is_user_logged_in_skautis() ) {
			$this->registerUser();
		}
	}

	/**
	 * Returns the module ID.
	 */
	public static function get_id(): string {
		return self::$module_id;
	}

	/**
	 * Returns the localized module name.
	 */
	public static function get_label(): string {
		return __( 'Registrace', 'skautis-integration' );
	}

	/**
	 * Returns the path to the module.
	 */
	public static function get_path(): string {
		return plugin_dir_path( __FILE__ );
	}

	/**
	 * Returns the URL of the module.
	 */
	public static function get_url(): string {
		return plugin_dir_url( __FILE__ );
	}

	/**
	 * Returns the module WP_Register instance.
	 */
	public function getWpRegister(): WP_Register {
		return $this->wp_register;
	}

	/**
	 * Returns the module Rules_Manager instance.
	 */
	public function getRulesManager(): Rules_Manager {
		return $this->rules_manager;
	}

	/**
	 * Handles a call to log the user into SkautIS and possibly register them in WordPress in the process.
	 *
	 * @see Actions::auth_actions_router() for more details about how this function gets called.
	 * @see Register::addActionsToRouter() for more details about how this function gets called.
	 *
	 * @return void
	 *
	 * @SuppressWarnings(PHPMD.ExitExpression)
	 */
	public function register() {
		if ( ! $this->skautis_login->is_user_logged_in_skautis() ) {
			$return_url = Helpers::get_return_url() ?? Helpers::get_current_url();
			wp_safe_redirect( esc_url_raw( $this->skautis_gateway->get_skautis_instance()->getLoginUrl( $return_url ) ), 302 );
			die();
		}

		$this->registerUser();
	}

	/**
	 * This function actually handles the user login and potential registration.
	 *
	 * @return void
	 */
	public function registerUser() {
		$wp_role = $this->rules_manager->check_if_user_passed_rules_and_get_his_role();
		if ( '' !== $wp_role ) {
			if ( $this->wp_register->register_to_wp( $wp_role ) ) {
				$this->loginUserAfterRegistration();
			}
		} else {
			$wp_user_id = $this->wp_register->check_if_user_is_already_registered_and_get_his_user_id();
			if ( $wp_user_id > 0 ) {
				if ( false !== get_option( SKAUTIS_INTEGRATION_NAME . '_checkUserPrivilegesIfLoginBySkautis' ) ) {
					if ( user_can( $wp_user_id, Helpers::get_skautis_manager_capability() ) ) {
						$this->loginUserAfterRegistration();
					}
				} else {
					$this->loginUserAfterRegistration();
				}
			}
		}

		$this->skautis_gateway->logout();

		$return_url = Helpers::get_return_url();
		if ( ! is_null( $return_url ) ) {
			/* translators: 1: Start of the link back 2: End of the link back */
			wp_die( sprintf( esc_html__( 'Nemáte oprávnění k registraci. %1$sZkuste to znovu%2$s', 'skautis-integration' ), '<a href="' . esc_url( $return_url ) . '">', '</a>' ), esc_html__( 'Neautorizovaný přístup', 'skautis-integration' ) );
		}
		wp_die( esc_html__( 'Nemáte oprávnění k registraci.', 'skautis-integration' ), esc_html__( 'Neautorizovaný přístup', 'skautis-integration' ) );
	}

	/**
	 * Registers an existing SkautIS user as a new WordPress user.
	 *
	 * This function is used to register other users than the current user.
	 *
	 * @return void
	 *
	 * @SuppressWarnings(PHPMD.ExitExpression)
	 */
	public function registerUserManually() {
		$return_url = Helpers::get_return_url();
		$nonce = Request_Parameter_Helpers::get_string_variable( SKAUTIS_INTEGRATION_NAME . '_register_user_nonce' );
		$wp_role = Request_Parameter_Helpers::get_string_variable( 'wpRole' );
		$skautis_user_id = Request_Parameter_Helpers::get_int_variable( 'skautisUserId' );
		if ( false === wp_verify_nonce( $nonce, SKAUTIS_INTEGRATION_NAME . '_register_user' ) ||
			! $this->skautis_login->is_user_logged_in_skautis() ||
			! Helpers::user_is_skautis_manager() ||
			! current_user_can( 'create_users' ) ||
			is_null( $return_url ) ||
			'' === $wp_role ||
			-1 === $skautis_user_id ) {
			wp_die( esc_html__( 'Nemáte oprávnění k registraci nových uživatelů.', 'skautis-integration' ), esc_html__( 'Neautorizovaný přístup', 'skautis-integration' ) );
			return;
		}

		if ( ! wp_roles()->is_role( $wp_role ) ) {
			wp_die( esc_html__( 'Uživatele se nepodařilo zaregistrovat - role neexistuje.', 'skautis-integration' ), esc_html__( 'Chyba při registraci uživatele', 'skautis-integration' ) );
		}

		if ( $this->wp_register->register_to_wp_manually( $wp_role, $skautis_user_id ) ) {
			wp_safe_redirect( $return_url, 302 );
			die();
		} else {
			wp_die( esc_html__( 'Uživatele se nepodařilo zaregistrovat', 'skautis-integration' ), esc_html__( 'Chyba při registraci uživatele', 'skautis-integration' ) );
		}
	}

}
