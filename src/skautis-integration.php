<?php
/**
 * Plugin Name:       skautIS integration
 * Plugin URI:        https://github.com/skaut/skautis-integration
 * Description:       Integrace WordPressu se skautISem
 * Version:           1.1.23
 * Author:            Junák - český skaut
 * Author URI:        https://github.com/skaut
 * Text Domain:       skautis-integration
 */

namespace SkautisIntegration;

use SkautisIntegration\Services\Services;
use SkautisIntegration\Utils\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SKAUTISINTEGRATION_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'SKAUTISINTEGRATION_PATH', plugin_dir_path( __FILE__ ) );
define( 'SKAUTISINTEGRATION_URL', plugin_dir_url( __FILE__ ) );
define( 'SKAUTISINTEGRATION_NAME', 'skautis-integration' );
define( 'SKAUTISINTEGRATION_VERSION', '1.1.23' );

class SkautisIntegration {

	public function __construct() {
		$this->initHooks();

		// if incompatible version of WP / PHP or deactivating plugin right now => don´t init
		if ( ! $this->isCompatibleVersionOfWp() ||
			 ! $this->isCompatibleVersionOfPhp() ||
			 ( isset( $_GET['action'], $_GET['plugin'] ) &&
			   'deactivate' == $_GET['action'] &&
			   SKAUTISINTEGRATION_PLUGIN_BASENAME == $_GET['plugin'] )
		) {
			return;
		}

		require __DIR__ . '/vendor/scoper-autoload.php';
		require __DIR__ . '/globalFunctions.php';

		require __DIR__ . '/src/admin/Admin.php';
		require __DIR__ . '/src/admin/Settings.php';
		require __DIR__ . '/src/admin/Users.php';
		require __DIR__ . '/src/admin/UsersManagement.php';

		require __DIR__ . '/src/auth/TransientSessionAdapter.php';
		require __DIR__ . '/src/auth/ConnectAndDisconnectWpAccount.php';
		require __DIR__ . '/src/auth/SkautisGateway.php';
		require __DIR__ . '/src/auth/SkautisLogin.php';
		require __DIR__ . '/src/auth/WpLoginLogout.php';

		require __DIR__ . '/src/frontend/Frontend.php';
		require __DIR__ . '/src/frontend/LoginForm.php';

		require __DIR__ . '/src/general/Actions.php';
		require __DIR__ . '/src/general/General.php';

		require __DIR__ . '/src/modules/IModule.php';
		require __DIR__ . '/src/modules/ModulesManager.php';
		require __DIR__ . '/src/modules/Register/admin/Admin.php';
		require __DIR__ . '/src/modules/Register/admin/Settings.php';
		require __DIR__ . '/src/modules/Register/frontend/Frontend.php';
		require __DIR__ . '/src/modules/Register/frontend/LoginForm.php';
		require __DIR__ . '/src/modules/Register/Register.php';
		require __DIR__ . '/src/modules/Register/WpRegister.php';
		require __DIR__ . '/src/modules/Shortcodes/admin/Admin.php';
		require __DIR__ . '/src/modules/Shortcodes/admin/Settings.php';
		require __DIR__ . '/src/modules/Shortcodes/frontend/Frontend.php';
		require __DIR__ . '/src/modules/Shortcodes/Shortcodes.php';
		require __DIR__ . '/src/modules/Visibility/admin/Admin.php';
		require __DIR__ . '/src/modules/Visibility/admin/Metabox.php';
		require __DIR__ . '/src/modules/Visibility/admin/Settings.php';
		require __DIR__ . '/src/modules/Visibility/frontend/Frontend.php';
		require __DIR__ . '/src/modules/Visibility/Visibility.php';

		require __DIR__ . '/src/repository/Users.php';

		require __DIR__ . '/src/rules/admin/Admin.php';
		require __DIR__ . '/src/rules/admin/Columns.php';
		require __DIR__ . '/src/rules/IRule.php';
		require __DIR__ . '/src/rules/Revisions.php';
		require __DIR__ . '/src/rules/Rule/All.php';
		require __DIR__ . '/src/rules/Rule/Func.php';
		require __DIR__ . '/src/rules/Rule/Role.php';
		require __DIR__ . '/src/rules/Rule/Membership.php';
		require __DIR__ . '/src/rules/Rule/Qualification.php';
		require __DIR__ . '/src/rules/RulesInit.php';
		require __DIR__ . '/src/rules/RulesManager.php';

		require __DIR__ . '/src/services/services.php';

		require __DIR__ . '/src/utils/Helpers.php';
		require __DIR__ . '/src/utils/RoleChanger.php';

		$this->init();
	}

