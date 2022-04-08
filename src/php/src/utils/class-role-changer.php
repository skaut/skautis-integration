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

class Role_Changer {

	protected $skautis_gateway;
	protected $skautis_login;

	/**
	 * Constructs the service and saves all dependencies.
	 */
	public function __construct( Skautis_Gateway $skautis_gateway, Skautis_Login $skautis_login ) {
		$this->skautis_gateway = $skautis_gateway;
		$this->skautis_login   = $skautis_login;
		$this->check_if_user_change_skautis_role();
	}

	/**
	 * On page load, changes the user's SkautIS role if requested by a POST variable.
	 *
	 * TODO: Find a more robust way to do this?
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
	 * Prints the SkautIS role changer.
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

		echo '
<form method="post" action="' . esc_attr( Helpers::get_current_url() ) . '" novalidate="novalidate">' .
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		wp_nonce_field( SKAUTIS_INTEGRATION_NAME . '_changeSkautisUserRole', '_wpnonce', true, false ) .
		'<table class="form-table">
<tbody>
<tr>
<th scope="row" style="width: 13ex;">
<label for="skautisRoleChanger">' . esc_html__( 'Moje role', 'skautis-integration' ) . '</label>
</th>
<td>
<select id="skautisRoleChanger" name="changeSkautisUserRole">';
		foreach ( (array) $current_user_roles as $role ) {
			echo '<option value="' . esc_attr( $role->ID ) . '" ' . selected( $role->ID, $current_user_role, false ) . '>' . esc_html( $role->DisplayName ) . '</option>';
		}
		echo '
</select>
<br/>
<em>' . esc_html__( 'Vybraná role ovlivní, kteří uživatelé se zobrazí v tabulce níže.', 'skautis-integration' ) . '</em>
</td>
</tr>
</tbody>
</table>
</form>
<script>
var timeout = 0;
if (!jQuery.fn.select2) {
	timeout = 500;
}
setTimeout(function() {
	(function ($) {
		"use strict";
		$("#skautisRoleChanger").select2().on("change.roleChanger", function () {
			$(this).closest("form").submit();
		});
	})(jQuery);
}, timeout);
</script>
';
	}

}
