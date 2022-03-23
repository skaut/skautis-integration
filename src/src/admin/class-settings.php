<?php

declare( strict_types=1 );

namespace SkautisIntegration\Admin;

use SkautisIntegration\Auth\Skautis_Gateway;
use SkautisIntegration\Modules\Modules_Manager;
use SkautisIntegration\Utils\Helpers;
use SkautisIntegration\Services\Services;
use SkautisIntegration\Modules\Register\Register;

final class Settings {

	const HELP_PAGE_URL = 'https://napoveda.skaut.cz/skautis/skautis-integration';

	private $skautisGateway;
	private $modulesManager;
	private $adminDirUrl = '';

	public function __construct( Skautis_Gateway $skautisGateway, Modules_Manager $modulesManager ) {
		$this->skautisGateway = $skautisGateway;
		$this->modulesManager = $modulesManager;
		$this->adminDirUrl    = plugin_dir_url( __FILE__ ) . 'public/';
		$this->init_hooks();
	}

	private function init_hooks() {
		add_filter(
			'plugin_action_links_' . SKAUTISINTEGRATION_PLUGIN_BASENAME,
			array(
				$this,
				'add_settings_link_to_plugins_table',
			)
		);
		add_filter(
			'plugin_action_links_' . SKAUTISINTEGRATION_PLUGIN_BASENAME,
			array(
				$this,
				'add_help_link_to_plugins_table',
			)
		);

		add_action( 'admin_menu', array( $this, 'setup_setting_page' ), 5 );
		add_action( 'admin_init', array( $this, 'setup_setting_fields' ) );
		add_action( 'admin_init', array( $this, 'setup_login_fields' ) );

		$this->check_if_app_id_is_set_and_show_notices();
	}

	private function check_if_app_id_is_set_and_show_notices() {
		$envType = get_option( 'skautis_integration_appid_type' );
		if ( Skautis_Gateway::PROD_ENV === $envType ) {
			if ( ! get_option( 'skautis_integration_appid_prod' ) ) {
				/* translators: 1: Start of a link to the settings 2: End of the link to the settings */
				Helpers::show_admin_notice( sprintf( __( 'Zadejte v %1$snastavení%2$s pluginu APP ID produkční verze skautISu', 'skautis-integration' ), '<a href="' . esc_url( admin_url( 'admin.php?page=' . SKAUTISINTEGRATION_NAME ) ) . '">', '</a>' ), 'warning', 'toplevel-page-' . SKAUTISINTEGRATION_NAME );
			}
		} elseif ( Skautis_Gateway::TEST_ENV === $envType ) {
			if ( ! get_option( 'skautis_integration_appid_test' ) ) {
				/* translators: 1: Start of a link to the settings 2: End of the link to the settings */
				Helpers::show_admin_notice( sprintf( __( 'Zadejte v %1$snastavení%2$s pluginu APP ID testovací verze skautISu', 'skautis-integration' ), '<a href="' . esc_url( admin_url( 'admin.php?page=' . SKAUTISINTEGRATION_NAME ) ) . '">', '</a>' ), 'warning', 'toplevel-page-' . SKAUTISINTEGRATION_NAME );
			}
		} else {
				/* translators: 1: Start of a link to the settings 2: End of the link to the settings */
			Helpers::show_admin_notice( sprintf( __( 'Vyberte v %1$snastavení%2$s pluginu typ prostředí skautISu', 'skautis-integration' ), '<a href="' . esc_url( admin_url( 'admin.php?page=' . SKAUTISINTEGRATION_NAME ) ) . '">', '</a>' ), 'warning', 'toplevel-page-' . SKAUTISINTEGRATION_NAME );
		}
	}

	public function add_settings_link_to_plugins_table( array $links = array() ): array {
		$mylinks = array(
			'<a href="' . admin_url( 'admin.php?page=' . SKAUTISINTEGRATION_NAME, 'skautis-integration' ) . '">' . __( 'Settings', 'skautis-integration' ) . '</a>',
		);

		return array_merge( $links, $mylinks );
	}

	public function add_help_link_to_plugins_table( array $links = array() ): array {
		$mylinks = array(
			'<a href="' . self::HELP_PAGE_URL . '" target="_blank">' . __( 'Help', 'skautis-integration' ) . '</a>',
		);

		return array_merge( $links, $mylinks );
	}

