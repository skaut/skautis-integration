<?php
/**
 * Contains the Settings class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Modules\Register\Admin;

use Skautis_Integration\Rules\Rules_Init;
use Skautis_Integration\Rules\Rules_Manager;
use Skautis_Integration\Utils\Helpers;

final class Settings {

	private $rules_manager;

	public function __construct( Rules_Manager $rules_manager ) {
		$this->rules_manager = $rules_manager;
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
			SKAUTIS_INTEGRATION_NAME,
			__( 'Registrace', 'skautis-integration' ),
			__( 'Registrace', 'skautis-integration' ),
			Helpers::get_skautis_manager_capability(),
			SKAUTIS_INTEGRATION_NAME . '_modules_register',
			array( $this, 'print_setting_page' )
		);
	}

	public function print_setting_page() {
		if ( ! Helpers::user_is_skautis_manager() ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'skautis-integration' ) );
		}

		settings_errors();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Nastavení registrace', 'skautis-integration' ); ?></h1>
			<form method="POST" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
				<?php
				settings_fields( SKAUTIS_INTEGRATION_NAME . '_modules_register' );
				do_settings_sections( SKAUTIS_INTEGRATION_NAME . '_modules_register' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public function setup_setting_fields() {
		add_settings_section(
			SKAUTIS_INTEGRATION_NAME . '_modules_register',
			'',
			function () {
				echo '';
			},
			SKAUTIS_INTEGRATION_NAME . '_modules_register'
		);

		add_settings_field(
			SKAUTIS_INTEGRATION_NAME . '_modules_register_defaultwpRole',
			__( 'Výchozí úroveň po registraci uživatele přes skautIS', 'skautis-integration' ),
			array( $this, 'field_wp_role' ),
			SKAUTIS_INTEGRATION_NAME . '_modules_register',
			SKAUTIS_INTEGRATION_NAME . '_modules_register'
		);

		add_settings_field(
			SKAUTIS_INTEGRATION_NAME . '_modules_register_notifications',
			__( 'Po úspěšné registraci uživatele poslat emaily:', 'skautis-integration' ),
			array( $this, 'field_new_user_notifications' ),
			SKAUTIS_INTEGRATION_NAME . '_modules_register',
			SKAUTIS_INTEGRATION_NAME . '_modules_register'
		);

		add_settings_field(
			SKAUTIS_INTEGRATION_NAME . '_modules_register_rules',
			__( 'Pravidla registrace', 'skautis-integration' ),
			array( $this, 'field_rules' ),
			SKAUTIS_INTEGRATION_NAME . '_modules_register',
			SKAUTIS_INTEGRATION_NAME . '_modules_register'
		);

		register_setting(
			SKAUTIS_INTEGRATION_NAME . '_modules_register',
			SKAUTIS_INTEGRATION_NAME . '_modules_register_defaultwpRole',
			array(
				'type'              => 'string',
				'show_in_rest'      => false,
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		register_setting(
			SKAUTIS_INTEGRATION_NAME . '_modules_register',
			SKAUTIS_INTEGRATION_NAME . '_modules_register_notifications',
			array(
				'type'              => 'string',
				'show_in_rest'      => false,
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		register_setting(
			SKAUTIS_INTEGRATION_NAME . '_modules_register',
			SKAUTIS_INTEGRATION_NAME . '_modules_register_rules',
			array(
				'type'         => 'string',
				'show_in_rest' => false,
			)
		);
	}

	public function field_wp_role() {
		?>
		<select name="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_modules_register_defaultwpRole"
				id="skautis_integration_modules_register_rules_wpRole"><?php wp_dropdown_roles( get_option( SKAUTIS_INTEGRATION_NAME . '_modules_register_defaultwpRole' ) ); ?></select>
		<?php
	}

	public function field_new_user_notifications() {
		$notification_option = get_option( SKAUTIS_INTEGRATION_NAME . '_modules_register_notifications', 'none' );
		?>
		<label>
			<input type="radio"
				name="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_modules_register_notifications"
				value="none"<?php checked( 'none' === $notification_option ); ?> />
			<span><?php esc_html_e( 'Nikomu', 'skautis-integration' ); ?></span>
		</label>
		<br/>
		<label>
			<input type="radio"
				name="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_modules_register_notifications"
				value="admin"<?php checked( 'admin' === $notification_option ); ?> />
			<span><?php esc_html_e( 'Administrátorovi (info o registraci nového uživatele)', 'skautis-integration' ); ?></span>
		</label>
		<br/>
		<label>
			<input type="radio"
				name="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_modules_register_notifications"
				value="user"<?php checked( 'user' === $notification_option ); ?> />
			<span><?php esc_html_e( 'Uživateli (přístupové údaje)', 'skautis-integration' ); ?></span>
		</label>
		<br/>
		<label>
			<input type="radio"
				name="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_modules_register_notifications"
				value="both"<?php checked( 'both' === $notification_option ); ?> />
			<span><?php esc_html_e( 'Administrátorovi i uživateli', 'skautis-integration' ); ?></span>
		</label>
		<?php
	}

	public function field_rules() {
		?>
		<div>
			<em><?php esc_html_e( 'Nastavením omezíte registraci uživatelů pouze při splnění následujících pravidel.', 'skautis-integration' ); ?></em>
		</div>
		<div id="skautis_integration_modules_register_rulesNotSetHelp">
			<em><?php esc_html_e( 'Ponecháte-li prázdné - budou se moci přes skautIS registrovat všichni uživatelé. Jejich výchozí úroveň pak bude: ', 'skautis-integration' ); ?>
				<strong><?php echo esc_html( translate_user_role( ucfirst( get_option( SKAUTIS_INTEGRATION_NAME . '_modules_register_defaultwpRole', '' ) ) ) ); ?></strong></em>
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
			<div data-repeater-list="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_modules_register_rules">
				<div data-repeater-item>

					<span class="dashicons dashicons-move handle" style="vertical-align: middle;"></span>

					<label for="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_modules_register_rule">
						<?php esc_html_e( 'Při splnění pravidla:', 'skautis-integration' ); ?>
					</label>
					<select name="rule" class="rule select2">
						<?php
						foreach ( (array) $this->rules_manager->get_all_rules() as $rule ) {
							echo '<option value="' . esc_attr( $rule->ID ) . '">' . esc_html( $rule->post_title ) . '</option>';
						}
						?>
					</select>

					<label for="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_modules_register_role">
						<?php esc_html_e( 'Přiřadit uživateli úroveň:', 'skautis-integration' ); ?>
					</label>
					<select name="role" id="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_modules_register_role">
						<?php wp_dropdown_roles( get_option( SKAUTIS_INTEGRATION_NAME . '_modules_register_defaultwpRole' ) ); ?>
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
