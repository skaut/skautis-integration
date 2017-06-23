<?php

namespace SkautisIntegration\Admin;

use SkautisIntegration\Auth\SkautisGateway;
use SkautisIntegration\Modules\ModulesManager;
use SkautisIntegration\Utils\Helpers;
use SkautisIntegration\Services\Services;
use SkautisIntegration\Modules\Register\Register;

final class Settings {

	private $modulesManager;
	private $adminDirUrl = '';

	public function __construct( ModulesManager $modulesManager ) {
		$this->modulesManager = $modulesManager;
		$this->adminDirUrl    = plugin_dir_url( __FILE__ ) . 'public/';
		$this->initHooks();
	}

	private function initHooks() {
		add_filter( 'plugin_action_links_' . SKAUTISINTEGRATION_PLUGIN_BASENAME, [
			$this,
			'addSettingsLinkToPluginsTable'
		] );

		add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', [ $this, 'setupSettingPage' ], 5 );
		add_action( 'admin_init', [ $this, 'setupSettingFields' ] );
		add_action( 'admin_init', [ $this, 'setupLoginFields' ] );

		$this->checkIfAppIdIsSetAndShowNotices();
	}

	private function checkIfAppIdIsSetAndShowNotices() {
		$envType = get_option( 'skautis_integration_appid_type' );
		if ( $envType === SkautisGateway::PROD_ENV ) {
			if ( ! get_option( 'skautis_integration_appid_production' ) ) {
				Helpers::showAdminNotice( sprintf( __( 'Zadejte v <a href="%1$s">nastavení</a> pluginu APP ID produkční verze SkautISu', 'skautis-integration' ), admin_url( 'admin.php?page=' . SKAUTISINTEGRATION_NAME ) ), 'warning', 'toplevel_page_' . SKAUTISINTEGRATION_NAME );
			}
		} else if ( $envType === SkautisGateway::TEST_ENV ) {
			if ( ! get_option( 'skautis_integration_appid_test' ) ) {
				Helpers::showAdminNotice( sprintf( __( 'Zadejte v <a href="%1$s">nastavení</a> pluginu APP ID testovací verze SkautISu', 'skautis-integration' ), admin_url( 'admin.php?page=' . SKAUTISINTEGRATION_NAME ) ), 'warning', 'toplevel_page_' . SKAUTISINTEGRATION_NAME );
			}
		} else {
			Helpers::showAdminNotice( sprintf( __( 'Vyberte v <a href="%1$s">nastavení</a> pluginu typ prostředí SkautISu', 'skautis-integration' ), admin_url( 'admin.php?page=' . SKAUTISINTEGRATION_NAME ) ), 'warning', 'toplevel_page_' . SKAUTISINTEGRATION_NAME );
		}
	}

	public function addSettingsLinkToPluginsTable( array $links = [] ) {
		$mylinks = [
			'<a href="' . admin_url( 'admin.php?page=skautis-integration' ) . '">' . __( 'Settings' ) . '</a>',
		];

		return array_merge( $links, $mylinks );
	}

	public function setupSettingPage() {
		add_menu_page(
			__( 'Obecné', 'skautis-integration' ),
			__( 'SkautIS', 'skautis-integration' ),
			'manage_options',
			SKAUTISINTEGRATION_NAME,
			[ $this, 'printSettingPage' ],
			$this->adminDirUrl . 'img/lilie.png'
		);

		add_submenu_page(
			SKAUTISINTEGRATION_NAME,
			__( 'Obecné', 'skautis-integration' ),
			__( 'Obecné', 'skautis-integration' ),
			'manage_options',
			SKAUTISINTEGRATION_NAME,
			[ $this, 'printSettingPage' ]
		);

		add_submenu_page(
			SKAUTISINTEGRATION_NAME,
			__( 'Přihlašování', 'skautis-integration' ),
			__( 'Přihlašování', 'skautis-integration' ),
			'manage_options',
			SKAUTISINTEGRATION_NAME . '_login',
			[ $this, 'printLoginPage' ]
		);

		add_submenu_page(
			SKAUTISINTEGRATION_NAME,
			__( 'Moduly', 'skautis-integration' ),
			__( 'Moduly', 'skautis-integration' ),
			'manage_options',
			SKAUTISINTEGRATION_NAME . '_modules',
			[ $this, 'printModulesPage' ]
		);
	}

