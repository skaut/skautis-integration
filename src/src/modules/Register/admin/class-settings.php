<?php

declare( strict_types=1 );

namespace SkautisIntegration\Modules\Register\Admin;

use SkautisIntegration\Rules\Rules_Init;
use SkautisIntegration\Rules\Rules_Manager;
use SkautisIntegration\Utils\Helpers;

final class Settings {

	private $rulesManager;

	public function __construct( Rules_Manager $rulesManager ) {
		$this->rulesManager = $rulesManager;
		$this->init_hooks();
	}

	private function init_hooks() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_menu', array( $this, 'setup_setting_page' ), 25 );
		add_action( 'admin_init', array( $this, 'setup_setting_fields' ) );
	}

	public function setup_setting_page() {
		add_submenu_page(
			SKAUTISINTEGRATION_NAME,
			__( 'Registrace', 'skautis-integration' ),
			__( 'Registrace', 'skautis-integration' ),
			Helpers::getSkautisManagerCapability(),
			SKAUTISINTEGRATION_NAME . '_modules_register',
			array( $this, 'print_setting_page' )
		);
	}

	public function print_setting_page() {
		if ( ! Helpers::userIsSkautisManager() ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'skautis-integration' ) );
		}

		settings_errors();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Nastavení registrace', 'skautis-integration' ); ?></h1>
			<form method="POST" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
				<?php
				settings_fields( SKAUTISINTEGRATION_NAME . '_modules_register' );
				do_settings_sections( SKAUTISINTEGRATION_NAME . '_modules_register' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public function setup_setting_fields() {
		add_settings_section(
			SKAUTISINTEGRATION_NAME . '_modules_register',
			'',
			function () {
				echo '';
			},
			SKAUTISINTEGRATION_NAME . '_modules_register'
		);

		add_settings_field(
			SKAUTISINTEGRATION_NAME . '_modules_register_defaultwpRole',
			__( 'Výchozí úroveň po registraci uživatele přes skautIS', 'skautis-integration' ),
			array( $this, 'fieldWpRole' ),
			SKAUTISINTEGRATION_NAME . '_modules_register',
			SKAUTISINTEGRATION_NAME . '_modules_register'
		);

		add_settings_field(
			SKAUTISINTEGRATION_NAME . '_modules_register_notifications',
			__( 'Po úspěšné registraci uživatele poslat emaily:', 'skautis-integration' ),
			array( $this, 'fieldNewUserNotifications' ),
			SKAUTISINTEGRATION_NAME . '_modules_register',
			SKAUTISINTEGRATION_NAME . '_modules_register'
		);

		add_settings_field(
			SKAUTISINTEGRATION_NAME . '_modules_register_rules',
			__( 'Pravidla registrace', 'skautis-integration' ),
			array( $this, 'fieldRules' ),
			SKAUTISINTEGRATION_NAME . '_modules_register',
			SKAUTISINTEGRATION_NAME . '_modules_register'
		);

		register_setting(
			SKAUTISINTEGRATION_NAME . '_modules_register',
			SKAUTISINTEGRATION_NAME . '_modules_register_defaultwpRole',
			array(
				'type'              => 'string',
				'show_in_rest'      => false,
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		register_setting(
			SKAUTISINTEGRATION_NAME . '_modules_register',
			SKAUTISINTEGRATION_NAME . '_modules_register_notifications',
			array(
				'type'              => 'string',
				'show_in_rest'      => false,
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		register_setting(
			SKAUTISINTEGRATION_NAME . '_modules_register',
			SKAUTISINTEGRATION_NAME . '_modules_register_rules',
			array(
				'type'         => 'string',
				'show_in_rest' => false,
			)
		);
	}

	public function fieldWpRole() {
		?>
		<select name="<?php echo esc_attr( SKAUTISINTEGRATION_NAME ); ?>_modules_register_defaultwpRole"
				id="skautis_integration_modules_register_rules_wpRole"><?php wp_dropdown_roles( get_option( SKAUTISINTEGRATION_NAME . '_modules_register_defaultwpRole' ) ); ?></select>
		<?php
	}

	public function fieldNewUserNotifications() {
		$notificationOption = get_option( SKAUTISINTEGRATION_NAME . '_modules_register_notifications', 'none' );
		?>
		<label>
			<input type="radio"
				name="<?php echo esc_attr( SKAUTISINTEGRATION_NAME ); ?>_modules_register_notifications"
				value="none"<?php checked( 'none' === $notificationOption ); ?> />
			<span><?php esc_html_e( 'Nikomu', 'skautis-integration' ); ?></span>
		</label>
		<br/>
		<label>
			<input type="radio"
				name="<?php echo esc_attr( SKAUTISINTEGRATION_NAME ); ?>_modules_register_notifications"
				value="admin"<?php checked( 'admin' === $notificationOption ); ?> />
			<span><?php esc_html_e( 'Administrátorovi (info o registraci nového uživatele)', 'skautis-integration' ); ?></span>
		</label>
		<br/>
		<label>
			<input type="radio"
				name="<?php echo esc_attr( SKAUTISINTEGRATION_NAME ); ?>_modules_register_notifications"
				value="user"<?php checked( 'user' === $notificationOption ); ?> />
			<span><?php esc_html_e( 'Uživateli (přístupové údaje)', 'skautis-integration' ); ?></span>
		</label>
		<br/>
		<label>
			<input type="radio"
				name="<?php echo esc_attr( SKAUTISINTEGRATION_NAME ); ?>_modules_register_notifications"
				value="both"<?php checked( 'both' === $notificationOption ); ?> />
			<span><?php esc_html_e( 'Administrátorovi i uživateli', 'skautis-integration' ); ?></span>
		</label>
		<?php
	}

	public function fieldRules() {
		?>
		<div>
			<em><?php esc_html_e( 'Nastavením omezíte registraci uživatelů pouze při splnění následujících pravidel.', 'skautis-integration' ); ?></em>
		</div>
		<div id="skautis_integration_modules_register_rulesNotSetHelp">
			<em><?php esc_html_e( 'Ponecháte-li prázdné - budou se moci přes skautIS registrovat všichni uživatelé. Jejich výchozí úroveň pak bude: ', 'skautis-integration' ); ?>
				<strong><?php echo esc_html( translate_user_role( ucfirst( get_option( SKAUTISINTEGRATION_NAME . '_modules_register_defaultwpRole', '' ) ) ) ); ?></strong></em>
		</div>
		<div><em><?php esc_html_e( 'Pravidla můžete přidávat v sekci', 'skautis-integration' ); ?>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . Rules_Init::RULES_TYPE_SLUG ) ); ?>"><?php esc_html_e( 'Správa pravidel', 'skautis-integration' ); ?></a>.</em>
		</div>
		<div id="skautis_integration_modules_register_rulesSetHelp">
			<em><strong><?php esc_html_e( 'Pravidla se vyhodnocují shora dolů.', 'skautis-integration' ); ?></strong> 
								<?php
									esc_html_e(
										'Jakmile je
			některé pravidlo splněno, další, po něm následující, se již nevyhodnocují. Proto udržujte pořadí pravidel
			takové, aby nahoře byly vždy specifičtější pravidla, která platí pro užší skupinu uživatelů.',
										'skautis-integration'
									);
								?>
			</em></div>
		<div id="repeater">
			<div data-repeater-list="<?php echo esc_attr( SKAUTISINTEGRATION_NAME ); ?>_modules_register_rules">
				<div data-repeater-item>

					<span class="dashicons dashicons-move handle" style="vertical-align: middle;"></span>

					<label for="<?php echo esc_attr( SKAUTISINTEGRATION_NAME ); ?>_modules_register_rule">
						<?php esc_html_e( 'Při splnění pravidla:', 'skautis-integration' ); ?>
					</label>
					<select name="rule" class="rule select2">
						<?php
						foreach ( (array) $this->rulesManager->getAllRules() as $rule ) {
							echo '<option value="' . esc_attr( $rule->ID ) . '">' . esc_html( $rule->post_title ) . '</option>';
						}
						?>
					</select>

					<label for="<?php echo esc_attr( SKAUTISINTEGRATION_NAME ); ?>_modules_register_role">
						<?php esc_html_e( 'Přiřadit uživateli úroveň:', 'skautis-integration' ); ?>
					</label>
					<select name="role" id="<?php echo esc_attr( SKAUTISINTEGRATION_NAME ); ?>_modules_register_role">
						<?php wp_dropdown_roles( get_option( SKAUTISINTEGRATION_NAME . '_modules_register_defaultwpRole' ) ); ?>
					</select>

					<input data-repeater-delete type="button"
						value="<?php esc_attr_e( 'Odstranit', 'skautis-integration' ); ?>"/>
				</div>
			</div>
			<input data-repeater-create type="button" value="<?php esc_attr_e( 'Přidat', 'skautis-integration' ); ?>"/>
		</div>
		<?php
	}

}
