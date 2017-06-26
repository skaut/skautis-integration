<?php
/**
 * @link              https://davidodehnal.cz/
 * @since             0.1
 * @package           skautis-integration
 *
 * @wordpress-plugin
 * Plugin Name:       skautIS integrace
 * Plugin URI:        https://davidodehnal.cz/
 * Description:       Integrace WordPressu se skautISem
 * Version:           0.1
 * Author:            David Odehnal
 * Author URI:        https://davidodehnal.cz/
 * Text Domain:       skautis-integration
 * Domain Path:       /languages
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'SKAUTISINTEGRATION_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

register_activation_hook( __FILE__, function () {
	if ( ! get_option( 'skautis_rewrite_rules_need_to_flush' ) ) {
		add_option( 'skautis_rewrite_rules_need_to_flush', true );
	}

	if ( ! get_option( 'skautis_integration_login_page_url' ) ) {
		update_option( 'skautis_integration_login_page_url', 'skautis/prihlaseni' );
	}

	require_once plugin_dir_path( __FILE__ ) . 'src/rules/RulesInit.php';
	\SkautisIntegration\Rules\RulesInit::registerCapabilitiesToRole( 'administrator' );

} );

register_deactivation_hook( __FILE__, function () {
	delete_option( 'skautis_rewrite_rules_need_to_flush' );
	flush_rewrite_rules();

	require_once plugin_dir_path( __FILE__ ) . 'src/rules/RulesInit.php';
	\SkautisIntegration\Rules\RulesInit::unregisterCapabilitiesFromRole( 'administrator' );
} );

register_uninstall_hook( __FILE__, 'skautisIntegrationUninstall' );

function skautisIntegrationUninstall() {
	if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
		exit;
	}
}

function runSkautisIntegration() {

	// if deactivating plugin right now => do nothing
	if ( isset( $_GET['action'], $_GET['plugin'] ) && 'deactivate' == $_GET['action'] && plugin_basename( __FILE__ ) == $_GET['plugin'] ) {
		return;
	}

	// load translates
	add_action( 'plugins_loaded', function () {
		load_plugin_textdomain(
			'skautis-integration',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	} );

	require_once plugin_dir_path( __FILE__ ) . 'init.php';
}

runSkautisIntegration();