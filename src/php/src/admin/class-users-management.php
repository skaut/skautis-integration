<?php
/**
 * Contains the Users_Management class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Admin;

use Skautis_Integration\Auth\Skautis_Gateway;
use Skautis_Integration\Auth\WP_Login_Logout;
use Skautis_Integration\Auth\Skautis_Login;
use Skautis_Integration\Auth\Connect_And_Disconnect_WP_Account;
use Skautis_Integration\Repository\Users as UsersRepository;
use Skautis_Integration\General\Actions;
use Skautis_Integration\Services\Services;
use Skautis_Integration\Modules\Register\Register;
use Skautis_Integration\Utils\Helpers;
use Skautis_Integration\Utils\Role_Changer;

/**
 * Adds an administration page for management of SkautIS users.
 */
class Users_Management {

	/**
	 * A link to the Skautis_Gateway service instance.
	 *
	 * TODO: Private?
	 *
	 * @var Skautis_Gateway
	 */
	protected $skautis_gateway;

	/**
	 * A link to the WP_Login_Logout service instance.
	 *
	 * TODO: Private?
	 *
	 * @var WP_Login_Logout
	 */
	protected $wp_login_logout;

	/**
	 * A link to the Skautis_Login service instance.
	 *
	 * TODO: Private?
	 *
	 * @var Skautis_Login
	 */
	protected $skautis_login;

	/**
	 * A link to the Connect_And_Disconnect_WP_Account service instance.
	 *
	 * TODO: Private?
	 *
	 * @var Connect_And_Disconnect_WP_Account
	 */
	protected $connect_and_disconnect_wp_account;

	/**
	 * A link to the Users service instance.
	 *
	 * TODO: Private?
	 *
	 * @var UsersRepository
	 */
	protected $users_repository;

	/**
	 * A link to the Role_Changer service instance.
	 *
	 * TODO: Private?
	 *
	 * @var Role_Changer
	 */
	protected $role_changer;

	/**
	 * TODO: Unused?
	 *
	 * @var string
	 */
	protected $admin_dir_url = '';

	/**
	 * Constructs the service and saves all dependencies.
	 *
	 * @param Skautis_Gateway                   $skautis_gateway An injected Skautis_Gateway service instance.
	 * @param WP_Login_Logout                   $wp_login_logout An injected WP_Login_Logout service instance.
	 * @param Skautis_Login                     $skautis_login An injected Skautis_Login service instance.
	 * @param Connect_And_Disconnect_WP_Account $connect_and_disconnect_wp_account An injected Connect_And_Disconnect_WP_Account service instance.
	 * @param UsersRepository                   $users_repository An injected Users service instance.
	 * @param Role_Changer                      $role_changer An injected Role_Changer service instance.
	 */
	public function __construct( Skautis_Gateway $skautis_gateway, WP_Login_Logout $wp_login_logout, Skautis_Login $skautis_login, Connect_And_Disconnect_WP_Account $connect_and_disconnect_wp_account, UsersRepository $users_repository, Role_Changer $role_changer ) {
		$this->skautis_gateway                   = $skautis_gateway;
		$this->wp_login_logout                   = $wp_login_logout;
		$this->skautis_login                     = $skautis_login;
		$this->connect_and_disconnect_wp_account = $connect_and_disconnect_wp_account;
		$this->users_repository                  = $users_repository;
		$this->role_changer                      = $role_changer;
		$this->admin_dir_url                     = plugin_dir_url( __FILE__ ) . 'public/';
		$this->check_if_user_change_skautis_role();
		$this->init_hooks();
	}

	/**
	 * Intializes all hooks used by the object.
	 */
	protected function init_hooks() {
		add_action(
			'admin_menu',
			array(
				$this,
				'setup_users_management_page',
			),
			10
		);

		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_scripts_and_styles' ) );
	}

	/**
	 * On page load, changes the user's SkautIS role if requested by a POST variable.
	 *
	 * TODO: Find a more robust way to do this?
	 * TODO: Duplicated in Role_Changer.
	 */
	protected function check_if_user_change_skautis_role() {
		add_action(
			'init',
			function () {
				if ( isset( $_POST['changeSkautisUserRole'], $_POST['_wpnonce'], $_POST['_wp_http_referer'] ) ) {
					if ( check_admin_referer( SKAUTIS_INTEGRATION_NAME . '_changeSkautisUserRole', '_wpnonce' ) ) {
						if ( $this->skautis_login->is_user_logged_in_skautis() ) {
							$this->skautis_login->change_user_role_in_skautis( absint( $_POST['changeSkautisUserRole'] ) );
						}
					}
				}
			}
		);
	}