	public function setup_setting_page() {
		add_menu_page(
			__( 'Obecné', 'skautis-integration' ),
			__( 'SkautIS', 'skautis-integration' ),
			Helpers::get_skautis_manager_capability(),
			SKAUTISINTEGRATION_NAME,
			array( $this, 'print_setting_page' ),
			$this->adminDirUrl . 'img/lilie.png'
		);

		add_submenu_page(
			SKAUTISINTEGRATION_NAME,
			__( 'Obecné', 'skautis-integration' ),
			__( 'Obecné', 'skautis-integration' ),
			Helpers::get_skautis_manager_capability(),
			SKAUTISINTEGRATION_NAME,
			array( $this, 'print_setting_page' )
		);

		add_submenu_page(
			SKAUTISINTEGRATION_NAME,
			__( 'Přihlašování', 'skautis-integration' ),
			__( 'Přihlašování', 'skautis-integration' ),
			Helpers::get_skautis_manager_capability(),
			SKAUTISINTEGRATION_NAME . '_login',
			array( $this, 'print_login_page' )
		);

		add_submenu_page(
			SKAUTISINTEGRATION_NAME,
			__( 'Moduly', 'skautis-integration' ),
			__( 'Moduly', 'skautis-integration' ),
			Helpers::get_skautis_manager_capability(),
			SKAUTISINTEGRATION_NAME . '_modules',
			array( $this, 'print_modules_page' )
		);
	}

	public function print_setting_page() {
		if ( ! current_user_can( Helpers::get_skautis_manager_capability() ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'skautis-integration' ) );
		}

		settings_errors();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Nastavení propojení se skautISem', 'skautis-integration' ); ?></h1>
			<form method="POST" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
				<?php
				settings_fields( SKAUTISINTEGRATION_NAME );
				do_settings_sections( SKAUTISINTEGRATION_NAME );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public function test_app_id( $value ) {
		if ( ! $this->skautisGateway->test_active_app_id() ) {
			add_settings_error( 'general', 'api_invalid', esc_html__( 'Zadané APP ID není pro tento web platné.', 'skautis-integration' ), 'notice-error' );
		}
		return sanitize_text_field( $value );
	}