	public function printSettingPage() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		settings_errors();
		?>
		<div class="wrap">
			<h1><?php _e( 'Nastavení propojení se SkautISem', 'skautis-integration' ); ?></h1>
			<form method="POST" action="<?php echo admin_url( 'options.php' ); ?>">
				<?php settings_fields( SKAUTISINTEGRATION_NAME );
				do_settings_sections( SKAUTISINTEGRATION_NAME );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public function setupSettingFields() {
		add_settings_section(
			'skautis_integration_setting',
			__( 'APP ID', 'skautis-integration' ),
			function () {
				echo '';
			},
			SKAUTISINTEGRATION_NAME
		);

		add_settings_field(
			'skautis_integration_appid_prod',
			__( 'APP ID produkční verze', 'skautis-integration' ),
			[ $this, 'fieldAppIdProd' ],
			SKAUTISINTEGRATION_NAME,
			'skautis_integration_setting'
		);

		add_settings_field(
			'skautis_integration_appid_test',
			__( 'APP ID testovací verze', 'skautis-integration' ),
			[ $this, 'fieldAppIdTest' ],
			SKAUTISINTEGRATION_NAME,
			'skautis_integration_setting'
		);

		add_settings_field(
			'skautis_integration_appid_type',
			__( 'Vyberte aktivní APP ID', 'skautis-integration' ),
			[ $this, 'fieldAppIdType' ],
			SKAUTISINTEGRATION_NAME,
			'skautis_integration_setting'
		);

		register_setting( SKAUTISINTEGRATION_NAME, 'skautis_integration_appid_prod' );
		register_setting( SKAUTISINTEGRATION_NAME, 'skautis_integration_appid_test' );
		register_setting( SKAUTISINTEGRATION_NAME, 'skautis_integration_appid_type' );

		add_settings_section(
			SKAUTISINTEGRATION_NAME . '_modules',
			__( 'Dostupné moduly', 'skautis-integration' ),
			function () {
				echo '';
			},
			SKAUTISINTEGRATION_NAME . '_modules'
		);

		$activatedModules = (array) get_option( 'skautis_integration_activated_modules' );

		foreach ( (array) $this->modulesManager->getAllModules() as $moduleId => $moduleLabel ) {
			add_settings_field(
				'skautis_integration_' . $moduleId,
				$moduleLabel,
				function () use ( $moduleId, $moduleLabel, $activatedModules ) {
					$checked = '';
					if ( in_array( $moduleId, $activatedModules ) ) {
						$checked = 'checked="checked"';
					}
					echo '
					<label for="' . $moduleId . '"><input name="skautis_integration_activated_modules[]" type="checkbox" id="' . $moduleId . '" value="' . $moduleId . '" ' . $checked . '></label>
					';
				},
				SKAUTISINTEGRATION_NAME . '_modules',
				SKAUTISINTEGRATION_NAME . '_modules'
			);
		}
		register_setting( SKAUTISINTEGRATION_NAME . '_modules', 'skautis_integration_activated_modules' );
	}

	public function printLoginPage() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		if ( ! empty( $_GET['settings-updated'] ) ) {
			flush_rewrite_rules();
		}

