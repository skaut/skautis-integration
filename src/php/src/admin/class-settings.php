<?php
/**
 * Contains the Settings class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Admin;

use Skautis_Integration\Auth\Skautis_Gateway;
use Skautis_Integration\Modules\Modules_Manager;
use Skautis_Integration\Utils\Helpers;
use Skautis_Integration\Services\Services;
use Skautis_Integration\Modules\Register\Register;

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
	 * A link to the Modules_Manager service instance.
	 *
	 * @var Modules_Manager
	 */
	private $modules_manager;

	/**
	 * The location of the administration files for the module
	 *
	 * @var string
	 */
	private $admin_dir_url = '';

	/**
	 * Constructs the service and saves all dependencies.
	 *
	 * @param Skautis_Gateway $skautis_gateway An injected Skautis_Gateway service instance.
	 * @param Modules_Manager $modules_manager An injected Modules_Manager service instance.
	 */
	public function __construct( Skautis_Gateway $skautis_gateway, Modules_Manager $modules_manager ) {
		$this->skautis_gateway = $skautis_gateway;
		$this->modules_manager = $modules_manager;
		$this->admin_dir_url   = plugin_dir_url( __FILE__ ) . 'public/';
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
		add_action( 'admin_init', array( $this, 'setup_setting_fields' ) );
		add_action( 'admin_init', array( $this, 'setup_login_fields' ) );

		self::check_if_app_id_is_set_and_show_notices();
	}

	/**
	 * Shows a notice in the administration if the app id is not set.
	 *
	 * @return void
	 */
	private static function check_if_app_id_is_set_and_show_notices() {
		$env_type = get_option( 'skautis_integration_appid_type' );
		if ( Skautis_Gateway::PROD_ENV === $env_type ) {
			if ( false === get_option( 'skautis_integration_appid_prod' ) ) {
				/* translators: 1: Start of a link to the settings 2: End of the link to the settings */
				Helpers::show_admin_notice( sprintf( __( 'Zadejte v %1$snastavení%2$s pluginu APP ID produkční verze skautISu', 'skautis-integration' ), '<a href="' . esc_url( admin_url( 'admin.php?page=' . SKAUTIS_INTEGRATION_NAME ) ) . '">', '</a>' ), 'warning', 'toplevel-page-' . SKAUTIS_INTEGRATION_NAME );
			}
		} elseif ( Skautis_Gateway::TEST_ENV === $env_type ) {
			if ( false === get_option( 'skautis_integration_appid_test' ) ) {
				/* translators: 1: Start of a link to the settings 2: End of the link to the settings */
				Helpers::show_admin_notice( sprintf( __( 'Zadejte v %1$snastavení%2$s pluginu APP ID testovací verze skautISu', 'skautis-integration' ), '<a href="' . esc_url( admin_url( 'admin.php?page=' . SKAUTIS_INTEGRATION_NAME ) ) . '">', '</a>' ), 'warning', 'toplevel-page-' . SKAUTIS_INTEGRATION_NAME );
			}
		} else {
				/* translators: 1: Start of a link to the settings 2: End of the link to the settings */
			Helpers::show_admin_notice( sprintf( __( 'Vyberte v %1$snastavení%2$s pluginu typ prostředí skautISu', 'skautis-integration' ), '<a href="' . esc_url( admin_url( 'admin.php?page=' . SKAUTIS_INTEGRATION_NAME ) ) . '">', '</a>' ), 'warning', 'toplevel-page-' . SKAUTIS_INTEGRATION_NAME );
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
			'settings' => '<a href="' . admin_url( 'admin.php?page=' . SKAUTIS_INTEGRATION_NAME, 'skautis-integration' ) . '">' . __( 'Settings', 'skautis-integration' ) . '</a>',
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
			'help' => '<a href="' . self::HELP_PAGE_URL . '" target="_blank">' . __( 'Help', 'skautis-integration' ) . '</a>',
		);

		return array_merge( $links, $mylinks );
	}

	/**
	 * Adds the settings pages to the administration.
	 *
	 * @return void
	 */
	public function setup_setting_page() {
		add_menu_page(
			__( 'Obecné', 'skautis-integration' ),
			__( 'SkautIS', 'skautis-integration' ),
			Helpers::get_skautis_manager_capability(),
			SKAUTIS_INTEGRATION_NAME,
			array( self::class, 'print_setting_page' ),
			$this->admin_dir_url . 'img/lilie.png'
		);

		add_submenu_page(
			SKAUTIS_INTEGRATION_NAME,
			__( 'Obecné', 'skautis-integration' ),
			__( 'Obecné', 'skautis-integration' ),
			Helpers::get_skautis_manager_capability(),
			SKAUTIS_INTEGRATION_NAME,
			array( self::class, 'print_setting_page' )
		);

		add_submenu_page(
			SKAUTIS_INTEGRATION_NAME,
			__( 'Přihlašování', 'skautis-integration' ),
			__( 'Přihlašování', 'skautis-integration' ),
			Helpers::get_skautis_manager_capability(),
			SKAUTIS_INTEGRATION_NAME . '_login',
			array( self::class, 'print_login_page' )
		);

		add_submenu_page(
			SKAUTIS_INTEGRATION_NAME,
			__( 'Moduly', 'skautis-integration' ),
			__( 'Moduly', 'skautis-integration' ),
			Helpers::get_skautis_manager_capability(),
			SKAUTIS_INTEGRATION_NAME . '_modules',
			array( self::class, 'print_modules_page' )
		);
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
			<h1><?php esc_html_e( 'Nastavení propojení se skautISem', 'skautis-integration' ); ?></h1>
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
	 * Checks that the App ID works with SkautIS.
	 *
	 * @param string $value The App ID.
	 *
	 * @return string The sanitized App ID.
	 *
	 * @suppress PhanPluginPossiblyStaticPublicMethod
	 */
	public function test_app_id( $value ) {
		/* phpcs:ignore Squiz.PHP.CommentedOutCode.Found
		 * Disabled on 2025-09 due to errors in SkautIS.
		if ( ! $this->skautis_gateway->test_active_app_id() ) {
			add_settings_error( 'general', 'api_invalid', esc_html__( 'Zadané APP ID není pro tento web platné.', 'skautis-integration' ), 'notice-error' );
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
				printf( esc_html__( 'Návod pro nastavení pluginu a získání APP ID najdete v %1$snápovědě%2$s.', 'skautis-integration' ), '<a href="' . esc_url( self::HELP_PAGE_URL ) . '" target="_blank">', '</a>' );
			},
			SKAUTIS_INTEGRATION_NAME
		);

		add_settings_field(
			'skautis_integration_appid_prod',
			__( 'APP ID produkční verze', 'skautis-integration' ),
			array( self::class, 'field_app_id_prod' ),
			SKAUTIS_INTEGRATION_NAME,
			'skautis_integration_setting'
		);

		add_settings_field(
			'skautis_integration_appid_test',
			__( 'APP ID testovací verze', 'skautis-integration' ),
			array( self::class, 'field_app_id_test' ),
			SKAUTIS_INTEGRATION_NAME,
			'skautis_integration_setting'
		);

		add_settings_field(
			'skautis_integration_appid_type',
			__( 'Vyberte aktivní APP ID', 'skautis-integration' ),
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

		add_settings_section(
			SKAUTIS_INTEGRATION_NAME . '_modules',
			__( 'Dostupné moduly', 'skautis-integration' ),
			static function () {
				echo '';
			},
			SKAUTIS_INTEGRATION_NAME . '_modules'
		);

		add_settings_field(
			'skautis_integration_login',
			__( 'Přihlašování', 'skautis-integration' ),
			static function () {
				echo '<label for="skautis_integration_login"><input type="checkbox" id="skautis_integration_login" checked="checked" disabled="disabled"/></label>';
			},
			SKAUTIS_INTEGRATION_NAME . '_modules',
			SKAUTIS_INTEGRATION_NAME . '_modules'
		);

		$activated_modules = (array) get_option( 'skautis_integration_activated_modules' );

		foreach ( $this->modules_manager->get_all_modules() as $module_id => $module_label ) {
			add_settings_field(
				SKAUTIS_INTEGRATION_NAME . '_modules_' . $module_id,
				$module_label,
				static function () use ( $module_id, $activated_modules ) {
					$checked = in_array( $module_id, $activated_modules, true );
					echo '
					<label for="' . esc_attr( $module_id ) . '"><input name="skautis_integration_activated_modules[]" type="checkbox" id="' . esc_attr( $module_id ) . '" value="' . esc_attr( $module_id ) . '" ' . ( $checked ? 'checked="checked"' : '' ) . '></label>
					';
				},
				SKAUTIS_INTEGRATION_NAME . '_modules',
				SKAUTIS_INTEGRATION_NAME . '_modules'
			);
		}

		register_setting(
			SKAUTIS_INTEGRATION_NAME . '_modules',
			'skautis_integration_activated_modules',
			array(
				'type'         => 'string',
				'show_in_rest' => false,
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
			<h1><?php esc_html_e( 'Nastavení přihlašování', 'skautis-integration' ); ?></h1>
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
			__( 'Zrušení spojení se skautISem', 'skautis-integration' ),
			array( self::class, 'field_allow_users_disconnect_from_skautis' ),
			SKAUTIS_INTEGRATION_NAME . '_login',
			SKAUTIS_INTEGRATION_NAME . '_login'
		);

		if ( Services::get_modules_manager()->is_module_activated( Register::get_id() ) ) {
			add_settings_field(
				SKAUTIS_INTEGRATION_NAME . '_checkUserPrivilegesIfLoginBySkautis',
				__( 'Ověřování podmínek registrace', 'skautis-integration' ),
				array( self::class, 'field_check_user_privileges_if_login_by_skautis' ),
				SKAUTIS_INTEGRATION_NAME . '_login',
				SKAUTIS_INTEGRATION_NAME . '_login'
			);
		}

		add_settings_field(
			SKAUTIS_INTEGRATION_NAME . '_login_page_url',
			__( 'Adresa stránky s přihlašováním', 'skautis-integration' ),
			array( self::class, 'field_login_page_url' ),
			SKAUTIS_INTEGRATION_NAME . '_login',
			SKAUTIS_INTEGRATION_NAME . '_login'
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

					flush_rewrite_rules();

					return $url;
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

		if ( Services::get_modules_manager()->is_module_activated( Register::get_id() ) ) {
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
			<span><?php esc_html_e( 'Produkční', 'skautis-integration' ); ?></span>
		</label>
		<br/>
		<label>
			<input type="radio" name="skautis_integration_appid_type"
				value="test"<?php checked( 'test' === $app_id_type ); ?> />
			<span><?php esc_html_e( 'Testovací', 'skautis-integration' ); ?></span>
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
			'Pro vlastní vzhled přihlašovací stránky přidejte do složky aktivní šablony složku "skautis" a do ní soubor
			"login.php".',
			'skautis-integration'
		);
		?>
			</em>
		<br/>
		<em>
		<?php
		esc_html_e(
			'V souboru login.php pak můžete volat globální funkce: getSkautisLoginUrl(), getSkautisLogoutUrl(), getSkautisRegisterUrl(),
			isUserLoggedInSkautis()',
			'skautis-integration'
		);
		?>
		</em>
		<br/>
		<em><?php esc_html_e( 'Příklad kódu v souboru login.php:', 'skautis-integration' ); ?></em>
		<pre>
		<?php
		echo esc_html(
			'
<?php get_header(); ?>

<?php
if ( ! isUserLoggedInSkautis() ) {
?>
	<div class="wp-core-ui" style="text-align: center;">
	<a class="button button-primary button-hero button-skautis" href="<?php echo getSkautisRegisterUrl(); ?>">
		Přihlásit se přes skautIS
	</a>
	</div>
<?php
} else {
?>
	<div style="text-align: center;">
		<strong>Jste přihlášeni ve skautISu</strong><br/>
		<a class="button" href="<?php echo getSkautisLogoutUrl(); ?>">
			Odhlásit se ze skautISu
		</a>
	</div>
<?php
}
?>

<?php get_sidebar(); ?>
<?php get_footer(); ?>
'
		);
		?>
		</pre>
		<?php
	}

	/**
	 * Prints the settings field for dis/allowing users to disconnect their SkautIS account from their WordPress account.
	 *
	 * @return void
	 */
	public static function field_allow_users_disconnect_from_skautis() {
		?>
		<input name="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_allowUsersDisconnectFromSkautis"
			id="skautis_integration_allowUsersDisconnectFromSkautis" type="checkbox"
			<?php checked( get_option( SKAUTIS_INTEGRATION_NAME . '_allowUsersDisconnectFromSkautis' ) === '1' ); ?>value="1"/>
		<div
			style="margin: 0.4em 0;"><?php esc_html_e( 'Umožní uživatelům zrušit propojení svého účtu se skautISem.', 'skautis-integration' ); ?></div>
		<em><?php esc_html_e( 'Nastavení nebude mít dopad na uživatele s úrovní administrátora.', 'skautis-integration' ); ?></em>
		<?php
	}

	/**
	 * Prints the settings field for checking user rules on each login.
	 *
	 * @return void
	 */
	public static function field_check_user_privileges_if_login_by_skautis() {
		?>
		<input name="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_checkUserPrivilegesIfLoginBySkautis"
			id="skautis_integration_checkUserPrivilegesIfLoginBySkautis" type="checkbox"
			<?php checked( get_option( SKAUTIS_INTEGRATION_NAME . '_checkUserPrivilegesIfLoginBySkautis' ) === '1' ); ?>value="1"/>
		<div
			style="margin: 0.4em 0;"><?php esc_html_e( 'Při přihlašování uživatele přes skautIS ověřit, zda stále splňuje podmínky pro registraci.', 'skautis-integration' ); ?></div>
		<em><?php esc_html_e( 'Nastavení nebude mít dopad na uživatele s úrovní administrátora.', 'skautis-integration' ); ?></em>
		<?php
	}

	/**
	 * Prints the module settings page.
	 *
	 * @return void
	 */
	public static function print_modules_page() {
		if ( ! Helpers::user_is_skautis_manager() ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'skautis-integration' ) );
		}
		settings_errors();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Moduly', 'skautis-integration' ); ?></h1>
			<form method="POST" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
				<?php
				settings_fields( SKAUTIS_INTEGRATION_NAME . '_modules' );
				do_settings_sections( SKAUTIS_INTEGRATION_NAME . '_modules' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