	public function setup_setting_fields() {
		add_settings_section(
			'skautis_integration_setting',
			__( 'APP ID', 'skautis-integration' ),
			function () {
				/* translators: 1: Start of a link to the documentation 2: End of the link to the documentation */
				printf( esc_html__( 'Návod pro nastavení pluginu a získání APP ID najdete v %1$snápovědě%2$s.', 'skautis-integration' ), '<a href="' . esc_url( self::HELP_PAGE_URL ) . '" target="_blank">', '</a>' );
			},
			SKAUTISINTEGRATION_NAME
		);

		add_settings_field(
			'skautis_integration_appid_prod',
			__( 'APP ID produkční verze', 'skautis-integration' ),
			array( $this, 'field_app_id_prod' ),
			SKAUTISINTEGRATION_NAME,
			'skautis_integration_setting'
		);

		add_settings_field(
			'skautis_integration_appid_test',
			__( 'APP ID testovací verze', 'skautis-integration' ),
			array( $this, 'field_app_id_test' ),
			SKAUTISINTEGRATION_NAME,
			'skautis_integration_setting'
		);

		add_settings_field(
			'skautis_integration_appid_type',
			__( 'Vyberte aktivní APP ID', 'skautis-integration' ),
			array( $this, 'field_app_id_type' ),
			SKAUTISINTEGRATION_NAME,
			'skautis_integration_setting'
		);

		register_setting(
			SKAUTISINTEGRATION_NAME,
			'skautis_integration_appid_prod',
			array(
				'type'              => 'integer',
				'show_in_rest'      => false,
				'sanitize_callback' => array( $this, 'test_app_id' ),
			)
		);
		register_setting(
			SKAUTISINTEGRATION_NAME,
			'skautis_integration_appid_test',
			array(
				'type'              => 'integer',
				'show_in_rest'      => false,
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		register_setting(
			SKAUTISINTEGRATION_NAME,
			'skautis_integration_appid_type',
			array(
				'type'              => 'string',
				'show_in_rest'      => false,
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		add_settings_section(
			SKAUTISINTEGRATION_NAME . '_modules',
			__( 'Dostupné moduly', 'skautis-integration' ),
			function () {
				echo '';
			},
			SKAUTISINTEGRATION_NAME . '_modules'
		);

		add_settings_field(
			'skautis_integration_login',
			__( 'Přihlašování', 'skautis-integration' ),
			function () {
				echo '<label for="skautis_integration_login"><input type="checkbox" id="skautis_integration_login" checked="checked" disabled="disabled"/></label>';
			},
			SKAUTISINTEGRATION_NAME . '_modules',
			SKAUTISINTEGRATION_NAME . '_modules'
		);

		$activatedModules = (array) get_option( 'skautis_integration_activated_modules' );

		foreach ( (array) $this->modulesManager->get_all_modules() as $moduleId => $moduleLabel ) {
			add_settings_field(
				SKAUTISINTEGRATION_NAME . '_modules_' . $moduleId,
				$moduleLabel,
				function () use ( $moduleId, $moduleLabel, $activatedModules ) {
					$checked = in_array( $moduleId, $activatedModules, true );
					echo '
					<label for="' . esc_attr( $moduleId ) . '"><input name="skautis_integration_activated_modules[]" type="checkbox" id="' . esc_attr( $moduleId ) . '" value="' . esc_attr( $moduleId ) . '" ' . ( $checked ? 'checked="checked"' : '' ) . '></label>
					';
				},
				SKAUTISINTEGRATION_NAME . '_modules',
				SKAUTISINTEGRATION_NAME . '_modules'
			);
		}

		register_setting(
			SKAUTISINTEGRATION_NAME . '_modules',
			'skautis_integration_activated_modules',
			array(
				'type'         => 'string',
				'show_in_rest' => false,
			)
		);
	}

	public function print_login_page() {
		if ( ! current_user_can( Helpers::get_skautis_manager_capability() ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'skautis-integration' ) );
		}

		settings_errors();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Nastavení přihlašování', 'skautis-integration' ); ?></h1>
			<form method="POST" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
				<?php
				settings_fields( SKAUTISINTEGRATION_NAME . '_login' );
				do_settings_sections( SKAUTISINTEGRATION_NAME . '_login' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public function setup_login_fields() {
		add_settings_section(
			SKAUTISINTEGRATION_NAME . '_login',
			'',
			function () {
				echo '';
			},
			SKAUTISINTEGRATION_NAME . '_login'
		);

		add_settings_field(
			SKAUTISINTEGRATION_NAME . '_allowUsersDisconnectFromSkautis',
			__( 'Zrušení spojení se skautISem', 'skautis-integration' ),
			array( $this, 'field_allow_users_disconnect_from_skautis' ),
			SKAUTISINTEGRATION_NAME . '_login',
			SKAUTISINTEGRATION_NAME . '_login'
		);

		if ( Services::get_services_container()['modulesManager']->is_module_activated( Register::get_id() ) ) {
			add_settings_field(
				SKAUTISINTEGRATION_NAME . '_checkUserPrivilegesIfLoginBySkautis',
				__( 'Ověřování podmínek registrace', 'skautis-integration' ),
				array( $this, 'field_check_user_privileges_if_login_by_skautis' ),
				SKAUTISINTEGRATION_NAME . '_login',
				SKAUTISINTEGRATION_NAME . '_login'
			);
		}

		add_settings_field(
			SKAUTISINTEGRATION_NAME . '_login_page_url',
			__( 'Adresa stránky s přihlašováním', 'skautis-integration' ),
			array( $this, 'field_login_page_url' ),
			SKAUTISINTEGRATION_NAME . '_login',
			SKAUTISINTEGRATION_NAME . '_login'
		);

		register_setting(
			SKAUTISINTEGRATION_NAME . '_login',
			SKAUTISINTEGRATION_NAME . '_login_page_url',
			array(
				'type'              => 'string',
				'show_in_rest'      => true,
				'sanitize_callback' => function ( $url ) {
					$url = str_replace( ' ', '%20', $url );
					$url = preg_replace( '|[^a-z0-9-~+_.?=!&;,/:%@$\|*\'()\[\]\\x80-\\xff]|i', '', $url );
					$url = wp_kses_normalize_entities( $url );
					$url = str_replace( '&amp;', '&#038;', $url );
					$url = str_replace( "'", '&#039;', $url );

					flush_rewrite_rules();

					return $url;
				},
			)
		);
		register_setting(
			SKAUTISINTEGRATION_NAME . '_login',
			SKAUTISINTEGRATION_NAME . '_allowUsersDisconnectFromSkautis',
			array(
				'type'         => 'boolean',
				'show_in_rest' => false,
			)
		);

		if ( Services::get_services_container()['modulesManager']->is_module_activated( Register::get_id() ) ) {
			register_setting(
				SKAUTISINTEGRATION_NAME . '_login',
				SKAUTISINTEGRATION_NAME . '_checkUserPrivilegesIfLoginBySkautis',
				array(
					'type'         => 'boolean',
					'show_in_rest' => false,
				)
			);
		}
	}

	public function field_app_id_prod() {
		echo '<input name="skautis_integration_appid_prod" id="skautis_integration_appid_prod" type="text" value="' . esc_attr( get_option( 'skautis_integration_appid_prod' ) ) . '" class="regular-text" />';
	}

	public function field_app_id_test() {
		echo '<input name="skautis_integration_appid_test" id="skautis_integration_appid_test" type="text" value="' . esc_attr( get_option( 'skautis_integration_appid_test' ) ) . '" class="regular-text" />';
	}

	public function field_app_id_type() {
		$appIdType = get_option( 'skautis_integration_appid_type' );
		?>
		<label>
			<input type="radio" name="skautis_integration_appid_type"
				value="prod"<?php checked( 'prod' === $appIdType ); ?> />
			<span><?php esc_html_e( 'Produkční', 'skautis-integration' ); ?></span>
		</label>
		<br/>
		<label>
			<input type="radio" name="skautis_integration_appid_type"
				value="test"<?php checked( 'test' === $appIdType ); ?> />
			<span><?php esc_html_e( 'Testovací', 'skautis-integration' ); ?></span>
		</label>
		<?php
	}

	public function field_login_page_url() {
		echo esc_html( get_home_url() ) . '/<input name="' . esc_attr( SKAUTISINTEGRATION_NAME ) . '_login_page_url" id="' . esc_attr( SKAUTISINTEGRATION_NAME ) . '_login_page_url" type="text" value="' . esc_attr( get_option( SKAUTISINTEGRATION_NAME . '_login_page_url' ) ) . '" class="regular-text" placeholder="skautis/prihlaseni" />';
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

	public function field_allow_users_disconnect_from_skautis() {
		?>
		<input name="<?php echo esc_attr( SKAUTISINTEGRATION_NAME ); ?>_allowUsersDisconnectFromSkautis"
			id="skautis_integration_allowUsersDisconnectFromSkautis" type="checkbox"
			<?php checked( get_option( SKAUTISINTEGRATION_NAME . '_allowUsersDisconnectFromSkautis' ) === '1' ); ?>value="1"/>
		<div
			style="margin: 0.4em 0;"><?php esc_html_e( 'Umožní uživatelům zrušit propojení svého účtu se skautISem.', 'skautis-integration' ); ?></div>
		<em><?php esc_html_e( 'Nastavení nebude mít dopad na uživatele s úrovní administrátora.', 'skautis-integration' ); ?></em>
		<?php
	}

	public function field_check_user_privileges_if_login_by_skautis() {
		?>
		<input name="<?php echo esc_attr( SKAUTISINTEGRATION_NAME ); ?>_checkUserPrivilegesIfLoginBySkautis"
			id="skautis_integration_checkUserPrivilegesIfLoginBySkautis" type="checkbox"
			<?php checked( get_option( SKAUTISINTEGRATION_NAME . '_checkUserPrivilegesIfLoginBySkautis' ) === '1' ); ?>value="1"/>
		<div
			style="margin: 0.4em 0;"><?php esc_html_e( 'Při přihlašování uživatele přes skautIS ověřit, zda stále splňuje podmínky pro registraci.', 'skautis-integration' ); ?></div>
		<em><?php esc_html_e( 'Nastavení nebude mít dopad na uživatele s úrovní administrátora.', 'skautis-integration' ); ?></em>
		<?php
	}

	public function print_modules_page() {
		if ( ! Helpers::user_is_skautis_manager() ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'skautis-integration' ) );
		}
		settings_errors();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Moduly', 'skautis-integration' ); ?></h1>
			<form method="POST" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
				<?php
				settings_fields( SKAUTISINTEGRATION_NAME . '_modules' );
				do_settings_sections( SKAUTISINTEGRATION_NAME . '_modules' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

}
