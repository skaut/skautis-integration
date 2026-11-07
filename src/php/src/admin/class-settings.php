<?php
/**
 * Contains the Settings class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Admin;

use PHPMD\Attribute\SuppressWarnings;
use PHPMD\Rule\Design\ExitExpression;
use Skautis_Integration\Auth\Skautis_Gateway;
use Skautis_Integration\Utils\Helpers;
use Skautis_Integration\Services\Services;

/**
 * Registers, handles and shows plugin settings.
 */
final class Settings {

	const HELP_PAGE_URL = 'https://napoveda.skaut.cz/skautis/skautis-integration';

	/**
	 * A link to the Skautis_Gateway service instance.
	 *
	 * @var Skautis_Gateway
	 *
	 * @phpstan-ignore property.onlyWritten
	 */
	private $skautis_gateway;

	/**
	 * Constructs the service and saves all dependencies.
	 *
	 * @param Skautis_Gateway $skautis_gateway An injected Skautis_Gateway service instance.
	 */
	public function __construct( Skautis_Gateway $skautis_gateway ) {
		$this->skautis_gateway = $skautis_gateway;
		$this->init_hooks();
	}

	/**
	 * Intializes all hooks used by the object.
	 *
	 * @return void
	 */
	private function init_hooks() {
		add_filter(
			'plugin_action_links_' . SKAUTIS_INTEGRATION_PLUGIN_BASENAME,
			array(
				self::class,
				'add_settings_link_to_plugins_table',
			)
		);
		add_filter(
			'plugin_action_links_' . SKAUTIS_INTEGRATION_PLUGIN_BASENAME,
			array(
				self::class,
				'add_help_link_to_plugins_table',
			)
		);

		add_action( 'admin_menu', array( $this, 'setup_setting_page' ), 5 );
		// The page no longer exists, so WordPress denies the access before admin_init.
		add_action( 'admin_page_access_denied', array( self::class, 'redirect_old_modules_page' ) );
		add_action( 'admin_init', array( $this, 'setup_setting_fields' ) );
		add_action( 'admin_init', array( $this, 'setup_login_fields' ) );

		// The notices are translated, so they must not be prepared before the `init` action.
		add_action( 'admin_init', array( self::class, 'check_if_app_id_is_set_and_show_notices' ) );
	}

	/**
	 * Shows a notice in the administration if the app id is not set.
	 *
	 * @return void
	 */
	public static function check_if_app_id_is_set_and_show_notices() {
		// Only the users who can change the settings can act on the notices.
		if ( ! Helpers::user_is_skautis_manager() ) {
			return;
		}

		$env_type = get_option( 'skautis_integration_appid_type' );
		if ( Skautis_Gateway::PROD_ENV === $env_type ) {
			if ( '' === strval( get_option( 'skautis_integration_appid_prod', '' ) ) ) {
				/* translators: 1: Start of a link to the settings 2: End of the link to the settings */
				Helpers::show_admin_notice( sprintf( __( 'Enter the App ID of the production version of skautIS in the plugin %1$ssettings%2$s', 'skautis-integration' ), '<a href="' . esc_url( admin_url( 'admin.php?page=' . SKAUTIS_INTEGRATION_NAME ) ) . '">', '</a>' ), 'warning', 'toplevel-page-' . SKAUTIS_INTEGRATION_NAME );
			}
		} elseif ( Skautis_Gateway::TEST_ENV === $env_type ) {
			if ( '' === strval( get_option( 'skautis_integration_appid_test', '' ) ) ) {
				/* translators: 1: Start of a link to the settings 2: End of the link to the settings */
				Helpers::show_admin_notice( sprintf( __( 'Enter the App ID of the test version of skautIS in the plugin %1$ssettings%2$s', 'skautis-integration' ), '<a href="' . esc_url( admin_url( 'admin.php?page=' . SKAUTIS_INTEGRATION_NAME ) ) . '">', '</a>' ), 'warning', 'toplevel-page-' . SKAUTIS_INTEGRATION_NAME );
			}
		} else {
				/* translators: 1: Start of a link to the settings 2: End of the link to the settings */
			Helpers::show_admin_notice( sprintf( __( 'Select the skautIS environment in the plugin %1$ssettings%2$s', 'skautis-integration' ), '<a href="' . esc_url( admin_url( 'admin.php?page=' . SKAUTIS_INTEGRATION_NAME ) ) . '">', '</a>' ), 'warning', 'toplevel-page-' . SKAUTIS_INTEGRATION_NAME );
		}
	}

