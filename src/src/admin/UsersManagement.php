<?php

declare( strict_types=1 );

namespace SkautisIntegration\Admin;

use SkautisIntegration\Auth\SkautisGateway;
use SkautisIntegration\Auth\WpLoginLogout;
use SkautisIntegration\Auth\SkautisLogin;
use SkautisIntegration\Auth\ConnectAndDisconnectWpAccount;
use SkautisIntegration\Repository\Users as UsersRepository;
use SkautisIntegration\General\Actions;
use SkautisIntegration\Services\Services;
use SkautisIntegration\Modules\Register\Register;
use SkautisIntegration\Utils\Helpers;
use SkautisIntegration\Utils\RoleChanger;

class UsersManagement {

	protected $skautisGateway;
	protected $wpLoginLogout;
	protected $skautisLogin;
	protected $connectAndDisconnectWpAccount;
	protected $usersRepository;
	protected $roleChanger;
	protected $adminDirUrl = '';

	public function __construct( SkautisGateway $skautisGateway, WpLoginLogout $wpLoginLogout, SkautisLogin $skautisLogin, ConnectAndDisconnectWpAccount $connectAndDisconnectWpAccount, UsersRepository $usersRepository, RoleChanger $roleChanger ) {
		$this->skautisGateway                = $skautisGateway;
		$this->wpLoginLogout                 = $wpLoginLogout;
		$this->skautisLogin                  = $skautisLogin;
		$this->connectAndDisconnectWpAccount = $connectAndDisconnectWpAccount;
		$this->usersRepository               = $usersRepository;
		$this->roleChanger                   = $roleChanger;
		$this->adminDirUrl                   = plugin_dir_url( __FILE__ ) . 'public/';
		$this->checkIfUserChangeSkautisRole();
		$this->initHooks();
	}

	protected function initHooks() {
		add_action(
			'admin_menu',
			array(
				$this,
				'setupUsersManagementPage',
			),
			10
		);

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueScriptsAndStyles' ) );
	}

	protected function checkIfUserChangeSkautisRole() {
		add_action(
			'init',
			function () {
				if ( isset( $_POST['changeSkautisUserRole'], $_POST['_wpnonce'], $_POST['_wp_http_referer'] ) ) {
					if ( check_admin_referer( SKAUTISINTEGRATION_NAME . '_changeSkautisUserRole', '_wpnonce' ) ) {
						if ( $this->skautisLogin->isUserLoggedInSkautis() ) {
							$this->skautisLogin->changeUserRoleInSkautis( absint( $_POST['changeSkautisUserRole'] ) );
						}
					}
				}
			}
		);
	}

	public function enqueueScriptsAndStyles( $hook_suffix ) {
		if ( ! str_ends_with( $hook_suffix, SKAUTISINTEGRATION_NAME . '_usersManagement' ) ) {
			return;
		}
		wp_enqueue_script( 'thickbox' );
		wp_enqueue_style( 'thickbox' );
		if ( is_network_admin() ) {
			add_action( 'admin_head', '_thickbox_path_admin_subfolder' );
		}

		wp_enqueue_style(
			SKAUTISINTEGRATION_NAME . '_datatables',
			SKAUTISINTEGRATION_URL . 'bundled/jquery.dataTables.min.css',
			array(),
			SKAUTISINTEGRATION_VERSION,
			'all'
		);

		wp_enqueue_script(
			SKAUTISINTEGRATION_NAME . '_datatables',
			SKAUTISINTEGRATION_URL . 'bundled/jquery.dataTables.min.js',
			array( 'jquery' ),
			SKAUTISINTEGRATION_VERSION,
			true
		);

		wp_enqueue_style(
			SKAUTISINTEGRATION_NAME,
			$this->adminDirUrl . 'css/skautis-admin.css',
			array(),
			SKAUTISINTEGRATION_VERSION,
			'all'
		);

		wp_enqueue_style(
			SKAUTISINTEGRATION_NAME . '_admin-users-management',
			$this->adminDirUrl . 'css/skautis-admin-users-management.css',
			array(),
			SKAUTISINTEGRATION_VERSION,
			'all'
		);

		wp_enqueue_script(
			SKAUTISINTEGRATION_NAME . '_admin-users-management',
			$this->adminDirUrl . 'js/skautis-admin-users-management.js',
			array( 'jquery', SKAUTISINTEGRATION_NAME . '_select2' ),
			SKAUTISINTEGRATION_VERSION,
			true
		);

		wp_localize_script(
			SKAUTISINTEGRATION_NAME . '_admin-users-management',
			'skautisIntegrationAdminUsersManagementLocalize',
			array(
				'datatablesFilesUrl' => SKAUTISINTEGRATION_URL . 'bundled/datatables-files',
				'searchNonceName'    => SKAUTISINTEGRATION_NAME . '_skautis_search_user_nonce',
				'searchNonceValue'   => wp_create_nonce( SKAUTISINTEGRATION_NAME . '_skautis_search_user' ),
			)
		);
	}

