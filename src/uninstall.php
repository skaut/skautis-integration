<?php
/**
 * Plugin uninstallation file.
 *
 * Deletes all the plugin options so that the database is clean after uninstall.
 *
 * @package skautis-integration
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die( 'Die, die, die!' );
}

// defined in skautis-integration.php
delete_option( 'skautis_rewrite_rules_need_to_flush' );

// defined in src/admin/class-settings.php
delete_option( 'skautis_integration_appid_prod' );
delete_option( 'skautis_integration_appid_test' );
delete_option( 'skautis_integration_appid_type' );
delete_option( 'skautis_integration_activated_modules' );
delete_option( 'skautis-integration_login_page_url' );
delete_option( 'skautis-integration_allowUsersDisconnectFromSkautis' );
delete_option( 'skautis-integration_checkUserPrivilegesIfLoginBySkautis' );

// defined in src/modules/Register/admin/class-settings.php
delete_option( 'skautis-integration_modules_register_defaultwpRole' );
delete_option( 'skautis-integration_modules_register_notifications' );
delete_option( 'skautis-integration_modules_register_rules' );

// defined in src/modules/Shortcodes/admin/class-settings.php
delete_option( 'skautis-integration_modules_shortcodes_visibilityMode' );

// defined in src/modules/Visibility/admin/class-settings.php
delete_option( 'skautis-integration_modules_visibility_postTypes' );
delete_option( 'skautis-integration_modules_visibility_visibilityMode' );
delete_option( 'skautis-integration_modules_visibility_includeChildren' );

flush_rewrite_rules();
