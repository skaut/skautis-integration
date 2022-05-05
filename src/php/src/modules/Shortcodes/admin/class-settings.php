<?php
/**
 * Contains the Settings class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Modules\Shortcodes\Admin;

use Skautis_Integration\Utils\Helpers;

/**
 * Registers, handles and shows all settings for the Shortcodes module.
 */
final class Settings {

	/**
	 * Constructs the service and saves all dependencies.
	 */
	public function __construct() {
		self::init_hooks();
	}

	/**
	 * Intializes all hooks used by the object.
	 */
	private static function init_hooks() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_menu', array( self::class, 'setup_setting_page' ), 25 );
		add_action( 'admin_init', array( self::class, 'setup_setting_fields' ) );
	}

	/**
	 * Adds an admin settings page for the Shortcodes module.
	 */
	public static function setup_setting_page() {
		add_submenu_page(
			SKAUTIS_INTEGRATION_NAME,
			__( 'Shortcodes', 'skautis-integration' ),
			__( 'Shortcodes', 'skautis-integration' ),
			Helpers::get_skautis_manager_capability(),
			SKAUTIS_INTEGRATION_NAME . '_modules_shortcodes',
			array( self::class, 'print_setting_page' )
		);
	}

	/**
	 * Prints the admin settings page for the Shortcodes module.
	 */
	public static function print_setting_page() {
		if ( ! Helpers::user_is_skautis_manager() ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'skautis-integration' ) );
		}

		settings_errors();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Nastavení shortcodes', 'skautis-integration' ); ?></h1>
			<form method="POST" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
				<?php
				settings_fields( SKAUTIS_INTEGRATION_NAME . '_modules_shortcodes' );
				do_settings_sections( SKAUTIS_INTEGRATION_NAME . '_modules_shortcodes' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Adds Shortcodes module seetings to WordPress.
	 */
	public static function setup_setting_fields() {
		add_settings_section(
			SKAUTIS_INTEGRATION_NAME . '_modules_shortcodes',
			'',
			static function () {
				echo '';
			},
			SKAUTIS_INTEGRATION_NAME . '_modules_shortcodes'
		);

		add_settings_field(
			SKAUTIS_INTEGRATION_NAME . '_modules_shortcodes_visibilityMode',
			__( 'Výchozí způsob skrytí', 'skautis-integration' ),
			array( self::class, 'field_visibility_mode' ),
			SKAUTIS_INTEGRATION_NAME . '_modules_shortcodes',
			SKAUTIS_INTEGRATION_NAME . '_modules_shortcodes'
		);

		register_setting(
			SKAUTIS_INTEGRATION_NAME . '_modules_shortcodes',
			SKAUTIS_INTEGRATION_NAME . '_modules_shortcodes_visibilityMode',
			array(
				'type'         => 'string',
				'show_in_rest' => false,
			)
		);
	}

	/**
	 * Prints the settings field for choosing whether to show a login prompt or hide the content outright.
	 */
	public static function field_visibility_mode() {
		$visibility_mode = get_option( SKAUTIS_INTEGRATION_NAME . '_modules_shortcodes_visibilityMode', 'hide' );
		?>
		<label><input type="radio" name="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_modules_shortcodes_visibilityMode"
					value="hide" <?php checked( 'hide', $visibility_mode ); ?> /><span><?php esc_html_e( 'Úplně skrýt obsah', 'skautis-integration' ); ?></span></label>
		<br/>
		<label><input type="radio" name="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_modules_shortcodes_visibilityMode"
					value="showLogin" <?php checked( 'showLogin', $visibility_mode ); ?> /><span><?php esc_html_e( 'Zobrazit přihlášení', 'skautis-integration' ); ?></span></label>
		<p>
			<em><?php esc_html_e( 'Nastavení můžete změnit u jednotlivých typů obsahu dle potřeby.', 'skautis-integration' ); ?></em>
		</p>
		<?php
	}


}