	protected function initHooks() {
		add_action( 'admin_init', array( $this, 'checkVersionAndPossiblyDeactivatePlugin' ) );

		register_activation_hook( __FILE__, array( $this, 'activation' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivation' ) );
		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );
	}

	protected function init() {
		Services::getServicesContainer()['general'];
		if ( is_admin() ) {
			( Services::getServicesContainer()['admin'] );
		} else {
			( Services::getServicesContainer()['frontend'] );
		}
		Services::getServicesContainer()['modulesManager'];
	}

	protected function isCompatibleVersionOfWp() {
		if ( isset( $GLOBALS['wp_version'] ) && version_compare( $GLOBALS['wp_version'], '4.9.6', '>=' ) ) {
			return true;
		}

		return false;
	}

	protected function isCompatibleVersionOfPhp() {
		if ( version_compare( PHP_VERSION, '7.4', '>=' ) ) {
			return true;
		}

		return false;
	}

	public function activation() {
		if ( ! $this->isCompatibleVersionOfWp() ) {
			deactivate_plugins( SKAUTISINTEGRATION_PLUGIN_BASENAME );
			wp_die( esc_html__( 'Plugin skautIS integrace vyžaduje verzi WordPress 4.9.6 nebo vyšší!', 'skautis-integration' ) );
		}

		if ( ! $this->isCompatibleVersionOfPhp() ) {
			deactivate_plugins( SKAUTISINTEGRATION_PLUGIN_BASENAME );
			wp_die( esc_html__( 'Plugin skautIS integrace vyžaduje verzi PHP 7.4 nebo vyšší!', 'skautis-integration' ) );
		}

		if ( ! get_option( 'skautis_rewrite_rules_need_to_flush' ) ) {
			add_option( 'skautis_rewrite_rules_need_to_flush', true );
		}

		if ( ! get_option( 'skautis_integration_login_page_url' ) ) {
			update_option( 'skautis_integration_login_page_url', 'skautis/prihlaseni' );
		}

		if ( ! get_option( 'skautis_integration_appid_type' ) ) {
			update_option( 'skautis_integration_appid_type', 'prod' );
		}
	}

	public function deactivation() {
		delete_option( 'skautis_rewrite_rules_need_to_flush' );
		flush_rewrite_rules();
	}

	public static function uninstall() {
		global $wpdb;
		$options = $wpdb->get_results(
			$wpdb->prepare(
				"
SELECT `option_name`
FROM $wpdb->options
WHERE `option_name` LIKE %s OR `option_name` LIKE %s
",
				array( 'skautis_integration_%', SKAUTISINTEGRATION_NAME . '_%' )
			)
		);
		foreach ( $options as $option ) {
			delete_option( $option->option_name );
		}

		delete_option( 'skautis_rewrite_rules_need_to_flush' );

		flush_rewrite_rules();

		return true;
	}

	public function checkVersionAndPossiblyDeactivatePlugin() {
		if ( ! $this->isCompatibleVersionOfWp() ) {
			if ( is_plugin_active( SKAUTISINTEGRATION_PLUGIN_BASENAME ) ) {
				deactivate_plugins( SKAUTISINTEGRATION_PLUGIN_BASENAME );

				Helpers::showAdminNotice( esc_html__( 'Plugin skautIS integrace vyžaduje verzi WordPress 4.8 nebo vyšší!', 'skautis-integration' ), 'warning' );

				if ( isset( $_GET['activate'] ) ) {
					unset( $_GET['activate'] );
				}
			}
		}

		if ( ! $this->isCompatibleVersionOfPhp() ) {
			if ( is_plugin_active( SKAUTISINTEGRATION_PLUGIN_BASENAME ) ) {
				deactivate_plugins( SKAUTISINTEGRATION_PLUGIN_BASENAME );

				Helpers::showAdminNotice( esc_html__( 'Plugin skautIS integrace vyžaduje verzi PHP 7.4 nebo vyšší!', 'skautis-integration' ), 'warning' );

				if ( isset( $_GET['activate'] ) ) {
					unset( $_GET['activate'] );
				}
			}
		}
	}
}

global $skautisIntegration;
$skautisIntegration = new SkautisIntegration();