	/**
	 * Enqueues scripts and styles used for the user management table.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public static function enqueue_scripts_and_styles( $hook_suffix ) {
		if ( ! str_ends_with( $hook_suffix, SKAUTIS_INTEGRATION_NAME . '_usersManagement' ) ) {
			return;
		}
		wp_enqueue_script( 'thickbox' );
		wp_enqueue_style( 'thickbox' );
		if ( is_network_admin() ) {
			add_action( 'admin_head', '_thickbox_path_admin_subfolder' );
		}

		wp_enqueue_style(
			SKAUTIS_INTEGRATION_NAME . '_datatables',
			SKAUTIS_INTEGRATION_URL . 'bundled/jquery.dataTables.min.css',
			array(),
			SKAUTIS_INTEGRATION_VERSION,
			'all'
		);

		wp_enqueue_script(
			SKAUTIS_INTEGRATION_NAME . '_datatables',
			SKAUTIS_INTEGRATION_URL . 'bundled/jquery.dataTables.min.js',
			array( 'jquery' ),
			SKAUTIS_INTEGRATION_VERSION,
			true
		);

		Helpers::enqueue_style( 'admin', 'admin/css/skautis-admin.min.css' );
		Helpers::enqueue_style( 'admin-users-management', 'admin/css/skautis-admin-users-management.min.css' );
		Helpers::enqueue_script(
			'admin-users-management',
			'admin/js/skautis-admin-users-management.min.js',
			array( 'jquery', SKAUTIS_INTEGRATION_NAME . '_select2' ),
		);

		wp_localize_script(
			SKAUTIS_INTEGRATION_NAME . '_admin-users-management',
			'skautisIntegrationAdminUsersManagementLocalize',
			array(
				'cancel'             => esc_html__( 'Zrušit', 'skautis-integration' ),
				'datatablesFilesUrl' => SKAUTIS_INTEGRATION_URL . 'bundled/datatables-files',
				'searchNonceName'    => SKAUTIS_INTEGRATION_NAME . '_skautis_search_user_nonce',
				'searchNonceValue'   => wp_create_nonce( SKAUTIS_INTEGRATION_NAME . '_skautis_search_user' ),
			)
		);
	}

	/**
	 * Registers the user management administration page with WordPress.
	 */
	public function setup_users_management_page() {
		add_submenu_page(
			SKAUTIS_INTEGRATION_NAME,
			__( 'Správa uživatelů', 'skautis-integration' ),
			__( 'Správa uživatelů', 'skautis-integration' ),
			Helpers::get_skautis_manager_capability(),
			SKAUTIS_INTEGRATION_NAME . '_usersManagement',
			array( $this, 'print_child_users' )
		);
	}

