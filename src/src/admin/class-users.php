<?php

declare( strict_types=1 );

namespace SkautisIntegration\Admin;

use SkautisIntegration\Auth\Connect_And_Disconnect_WP_Account;
use SkautisIntegration\Auth\Skautis_Gateway;
use SkautisIntegration\Utils\Helpers;

final class Users {

	private $connectWpAccount;

	public function __construct( Connect_And_Disconnect_WP_Account $connectWpAccount ) {
		$this->connectWpAccount = $connectWpAccount;
		$this->init_hooks();
	}

	private function init_hooks() {
		add_filter( 'manage_users_columns', array( $this, 'add_column_header_to_users_table' ) );
		add_filter( 'manage_users_custom_column', array( $this, 'add_column_to_users_table' ), 10, 3 );

		add_action( 'show_user_profile', array( $this, 'skautis_user_id_field' ) );
		add_action( 'edit_user_profile', array( $this, 'skautis_user_id_field' ) );
		add_action( 'personal_options_update', array( $this, 'manage_skautis_user_id_field' ) );
		add_action( 'edit_user_profile_update', array( $this, 'manage_skautis_user_id_field' ) );
	}

	public function add_column_header_to_users_table( array $columns = array() ): array {
		$columns[ SKAUTISINTEGRATION_NAME ] = __( 'skautIS', 'skautis-integration' );

		return $columns;
	}

	public function add_column_to_users_table( $value, string $columnName, int $userId ) {
		if ( SKAUTISINTEGRATION_NAME === $columnName ) {
			$envType = get_option( 'skautis_integration_appid_type' );
			if ( Skautis_Gateway::PROD_ENV === $envType ) {
				$userId = get_the_author_meta( 'skautisUserId_' . Skautis_Gateway::PROD_ENV, $userId );
			} else {
				$userId = get_the_author_meta( 'skautisUserId_' . Skautis_Gateway::TEST_ENV, $userId );
			}

			if ( $userId ) {
				return '✓';
			}

			return '–';
		}

		return $value;
	}

	public function skautis_user_id_field( \WP_User $user ) {
		?>
		<h3><?php esc_html_e( 'skautIS', 'skautis-integration' ); ?></h3>
		<?php
		$this->connectWpAccount->print_connect_and_disconnect_button( $user->ID );
		do_action( SKAUTISINTEGRATION_NAME . '_userScreen_userIds_before' );
		?>
		<table class="form-table">
			<tr>
				<th><label for="skautisUserId_prod"><?php esc_html_e( 'skautIS user ID', 'skautis-integration' ); ?></label>
				</th>
				<td>
					<input type="text" name="skautisUserId_prod" id="skautisUserId_prod" class="regular-text" 
					<?php
					if ( ! Helpers::userIsSkautisManager() ) {
						echo 'disabled="disabled"';
					}
					?>
						value="<?php echo esc_attr( get_the_author_meta( 'skautisUserId_prod', $user->ID ) ); ?>"/><br/>
				</td>
			</tr>
			<tr>
				<th><label
						for="skautisUserId_test"><?php esc_html_e( 'skautIS user ID (testovací)', 'skautis-integration' ); ?></label>
				</th>
				<td>
					<input type="text" name="skautisUserId_test" id="skautisUserId_test" class="regular-text" 
					<?php
					if ( ! Helpers::userIsSkautisManager() ) {
						echo 'disabled="disabled"';
					}
					?>
						value="<?php echo esc_attr( get_the_author_meta( 'skautisUserId_test', $user->ID ) ); ?>"/><br/>
				</td>
			</tr>
		</table>
		<?php
		do_action( SKAUTISINTEGRATION_NAME . '_userScreen_userIds_after' );
	}

	public function manage_skautis_user_id_field( int $userId ): bool {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'update-user_' . $user_id ) ) {
			return false;
		}

		$saved = false;
		if ( Helpers::userIsSkautisManager() ) {
			if ( isset( $_POST['skautisUserId_prod'] ) ) {
				$skautisUserId = absint( $_POST['skautisUserId_prod'] );
				if ( 0 === $skautisUserId ) {
					$skautisUserId = '';
				}
				update_user_meta( $userId, 'skautisUserId_prod', $skautisUserId );
				$saved = true;
			}
			if ( isset( $_POST['skautisUserId_test'] ) ) {
				$skautisUserId = absint( $_POST['skautisUserId_test'] );
				if ( 0 === $skautisUserId ) {
					$skautisUserId = '';
				}
				update_user_meta( $userId, 'skautisUserId_test', $skautisUserId );
				$saved = true;
			}
		}

		return $saved;
	}

}
