<?php
/**
 * Contains the Role_Changer class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Utils;

use Skautis_Integration\Auth\Skautis_Gateway;
use Skautis_Integration\Auth\Skautis_Login;

/**
 * Adds a skautIS role changer to the skautIS user management admin page.
 */
class Role_Changer {

	/**
	 * A link to the Skautis_Gateway service instance.
	 *
	 * TODO: Private?
	 *
	 * @var Skautis_Gateway
	 */
	protected $skautis_gateway;

	/**
	 * A link to the Skautis_Login service instance.
	 *
	 * TODO: Private?
	 *
	 * @var Skautis_Login
	 */
	protected $skautis_login;

	/**
	 * Constructs the service and saves all dependencies.
	 *
	 * @param Skautis_Gateway $skautis_gateway An injected Skautis_Gateway service instance.
	 * @param Skautis_Login   $skautis_login An injected Skautis_Login service instance.
	 */
	public function __construct( Skautis_Gateway $skautis_gateway, Skautis_Login $skautis_login ) {
		$this->skautis_gateway = $skautis_gateway;
		$this->skautis_login   = $skautis_login;
		$this->check_if_user_change_skautis_role();
	}

	/**
	 * On page load, changes the user's skautIS role if requested by a POST variable.
	 *
	 * TODO: Find a more robust way to do this?
	 * TODO: Duplicated in Users_Management.
	 *
	 * @return void
	 */
	protected function check_if_user_change_skautis_role() {
		add_action(
			'init',
			function () {
				$role = Request_Parameter_Helpers::post_int_variable( 'changeSkautisUserRole' );
				if ( -1 !== $role && isset( $_POST['_wpnonce'], $_POST['_wp_http_referer'] ) ) {
					if ( false !== check_admin_referer( SKAUTIS_INTEGRATION_NAME . '_changeSkautisUserRole', '_wpnonce' ) ) {
						if ( $this->skautis_login->is_user_logged_in_skautis() ) {
							$this->skautis_login->change_user_role_in_skautis( $role );
						}
					}
				}
			}
		);
	}

	/**
	 * Prints the skautIS role changer.
	 *
	 * @return void
	 */
	public function print_change_roles_form() {
		$current_user_roles = $this->skautis_gateway->get_skautis_instance()->UserManagement->UserRoleAll(
			array(
				'ID_Login' => $this->skautis_gateway->get_skautis_instance()->getUser()->getLoginId(),
				'ID_User'  => $this->skautis_gateway->get_skautis_instance()->UserManagement->UserDetail()->ID,
				'IsActive' => true,
			)
		);
		$current_user_role  = $this->skautis_gateway->get_skautis_instance()->getUser()->getRoleId();

		?>
		<div class="skautis-settings-card">
			<h2><?php esc_html_e( 'My skautIS role', 'skautis-integration' ); ?></h2>
			<form method="post" action="<?php echo esc_url( Helpers::get_current_url() ); ?>" class="skautis-role-changer">
				<?php wp_nonce_field( SKAUTIS_INTEGRATION_NAME . '_changeSkautisUserRole', '_wpnonce' ); ?>
				<label for="skautisRoleChanger" class="screen-reader-text"><?php esc_html_e( 'My role', 'skautis-integration' ); ?></label>
				<select id="skautisRoleChanger" name="changeSkautisUserRole" aria-describedby="skautisRoleChanger-description">
					<?php
					foreach ( (array) $current_user_roles as $role ) {
						echo '<option value="' . esc_attr( $role->ID ) . '" ' . selected( $role->ID, $current_user_role, false ) . '>' . esc_html( $role->DisplayName ) . '</option>';
					}
					?>
				</select>
				<button type="submit" class="button"><?php esc_html_e( 'Change role', 'skautis-integration' ); ?></button>
				<p class="description" id="skautisRoleChanger-description"><?php esc_html_e( 'The selected role determines which users are shown in the table below.', 'skautis-integration' ); ?></p>
			</form>
		</div>
		<?php
	}
}
