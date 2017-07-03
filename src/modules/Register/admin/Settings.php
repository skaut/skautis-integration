<?php

namespace SkautisIntegration\Modules\Register\Admin;

use SkautisIntegration\Rules\RulesInit;
use SkautisIntegration\Rules\RulesManager;
use SkautisIntegration\Utils\Helpers;

final class Settings {

	private $rulesManager;

	public function __construct( RulesManager $rulesManager ) {
		$this->rulesManager = $rulesManager;
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
			__( 'Registrace', 'skautis-integration' ),
			__( 'Registrace', 'skautis-integration' ),
			Helpers::getSkautisManagerCapability(),
			SKAUTISINTEGRATION_NAME . '_modules_register',
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
			<h1><?php _e( 'Nastavení registrace', 'skautis-integration' ); ?></h1>
			<form method="POST" action="<?php echo admin_url( 'options.php' ); ?>">
				<?php settings_fields( SKAUTISINTEGRATION_NAME . '_modules_register' );
				do_settings_sections( SKAUTISINTEGRATION_NAME . '_modules_register' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public function setupSettingFields() {
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
			[ $this, 'fieldWpRole' ],
			SKAUTISINTEGRATION_NAME . '_modules_register',
			SKAUTISINTEGRATION_NAME . '_modules_register'
		);

		add_settings_field(
			SKAUTISINTEGRATION_NAME . '_modules_register_emailNotificationsAfterNewUserRegister',
			__( 'Po úspěšné registraci uživatele poslat emaily:', 'skautis-integration' ),
			[ $this, 'fieldNewUserNotifications' ],
			SKAUTISINTEGRATION_NAME . '_modules_register',
			SKAUTISINTEGRATION_NAME . '_modules_register'
		);

		add_settings_field(
			SKAUTISINTEGRATION_NAME . '_modules_register_rules',
			__( 'Pravidla registrace', 'skautis-integration' ),
			[ $this, 'fieldRules' ],
			SKAUTISINTEGRATION_NAME . '_modules_register',
			SKAUTISINTEGRATION_NAME . '_modules_register'
		);

		register_setting( SKAUTISINTEGRATION_NAME . '_modules_register', SKAUTISINTEGRATION_NAME . '_modules_register_defaultwpRole', [
			'type'              => 'string',
			'show_in_rest'      => false,
			'sanitize_callback' => 'sanitize_text_field'
		] );
		register_setting( SKAUTISINTEGRATION_NAME . '_modules_register', SKAUTISINTEGRATION_NAME . '_modules_register_emailNotificationsAfterNewUserRegister', [
			'type'              => 'string',
			'show_in_rest'      => false,
			'sanitize_callback' => 'sanitize_text_field'
		] );
		register_setting( SKAUTISINTEGRATION_NAME . '_modules_register', SKAUTISINTEGRATION_NAME . '_modules_register_rules', [
			'type'         => 'string',
			'show_in_rest' => false
		] );
	}

	public function fieldWpRole() {
		?>
		<select name="<?php echo SKAUTISINTEGRATION_NAME; ?>_modules_register_defaultwpRole"
		        id="skautis_integration_modules_register_rules_wpRole"><?php wp_dropdown_roles( get_option( SKAUTISINTEGRATION_NAME . '_modules_register_defaultwpRole' ) ); ?></select>
		<?php
	}

	public function fieldNewUserNotifications() {
		$notificationOption = get_option( SKAUTISINTEGRATION_NAME . '_modules_register_emailNotificationsAfterNewUserRegister' );
		?>
		<label>
			<input type="radio"
			       name="<?php echo SKAUTISINTEGRATION_NAME; ?>_modules_register_emailNotificationsAfterNewUserRegister"
			       value="none"<?php checked( 'none' === $notificationOption ); ?> />
			<span><?php _e( 'Nikomu', 'skautis-integration' ); ?></span>
		</label>
		<br/>
		<label>
			<input type="radio"
			       name="<?php echo SKAUTISINTEGRATION_NAME; ?>_modules_register_emailNotificationsAfterNewUserRegister"
			       value="admin"<?php checked( 'admin' === $notificationOption ); ?> />
			<span><?php _e( 'Administrátorovi (info o registraci nového uživatele)', 'skautis-integration' ); ?></span>
		</label>
		<br/>
		<label>
			<input type="radio"
			       name="<?php echo SKAUTISINTEGRATION_NAME; ?>_modules_register_emailNotificationsAfterNewUserRegister"
			       value="user"<?php checked( 'user' === $notificationOption ); ?> />
			<span><?php _e( 'Uživateli (přístupové údaje)', 'skautis-integration' ); ?></span>
		</label>
		<br/>
		<label>
			<input type="radio"
			       name="<?php echo SKAUTISINTEGRATION_NAME; ?>_modules_register_emailNotificationsAfterNewUserRegister"
			       value="both"<?php checked( 'both' === $notificationOption ); ?> />
			<span><?php _e( 'Administrátorovi i uživateli', 'skautis-integration' ); ?></span>
		</label>
		<?php
	}

	public function fieldRules() {
		?>
		<div>
			<em><?php _e( 'Nastavením omezíte registraci uživatelů pouze při splnění následujících pravidel.', 'skautis-integration' ); ?></em>
		</div>
		<div id="skautis_integration_modules_register_rulesNotSetHelp">
			<em><?php _e( 'Ponecháte-li prázdné - budou se moci přes skautIS registrovat všichni uživatelé. Jejich výchozí úroveň pak bude: ', 'skautis-integration' ); ?>
				<strong><?php echo translate_user_role( ucfirst( esc_html( get_option( SKAUTISINTEGRATION_NAME . '_modules_register_defaultwpRole' ) ) ) ); ?></strong></em>
		</div>
		<div><em><?php _e( 'Pravidla můžete přidávat v sekci', 'skautis-integration' ); ?>
				<a href="<?php echo admin_url( 'edit.php?post_type=' . RulesInit::RULES_TYPE_SLUG ); ?>"><?php _e( 'Správa pravidel', 'skautis-integration' ); ?></a>.</em>
		</div>
		<div id="skautis_integration_modules_register_rulesSetHelp">
			<em><strong><?php _e( 'Pravidla se vyhodnocují shora dolů.', 'skautis-integration' ); ?></strong> <?php _e( 'Jakmile je
			některé pravidlo splněno, další, po něm následující, se již nevyhodnocují. Proto udržujte pořadí pravidel
			takové, aby nahoře byly vždy specifičtější pravidla, která platí pro užší skupinu uživatelů.', 'skautis-integration' ); ?>
			</em></div>
		<div id="repeater">
			<div data-repeater-list="<?php echo SKAUTISINTEGRATION_NAME; ?>_modules_register_rules">
				<div data-repeater-item>

					<span class="dashicons dashicons-move handle" style="vertical-align: middle;"></span>

					<label for="<?php echo SKAUTISINTEGRATION_NAME; ?>_modules_register_rule">
						<?php _e( 'Při splnění pravidla:', 'skautis-integration' ); ?>
					</label>
					<select name="rule" class="rule select2"
					        id="<?php echo SKAUTISINTEGRATION_NAME; ?>_modules_register_rule">
						<?php
						foreach ( (array) $this->rulesManager->getAllRules() as $rule ) {
							echo '<option value="' . $rule->ID . '">' . $rule->post_title . '</option>';
						}
						?>
					</select>

					<label for="<?php echo SKAUTISINTEGRATION_NAME; ?>_modules_register_role">
						<?php _e( 'Přiřadit uživateli úroveň:', 'skautis-integration' ); ?>
					</label>
					<select name="role" id="<?php echo SKAUTISINTEGRATION_NAME; ?>_modules_register_role">
						<?php wp_dropdown_roles( get_option( SKAUTISINTEGRATION_NAME . '_modules_register_defaultwpRole' ) ); ?>
					</select>

					<input data-repeater-delete type="button"
					       value="<?php _e( 'Odstranit', 'skautis-integration' ); ?>"/>
				</div>
			</div>
			<input data-repeater-create type="button" value="<?php _e( 'Přidat', 'skautis-integration' ); ?>"/>
		</div>
		<?php
	}

}
