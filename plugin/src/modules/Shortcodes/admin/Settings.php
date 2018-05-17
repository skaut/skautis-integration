<?php

declare( strict_types=1 );

namespace SkautisIntegration\Modules\Shortcodes\Admin;

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
			__( 'Shortcodes', 'skautis-integration' ),
			__( 'Shortcodes', 'skautis-integration' ),
			Helpers::getSkautisManagerCapability(),
			SKAUTISINTEGRATION_NAME . '_modules_shortcodes',
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
			<h1><?php _e( 'Nastavení shortcodes', 'skautis-integration' ); ?></h1>
			<form method="POST" action="<?php echo admin_url( 'options.php' ); ?>">
				<?php settings_fields( SKAUTISINTEGRATION_NAME . '_modules_shortcodes' );
				do_settings_sections( SKAUTISINTEGRATION_NAME . '_modules_shortcodes' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public function setupSettingFields() {
		add_settings_section(
			SKAUTISINTEGRATION_NAME . '_modules_shortcodes',
			'',
			function () {
				echo '';
			},
			SKAUTISINTEGRATION_NAME . '_modules_shortcodes'
		);

		add_settings_field(
			SKAUTISINTEGRATION_NAME . '_modules_shortcodes_visibilityMode',
			__( 'Výchozí způsob skrytí', 'skautis-integration' ),
			[ $this, 'fieldVisibilityMode' ],
			SKAUTISINTEGRATION_NAME . '_modules_shortcodes',
			SKAUTISINTEGRATION_NAME . '_modules_shortcodes'
		);

		register_setting( SKAUTISINTEGRATION_NAME . '_modules_shortcodes', SKAUTISINTEGRATION_NAME . '_modules_shortcodes_visibilityMode', [
			'type'         => 'string',
			'show_in_rest' => false
		] );
	}

	public function fieldVisibilityMode() {
		$visibilityMode = get_option( SKAUTISINTEGRATION_NAME . '_modules_shortcodes_visibilityMode', 'hide' );
		?>
		<label><input type="radio" name="<?php echo SKAUTISINTEGRATION_NAME; ?>_modules_shortcodes_visibilityMode"
		              value="hide" <?php checked( 'hide', $visibilityMode ); ?> /><span><?php _e( 'Úplně skrýt obsah', 'skautis-integration' ); ?></span></label>
		<br/>
		<label><input type="radio" name="<?php echo SKAUTISINTEGRATION_NAME; ?>_modules_shortcodes_visibilityMode"
		              value="showLogin" <?php checked( 'showLogin', $visibilityMode ); ?> /><span><?php _e( 'Zobrazit přihlášení', 'skautis-integration' ); ?></span></label>
		<p>
			<em><?php _e( 'Nastavení můžete změnit u jednotlivých typů obsahu dle potřeby.', 'skautis-integration' ); ?></em>
		</p>
		<?php
	}


}