	/**
	 * Prints the user management administration page.
	 */
	public function print_child_users() {
		if ( ! Helpers::user_is_skautis_manager() ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'skautis-integration' ) );
		}

		echo '
		<div class="wrap">
			<h1>' . esc_html__( 'Správa uživatelů', 'skautis-integration' ) . '</h1>
			<p>' . esc_html__( 'Zde si můžete propojit členy ze skautISu s uživateli ve WordPressu nebo je rovnou zaregistrovat (vyžaduje aktivovaný modul Registrace).', 'skautis-integration' ) . '</p>
		';

		if ( ! $this->skautis_login->is_user_logged_in_skautis() ) {
			if ( $this->skautis_gateway->is_initialized() ) {
				echo '<a href="' . esc_url( $this->wp_login_logout->get_login_url( add_query_arg( 'noWpLogin', true, Helpers::get_current_url() ) ) ) . '">' . esc_html__( 'Pro zobrazení obsahu je nutné se přihlásit do skautISu', 'skautis-integration' ) . '</a>';
				echo '
		</div>
			';
			} else {
				/* translators: 1: Start of link to the settings 2: End of link to the settings */
				printf( esc_html__( 'Vyberte v %1$snastavení%2$s pluginu typ prostředí skautISu', 'skautis-integration' ), '<a href="' . esc_url( admin_url( 'admin.php?page=' . SKAUTIS_INTEGRATION_NAME ) ) . '">', '</a>' );
				echo '
		</div>
			';
			}

			return;
		}

		$this->role_changer->print_change_roles_form();

		echo '<table class="skautis-user-management-table"><thead style="font-weight: bold;"><tr>';
		echo '<th>' . esc_html__( 'Jméno a příjmení', 'skautis-integration' ) . '</th><th>' . esc_html__( 'Přezdívka', 'skautis-integration' ) . '</th><th>' . esc_html__( 'ID uživatele', 'skautis-integration' ) . '</th><th>' . esc_html__( 'Propojený uživatel', 'skautis-integration' ) . '</th><th>' . esc_html__( 'Propojení', 'skautis-integration' ) . '</th>';
		echo '</tr></thead ><tbody>';

		$users_data = $this->users_repository->get_connected_wp_users();

		$users = $this->users_repository->get_users()['users'];

		foreach ( $users as $user ) {
			if ( isset( $users_data[ $user->id ] ) ) {
				$home_url                = get_home_url( null, 'skautis/auth/' . Actions::DISCONNECT_ACTION );
				$nonce                   = wp_create_nonce( SKAUTIS_INTEGRATION_NAME . '_disconnectWpAccountFromSkautis' );
				$user_edit_link          = get_edit_user_link( $users_data[ $user->id ]['id'] );
				$return_url              = add_query_arg( SKAUTIS_INTEGRATION_NAME . '_disconnectWpAccountFromSkautis', $nonce, Helpers::get_current_url() );
				$return_url              = add_query_arg( 'user-edit_php', '', $return_url );
				$return_url              = add_query_arg( 'user_id', $users_data[ $user->id ]['id'], $return_url );
				$connect_disconnect_link = add_query_arg( 'ReturnUrl', rawurlencode( $return_url ), $home_url );
				echo '<tr style="background-color: #d1ffd1;">
	<td class="username">
		<span class="firstName">' . esc_html( $user->firstName ) . '</span> <span class="lastName">' . esc_html( $user->lastName ) . '</span>
	</td>
	<td>&nbsp;&nbsp;<span class="nickName">' . esc_html( $user->nickName ) . '</span></td><td>&nbsp;&nbsp;<span class="skautisUserId">' . esc_html( $user->id ) . '</span></td><td><a href="' . esc_url( $user_edit_link ) . '">' . esc_html( $users_data[ $user->id ]['name'] ) . '</a></td><td><a href="' . esc_url( $connect_disconnect_link ) . '" class="button">' . esc_html__( 'Odpojit', 'skautis-integration' ) . '</a></td></tr>';
			} else {
				echo '<tr>
	<td class="username">
		<span class="firstName">' . esc_html( $user->firstName ) . '</span> <span class="lastName">' . esc_html( $user->lastName ) . '</span>
	</td>
	<td>&nbsp;&nbsp;<span class="nickName">' . esc_html( $user->nickName ) . '</span></td><td>&nbsp;&nbsp;<span class="skautisUserId">' . esc_html( $user->id ) . '</span></td><td></td><td><a href="#TB_inline?width=450&height=380&inlineId=connectUserToSkautisModal" class="button thickbox">' . esc_html__( 'Propojit', 'skautis-integration' ) . '</a></td></tr>';
			}
		}
		echo '</tbody></table>';

		?>
		</div>
		<div id="connectUserToSkautisModal" class="hidden">
			<div class="content">
				<h3><?php esc_html_e( 'Propojení uživatele', 'skautis-integration' ); ?> <span
						id="connectUserToSkautisModal_username"></span> <?php esc_html_e( 'se skautISem', 'skautis-integration' ); ?>
				</h3>
				<h4><?php esc_html_e( 'Vyberte uživatele již registrovaného ve WordPressu', 'skautis-integration' ); ?>:</h4>
				<select id="connectUserToSkautisModal_select">
					<option><?php esc_html_e( 'Vyberte uživatele...', 'skautis-integration' ); ?></option>
					<?php
					foreach ( $this->users_repository->get_connectable_wp_users() as $user ) {
						$user_name = $user->data->display_name;
						if ( ! $user_name ) {
							$user_name = $user->data->user_login;
						}
						echo '
						<option value="' . absint( $user->ID ) . '">' . esc_html( $user_name ) . '</option>
						';
					}
					?>
				</select>
				<a id="connectUserToSkautisModal_connectLink" class="button button-primary"
					href="<?php echo esc_url( $this->connect_and_disconnect_wp_account->get_connect_wp_user_to_skautis_url() ); ?>"><?php esc_html_e( 'Potvrdit', 'skautis-integration' ); ?></a>
				<div>
					<em><?php esc_html_e( 'Je možné vybrat pouze ty uživatele, kteří ještě nemají propojený účet se skautISem.', 'skautis-integration' ); ?></em>
				</div>
				<?php
				if ( Services::get_modules_manager()->is_module_activated( Register::get_id() ) ) {
					?>
					<hr/>
					<h3><?php esc_html_e( 'Vytvořit nový účet', 'skautis-integration' ); ?></h3>
					<p>
						<?php esc_html_e( 'Vytvoří nového uživatele ve WordPressu se jménem, příjmením, přezdívkou a emailem ze skautISu. Účet bude automaticky propojen se skautISem.', 'skautis-integration' ); ?>
					</p>
					<label>
						<span><?php esc_html_e( 'Vyberte úroveň nového uživatele', 'skautis-integration' ); ?></span>
						<select name="role" id="connectUserToSkautisModal_defaultRole">
							<?php wp_dropdown_roles( get_option( SKAUTIS_INTEGRATION_NAME . '_modules_register_defaultwpRole' ) ); ?>
						</select>
					</label>
					<p>
						<a id="connectUserToSkautisModal_registerLink" class="button button-primary"
							href="<?php echo esc_url( Services::get_module( Register::get_id() )->getWpRegister()->get_manually_register_wp_user_url() ); ?>"><?php esc_html_e( 'Vytvořit nový účet', 'skautis-integration' ); ?></a>
					</p>
					<?php
				}
				?>
			</div>
		</div>
		<?php
	}

}