	public function setupUsersManagementPage() {
		add_submenu_page(
			SKAUTISINTEGRATION_NAME,
			__( 'Správa uživatelů', 'skautis-integration' ),
			__( 'Správa uživatelů', 'skautis-integration' ),
			Helpers::getSkautisManagerCapability(),
			SKAUTISINTEGRATION_NAME . '_usersManagement',
			array( $this, 'printChildUsers' )
		);
	}

	public function printChildUsers() {
		if ( ! Helpers::userIsSkautisManager() ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'skautis-integration' ) );
		}

		echo '
		<div class="wrap">
			<h1>' . esc_html__( 'Správa uživatelů', 'skautis-integration' ) . '</h1>
			<p>' . esc_html__( 'Zde si můžete propojit členy ze skautISu s uživateli ve WordPressu nebo je rovnou zaregistrovat (vyžaduje aktivovaný modul Registrace).', 'skautis-integration' ) . '</p>
		';

		if ( ! $this->skautisLogin->isUserLoggedInSkautis() ) {
			if ( $this->skautisGateway->isInitialized() ) {
				echo '<a href="' . esc_url( $this->wpLoginLogout->getLoginUrl( add_query_arg( 'noWpLogin', true, Helpers::getCurrentUrl() ) ) ) . '">' . esc_html__( 'Pro zobrazení obsahu je nutné se přihlásit do skautISu', 'skautis-integration' ) . '</a>';
				echo '
		</div>
			';
			} else {
				/* translators: 1: Start of link to the settings 2: End of link to the settings */
				printf( esc_html__( 'Vyberte v %1$snastavení%2$s pluginu typ prostředí skautISu', 'skautis-integration' ), '<a href="' . esc_url( admin_url( 'admin.php?page=' . SKAUTISINTEGRATION_NAME ) ) . '">', '</a>' );
				echo '
		</div>
			';
			}

			return;
		}

		$this->roleChanger->printChangeRolesForm();

		echo '<table class="skautisUserManagementTable"><thead style="font-weight: bold;"><tr>';
		echo '<th>' . esc_html__( 'Jméno a příjmení', 'skautis-integration' ) . '</th><th>' . esc_html__( 'Přezdívka', 'skautis-integration' ) . '</th><th>' . esc_html__( 'ID uživatele', 'skautis-integration' ) . '</th><th>' . esc_html__( 'Propojený uživatel', 'skautis-integration' ) . '</th><th>' . esc_html__( 'Propojení', 'skautis-integration' ) . '</th>';
		echo '</tr></thead ><tbody>';

		$usersData = $this->usersRepository->getConnectedWpUsers();

		$users = $this->usersRepository->getUsers()['users'];

		foreach ( $users as $user ) {
			if ( isset( $usersData[ $user->id ] ) ) {
				$homeUrl               = get_home_url( null, 'skautis/auth/' . Actions::DISCONNECT_ACTION );
				$nonce                 = wp_create_nonce( SKAUTISINTEGRATION_NAME . '_disconnectWpAccountFromSkautis' );
				$userEditLink          = get_edit_user_link( $usersData[ $user->id ]['id'] );
				$returnUrl             = add_query_arg( SKAUTISINTEGRATION_NAME . '_disconnectWpAccountFromSkautis', $nonce, Helpers::getCurrentUrl() );
				$returnUrl             = add_query_arg( 'user-edit_php', '', $returnUrl );
				$returnUrl             = add_query_arg( 'user_id', $usersData[ $user->id ]['id'], $returnUrl );
				$connectDisconnectLink = add_query_arg( 'ReturnUrl', rawurlencode( $returnUrl ), $homeUrl );
				echo '<tr style="background-color: #d1ffd1;">
	<td class="username">
		<span class="firstName">' . esc_html( $user->firstName ) . '</span> <span class="lastName">' . esc_html( $user->lastName ) . '</span>
	</td>
	<td>&nbsp;&nbsp;<span class="nickName">' . esc_html( $user->nickName ) . '</span></td><td>&nbsp;&nbsp;<span class="skautisUserId">' . esc_html( $user->id ) . '</span></td><td><a href="' . esc_url( $userEditLink ) . '">' . esc_html( $usersData[ $user->id ]['name'] ) . '</a></td><td><a href="' . esc_url( $connectDisconnectLink ) . '" class="button">' . esc_html__( 'Odpojit', 'skautis-integration' ) . '</a></td></tr>';
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
					foreach ( $this->usersRepository->getConnectableWpUsers() as $user ) {
						$userName = $user->data->display_name;
						if ( ! $userName ) {
							$userName = $user->data->user_login;
						}
						echo '
						<option value="' . absint( $user->ID ) . '">' . esc_html( $userName ) . '</option>
						';
					}
					?>
				</select>
				<a id="connectUserToSkautisModal_connectLink" class="button button-primary"
					href="<?php echo esc_url( $this->connectAndDisconnectWpAccount->getConnectWpUserToSkautisUrl() ); ?>"><?php esc_html_e( 'Potvrdit', 'skautis-integration' ); ?></a>
				<div>
					<em><?php esc_html_e( 'Je možné vybrat pouze ty uživatele, kteří ještě nemají propojený účet se skautISem.', 'skautis-integration' ); ?></em>
				</div>
				<?php
				if ( Services::getServicesContainer()['modulesManager']->isModuleActivated( Register::getId() ) ) {
					?>
					<hr/>
					<h3><?php esc_html_e( 'Vytvořit nový účet', 'skautis-integration' ); ?></h3>
					<p>
						<?php esc_html_e( 'Vytvoří nového uživatele ve WordPressu se jménem, příjmením, přezdívkou a emailem ze skautISu. Účet bude automaticky propojen se skautISem.', 'skautis-integration' ); ?>
					</p>
					<label>
						<span><?php esc_html_e( 'Vyberte úroveň nového uživatele', 'skautis-integration' ); ?></span>
						<select name="role" id="connectUserToSkautisModal_defaultRole">
							<?php wp_dropdown_roles( get_option( SKAUTISINTEGRATION_NAME . '_modules_register_defaultwpRole' ) ); ?>
						</select>
					</label>
					<p>
						<a id="connectUserToSkautisModal_registerLink" class="button button-primary"
							href="<?php echo esc_url( Services::getServicesContainer()[ Register::getId() ]->getWpRegister()->getManuallyRegisterWpUserUrl() ); ?>"><?php esc_html_e( 'Vytvořit nový účet', 'skautis-integration' ); ?></a>
					</p>
					<?php
				}
				?>
			</div>
		</div>
		<?php
	}

}
