<?php
/**
 * Plugin Name:       skautIS integration
 * Plugin URI:        https://github.com/skaut/skautis-integration
 * Description:       Integrace WordPressu se skautISem
 * Version:           1.1.5
 * Author:            David Odehnal
 * Author URI:        https://davidodehnal.cz/
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
define( 'SKAUTISINTEGRATION_VERSION', '1.1.5' );

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

		require SKAUTISINTEGRATION_PATH . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

		$this->init();
	}

	protected function initHooks() {
		add_action( 'admin_init', [ $this, 'checkVersionAndPossiblyDeactivatePlugin' ] );

		register_activation_hook( __FILE__, [ $this, 'activation' ] );
		register_deactivation_hook( __FILE__, [ $this, 'deactivation' ] );
		register_uninstall_hook( __FILE__, [ __CLASS__, 'uninstall' ] );
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
		if ( isset( $GLOBALS['wp_version'] ) && version_compare( $GLOBALS['wp_version'], '4.8', '>=' ) ) {
			return true;
		}

		return false;
	}

	protected function isCompatibleVersionOfPhp() {
		if ( version_compare( PHP_VERSION, '7.0', '>=' ) ) {
			return true;
		}

		return false;
	}

	public function activation() {
		if ( ! $this->isCompatibleVersionOfWp() ) {
			deactivate_plugins( SKAUTISINTEGRATION_PLUGIN_BASENAME );
			wp_die( __( 'Plugin skautIS integrace vyžaduje verzi WordPress 4.8 nebo vyšší!', 'skautis-integration' ) );
		}

		if ( ! $this->isCompatibleVersionOfPhp() ) {
			deactivate_plugins( SKAUTISINTEGRATION_PLUGIN_BASENAME );
			wp_die( __( 'Plugin skautIS integrace vyžaduje verzi PHP 7.0 nebo vyšší!', 'skautis-integration' ) );
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
		$options = $wpdb->get_results( $wpdb->prepare( "
SELECT `option_name`
FROM $wpdb->options
WHERE `option_name` LIKE %s OR `option_name` LIKE %s
", [ 'skautis_integration_%', SKAUTISINTEGRATION_NAME . '_%' ] ) );
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

				Helpers::showAdminNotice( esc_html__( 'Plugin skautIS integrace vyžaduje verzi PHP 7.0 nebo vyšší!', 'skautis-integration' ), 'warning' );

				if ( isset( $_GET['activate'] ) ) {
					unset( $_GET['activate'] );
				}
			}
		}
	}
}

global $skautisIntegration;
$skautisIntegration = new SkautisIntegration();
