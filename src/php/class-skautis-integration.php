<?php
/**
 * Contains the Skautis_Integration class.
 *
 * @package skautis-integration
 */

namespace Skautis_Integration;

use Skautis_Integration\Services\Services;
use Skautis_Integration\Utils\Helpers;

/**
 * The plugin main class.
 *
 * TODO: Rename to Main?
 */
class Skautis_Integration {

	/**
	 * Initializes the plugin.
	 */
	public function __construct() {
		self::init_hooks();

		require __DIR__ . '/vendor/scoper-autoload.php';
		require __DIR__ . '/global-functions.php';

		require __DIR__ . '/src/admin/class-admin.php';
		require __DIR__ . '/src/admin/class-settings.php';
		require __DIR__ . '/src/admin/class-settings-sections.php';
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

		require __DIR__ . '/src/modules/Register/admin/class-admin.php';
		require __DIR__ . '/src/modules/Register/admin/class-settings.php';
		require __DIR__ . '/src/modules/Register/frontend/class-login-form.php';
		require __DIR__ . '/src/modules/Register/class-register.php';
		require __DIR__ . '/src/modules/Register/class-wp-register.php';
		require __DIR__ . '/src/modules/Visibility/admin/class-admin.php';
		require __DIR__ . '/src/modules/Visibility/admin/class-block-editor.php';
		require __DIR__ . '/src/modules/Visibility/admin/class-metabox.php';
		require __DIR__ . '/src/modules/Visibility/admin/class-settings.php';
		require __DIR__ . '/src/modules/Visibility/frontend/class-block-visibility.php';
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
		require __DIR__ . '/src/utils/class-request-parameter-helpers.php';
		require __DIR__ . '/src/utils/class-role-changer.php';

		self::init();
	}

	/**
	 * Intializes all hooks used by the object.
	 *
	 * @return void
	 */
	protected static function init_hooks() {
		add_action( 'admin_init', array( self::class, 'check_version_and_maybe_deactivate' ) );

		// The hooks must be registered for the main plugin file, otherwise WordPress never runs them.
		register_activation_hook( __DIR__ . '/skautis-integration.php', array( self::class, 'activation' ) );
		register_deactivation_hook( __DIR__ . '/skautis-integration.php', array( self::class, 'deactivation' ) );
		add_action( 'wp_initialize_site', array( self::class, 'initialize_new_site' ), 200 );
	}

	/**
	 * Intializes all services used by the object.
	 *
	 * @return void
	 */
	protected static function init() {
		self::maybe_migrate_modules();
		Services::get_general();
		if ( is_admin() ) {
			( Services::get_admin() );
		} else {
			( Services::get_frontend() );
		}
		Services::get_features();
	}

	/**
	 * Converts the settings of the removed modules.
	 *
	 * The plugin used to have the Registration and Content visibility modules, which had to be activated. The content visibility is now always on and the registration has its own setting, which keeps the state of the old module.
	 *
	 * @return void
	 */
	private static function maybe_migrate_modules() {
		$activated_modules = get_option( 'skautis_integration_activated_modules' );
		if ( false === $activated_modules ) {
			return;
		}

		if ( false === get_option( SKAUTIS_INTEGRATION_NAME . '_registration_enabled' ) ) {
			update_option( SKAUTIS_INTEGRATION_NAME . '_registration_enabled', in_array( 'module_Register', (array) $activated_modules, true ) ? '1' : '0' );
		}
		self::set_default_post_types();
		delete_option( 'skautis_integration_activated_modules' );
	}

	/**
	 * Sets the post types with content visibility settings, unless they have already been set.
	 *
	 * @return void
	 */
	private static function set_default_post_types() {
		if ( false === get_option( SKAUTIS_INTEGRATION_NAME . '_modules_visibility_postTypes' ) ) {
			update_option( SKAUTIS_INTEGRATION_NAME . '_modules_visibility_postTypes', array( 'post', 'page' ) );
		}
	}

