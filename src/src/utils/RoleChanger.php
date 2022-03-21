<?php

declare( strict_types=1 );

namespace SkautisIntegration\Utils;

use SkautisIntegration\Auth\SkautisGateway;
use SkautisIntegration\Auth\Skautis_Login;

class RoleChanger {

	protected $skautisGateway;
	protected $skautisLogin;

	public function __construct( SkautisGateway $skautisGateway, Skautis_Login $skautisLogin ) {
		$this->skautisGateway = $skautisGateway;
		$this->skautisLogin   = $skautisLogin;
		$this->checkIfUserChangeSkautisRole();
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

	public function printChangeRolesForm() {
		$currentUserRoles = $this->skautisGateway->getSkautisInstance()->UserManagement->UserRoleAll(
			array(
				'ID_Login' => $this->skautisGateway->getSkautisInstance()->getUser()->getLoginId(),
				'ID_User'  => $this->skautisGateway->getSkautisInstance()->UserManagement->UserDetail()->ID,
				'IsActive' => true,
			)
		);
		$currentUserRole  = $this->skautisGateway->getSkautisInstance()->getUser()->getRoleId();

		echo '
<form method="post" action="' . esc_attr( Helpers::getCurrentUrl() ) . '" novalidate="novalidate">' .
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		wp_nonce_field( SKAUTISINTEGRATION_NAME . '_changeSkautisUserRole', '_wpnonce', true, false ) .
		'<table class="form-table">
<tbody>
<tr>
<th scope="row" style="width: 13ex;">
<label for="skautisRoleChanger">' . esc_html__( 'Moje role', 'skautis-integration' ) . '</label>
</th>
<td>
<select id="skautisRoleChanger" name="changeSkautisUserRole">';
		foreach ( (array) $currentUserRoles as $role ) {
			echo '<option value="' . esc_attr( $role->ID ) . '" ' . selected( $role->ID, $currentUserRole, false ) . '>' . esc_html( $role->DisplayName ) . '</option>';
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
