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
	 * The tabs of the settings page in the form `tab => settings page`. Each tab has its own settings group.
	 *
	 * @var array<string, string>
	 */
	const TABS = array(
		'general'      => SKAUTIS_INTEGRATION_NAME,
		'login'        => SKAUTIS_INTEGRATION_NAME . '_login',
		'registration' => SKAUTIS_INTEGRATION_NAME . '_modules_register',
		'visibility'   => SKAUTIS_INTEGRATION_NAME . '_modules_visibility',
	);

	/**
	 * The removed settings pages in the form `old page => tab`. Links to them are redirected to the tabs.
	 *
	 * @var array<string, string>
	 */
	const OLD_PAGES = array(
		SKAUTIS_INTEGRATION_NAME . '_login'              => 'login',
		SKAUTIS_INTEGRATION_NAME . '_modules'            => 'registration',
		SKAUTIS_INTEGRATION_NAME . '_modules_register'   => 'registration',
		SKAUTIS_INTEGRATION_NAME . '_modules_visibility' => 'visibility',
	);

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
		// The old pages no longer exist, so WordPress denies the access before admin_init.
		add_action( 'admin_page_access_denied', array( self::class, 'redirect_old_pages' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_styles' ) );
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
				Helpers::show_admin_notice( sprintf( __( 'Enter the App ID of the production version of skautIS in the plugin %1$ssettings%2$s', 'skautis-integration' ), '<a href="' . esc_url( self::get_tab_url( 'general' ) ) . '">', '</a>' ), 'warning', 'toplevel-page-' . SKAUTIS_INTEGRATION_NAME );
			}
		} elseif ( Skautis_Gateway::TEST_ENV === $env_type ) {
			if ( '' === strval( get_option( 'skautis_integration_appid_test', '' ) ) ) {
				/* translators: 1: Start of a link to the settings 2: End of the link to the settings */
				Helpers::show_admin_notice( sprintf( __( 'Enter the App ID of the test version of skautIS in the plugin %1$ssettings%2$s', 'skautis-integration' ), '<a href="' . esc_url( self::get_tab_url( 'general' ) ) . '">', '</a>' ), 'warning', 'toplevel-page-' . SKAUTIS_INTEGRATION_NAME );
			}
		} else {
				/* translators: 1: Start of a link to the settings 2: End of the link to the settings */
			Helpers::show_admin_notice( sprintf( __( 'Select the skautIS environment in the plugin %1$ssettings%2$s', 'skautis-integration' ), '<a href="' . esc_url( self::get_tab_url( 'general' ) ) . '">', '</a>' ), 'warning', 'toplevel-page-' . SKAUTIS_INTEGRATION_NAME );
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
			'settings' => '<a href="' . esc_url( self::get_tab_url( 'general' ) ) . '">' . esc_html__( 'Settings', 'skautis-integration' ) . '</a>',
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
	 * Adds the settings page to the administration.
	 *
	 * @return void
	 */
	public function setup_setting_page() {
		add_menu_page(
			__( 'skautIS settings', 'skautis-integration' ),
			__( 'SkautIS', 'skautis-integration' ),
			Helpers::get_skautis_manager_capability(),
			SKAUTIS_INTEGRATION_NAME,
			array( self::class, 'print_setting_page' ),
			self::get_menu_icon()
		);

		add_submenu_page(
			SKAUTIS_INTEGRATION_NAME,
			__( 'skautIS settings', 'skautis-integration' ),
			__( 'Settings', 'skautis-integration' ),
			Helpers::get_skautis_manager_capability(),
			SKAUTIS_INTEGRATION_NAME,
			array( self::class, 'print_setting_page' )
		);
	}

	/**
	 * Returns the localized labels of the settings tabs.
	 *
	 * @return array<string, string> The labels in the form `tab => label`.
	 */
	private static function get_tab_labels(): array {
		return array(
			'general'      => __( 'General', 'skautis-integration' ),
			'login'        => __( 'Login', 'skautis-integration' ),
			'registration' => __( 'Registration', 'skautis-integration' ),
			'visibility'   => __( 'Content visibility', 'skautis-integration' ),
		);
	}

	/**
	 * Returns the URL of a settings tab.
	 *
	 * @param string $tab The tab.
	 */
	public static function get_tab_url( string $tab ): string {
		$url = admin_url( 'admin.php?page=' . SKAUTIS_INTEGRATION_NAME );

		return 'general' === $tab ? $url : add_query_arg( 'tab', $tab, $url );
	}

	/**
	 * Returns the settings tab selected in the current request.
	 */
	public static function get_current_tab(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = isset( $_GET['tab'] ) && is_string( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';

		return array_key_exists( $tab, self::TABS ) ? $tab : 'general';
	}

	/**
	 * Returns whether the current admin screen is the given tab of the settings page.
	 *
	 * @param string $tab The tab.
	 */
	public static function is_settings_tab( string $tab ): bool {
		$screen = get_current_screen();

		return null !== $screen && 'toplevel_page_' . SKAUTIS_INTEGRATION_NAME === $screen->id && self::get_current_tab() === $tab;
	}

	/**
	 * Returns the arguments that show a settings section as a card.
	 *
	 * @return array{before_section: string, after_section: string, section_class: string} The arguments for add_settings_section().
	 */
	public static function get_card_section_args(): array {
		return array(
			'before_section' => '<div class="%s">',
			'after_section'  => '</div>',
			'section_class'  => 'skautis-settings-card',
		);
	}

	/**
	 * Redirects the removed settings pages to the tabs of the settings page.
	 *
	 * @return void
	 */
	#[SuppressWarnings( ExitExpression::class )]
	public static function redirect_old_pages() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) && is_string( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( ! array_key_exists( $page, self::OLD_PAGES ) ) {
			return;
		}
		wp_safe_redirect( self::get_tab_url( self::OLD_PAGES[ $page ] ), 301 );
		exit();
	}

	/**
	 * Enqueues the styles of the settings page.
	 *
	 * @param string $hook_suffix The current admin page.
	 *
	 * @return void
	 */
	public static function enqueue_styles( $hook_suffix ) {
		if ( 'toplevel_page_' . SKAUTIS_INTEGRATION_NAME !== $hook_suffix ) {
			return;
		}
		Helpers::enqueue_style( 'admin_settings', 'admin/css/skautis-admin-settings.min.css' );
	}

	/**
	 * Prints the settings page with the selected tab.
	 *
	 * @return void
	 */
	public static function print_setting_page() {
		if ( ! Helpers::user_is_skautis_manager() ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'skautis-integration' ) );
		}

		$current_tab = self::get_current_tab();
		?>
		<div class="skautis-settings-header">
			<div class="skautis-settings-title-section">
				<h1><?php esc_html_e( 'skautIS settings', 'skautis-integration' ); ?></h1>
				<a class="skautis-settings-help" href="<?php echo esc_url( self::HELP_PAGE_URL ); ?>" target="_blank">
					<?php esc_html_e( 'Help', 'skautis-integration' ); ?>
					<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'skautis-integration' ); ?></span>
					<span aria-hidden="true" class="dashicons dashicons-external"></span>
				</a>
			</div>
			<nav class="skautis-settings-tabs" aria-label="<?php esc_attr_e( 'Settings sections', 'skautis-integration' ); ?>">
				<?php
				foreach ( self::get_tab_labels() as $tab => $label ) {
					$is_current = $tab === $current_tab;
					echo '<a href="' . esc_url( self::get_tab_url( $tab ) ) . '" class="skautis-settings-tab' . ( $is_current ? ' is-active" aria-current="page' : '' ) . '">' . esc_html( $label ) . '</a>';
				}
				?>
			</nav>
		</div>
		<hr class="wp-header-end">
		<div class="skautis-settings-body">
			<?php settings_errors(); ?>
			<form method="POST" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
				<?php
				settings_fields( self::TABS[ $current_tab ] );
				do_settings_sections( self::TABS[ $current_tab ] );
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
	 * Adds the settings of the General tab to WordPress.
	 *
	 * @return void
	 */
	public function setup_setting_fields() {
		add_settings_section(
			'skautis_integration_setting',
			__( 'Connection to skautIS', 'skautis-integration' ),
			static function () {
				/* translators: 1: Start of a link to the documentation 2: End of the link to the documentation */
				echo '<p>' . sprintf( esc_html__( 'You can find instructions for setting up the plugin and getting an App ID in the %1$shelp%2$s.', 'skautis-integration' ), '<a href="' . esc_url( self::HELP_PAGE_URL ) . '" target="_blank">', '</a>' ) . '</p>';
			},
			SKAUTIS_INTEGRATION_NAME,
			self::get_card_section_args()
		);

		add_settings_field(
			'skautis_integration_appid_type',
			__( 'Active App ID', 'skautis-integration' ),
			array( self::class, 'field_app_id_type' ),
			SKAUTIS_INTEGRATION_NAME,
			'skautis_integration_setting'
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
	 * Adds the settings of the Login tab to WordPress.
	 *
	 * @return void
	 */
	public static function setup_login_fields() {
		add_settings_section(
			SKAUTIS_INTEGRATION_NAME . '_login',
			__( 'Login page', 'skautis-integration' ),
			static function () {
				echo '<p>' . esc_html__( 'The address of the page where users can log in with skautIS.', 'skautis-integration' ) . '</p>';
			},
			SKAUTIS_INTEGRATION_NAME . '_login',
			self::get_card_section_args()
		);

		add_settings_field(
			SKAUTIS_INTEGRATION_NAME . '_login_page_url',
			__( 'Login page address', 'skautis-integration' ),
			array( self::class, 'field_login_page_url' ),
			SKAUTIS_INTEGRATION_NAME . '_login',
			SKAUTIS_INTEGRATION_NAME . '_login',
			array( 'label_for' => SKAUTIS_INTEGRATION_NAME . '_login_page_url' )
		);

		add_settings_section(
			SKAUTIS_INTEGRATION_NAME . '_login_accounts',
			__( 'User accounts', 'skautis-integration' ),
			static function () {
				echo '';
			},
			SKAUTIS_INTEGRATION_NAME . '_login',
			self::get_card_section_args()
		);

		add_settings_field(
			SKAUTIS_INTEGRATION_NAME . '_allowUsersDisconnectFromSkautis',
			__( 'Disconnecting from skautIS', 'skautis-integration' ),
			array( self::class, 'field_allow_users_disconnect' ),
			SKAUTIS_INTEGRATION_NAME . '_login',
			SKAUTIS_INTEGRATION_NAME . '_login_accounts'
		);

		if ( Services::is_registration_enabled() ) {
			add_settings_field(
				SKAUTIS_INTEGRATION_NAME . '_checkUserPrivilegesIfLoginBySkautis',
				__( 'Checking registration conditions', 'skautis-integration' ),
				array( self::class, 'field_check_user_privileges' ),
				SKAUTIS_INTEGRATION_NAME . '_login',
				SKAUTIS_INTEGRATION_NAME . '_login_accounts'
			);
		}

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
		echo '<p class="description">' . esc_html__( 'Use the test version of skautIS only for trying out the plugin.', 'skautis-integration' ) . '</p>';
	}

	/**
	 * Prints the settings field for choosing between testing and production environment.
	 *
	 * @return void
	 */
	public static function field_app_id_type() {
		$app_id_type = get_option( 'skautis_integration_appid_type' );
		?>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Active App ID', 'skautis-integration' ); ?></legend>
			<label>
				<input type="radio" name="skautis_integration_appid_type"
					value="prod"<?php checked( 'prod' === $app_id_type ); ?> />
				<?php esc_html_e( 'Production', 'skautis-integration' ); ?>
			</label>
			<br/>
			<label>
				<input type="radio" name="skautis_integration_appid_type"
					value="test"<?php checked( 'test' === $app_id_type ); ?> />
				<?php esc_html_e( 'Test', 'skautis-integration' ); ?>
			</label>
		</fieldset>
		<?php
	}

	/**
	 * Prints the settings field for custom login URL.
	 *
	 * @return void
	 */
	public static function field_login_page_url() {
		echo '<code>' . esc_html( trailingslashit( get_home_url() ) ) . '</code><input name="' . esc_attr( SKAUTIS_INTEGRATION_NAME ) . '_login_page_url" id="' . esc_attr( SKAUTIS_INTEGRATION_NAME ) . '_login_page_url" type="text" value="' . esc_attr( get_option( SKAUTIS_INTEGRATION_NAME . '_login_page_url' ) ) . '" class="regular-text code" placeholder="skautis/prihlaseni" aria-describedby="' . esc_attr( SKAUTIS_INTEGRATION_NAME ) . '_login_page_url_description" />';
		echo '<p class="description" id="' . esc_attr( SKAUTIS_INTEGRATION_NAME ) . '_login_page_url_description">' . esc_html__( 'For a custom login page, add a "skautis" folder with a "login.php" file to the active theme.', 'skautis-integration' ) . ' ' . esc_html__( 'In the login.php file, you can call the global functions getSkautisLoginUrl(), getSkautisLogoutUrl(), getSkautisRegisterUrl() and isUserLoggedInSkautis().', 'skautis-integration' ) . '</p>';
	}

	/**
	 * Prints the settings field for dis/allowing users to disconnect their skautIS account from their WordPress account.
	 *
	 * @return void
	 */
	public static function field_allow_users_disconnect() {
		?>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Disconnecting from skautIS', 'skautis-integration' ); ?></legend>
			<label>
				<input name="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_allowUsersDisconnectFromSkautis"
					id="skautis_integration_allowUsersDisconnectFromSkautis" type="checkbox" value="1"
					<?php checked( get_option( SKAUTIS_INTEGRATION_NAME . '_allowUsersDisconnectFromSkautis' ) === '1' ); ?>/>
				<?php esc_html_e( 'Allows users to disconnect their account from skautIS.', 'skautis-integration' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'This setting does not affect users with the administrator role.', 'skautis-integration' ); ?></p>
		</fieldset>
		<?php
	}

	/**
	 * Prints the settings field for checking user rules on each login.
	 *
	 * @return void
	 */
	public static function field_check_user_privileges() {
		?>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Checking registration conditions', 'skautis-integration' ); ?></legend>
			<label>
				<input name="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_checkUserPrivilegesIfLoginBySkautis"
					id="skautis_integration_checkUserPrivilegesIfLoginBySkautis" type="checkbox" value="1"
					<?php checked( get_option( SKAUTIS_INTEGRATION_NAME . '_checkUserPrivilegesIfLoginBySkautis' ) === '1' ); ?>/>
				<?php esc_html_e( 'When a user logs in with skautIS, check whether they still meet the registration conditions.', 'skautis-integration' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'This setting does not affect users with the administrator role.', 'skautis-integration' ); ?></p>
		</fieldset>
		<?php
	}
}
