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

/**
 * Registers, handles and shows all settings for the Register module.
 *
 * @phan-constructor-used-for-side-effects
 */
final class Settings {

	/**
	 * A link to the Rules_Manager service instance.
	 *
	 * @var Rules_Manager
	 */
	private $rules_manager;

	/**
	 * Constructs the service and saves all dependencies.
	 *
	 * @param Rules_Manager $rules_manager An injected Rules_Manager service instance.
	 */
	public function __construct( Rules_Manager $rules_manager ) {
		$this->rules_manager = $rules_manager;
		$this->init_hooks();
	}

	/**
	 * Intializes all hooks used by the object.
	 *
	 * @return void
	 */
	private function init_hooks() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_menu', array( self::class, 'setup_setting_page' ), 25 );
		add_action( 'admin_init', array( $this, 'setup_setting_fields' ) );
	}

	/**
	 * Adds an admin settings page for the Register module.
	 *
	 * @return void
	 */
	public static function setup_setting_page() {
		add_submenu_page(
			SKAUTIS_INTEGRATION_NAME,
			__( 'Registration', 'skautis-integration' ),
			__( 'Registration', 'skautis-integration' ),
			Helpers::get_skautis_manager_capability(),
			SKAUTIS_INTEGRATION_NAME . '_modules_register',
			array( self::class, 'print_setting_page' )
		);
	}

	/**
	 * Prints the admin settings page for the Register module.
	 *
	 * @return void
	 */
	public static function print_setting_page() {
		if ( ! Helpers::user_is_skautis_manager() ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'skautis-integration' ) );
		}

		settings_errors();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Registration settings', 'skautis-integration' ); ?></h1>
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

	/**
	 * Adds Register module seetings to WordPress.
	 *
	 * @return void
	 */
	public function setup_setting_fields() {
		add_settings_section(
			SKAUTIS_INTEGRATION_NAME . '_modules_register',
			'',
			static function () {
				echo '';
			},
			SKAUTIS_INTEGRATION_NAME . '_modules_register'
		);

		add_settings_field(
			SKAUTIS_INTEGRATION_NAME . '_modules_register_defaultwpRole',
			__( 'Default role after registration with skautIS', 'skautis-integration' ),
			array( self::class, 'field_wp_role' ),
			SKAUTIS_INTEGRATION_NAME . '_modules_register',
			SKAUTIS_INTEGRATION_NAME . '_modules_register',
			array( 'label_for' => 'skautis_integration_modules_register_rules_wpRole' )
		);

		add_settings_field(
			SKAUTIS_INTEGRATION_NAME . '_modules_register_notifications',
			__( 'After a successful registration, send e-mails to:', 'skautis-integration' ),
			array( self::class, 'field_new_user_notifications' ),
			SKAUTIS_INTEGRATION_NAME . '_modules_register',
			SKAUTIS_INTEGRATION_NAME . '_modules_register'
		);

		add_settings_field(
			SKAUTIS_INTEGRATION_NAME . '_modules_register_rules',
			__( 'Registration rules', 'skautis-integration' ),
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

	/**
	 * Prints the settings field for choosing the default WordPress role for newly registered users
	 *
	 * @return void
	 */
	public static function field_wp_role() {
		?>
		<select name="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_modules_register_defaultwpRole"
				id="skautis_integration_modules_register_rules_wpRole"><?php wp_dropdown_roles( get_option( SKAUTIS_INTEGRATION_NAME . '_modules_register_defaultwpRole' ) ); ?></select>
		<?php
	}

	/**
	 * Prints the settings field for choosing whom to send an e-mail upon sucessfull user registration.
	 *
	 * @return void
	 */
	public static function field_new_user_notifications() {
		$notification_option = get_option( SKAUTIS_INTEGRATION_NAME . '_modules_register_notifications', 'none' );
		?>
		<label>
			<input type="radio"
				name="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_modules_register_notifications"
				value="none"<?php checked( 'none' === $notification_option ); ?> />
			<span><?php esc_html_e( 'Nobody', 'skautis-integration' ); ?></span>
		</label>
		<br/>
		<label>
			<input type="radio"
				name="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_modules_register_notifications"
				value="admin"<?php checked( 'admin' === $notification_option ); ?> />
			<span><?php esc_html_e( 'The administrator (information about the new user)', 'skautis-integration' ); ?></span>
		</label>
		<br/>
		<label>
			<input type="radio"
				name="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_modules_register_notifications"
				value="user"<?php checked( 'user' === $notification_option ); ?> />
			<span><?php esc_html_e( 'The user (login details)', 'skautis-integration' ); ?></span>
		</label>
		<br/>
		<label>
			<input type="radio"
				name="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_modules_register_notifications"
				value="both"<?php checked( 'both' === $notification_option ); ?> />
			<span><?php esc_html_e( 'Both the administrator and the user', 'skautis-integration' ); ?></span>
		</label>
		<?php
	}

	/**
	 * Prints the settings field for selecting user registration rules.
	 *
	 * @return void
	 */
	public function field_rules() {
		?>
		<div>
			<em><?php esc_html_e( 'Only users who pass the following rules will be able to register.', 'skautis-integration' ); ?></em>
		</div>
		<div id="skautis_integration_modules_register_rulesNotSetHelp">
			<em><?php esc_html_e( 'If left empty, all users will be able to register with skautIS. Their default role will be: ', 'skautis-integration' ); ?>
				<strong><?php echo esc_html( translate_user_role( ucfirst( get_option( SKAUTIS_INTEGRATION_NAME . '_modules_register_defaultwpRole', '' ) ) ) ); ?></strong></em>
		</div>
		<div><em><?php esc_html_e( 'You can add rules in the section', 'skautis-integration' ); ?>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . Rules_Init::RULES_TYPE_SLUG ) ); ?>"><?php esc_html_e( 'Manage rules', 'skautis-integration' ); ?></a>.</em>
		</div>
		<div id="skautis_integration_modules_register_rulesSetHelp">
			<em><strong><?php esc_html_e( 'The rules are evaluated from top to bottom.', 'skautis-integration' ); ?></strong> 
								<?php
									esc_html_e(
										'Once a rule is passed, the following rules are not evaluated. Therefore, keep the more specific rules, which apply to a narrower group of users, at the top.',
										'skautis-integration'
									);
								?>
			</em></div>
		<div id="repeater">
			<div data-repeater-list="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_modules_register_rules">
				<div data-repeater-item>

					<span class="dashicons dashicons-move handle" style="vertical-align: middle;"></span>

					<label for="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_modules_register_rule">
						<?php esc_html_e( 'If the rule is passed:', 'skautis-integration' ); ?>
					</label>
					<select name="rule" class="rule select2">
						<?php
						foreach ( $this->rules_manager->get_all_rules() as $rule ) {
							echo '<option value="' . esc_attr( strval( $rule->ID ) ) . '">' . esc_html( $rule->post_title ) . '</option>';
						}
						?>
					</select>

					<label for="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_modules_register_role">
						<?php esc_html_e( 'Assign the user the role:', 'skautis-integration' ); ?>
					</label>
					<select name="role" id="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_modules_register_role">
						<?php wp_dropdown_roles( get_option( SKAUTIS_INTEGRATION_NAME . '_modules_register_defaultwpRole' ) ); ?>
					</select>

					<input data-repeater-delete type="button"
						value="<?php esc_attr_e( 'Remove', 'skautis-integration' ); ?>"/>
				</div>
			</div>
			<input data-repeater-create type="button" value="<?php esc_attr_e( 'Add', 'skautis-integration' ); ?>"/>
		</div>
		<?php
	}
}
