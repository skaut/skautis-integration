<?php
/**
 * Plugin Name:       skautIS integration
 * Plugin URI:        https://github.com/skaut/skautis-integration
 * Description:       Integrace WordPressu se skautISem
 * Version:           1.1.25
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
define( 'SKAUTISINTEGRATION_VERSION', '1.1.25' );

class SkautisIntegration {

	public function __construct() {
		$this->initHooks();

		require __DIR__ . '/vendor/scoper-autoload.php';
		require __DIR__ . '/global-functions.php';

		require __DIR__ . '/src/admin/class-admin.php';
		require __DIR__ . '/src/admin/class-settings.php';
		require __DIR__ . '/src/admin/class-users.php';
		require __DIR__ . '/src/admin/class-users-management.php';

		require __DIR__ . '/src/auth/class-transient-session-adapter.php';
		require __DIR__ . '/src/auth/ConnectAndDisconnectWpAccount.php';
		require __DIR__ . '/src/auth/SkautisGateway.php';
		require __DIR__ . '/src/auth/class-skautis-login.php';
		require __DIR__ . '/src/auth/class-wp-login-logout.php';

		require __DIR__ . '/src/frontend/class-frontend.php';
		require __DIR__ . '/src/frontend/class-login-form.php';

		require __DIR__ . '/src/general/class-actions.php';
		require __DIR__ . '/src/general/class-general.php';

		require __DIR__ . '/src/modules/IModule.php';
		require __DIR__ . '/src/modules/ModulesManager.php';
		require __DIR__ . '/src/modules/Register/admin/class-admin.php';
		require __DIR__ . '/src/modules/Register/admin/class-settings.php';
		require __DIR__ . '/src/modules/Register/frontend/class-frontend.php';
		require __DIR__ . '/src/modules/Register/frontend/class-login-form.php';
		require __DIR__ . '/src/modules/Register/class-register.php';
		require __DIR__ . '/src/modules/Register/class-wp-register.php';
		require __DIR__ . '/src/modules/Shortcodes/admin/class-admin.php';
		require __DIR__ . '/src/modules/Shortcodes/admin/class-settings.php';
		require __DIR__ . '/src/modules/Shortcodes/frontend/class-frontend.php';
		require __DIR__ . '/src/modules/Shortcodes/class-shortcodes.php';
		require __DIR__ . '/src/modules/Visibility/admin/class-admin.php';
		require __DIR__ . '/src/modules/Visibility/admin/class-metabox.php';
		require __DIR__ . '/src/modules/Visibility/admin/class-settings.php';
		require __DIR__ . '/src/modules/Visibility/frontend/class-frontend.php';
		require __DIR__ . '/src/modules/Visibility/class-visibility.php';

		require __DIR__ . '/src/repository/class-users.php';

		require __DIR__ . '/src/rules/admin/class-admin.php';
		require __DIR__ . '/src/rules/admin/class-columns.php';
		require __DIR__ . '/src/rules/IRule.php';
		require __DIR__ . '/src/rules/class-revisions.php';
		require __DIR__ . '/src/rules/Rule/class-all.php';
		require __DIR__ . '/src/rules/Rule/class-func.php';
		require __DIR__ . '/src/rules/Rule/class-role.php';
		require __DIR__ . '/src/rules/Rule/class-membership.php';
		require __DIR__ . '/src/rules/Rule/class-qualification.php';
		require __DIR__ . '/src/rules/RulesInit.php';
		require __DIR__ . '/src/rules/class-rules-manager.php';

		require __DIR__ . '/src/services/class-services.php';

		require __DIR__ . '/src/utils/class-helpers.php';
		require __DIR__ . '/src/utils/RoleChanger.php';

		$this->init();
	}

	protected function initHooks() {
		add_action( 'admin_init', array( $this, 'checkVersionAndPossiblyDeactivatePlugin' ) );

		register_activation_hook( __FILE__, array( $this, 'activation' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivation' ) );
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

	public function checkVersionAndPossiblyDeactivatePlugin() {
		if ( ! $this->isCompatibleVersionOfWp() ) {
			if ( is_plugin_active( SKAUTISINTEGRATION_PLUGIN_BASENAME ) ) {
				deactivate_plugins( SKAUTISINTEGRATION_PLUGIN_BASENAME );
				Helpers::showAdminNotice( __( 'Plugin skautIS integrace vyžaduje verzi WordPress 4.8 nebo vyšší!', 'skautis-integration' ), 'warning' );
			}
		}

		if ( ! $this->isCompatibleVersionOfPhp() ) {
			if ( is_plugin_active( SKAUTISINTEGRATION_PLUGIN_BASENAME ) ) {
				deactivate_plugins( SKAUTISINTEGRATION_PLUGIN_BASENAME );
				Helpers::showAdminNotice( __( 'Plugin skautIS integrace vyžaduje verzi PHP 7.4 nebo vyšší!', 'skautis-integration' ), 'warning' );
			}
		}
	}
}

global $skautisIntegration;
$skautisIntegration = new SkautisIntegration();
