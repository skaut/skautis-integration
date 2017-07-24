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
		add_action( 'admin_menu', [
			$this,
			'setupUsersManagementPage'
		], 10 );

		if ( ! empty( $_GET['page'] ) && $_GET['page'] == SKAUTISINTEGRATION_NAME . '_usersManagement' ) {
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueueScriptsAndStyles' ] );
		}
	}

	protected function checkIfUserChangeSkautisRole() {
		add_action( 'init', function () {
			if ( isset( $_POST['changeSkautisUserRole'], $_POST['_wpnonce'], $_POST['_wp_http_referer'] ) ) {
				if ( check_admin_referer( SKAUTISINTEGRATION_NAME . '_changeSkautisUserRole', '_wpnonce' ) ) {
					if ( $this->skautisLogin->isUserLoggedInSkautis() ) {
						$this->skautisLogin->changeUserRoleInSkautis( absint( $_POST['changeSkautisUserRole'] ) );
					}
				}
			}
		} );
	}

	public function enqueueScriptsAndStyles() {
		wp_enqueue_script( 'thickbox' );
		wp_enqueue_style( 'thickbox' );
		if ( is_network_admin() ) {
			add_action( 'admin_head', '_thickbox_path_admin_subfolder' );
		}

		wp_enqueue_style(
			'datatables',
			'https://cdn.datatables.net/v/dt/dt-1.10.15/r-2.1.1/datatables.min.css',
			[],
			'1.10.15',
			'all'
		);

		wp_enqueue_script(
			'datatables',
			'https://cdn.datatables.net/v/dt/dt-1.10.15/r-2.1.1/datatables.min.js',
			[ 'jquery' ],
			'1.10.15',
			true
		);

		wp_enqueue_style(
			SKAUTISINTEGRATION_NAME,
			$this->adminDirUrl . 'css/skautis-admin-users-management.css',
			[],
			SKAUTISINTEGRATION_VERSION,
			'all'
		);

		wp_enqueue_script(
			SKAUTISINTEGRATION_NAME,
			$this->adminDirUrl . 'js/skautis-admin-users-management.js',
			[ 'jquery', 'select2' ],
			SKAUTISINTEGRATION_VERSION,
			true
		);
	}

	public function setupUsersManagementPage() {
		add_submenu_page(
			SKAUTISINTEGRATION_NAME,
			__( 'Správa uživatelů', 'skautis-integration' ),
			__( 'Správa uživatelů', 'skautis-integration' ),
			Helpers::getSkautisManagerCapability(),
			SKAUTISINTEGRATION_NAME . '_usersManagement',
			[ $this, 'printChildUsers' ]
		);
	}

	public function printChildUsers() {
		if ( ! Helpers::userIsSkautisManager() ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		$result = '
		<div class="wrap">
			<h1>' . __( 'Podřízení členové', 'skautis-integration' ) . '</h1>
		';

		if ( ! $this->skautisLogin->isUserLoggedInSkautis() ) {

			if ( $this->skautisGateway->isInitialized() ) {
				$result .= '<a href="' . $this->wpLoginLogout->getLoginUrl( add_query_arg( 'noWpLogin', true, Helpers::getCurrentUrl() ) ) . '">' . __( 'Pro zobrazení obsahu je nutné se přihlásit do skautISu', 'skautis-integration' ) . '</a>';
				$result .= '
		</div>
			';
			} else {
				$result .= sprintf( __( 'Vyberte v <a href="%1$s">nastavení</a> pluginu typ prostředí skautISu', 'skautis-integration' ), admin_url( 'admin.php?page=' . SKAUTISINTEGRATION_NAME ) );
				$result .= '
		</div>
			';
			}


			echo $result;

			return;
		}

		$result .= $this->roleChanger->getChangeRolesForm();

		$result .= '<table class="skautisUserManagementTable"><thead style="font-weight: bold;"><tr>';
		$result .= '<th>' . __( 'Jméno a příjmení', 'skautis-integration' ) . '</th><th>' . __( 'Přezdívka', 'skautis-integration' ) . '</th><th>' . __( 'ID uživatele', 'skautis-integration' ) . '</th><th>' . __( 'Propojený uživatel', 'skautis-integration' ) . '</th><th>' . __( 'Propojení', 'skautis-integration' ) . '</th>';
		$result .= '</tr></thead ><tbody>';

		$usersData = $this->usersRepository->getConnectedWpUsers();

		$users = $this->usersRepository->getUsers()['users'];

		foreach ( $users as $user ) {
			$connected             = '';
			$trBg                  = '';
			$connectDisconnectLink = '';
			$homeUrl               = get_home_url( null, 'skautis/auth/' . Actions::DISCONNECT_ACTION );
			$nonce                 = wp_create_nonce( SKAUTISINTEGRATION_NAME . '_disconnectWpAccountFromSkautis' );
			if ( isset( $usersData[ $user->id ] ) ) {
				$userEditLink          = get_edit_user_link( $usersData[ $user->id ]['id'] );
				$trBg                  = 'background-color: #d1ffd1;';
				$connected             = '<a href="' . $userEditLink . '">' . $usersData[ $user->id ]['name'] . '</a>';
				$returnUrl             = add_query_arg( SKAUTISINTEGRATION_NAME . '_disconnectWpAccountFromSkautis', $nonce, Helpers::getCurrentUrl() );
				$returnUrl             = add_query_arg( 'user-edit_php', '', $returnUrl );
				$returnUrl             = add_query_arg( 'user_id', $usersData[ $user->id ]['id'], $returnUrl );
				$connectDisconnectLink = add_query_arg( 'ReturnUrl', urlencode( $returnUrl ), $homeUrl );
				$connectDisconnectLink = '<a href="' . esc_url( $connectDisconnectLink ) . '" class="button">' . __( 'Odpojit', 'skautis-integration' ) . '</a>';
			} else {
				$connectDisconnectLink = '<a href="#TB_inline?width=450&height=380&inlineId=connectUserToSkautisModal" class="button thickbox">' . __( 'Propojit', 'skautis-integration' ) . '</a>';
			}
			$result .= '<tr style="' . $trBg . '">
<td class="username">
	<span class="firstName">' . esc_html( $user->firstName ) . '</span> <span class="lastName">' . esc_html( $user->lastName ) . '</span>
</td>
<td>&nbsp;&nbsp;<span class="nickName">' . esc_html( $user->nickName ) . '</span></td><td>&nbsp;&nbsp;<span class="skautisUserId">' . esc_html( $user->id ) . '</span></td><td>' . $connected . '</td><td>' . $connectDisconnectLink . '</td></tr>';
		}
		$result .= '</tbody></table>';

		echo $result;

		?>
		</div>
		<div id="connectUserToSkautisModal" class="hidden">
			<div class="content">
				<h3><?php _e( 'Propojení uživatele', 'skautis-integration' ); ?> <span
						id="connectUserToSkautisModal_username"></span> <?php _e( 'se skautISem', 'skautis-integration' ); ?>
				</h3>
				<h4><?php _e( 'Vyberte uživatele již registrovaného ve WordPressu', 'skautis-integration' ); ?>:</h4>
				<select id="connectUserToSkautisModal_select">
					<option><?php _e( 'Vyberte uživatele...', 'skautis-integration' ); ?></option>
					<?php
					foreach ( $this->usersRepository->getConnectableWpUsers() as $user ) {
						echo '
						<option value="' . absint( $user->ID ) . '">' . esc_html( $user->data->display_name ) . '</option>
						';
					}
					?>
				</select>
				<a id="connectUserToSkautisModal_connectLink" class="button button-primary"
				   href="<?php echo $this->connectAndDisconnectWpAccount->getConnectWpUserToSkautisUrl(); ?>"><?php _e( 'Potvrdit', 'skautis-integration' ); ?></a>
				<div>
					<em><?php _e( 'Je možné vybrat pouze ty uživatele, kteří ještě nemají propojený účet se skautISem.', 'skautis-integration' ); ?></em>
				</div>
				<?php
				if ( Services::getServicesContainer()['modulesManager']->isModuleActivated( Register::getId() ) ) {
					?>
					<hr/>
					<h3><?php _e( 'Vytvořit nový účet', 'skautis-integration' ); ?></h3>
					<p>
						<?php _e( 'Vytvoří nového uživatele ve WordPressu se jménem, příjmením, přezdívkou a emailem ze skautISu. Účet bude automaticky propojen se skautISem.', 'skautis-integration' ); ?>
					</p>
					<label>
						<span><?php _e( 'Vyberte roli nového uživatele', 'skautis-integration' ); ?></span>
						<select name="role" id="connectUserToSkautisModal_defaultRole">
							<?php wp_dropdown_roles( get_option( SKAUTISINTEGRATION_NAME . '_modules_register_defaultwpRole' ) ); ?>
						</select>
					</label>
					<p>
						<a id="connectUserToSkautisModal_registerLink" class="button button-secondary"
						   href="<?php echo Services::getServicesContainer()[ Register::getId() ]->getWpRegister()->getManuallyRegisterWpUserUrl(); ?>"><?php _e( 'Vytvořit nový účet', 'skautis-integration' ); ?></a>
					</p>
					<?php
				}
				?>
			</div>
		</div>
		<?php
	}

}
