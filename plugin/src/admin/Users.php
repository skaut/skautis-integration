<?php

declare( strict_types=1 );

namespace SkautisIntegration\Admin;

use SkautisIntegration\Auth\ConnectAndDisconnectWpAccount;
use SkautisIntegration\Auth\SkautisGateway;
use SkautisIntegration\Utils\Helpers;

final class Users {

	private $connectWpAccount;

	public function __construct( ConnectAndDisconnectWpAccount $connectWpAccount ) {
		$this->connectWpAccount = $connectWpAccount;
		$this->initHooks();
	}

	private function initHooks() {
		add_filter( 'manage_users_columns', [ $this, 'addColumnHeaderToUsersTable' ] );
		add_filter( 'manage_users_custom_column', [ $this, 'addColumnToUsersTable' ], 10, 3 );

		add_action( 'show_user_profile', [ $this, 'skautisUserIdField' ] );
		add_action( 'edit_user_profile', [ $this, 'skautisUserIdField' ] );
		add_action( 'personal_options_update', [ $this, 'manageSkautisUserIdField' ] );
		add_action( 'edit_user_profile_update', [ $this, 'manageSkautisUserIdField' ] );
	}

	public function addColumnHeaderToUsersTable( array $columns = [] ): array {
		$columns[ SKAUTISINTEGRATION_NAME ] = __( 'skautIS', 'skautis-integration' );

		return $columns;
	}

	public function addColumnToUsersTable( $value, string $columnName, int $userId ) {
		if ( $columnName == SKAUTISINTEGRATION_NAME ) {
			$envType = get_option( 'skautis_integration_appid_type' );
			if ( $envType === SkautisGateway::PROD_ENV ) {
				$userId = get_the_author_meta( 'skautisUserId_' . SkautisGateway::PROD_ENV, $userId );
			} else {
				$userId = get_the_author_meta( 'skautisUserId_' . SkautisGateway::TEST_ENV, $userId );
			}

			if ( $userId ) {
				return '✓';
			}

			return '–';
		}

		return $value;
	}

	public function skautisUserIdField( \WP_User $user ) {
		?>
		<h3><?php _e( "skautIS", "blank" ); ?></h3>
		<?php
		echo $this->connectWpAccount->getConnectAndDisconnectButton( $user->ID );
		do_action( SKAUTISINTEGRATION_NAME . '_userScreen_userIds_before' );
		?>
		<table class="form-table">
			<tr>
				<th><label for="skautisUserId_prod"><?php _e( "skautIS user ID", SKAUTISINTEGRATION_NAME ); ?></label>
				</th>
				<td>
					<input type="text" name="skautisUserId_prod" id="skautisUserId_prod" class="regular-text" <?php
					if ( ! Helpers::userIsSkautisManager() ) {
						echo 'disabled="disabled"';
					}
					?>
					       value="<?php echo esc_attr( get_the_author_meta( 'skautisUserId_prod', $user->ID ) ); ?>"/><br/>
				</td>
			</tr>
			<tr>
				<th><label
						for="skautisUserId_test"><?php _e( "skautIS user ID (testovací)", SKAUTISINTEGRATION_NAME ); ?></label>
				</th>
				<td>
					<input type="text" name="skautisUserId_test" id="skautisUserId_test" class="regular-text" <?php
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

	public function manageSkautisUserIdField( int $userId ): bool {
		$saved = false;
		if ( Helpers::userIsSkautisManager() ) {
			if ( isset( $_POST['skautisUserId_prod'] ) ) {
				$skautisUserId = absint( $_POST['skautisUserId_prod'] );
				if ( $skautisUserId == 0 ) {
					$skautisUserId = '';
				}
				update_user_meta( $userId, 'skautisUserId_prod', $skautisUserId );
				$saved = true;
			}
			if ( isset( $_POST['skautisUserId_test'] ) ) {
				$skautisUserId = absint( $_POST['skautisUserId_test'] );
				if ( $skautisUserId == 0 ) {
					$skautisUserId = '';
				}
				update_user_meta( $userId, 'skautisUserId_test', $skautisUserId );
				$saved = true;
			}
		}

		return $saved;
	}

}
