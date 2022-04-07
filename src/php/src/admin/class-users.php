<?php
/**
 * Contains the Users class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Admin;

use Skautis_Integration\Auth\Connect_And_Disconnect_WP_Account;
use Skautis_Integration\Auth\Skautis_Gateway;
use Skautis_Integration\Utils\Helpers;

final class Users {

	private $connect_wp_account;

	/**
	 * Constructs the service and saves all dependencies.
	 */
	public function __construct( Connect_And_Disconnect_WP_Account $connect_wp_account ) {
		$this->connect_wp_account = $connect_wp_account;
		$this->init_hooks();
	}

	/**
	 * Intializes all hooks used by the object.
	 */
	private function init_hooks() {
		add_filter( 'manage_users_columns', array( $this, 'add_column_header_to_users_table' ) );
		add_filter( 'manage_users_custom_column', array( $this, 'add_column_to_users_table' ), 10, 3 );

		add_action( 'show_user_profile', array( $this, 'skautis_user_id_field' ) );
		add_action( 'edit_user_profile', array( $this, 'skautis_user_id_field' ) );
		add_action( 'personal_options_update', array( $this, 'manage_skautis_user_id_field' ) );
		add_action( 'edit_user_profile_update', array( $this, 'manage_skautis_user_id_field' ) );
	}

	public function add_column_header_to_users_table( array $columns = array() ): array {
		$columns[ SKAUTIS_INTEGRATION_NAME ] = __( 'skautIS', 'skautis-integration' );

		return $columns;
	}

	public function add_column_to_users_table( $value, string $column_name, int $user_id ) {
		if ( SKAUTIS_INTEGRATION_NAME === $column_name ) {
			$env_type = get_option( 'skautis_integration_appid_type' );
			if ( Skautis_Gateway::PROD_ENV === $env_type ) {
				$user_id = get_the_author_meta( 'skautisUserId_' . Skautis_Gateway::PROD_ENV, $user_id );
			} else {
				$user_id = get_the_author_meta( 'skautisUserId_' . Skautis_Gateway::TEST_ENV, $user_id );
			}

			if ( $user_id ) {
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
		$this->connect_wp_account->print_connect_and_disconnect_button( $user->ID );
		// TODO: Unused action?
		do_action( SKAUTIS_INTEGRATION_NAME . '_user_screen_user_ids_before' );
		?>
		<table class="form-table">
			<tr>
				<th><label for="skautisUserId_prod"><?php esc_html_e( 'skautIS user ID', 'skautis-integration' ); ?></label>
				</th>
				<td>
					<input type="text" name="skautisUserId_prod" id="skautisUserId_prod" class="regular-text" 
					<?php
					if ( ! Helpers::user_is_skautis_manager() ) {
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
					if ( ! Helpers::user_is_skautis_manager() ) {
						echo 'disabled="disabled"';
					}
					?>
						value="<?php echo esc_attr( get_the_author_meta( 'skautisUserId_test', $user->ID ) ); ?>"/><br/>
				</td>
			</tr>
		</table>
		<?php
		// TODO: Unused action?
		do_action( SKAUTIS_INTEGRATION_NAME . '_user_screen_user_ids_after' );
	}

	public function manage_skautis_user_id_field( int $user_id ): bool {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'update-user_' . $user_id ) ) {
			return false;
		}

		$saved = false;
		if ( Helpers::user_is_skautis_manager() ) {
			if ( isset( $_POST['skautisUserId_prod'] ) ) {
				$skautis_user_id = absint( $_POST['skautisUserId_prod'] );
				if ( 0 === $skautis_user_id ) {
					$skautis_user_id = '';
				}
				update_user_meta( $user_id, 'skautisUserId_prod', $skautis_user_id );
				$saved = true;
			}
			if ( isset( $_POST['skautisUserId_test'] ) ) {
				$skautis_user_id = absint( $_POST['skautisUserId_test'] );
				if ( 0 === $skautis_user_id ) {
					$skautis_user_id = '';
				}
				update_user_meta( $user_id, 'skautisUserId_test', $skautis_user_id );
				$saved = true;
			}
		}

		return $saved;
	}

}
