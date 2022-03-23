<?php
namespace SkautisIntegration;

use SkautisIntegration\Services\Services;
use SkautisIntegration\Utils\Helpers;

class Skautis_Integration {

	public function __construct() {
		$this->init_hooks();

		require __DIR__ . '/vendor/scoper-autoload.php';
		require __DIR__ . '/global-functions.php';

		require __DIR__ . '/src/admin/class-admin.php';
		require __DIR__ . '/src/admin/class-settings.php';
		require __DIR__ . '/src/admin/class-users.php';
		require __DIR__ . '/src/admin/class-users-management.php';

		require __DIR__ . '/src/auth/class-transient-session-adapter.php';
		require __DIR__ . '/src/auth/class-connect-and-disconnect-wp-account.php';
		require __DIR__ . '/src/auth/class-skautis-gateway.php';
		require __DIR__ . '/src/auth/class-skautis-login.php';
		require __DIR__ . '/src/auth/class-wp-login-logout.php';

		require __DIR__ . '/src/frontend/class-frontend.php';
		require __DIR__ . '/src/frontend/class-login-form.php';

		require __DIR__ . '/src/general/class-actions.php';
		require __DIR__ . '/src/general/class-general.php';

		require __DIR__ . '/src/modules/interface-module.php';
		require __DIR__ . '/src/modules/class-modules-manager.php';
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
		require __DIR__ . '/src/rules/interface-rule.php';
		require __DIR__ . '/src/rules/class-revisions.php';
		require __DIR__ . '/src/rules/Rule/class-all.php';
		require __DIR__ . '/src/rules/Rule/class-func.php';
		require __DIR__ . '/src/rules/Rule/class-role.php';
		require __DIR__ . '/src/rules/Rule/class-membership.php';
		require __DIR__ . '/src/rules/Rule/class-qualification.php';
		require __DIR__ . '/src/rules/class-rules-init.php';
		require __DIR__ . '/src/rules/class-rules-manager.php';

		require __DIR__ . '/src/services/class-services.php';

		require __DIR__ . '/src/utils/class-helpers.php';
		require __DIR__ . '/src/utils/class-role-changer.php';

		$this->init();
	}

	protected function init_hooks() {
		add_action( 'admin_init', array( $this, 'check_version_and_possibly_deactivate_plugin' ) );

		register_activation_hook( __FILE__, array( $this, 'activation' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivation' ) );
	}

	protected function init() {
		Services::get_services_container()['general'];
		if ( is_admin() ) {
			( Services::get_services_container()['admin'] );
		} else {
			( Services::get_services_container()['frontend'] );
		}
		Services::get_services_container()['modulesManager'];
	}

	protected function is_compatible_version_of_wp() {
		if ( isset( $GLOBALS['wp_version'] ) && version_compare( $GLOBALS['wp_version'], '4.9.6', '>=' ) ) {
			return true;
		}

		return false;
	}

	protected function is_compatible_version_of_php() {
		if ( version_compare( PHP_VERSION, '7.4', '>=' ) ) {
			return true;
		}

		return false;
	}

	public function activation() {
		if ( ! $this->is_compatible_version_of_wp() ) {
			deactivate_plugins( SKAUTISINTEGRATION_PLUGIN_BASENAME );
			wp_die( esc_html__( 'Plugin skautIS integrace vyžaduje verzi WordPress 4.9.6 nebo vyšší!', 'skautis-integration' ) );
		}

		if ( ! $this->is_compatible_version_of_php() ) {
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

	public function check_version_and_possibly_deactivate_plugin() {
		if ( ! $this->is_compatible_version_of_wp() ) {
			if ( is_plugin_active( SKAUTISINTEGRATION_PLUGIN_BASENAME ) ) {
				deactivate_plugins( SKAUTISINTEGRATION_PLUGIN_BASENAME );
				Helpers::show_admin_notice( __( 'Plugin skautIS integrace vyžaduje verzi WordPress 4.8 nebo vyšší!', 'skautis-integration' ), 'warning' );
			}
		}

		if ( ! $this->is_compatible_version_of_php() ) {
			if ( is_plugin_active( SKAUTISINTEGRATION_PLUGIN_BASENAME ) ) {
				deactivate_plugins( SKAUTISINTEGRATION_PLUGIN_BASENAME );
				Helpers::show_admin_notice( __( 'Plugin skautIS integrace vyžaduje verzi PHP 7.4 nebo vyšší!', 'skautis-integration' ), 'warning' );
			}
		}
	}
}