	/**
	 * Adds a link to the plugin settings to the plugin management table.
	 *
	 * @param array<string, string> $links A list of links already present for the plugin.
	 *
	 * @return array<string, string> The updated list.
	 */
	public static function add_settings_link_to_plugins_table( array $links = array() ): array {
		$mylinks = array(
			'settings' => '<a href="' . esc_url( admin_url( 'admin.php?page=' . SKAUTIS_INTEGRATION_NAME ) ) . '">' . esc_html__( 'Settings', 'skautis-integration' ) . '</a>',
		);

		return array_merge( $links, $mylinks );
	}

	/**
	 * Adds a link to the plugin help to the plugin management table.
	 *
	 * @param array<string, string> $links A list of links already present for the plugin.
	 *
	 * @return array<string, string> The updated list.
	 */
	public static function add_help_link_to_plugins_table( array $links = array() ): array {
		$mylinks = array(
			'help' => '<a href="' . esc_url( self::HELP_PAGE_URL ) . '" target="_blank">' . esc_html__( 'Help', 'skautis-integration' ) . '</a>',
		);

		return array_merge( $links, $mylinks );
	}

	/**
	 * Returns the menu icon as an SVG data URI.
	 *
	 * WordPress recolors SVG menu icons to match the admin color scheme of the user.
	 */
	private static function get_menu_icon(): string {
		$svg = file_get_contents( __DIR__ . '/public/img/lilie.svg' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- A local file.
		if ( false === $svg ) {
			return 'dashicons-admin-generic';
		}

		return 'data:image/svg+xml;base64,' . base64_encode( $svg ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- The format required by WordPress for menu icons.
	}

	/**
	 * Adds the settings pages to the administration.
	 *
	 * @return void
	 */
	public function setup_setting_page() {
		add_menu_page(
			__( 'General', 'skautis-integration' ),
			__( 'SkautIS', 'skautis-integration' ),
			Helpers::get_skautis_manager_capability(),
			SKAUTIS_INTEGRATION_NAME,
			array( self::class, 'print_setting_page' ),
			self::get_menu_icon()
		);

		add_submenu_page(
			SKAUTIS_INTEGRATION_NAME,
			__( 'General', 'skautis-integration' ),
			__( 'General', 'skautis-integration' ),
			Helpers::get_skautis_manager_capability(),
			SKAUTIS_INTEGRATION_NAME,
			array( self::class, 'print_setting_page' )
		);

		add_submenu_page(
			SKAUTIS_INTEGRATION_NAME,
			__( 'Login', 'skautis-integration' ),
			__( 'Login', 'skautis-integration' ),
			Helpers::get_skautis_manager_capability(),
			SKAUTIS_INTEGRATION_NAME . '_login',
			array( self::class, 'print_login_page' )
		);
	}

	/**
	 * Redirects the removed Modules page to the registration settings.
	 *
	 * @return void
	 */
	#[SuppressWarnings( ExitExpression::class )]
	public static function redirect_old_modules_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['page'] ) || SKAUTIS_INTEGRATION_NAME . '_modules' !== $_GET['page'] ) {
			return;
		}
		wp_safe_redirect( admin_url( 'admin.php?page=' . SKAUTIS_INTEGRATION_NAME . '_modules_register' ), 301 );
		exit();
	}

	/**
	 * Prints the basic settings page.
	 *
	 * @return void
	 */
	public static function print_setting_page() {
		if ( ! current_user_can( Helpers::get_skautis_manager_capability() ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'skautis-integration' ) );
		}

		settings_errors();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'skautIS connection settings', 'skautis-integration' ); ?></h1>
			<form method="POST" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
				<?php
				settings_fields( SKAUTIS_INTEGRATION_NAME );
				do_settings_sections( SKAUTIS_INTEGRATION_NAME );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Checks that the App ID works with skautIS.
	 *
	 * @param string $value The App ID.
	 *
	 * @return string The sanitized App ID.
	 *
	 * @suppress PhanPluginPossiblyStaticPublicMethod
	 */
	public function test_app_id( $value ) {
		/* phpcs:ignore Squiz.PHP.CommentedOutCode.Found
		 * Disabled on 2025-09 due to errors in skautIS.
		if ( ! $this->skautis_gateway->test_active_app_id() ) {
			add_settings_error( 'general', 'api_invalid', esc_html__( 'The App ID is not valid for this site.', 'skautis-integration' ), 'notice-error' );
		}
		*/
		return sanitize_text_field( $value );
	}

	/**
	 * Adds basic settings to WordPress.
	 *
	 * @return void
	 */
	public function setup_setting_fields() {
		add_settings_section(
			'skautis_integration_setting',
			__( 'APP ID', 'skautis-integration' ),
			static function () {
				/* translators: 1: Start of a link to the documentation 2: End of the link to the documentation */
				printf( esc_html__( 'You can find instructions for setting up the plugin and getting an App ID in the %1$shelp%2$s.', 'skautis-integration' ), '<a href="' . esc_url( self::HELP_PAGE_URL ) . '" target="_blank">', '</a>' );
			},
			SKAUTIS_INTEGRATION_NAME
		);

		add_settings_field(
			'skautis_integration_appid_prod',
			__( 'Production App ID', 'skautis-integration' ),
			array( self::class, 'field_app_id_prod' ),
			SKAUTIS_INTEGRATION_NAME,
			'skautis_integration_setting',
			array( 'label_for' => 'skautis_integration_appid_prod' )
		);

		add_settings_field(
			'skautis_integration_appid_test',
			__( 'Test App ID', 'skautis-integration' ),
			array( self::class, 'field_app_id_test' ),
			SKAUTIS_INTEGRATION_NAME,
			'skautis_integration_setting',
			array( 'label_for' => 'skautis_integration_appid_test' )
		);

		add_settings_field(
			'skautis_integration_appid_type',
			__( 'Active App ID', 'skautis-integration' ),
			array( self::class, 'field_app_id_type' ),
			SKAUTIS_INTEGRATION_NAME,
			'skautis_integration_setting'
		);

		register_setting(
			SKAUTIS_INTEGRATION_NAME,
			'skautis_integration_appid_prod',
			array(
				'type'              => 'integer',
				'show_in_rest'      => false,
				'sanitize_callback' => array( $this, 'test_app_id' ),
			)
		);
		register_setting(
			SKAUTIS_INTEGRATION_NAME,
			'skautis_integration_appid_test',
			array(
				'type'              => 'integer',
				'show_in_rest'      => false,
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		register_setting(
			SKAUTIS_INTEGRATION_NAME,
			'skautis_integration_appid_type',
			array(
				'type'              => 'string',
				'show_in_rest'      => false,
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
	}

	/**
	 * Prints the login settings page.
	 *
	 * @return void
	 */
	public static function print_login_page() {
		if ( ! current_user_can( Helpers::get_skautis_manager_capability() ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'skautis-integration' ) );
		}

		settings_errors();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Login settings', 'skautis-integration' ); ?></h1>
			<form method="POST" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
				<?php
				settings_fields( SKAUTIS_INTEGRATION_NAME . '_login' );
				do_settings_sections( SKAUTIS_INTEGRATION_NAME . '_login' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Adds login settings to WordPress.
	 *
	 * @return void
	 */
	public static function setup_login_fields() {
		add_settings_section(
			SKAUTIS_INTEGRATION_NAME . '_login',
			'',
			static function () {
				echo '';
			},
			SKAUTIS_INTEGRATION_NAME . '_login'
		);

		add_settings_field(
			SKAUTIS_INTEGRATION_NAME . '_allowUsersDisconnectFromSkautis',
			__( 'Disconnecting from skautIS', 'skautis-integration' ),
			array( self::class, 'field_allow_users_disconnect' ),
			SKAUTIS_INTEGRATION_NAME . '_login',
			SKAUTIS_INTEGRATION_NAME . '_login',
			array( 'label_for' => 'skautis_integration_allowUsersDisconnectFromSkautis' )
		);

		if ( Services::is_registration_enabled() ) {
			add_settings_field(
				SKAUTIS_INTEGRATION_NAME . '_checkUserPrivilegesIfLoginBySkautis',
				__( 'Checking registration conditions', 'skautis-integration' ),
				array( self::class, 'field_check_user_privileges' ),
				SKAUTIS_INTEGRATION_NAME . '_login',
				SKAUTIS_INTEGRATION_NAME . '_login',
				array( 'label_for' => 'skautis_integration_checkUserPrivilegesIfLoginBySkautis' )
			);
		}

		add_settings_field(
			SKAUTIS_INTEGRATION_NAME . '_login_page_url',
			__( 'Login page address', 'skautis-integration' ),
			array( self::class, 'field_login_page_url' ),
			SKAUTIS_INTEGRATION_NAME . '_login',
			SKAUTIS_INTEGRATION_NAME . '_login',
			array( 'label_for' => SKAUTIS_INTEGRATION_NAME . '_login_page_url' )
		);

		register_setting(
			SKAUTIS_INTEGRATION_NAME . '_login',
			SKAUTIS_INTEGRATION_NAME . '_login_page_url',
			array(
				'type'              => 'string',
				'show_in_rest'      => true,
				'sanitize_callback' => static function ( $url ) {
					$url = str_replace( ' ', '%20', $url );
					$url = preg_replace( '|[^a-z0-9-~+_.?=!&;,/:%@$\|*\'()\[\]\\x80-\\xff]|i', '', $url );
					if ( ! is_string( $url ) ) {
						$url = '';
					}
					$url = wp_kses_normalize_entities( $url );
					$url = str_replace( '&amp;', '&#038;', $url );
					$url = str_replace( "'", '&#039;', $url );

					// The rewrite rules can't be flushed here, before the new URL is saved. Actions::flush_rewrite_rules_if_necessary() refreshes them on the next request.
					return trim( $url, '/' );
				},
			)
		);
		register_setting(
			SKAUTIS_INTEGRATION_NAME . '_login',
			SKAUTIS_INTEGRATION_NAME . '_allowUsersDisconnectFromSkautis',
			array(
				'type'         => 'boolean',
				'show_in_rest' => false,
			)
		);

		if ( Services::is_registration_enabled() ) {
			register_setting(
				SKAUTIS_INTEGRATION_NAME . '_login',
				SKAUTIS_INTEGRATION_NAME . '_checkUserPrivilegesIfLoginBySkautis',
				array(
					'type'         => 'boolean',
					'show_in_rest' => false,
				)
			);
		}
	}

	/**
	 * Prints the settings field for the production app id.
	 *
	 * @return void
	 */
	public static function field_app_id_prod() {
		echo '<input name="skautis_integration_appid_prod" id="skautis_integration_appid_prod" type="text" value="' . esc_attr( get_option( 'skautis_integration_appid_prod' ) ) . '" class="regular-text" />';
	}

	/**
	 * Prints the settings field for the testing app id.
	 *
	 * @return void
	 */
	public static function field_app_id_test() {
		echo '<input name="skautis_integration_appid_test" id="skautis_integration_appid_test" type="text" value="' . esc_attr( get_option( 'skautis_integration_appid_test' ) ) . '" class="regular-text" />';
	}

	/**
	 * Prints the settings field for choosing between testing and production environment.
	 *
	 * @return void
	 */
	public static function field_app_id_type() {
		$app_id_type = get_option( 'skautis_integration_appid_type' );
		?>
		<label>
			<input type="radio" name="skautis_integration_appid_type"
				value="prod"<?php checked( 'prod' === $app_id_type ); ?> />
			<span><?php esc_html_e( 'Production', 'skautis-integration' ); ?></span>
		</label>
		<br/>
		<label>
			<input type="radio" name="skautis_integration_appid_type"
				value="test"<?php checked( 'test' === $app_id_type ); ?> />
			<span><?php esc_html_e( 'Test', 'skautis-integration' ); ?></span>
		</label>
		<?php
	}

	/**
	 * Prints the settings field for custom login URL.
	 *
	 * @return void
	 */
	public static function field_login_page_url() {
		echo esc_html( get_home_url() ) . '/<input name="' . esc_attr( SKAUTIS_INTEGRATION_NAME ) . '_login_page_url" id="' . esc_attr( SKAUTIS_INTEGRATION_NAME ) . '_login_page_url" type="text" value="' . esc_attr( get_option( SKAUTIS_INTEGRATION_NAME . '_login_page_url' ) ) . '" class="regular-text" placeholder="skautis/prihlaseni" />';
		?>
		<br/>
		<em>
		<?php
		esc_html_e(
			'For a custom login page, add a "skautis" folder with a "login.php" file to the active theme.',
			'skautis-integration'
		);
		?>
			</em>
		<br/>
		<em>
		<?php
		esc_html_e(
			'In the login.php file, you can call the global functions getSkautisLoginUrl(), getSkautisLogoutUrl(), getSkautisRegisterUrl() and isUserLoggedInSkautis().',
			'skautis-integration'
		);
		?>
		</em>
		<?php
	}

	/**
	 * Prints the settings field for dis/allowing users to disconnect their skautIS account from their WordPress account.
	 *
	 * @return void
	 */
	public static function field_allow_users_disconnect() {
		?>
		<input name="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_allowUsersDisconnectFromSkautis"
			id="skautis_integration_allowUsersDisconnectFromSkautis" type="checkbox"
			<?php checked( get_option( SKAUTIS_INTEGRATION_NAME . '_allowUsersDisconnectFromSkautis' ) === '1' ); ?>value="1"/>
		<div
			style="margin: 0.4em 0;"><?php esc_html_e( 'Allows users to disconnect their account from skautIS.', 'skautis-integration' ); ?></div>
		<em><?php esc_html_e( 'This setting does not affect users with the administrator role.', 'skautis-integration' ); ?></em>
		<?php
	}

	/**
	 * Prints the settings field for checking user rules on each login.
	 *
	 * @return void
	 */
	public static function field_check_user_privileges() {
		?>
		<input name="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_checkUserPrivilegesIfLoginBySkautis"
			id="skautis_integration_checkUserPrivilegesIfLoginBySkautis" type="checkbox"
			<?php checked( get_option( SKAUTIS_INTEGRATION_NAME . '_checkUserPrivilegesIfLoginBySkautis' ) === '1' ); ?>value="1"/>
		<div
			style="margin: 0.4em 0;"><?php esc_html_e( 'When a user logs in with skautIS, check whether they still meet the registration conditions.', 'skautis-integration' ); ?></div>
		<em><?php esc_html_e( 'This setting does not affect users with the administrator role.', 'skautis-integration' ); ?></em>
		<?php
	}
}
