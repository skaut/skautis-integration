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
use Skautis_Integration\Utils\Helpers;
use Skautis_Integration\Utils\Request_Parameter_Helpers;
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
	 * The hook suffix of the user management page, set when the page is added to the menu.
	 *
	 * @var string
	 */
	private static $hook_suffix = '';

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
		$this->init_hooks();
	}

	/**
	 * Intializes all hooks used by the object.
	 *
	 * The SkautIS role is changed by Role_Changer.
	 *
	 * @return void
	 */
	protected function init_hooks() {
		add_action( 'admin_menu', array( $this, 'setup_users_management_page' ), 10 );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_scripts_and_styles' ) );
	}

	/**
	 * Returns whether an admin page is the user management page.
	 *
	 * @param string $hook_suffix The admin page.
	 */
	public static function is_users_management_page( string $hook_suffix ): bool {
		return '' !== self::$hook_suffix && self::$hook_suffix === $hook_suffix;
	}

	/**
	 * Enqueues scripts and styles used for the user management table.
	 *
	 * @param string $hook_suffix The current admin page.
	 *
	 * @return void
	 */
	public static function enqueue_scripts_and_styles( $hook_suffix ) {
		if ( ! self::is_users_management_page( $hook_suffix ) ) {
			return;
		}

		Helpers::enqueue_style( 'admin-users-management', 'admin/css/skautis-admin-users-management.min.css' );
		Helpers::enqueue_script( 'admin-users-management', 'admin/js/skautis-admin-users-management.min.js', array( 'wp-a11y', 'wp-dom-ready', 'wp-i18n' ) );
		wp_set_script_translations( SKAUTIS_INTEGRATION_NAME . '_admin-users-management', 'skautis-integration' );
	}

	/**
	 * Registers the user management administration page with WordPress.
	 *
	 * @return void
	 */
	public function setup_users_management_page() {
		$hook_suffix = add_submenu_page(
			SKAUTIS_INTEGRATION_NAME,
			__( 'User management', 'skautis-integration' ),
			__( 'User management', 'skautis-integration' ),
			Helpers::get_skautis_manager_capability(),
			SKAUTIS_INTEGRATION_NAME . '_usersManagement',
			array( $this, 'print_child_users' )
		);
		if ( false !== $hook_suffix ) {
			self::$hook_suffix = $hook_suffix;
		}
	}

	/**
	 * Prints the user management administration page.
	 *
	 * @return void
	 */
	public function print_child_users() {
		if ( ! Helpers::user_is_skautis_manager() ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'skautis-integration' ) );
		}

		Settings::print_page_header( __( 'User management', 'skautis-integration' ) );
		echo '<div class="skautis-settings-body skautis-users-body">';

		if ( ! $this->skautis_gateway->is_initialized() ) {
			self::print_notice_card(
				__( 'SkautIS is not set up', 'skautis-integration' ),
				/* translators: 1: Start of link to the settings 2: End of link to the settings */
				sprintf( esc_html__( 'Select the SkautIS environment in the plugin %1$ssettings%2$s', 'skautis-integration' ), '<a href="' . esc_url( Settings::get_tab_url( 'general' ) ) . '">', '</a>' )
			);
		} elseif ( ! $this->skautis_login->is_user_logged_in_skautis() ) {
			self::print_notice_card(
				__( 'Log in to SkautIS', 'skautis-integration' ),
				esc_html__( 'The users are loaded from SkautIS according to your SkautIS role. Log in to SkautIS to see them.', 'skautis-integration' ),
				'<a class="button button-primary" href="' . esc_url( $this->wp_login_logout->get_login_url( add_query_arg( 'noWpLogin', true, Helpers::get_current_url() ) ) ) . '">' . esc_html__( 'Log in to SkautIS', 'skautis-integration' ) . '</a>'
			);
		} else {
			$this->role_changer->print_change_roles_form();
			$users = $this->users_repository->get_users()['users'];
			self::print_missing_permissions_notice( $this->users_repository->get_event_failed_methods() );
			self::print_users_table(
				$users,
				$this->users_repository->get_connected_wp_users(),
				$this->users_repository->get_connectable_wp_users(),
				$this->connect_and_disconnect_wp_account->get_connect_wp_user_to_skautis_url(),
				Services::is_registration_enabled() ? Services::get_register()->get_wp_register()->get_manually_register_wp_user_url() : null
			);
		}

		echo '</div>';
	}

	/**
	 * Prints a card with a message instead of the user table.
	 *
	 * @param string $title The title of the card.
	 * @param string $message The message, already escaped.
	 * @param string $action An optional button, already escaped.
	 *
	 * @return void
	 */
	private static function print_notice_card( string $title, string $message, string $action = '' ) {
		echo '<div class="skautis-settings-card"><h2>' . esc_html( $title ) . '</h2><p>' . $message . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by the callers.
		if ( '' !== $action ) {
			echo '<p>' . $action . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by the callers.
		}
		echo '</div>';
	}

	/**
	 * Returns the URL that disconnects a WordPress user from SkautIS and returns back to this page.
	 *
	 * @param int $wp_user_id The ID of the WordPress user.
	 */
	private static function get_disconnect_url( int $wp_user_id ): string {
		$return_url = add_query_arg( SKAUTIS_INTEGRATION_NAME . '_disconnectWpAccountFromSkautis', wp_create_nonce( SKAUTIS_INTEGRATION_NAME . '_disconnectWpAccountFromSkautis' ), Helpers::get_current_url() );
		$return_url = add_query_arg( 'user-edit_php', '', $return_url );
		$return_url = add_query_arg( 'user_id', $wp_user_id, $return_url );

		return add_query_arg( 'ReturnUrl', rawurlencode( $return_url ), get_home_url( null, 'skautis/auth/' . Actions::DISCONNECT_ACTION ) );
	}

	/**
	 * Prints a notice about the SkautIS methods that the App ID is not allowed to use.
	 *
	 * @param array<string> $methods The methods that could not be called.
	 *
	 * @return void
	 */
	private static function print_missing_permissions_notice( array $methods ) {
		if ( empty( $methods ) ) {
			return;
		}
		echo '<div class="notice notice-warning inline"><p>';
		echo esc_html__( 'Some people of the event could not be loaded from SkautIS. Your current SkautIS role may not have access to them, or the App ID of this site is not allowed to use these SkautIS methods:', 'skautis-integration' ) . ' <code>' . esc_html( implode( ', ', $methods ) ) . '</code>';
		echo '</p></div>';
	}

	/**
	 * Prints the table of SkautIS users with the panel for connecting them.
	 *
	 * @param array<\stdClass>                         $users The SkautIS users.
	 * @param array<int, array{id: int, name: string}> $connected_users The connected WordPress users, by the SkautIS user ID.
	 * @param array<\WP_User>                          $connectable_users The WordPress users without a SkautIS account.
	 * @param string                                   $connect_url The URL of the connecting form.
	 * @param string|null                              $register_url The URL of the registration form, or null if the registration is disabled.
	 *
	 * @return void
	 */
	public static function print_users_table( array $users, array $connected_users, array $connectable_users, string $connect_url, ?string $register_url ) {
		$search          = Request_Parameter_Helpers::get_string_variable( 'skautisSearchUsers' );
		$has_event_roles = false;
		foreach ( $users as $user ) {
			if ( isset( $user->eventRoles ) ) {
				$has_event_roles = true;
			}
		}
		$columns = $has_event_roles ? 5 : 4;
		?>
		<div class="skautis-settings-card skautis-users-card">
			<h2><?php esc_html_e( 'SkautIS users', 'skautis-integration' ); ?></h2>
			<p><?php esc_html_e( 'Connect SkautIS users with WordPress accounts, so that they can log in with SkautIS.', 'skautis-integration' ); ?></p>
			<form method="get" class="skautis-users-toolbar" role="search">
				<input type="hidden" name="page" value="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_usersManagement"/>
				<?php wp_nonce_field( SKAUTIS_INTEGRATION_NAME . '_skautis_search_user', SKAUTIS_INTEGRATION_NAME . '_skautis_search_user_nonce', false ); ?>
				<label for="skautis-users-search"><?php esc_html_e( 'Search users', 'skautis-integration' ); ?></label>
				<input type="search" id="skautis-users-search" name="skautisSearchUsers" value="<?php echo esc_attr( $search ); ?>" aria-describedby="skautis-users-search-description"/>
				<label for="skautis-users-filter" class="screen-reader-text"><?php esc_html_e( 'Show', 'skautis-integration' ); ?></label>
				<select id="skautis-users-filter" hidden>
					<option value=""><?php esc_html_e( 'All users', 'skautis-integration' ); ?></option>
					<option value="connected"><?php esc_html_e( 'Connected', 'skautis-integration' ); ?></option>
					<option value="not-connected"><?php esc_html_e( 'Not connected', 'skautis-integration' ); ?></option>
				</select>
				<button type="submit" class="button"><?php esc_html_e( 'Search in SkautIS', 'skautis-integration' ); ?></button>
				<?php
				if ( '' !== $search ) {
					echo '<a class="button-link" href="' . esc_url( remove_query_arg( array( 'skautisSearchUsers', SKAUTIS_INTEGRATION_NAME . '_skautis_search_user_nonce' ), Helpers::get_current_url() ) ) . '">' . esc_html__( 'Show all users', 'skautis-integration' ) . '</a>';
				}
				?>
				<p class="description" id="skautis-users-search-description"><?php esc_html_e( 'Typing filters the users below. To find a user who is not listed, search in SkautIS.', 'skautis-integration' ); ?></p>
			</form>
			<p class="skautis-users-count" aria-live="polite">
				<?php
				/* translators: %d: The number of users */
				echo esc_html( sprintf( _n( '%d user', '%d users', count( $users ), 'skautis-integration' ), count( $users ) ) );
				?>
			</p>
			<table class="wp-list-table widefat skautis-users-table">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Name', 'skautis-integration' ); ?></th>
						<?php
						if ( $has_event_roles ) {
							echo '<th scope="col">' . esc_html__( 'Role in the event', 'skautis-integration' ) . '</th>';
						}
						?>
						<th scope="col"><?php esc_html_e( 'SkautIS user ID', 'skautis-integration' ); ?></th>
						<th scope="col"><?php esc_html_e( 'WordPress account', 'skautis-integration' ); ?></th>
						<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'skautis-integration' ); ?></span></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $users as $user ) {
						self::print_user_row( $user, $connected_users[ $user->id ] ?? null, $has_event_roles );
					}
					?>
					<tr class="skautis-users-empty"<?php echo count( $users ) > 0 ? ' hidden' : ''; ?>>
						<td colspan="<?php echo esc_attr( strval( $columns ) ); ?>"><?php esc_html_e( 'No users found.', 'skautis-integration' ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
		self::print_connect_panel( $connectable_users, $connect_url, $register_url, $columns );
	}

	/**
	 * Prints one row of the user table.
	 *
	 * @param \stdClass                         $user The SkautIS user.
	 * @param array{id: int, name: string}|null $wp_user The connected WordPress user or null.
	 * @param bool                              $show_event_roles Whether to show the roles of the user in the event.
	 *
	 * @return void
	 */
	private static function print_user_row( \stdClass $user, ?array $wp_user, bool $show_event_roles ) {
		$name = trim( $user->firstName . ' ' . $user->lastName );
		?>
		<tr data-skautis-user-id="<?php echo esc_attr( strval( $user->id ) ); ?>" data-connected="<?php echo is_null( $wp_user ) ? '0' : '1'; ?>">
			<td>
				<strong class="skautis-users-name"><?php echo esc_html( $name ); ?></strong>
				<?php
				if ( '' !== $user->nickName ) {
					echo '<span class="skautis-users-nickname">' . esc_html( $user->nickName ) . '</span>';
				}
				?>
			</td>
			<?php
			if ( $show_event_roles ) {
				echo '<td>' . esc_html( implode( ', ', isset( $user->eventRoles ) && is_array( $user->eventRoles ) ? $user->eventRoles : array() ) ) . '</td>';
			}
			?>
			<td><?php echo esc_html( strval( $user->id ) ); ?></td>
			<td>
				<?php
				if ( is_null( $wp_user ) ) {
					echo '<span class="skautis-users-status">' . esc_html__( 'Not connected', 'skautis-integration' ) . '</span>';
				} else {
					echo '<span class="dashicons dashicons-yes-alt skautis-users-connected-icon" aria-hidden="true"></span> <a href="' . esc_url( get_edit_user_link( $wp_user['id'] ) ) . '">' . esc_html( $wp_user['name'] ) . '</a>';
				}
				?>
			</td>
			<td class="skautis-users-actions">
				<?php
				if ( is_null( $wp_user ) ) {
					echo '<button type="button" class="button skautis-users-connect" aria-expanded="false" aria-controls="skautis-users-connect-panel">' . esc_html__( 'Connect', 'skautis-integration' ) . '<span class="screen-reader-text"> ' . esc_html( $name ) . '</span></button>';
				} else {
					echo '<a class="button" href="' . esc_url( self::get_disconnect_url( $wp_user['id'] ) ) . '">' . esc_html__( 'Disconnect', 'skautis-integration' ) . '<span class="screen-reader-text"> ' . esc_html( $name ) . '</span></a>';
				}
				?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Prints the panel for connecting a SkautIS user. The script moves it below the row of the selected user.
	 *
	 * @param array<\WP_User> $connectable_users The WordPress users without a SkautIS account.
	 * @param string          $connect_url The URL of the connecting form.
	 * @param string|null     $register_url The URL of the registration form, or null if the registration is disabled.
	 * @param int             $columns The number of columns of the table.
	 *
	 * @return void
	 */
	private static function print_connect_panel( array $connectable_users, string $connect_url, ?string $register_url, int $columns ) {
		?>
		<table hidden>
			<tbody>
				<tr id="skautis-users-connect-panel" class="skautis-users-connect-row" hidden>
					<td colspan="<?php echo esc_attr( strval( $columns ) ); ?>">
						<div class="skautis-users-connect-panel" role="group" aria-labelledby="skautis-users-connect-title">
							<h3 id="skautis-users-connect-title" tabindex="-1"><?php esc_html_e( 'Connect to a WordPress account', 'skautis-integration' ); ?></h3>
							<div class="skautis-users-connect-options">
								<form method="post" action="<?php echo esc_url( $connect_url ); ?>">
									<?php wp_nonce_field( SKAUTIS_INTEGRATION_NAME . '_connect_user', SKAUTIS_INTEGRATION_NAME . '_connect_user_nonce', false ); ?>
									<input type="hidden" name="skautisUserId" class="skautis-users-connect-id" value=""/>
									<h4><?php esc_html_e( 'Existing account', 'skautis-integration' ); ?></h4>
									<p>
										<label for="skautis-users-connect-select"><?php esc_html_e( 'Select a user already registered in WordPress', 'skautis-integration' ); ?></label><br/>
										<select id="skautis-users-connect-select" name="wpUserId" required>
											<option value=""><?php esc_html_e( 'Select a user…', 'skautis-integration' ); ?></option>
											<?php
											foreach ( $connectable_users as $wp_user ) {
												$user_name = '' !== strval( $wp_user->display_name ) ? strval( $wp_user->display_name ) : strval( $wp_user->user_login );
												echo '<option value="' . absint( $wp_user->ID ) . '">' . esc_html( $user_name ) . '</option>';
											}
											?>
										</select>
									</p>
									<p class="description"><?php esc_html_e( 'Only users whose account is not connected to SkautIS yet can be selected.', 'skautis-integration' ); ?></p>
									<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Connect', 'skautis-integration' ); ?></button></p>
								</form>
								<?php
								if ( ! is_null( $register_url ) ) {
									?>
									<form method="post" action="<?php echo esc_url( $register_url ); ?>">
										<?php wp_nonce_field( SKAUTIS_INTEGRATION_NAME . '_register_user', SKAUTIS_INTEGRATION_NAME . '_register_user_nonce', false ); ?>
										<input type="hidden" name="skautisUserId" class="skautis-users-connect-id" value=""/>
										<h4><?php esc_html_e( 'Create a new account', 'skautis-integration' ); ?></h4>
										<p>
											<label for="skautis-users-connect-role"><?php esc_html_e( 'Select the role of the new user', 'skautis-integration' ); ?></label><br/>
											<select name="wpRole" id="skautis-users-connect-role">
												<?php wp_dropdown_roles( get_option( SKAUTIS_INTEGRATION_NAME . '_modules_register_defaultwpRole' ) ); ?>
											</select>
										</p>
										<p class="description"><?php esc_html_e( 'Creates a new WordPress user with the name, nickname and e-mail from SkautIS. The account will be connected to SkautIS automatically.', 'skautis-integration' ); ?></p>
										<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Create a new account', 'skautis-integration' ); ?></button></p>
									</form>
									<?php
								}
								?>
							</div>
							<p><button type="button" class="button skautis-users-connect-close"><?php esc_html_e( 'Cancel', 'skautis-integration' ); ?></button></p>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}
}