	/**
	 * Checks whether the current version of WordPress is supported by the plugin.
	 *
	 * @return bool Whether the current version of WordPress is supported.
	 */
	protected static function is_compatible_version_of_wp() {
		if ( isset( $GLOBALS['wp_version'] ) && version_compare( $GLOBALS['wp_version'], '7.0', '>=' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Checks whether the current version of PHP is supported by the plugin.
	 *
	 * @return bool Whether the current version of PHP is supported.
	 */
	protected static function is_compatible_version_of_php() {
		// @phpstan-ignore if.alwaysTrue
		if ( version_compare( PHP_VERSION, '8.3', '>=' ) ) {
			return true;
		}

		// @phpstan-ignore deadCode.unreachable
		return false;
	}

	/**
	 * Activation checks.
	 *
	 * This function runs on plugin activation. It deactivates the plugin if the current version of WordPress or PHP are not supported.
	 *
	 * @param bool $network_wide Whether the plugin is being activated for the whole network.
	 *
	 * @return void
	 */
	public static function activation( $network_wide = false ) {
		if ( ! self::is_compatible_version_of_wp() ) {
			deactivate_plugins( SKAUTIS_INTEGRATION_PLUGIN_BASENAME );
			wp_die( esc_html__( 'The SkautIS integration plugin requires WordPress 7.0 or newer!', 'skautis-integration' ) );
		}

		if ( ! self::is_compatible_version_of_php() ) {
			deactivate_plugins( SKAUTIS_INTEGRATION_PLUGIN_BASENAME );
			wp_die( esc_html__( 'The SkautIS integration plugin requires PHP 8.3 or newer!', 'skautis-integration' ) );
		}

		if ( is_multisite() && true === $network_wide ) {
			foreach ( get_sites( array( 'fields' => 'ids' ) ) as $site_id ) {
				switch_to_blog( intval( $site_id ) );
				self::set_default_options();
				restore_current_blog();
			}
		} else {
			self::set_default_options();
		}
	}

	/**
	 * Sets the default options of the current site.
	 *
	 * @return void
	 */
	private static function set_default_options() {
		if ( true !== get_option( 'skautis_rewrite_rules_need_to_flush' ) ) {
			add_option( 'skautis_rewrite_rules_need_to_flush', true );
		}

		if ( false === get_option( SKAUTIS_INTEGRATION_NAME . '_login_page_url' ) ) {
			update_option( SKAUTIS_INTEGRATION_NAME . '_login_page_url', 'skautis/prihlaseni' );
		}

		if ( false === get_option( 'skautis_integration_appid_type' ) ) {
			update_option( 'skautis_integration_appid_type', 'prod' );
		}

		if ( false === get_option( SKAUTIS_INTEGRATION_NAME . '_registration_enabled' ) ) {
			update_option( SKAUTIS_INTEGRATION_NAME . '_registration_enabled', '0' );
		}

		self::set_default_post_types();
	}

	/**
	 * Sets the default options of a new site in a network where the plugin is activated for the whole network.
	 *
	 * @param \WP_Site $site The new site.
	 *
	 * @return void
	 */
	public static function initialize_new_site( \WP_Site $site ) {
		// The same check as is_plugin_active_for_network(), which is only available in the administration.
		$network_plugins = get_site_option( 'active_sitewide_plugins', array() );
		if ( ! is_array( $network_plugins ) || ! array_key_exists( SKAUTIS_INTEGRATION_PLUGIN_BASENAME, $network_plugins ) ) {
			return;
		}

		switch_to_blog( intval( $site->blog_id ) );
		self::set_default_options();
		restore_current_blog();
	}

	/**
	 * Updates rewrite rules on plugin deactivation.
	 *
	 * This function runs on plugin activation. It deactivates the plugin if the current version of WordPress or PHP are not supported.
	 *
	 * @return void
	 */
	public static function deactivation() {
		delete_option( 'skautis_rewrite_rules_need_to_flush' );
		flush_rewrite_rules();
	}

	/**
	 * This function deactivates the plugin if the current version of WordPress or PHP are not supported.
	 *
	 * @return void
	 */
	public static function check_version_and_maybe_deactivate() {
		if ( ! self::is_compatible_version_of_wp() ) {
			if ( is_plugin_active( SKAUTIS_INTEGRATION_PLUGIN_BASENAME ) ) {
				deactivate_plugins( SKAUTIS_INTEGRATION_PLUGIN_BASENAME );
				Helpers::show_admin_notice( __( 'The SkautIS integration plugin requires WordPress 7.0 or newer!', 'skautis-integration' ), 'warning' );
			}
		}

		if ( ! self::is_compatible_version_of_php() ) {
			if ( is_plugin_active( SKAUTIS_INTEGRATION_PLUGIN_BASENAME ) ) {
				deactivate_plugins( SKAUTIS_INTEGRATION_PLUGIN_BASENAME );
				Helpers::show_admin_notice( __( 'The SkautIS integration plugin requires PHP 8.3 or newer!', 'skautis-integration' ), 'warning' );
			}
		}
	}
}
