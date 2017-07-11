<?php

declare( strict_types=1 );

namespace SkautisIntegration\Modules\Visibility\Admin;

use SkautisIntegration\Utils\Helpers;

final class Settings {

	public function __construct() {
		$this->initHooks();
	}

	private function initHooks() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_menu', [ $this, 'setupSettingPage' ], 25 );
		add_action( 'admin_init', [ $this, 'setupSettingFields' ] );
	}

	public function setupSettingPage() {
		add_submenu_page(
			SKAUTISINTEGRATION_NAME,
			__( 'Viditelnost obsahu', 'skautis-integration' ),
			__( 'Viditelnost obsahu', 'skautis-integration' ),
			Helpers::getSkautisManagerCapability(),
			SKAUTISINTEGRATION_NAME . '_modules_visibility',
			[ $this, 'printSettingPage' ]
		);
	}

	public function printSettingPage() {
		if ( ! Helpers::userIsSkautisManager() ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		settings_errors();
		?>
		<div class="wrap">
			<h1><?php _e( 'Nastavení viditelnosti obsahu', 'skautis-integration' ); ?></h1>
			<form method="POST" action="<?php echo admin_url( 'options.php' ); ?>">
				<?php settings_fields( SKAUTISINTEGRATION_NAME . '_modules_visibility' );
				do_settings_sections( SKAUTISINTEGRATION_NAME . '_modules_visibility' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public function setupSettingFields() {
		add_settings_section(
			SKAUTISINTEGRATION_NAME . '_modules_visibility',
			'',
			function () {
				echo '';
			},
			SKAUTISINTEGRATION_NAME . '_modules_visibility'
		);

		add_settings_field(
			SKAUTISINTEGRATION_NAME . '_modules_visibility_postTypes',
			__( 'Vyberte typy obsahu', 'skautis-integration' ),
			[ $this, 'fieldPostTypes' ],
			SKAUTISINTEGRATION_NAME . '_modules_visibility',
			SKAUTISINTEGRATION_NAME . '_modules_visibility'
		);

		register_setting( SKAUTISINTEGRATION_NAME . '_modules_visibility', SKAUTISINTEGRATION_NAME . '_modules_visibility_postTypes', [
			'type'         => 'string',
			'show_in_rest' => false
		] );
	}

	public function fieldPostTypes() {
		$availablePostTypes = get_post_types( [
			'public' => true
		], 'objects' );
		$postTypes          = (array) get_option( SKAUTISINTEGRATION_NAME . '_modules_visibility_postTypes' );
		?>
		<select multiple="true" name="<?php echo SKAUTISINTEGRATION_NAME; ?>_modules_visibility_postTypes[]"
		        class="select2"
		        id="<?php echo SKAUTISINTEGRATION_NAME; ?>_modules_visibility_postTypes">
			<?php
			foreach ( $availablePostTypes as $postType ) {
				echo '<option value="' . $postType->name . '" ' . selected( true, in_array( $postType->name, $postTypes ), false ) . '>' . $postType->label . '</option>';
			}
			?>
		</select>
		<?php
	}

}