		settings_errors();
		?>
		<div class="wrap">
			<h1><?php _e( 'Nastavení přihlašování', 'skautis-integration' ); ?></h1>
			<form method="POST" action="<?php echo admin_url( 'options.php' ); ?>">
				<?php settings_fields( SKAUTISINTEGRATION_NAME . '_login' );
				do_settings_sections( SKAUTISINTEGRATION_NAME . '_login' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public function setupLoginFields() {
		add_settings_section(
			SKAUTISINTEGRATION_NAME . '_login',
			'',
			function () {
				echo '';
			},
			SKAUTISINTEGRATION_NAME . '_login'
		);

		add_settings_field(
			SKAUTISINTEGRATION_NAME . '_login_page_url',
			__( 'Adresa stránky s přihlašováním', 'skautis-integration' ),
			[ $this, 'fieldLoginPageUrl' ],
			SKAUTISINTEGRATION_NAME . '_login',
			SKAUTISINTEGRATION_NAME . '_login'
		);

		add_settings_field(
			SKAUTISINTEGRATION_NAME . '_allowUsersDisconnectFromSkautis',
			__( 'Umožnit uživatelům zrušit propojení účtu se SkautISem', 'skautis-integration' ),
			[ $this, 'fieldAllowUsersDisconnectFromSkautis' ],
			SKAUTISINTEGRATION_NAME . '_login',
			SKAUTISINTEGRATION_NAME . '_login'
		);

		if ( Services::getServicesContainer()['modulesManager']->isModuleActivated( Register::getId() ) ) {
			add_settings_field(
				SKAUTISINTEGRATION_NAME . '_checkUserPrivilegesIfLoginBySkautis',
				__( 'Při přihlašování uživatele přes SkautIS ověřit, zda stále splňuje podmínky pro registraci', 'skautis-integration' ),
				[ $this, 'fieldcheckUserPrivilegesIfLoginBySkautis' ],
				SKAUTISINTEGRATION_NAME . '_login',
				SKAUTISINTEGRATION_NAME . '_login'
			);
		}

		register_setting( SKAUTISINTEGRATION_NAME . '_login', SKAUTISINTEGRATION_NAME . '_login_page_url' );
		register_setting( SKAUTISINTEGRATION_NAME . '_login', SKAUTISINTEGRATION_NAME . '_allowUsersDisconnectFromSkautis' );

		if ( Services::getServicesContainer()['modulesManager']->isModuleActivated( Register::getId() ) ) {
			register_setting( SKAUTISINTEGRATION_NAME . '_login', SKAUTISINTEGRATION_NAME . '_checkUserPrivilegesIfLoginBySkautis' );
		}
	}

	public function fieldAppIdProd() {
		echo '<input name="skautis_integration_appid_prod" id="skautis_integration_appid_prod" type="text" value="' . get_option( 'skautis_integration_appid_prod', false ) . '" class="regular-text" />';
	}

	public function fieldAppIdTest() {
		echo '<input name="skautis_integration_appid_test" id="skautis_integration_appid_test" type="text" value="' . get_option( 'skautis_integration_appid_test', false ) . '" class="regular-text" />';
	}

	public function fieldAppIdType() {
		$options = get_option( 'skautis_integration_appid_type' );
		?>
		<label>
			<input type="radio" name="skautis_integration_appid_type"
			       value="prod"<?php checked( 'prod' == $options ); ?> />
			<span><?php _e( 'Produkční', 'skautis-integration' ); ?></span>
		</label>
		<br/>
		<label>
			<input type="radio" name="skautis_integration_appid_type"
			       value="test"<?php checked( 'test' == $options ); ?> />
			<span><?php _e( 'Testovací', 'skautis-integration' ); ?></span>
		</label>
		<?php
	}

	public function fieldLoginPageUrl() {
		echo get_home_url() . '/<input name="' . SKAUTISINTEGRATION_NAME . '_login_page_url" id="' . SKAUTISINTEGRATION_NAME . '_login_page_url" type="text" value="' . get_option( SKAUTISINTEGRATION_NAME . '_login_page_url', false ) . '" class="regular-text" placeholder="skautis/prihlaseni" />';
		?>
		<br/>
		<em><?php _e( 'Pro vlastní vzhled přihlašovací stránky přidejte do složky aktivní šablony složku "skautis" a do ní soubor
			"login.php".', 'skautis-integration' ); ?></em>
		<br/>
		<em><?php _e( 'V souboru login.php pak můžete volat globální funkce: getSkautisLoginUrl(), getSkautisLogoutUrl(), getSkautisRegisterUrl(),
			isUserLoggedInSkautis()', 'skautis-integration' ); ?></em>
		<br/>
		<em><?php _e( 'Příklad kódu v souboru login.php:', 'skautis-integration' ); ?></em>
		<pre>
<?php echo htmlspecialchars( '
<?php get_header(); ?>

<?php
if ( ! isUserLoggedInSkautis() ) {
?>
	<div class="wp-core-ui" style="text-align: center;">
	<a class="button button-primary button-hero pic-lilie" href="<?php echo getSkautisRegisterUrl(); ?>">
		Přihlásit se přes SkautIS
	</a>
	</div>
<?php
} else {
?>
	<div style="text-align: center;">
		<strong>Jste přihlášeni ve SkautISu</strong><br/>
		<a class="button" href="<?php echo getSkautisLogoutUrl(); ?>">
			Odhlásit se ze SkautISu
		</a>
	</div>
<?php
}
?>

<?php get_sidebar(); ?>
<?php get_footer(); ?>
' ); ?>
		</pre>
		<?php
	}

	public function fieldAllowUsersDisconnectFromSkautis() {
		?>
		<em><?php _e( 'Nastavení nebude mít dopad na uživatele v roli administrátora.', 'skautis-integration' ); ?></em>
		<br/>
		<input name="<?php echo SKAUTISINTEGRATION_NAME; ?>_allowUsersDisconnectFromSkautis"
		       id="skautis_integration_allowUsersDisconnectFromSkautis" type="checkbox"
		       <?php checked( get_option( SKAUTISINTEGRATION_NAME . '_allowUsersDisconnectFromSkautis' ) == '1' ); ?>value="1"/>
		<?php
	}

	public function fieldcheckUserPrivilegesIfLoginBySkautis() {
		?>
		<em><?php _e( 'Nastavení nebude mít dopad na uživatele v roli administrátora.', 'skautis-integration' ); ?></em>
		<br/>
		<input name="<?php echo SKAUTISINTEGRATION_NAME; ?>_checkUserPrivilegesIfLoginBySkautis"
		       id="skautis_integration_checkUserPrivilegesIfLoginBySkautis" type="checkbox"
		       <?php checked( get_option( SKAUTISINTEGRATION_NAME . '_checkUserPrivilegesIfLoginBySkautis' ) == '1' ); ?>value="1"/>
		<?php
	}

	public function printModulesPage() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		settings_errors();
		?>
		<div class="wrap">
			<h1><?php _e( 'Moduly', 'skautis-integration' ); ?></h1>
			<form method="POST" action="<?php echo admin_url( 'options.php' ); ?>">
				<?php settings_fields( SKAUTISINTEGRATION_NAME . '_modules' );
				do_settings_sections( SKAUTISINTEGRATION_NAME . '_modules' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

}
