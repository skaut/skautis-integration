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
 * Version:           1.0
 * Author:            David Odehnal
 * Author URI:        https://davidodehnal.cz/
 * Text Domain:       skautis-integration
 * Domain Path:       /languages
 */

namespace SkautisIntegration;

use SkautisIntegration\Services\Services;
use SkautisIntegration\Utils\Helpers;

if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'SKAUTISINTEGRATION_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'SKAUTISINTEGRATION_PATH', plugin_dir_path( __FILE__ ) );
define( 'SKAUTISINTEGRATION_NAME', 'skautis-integration' );
define( 'SKAUTISINTEGRATION_VERSION', '1.0' );

require SKAUTISINTEGRATION_PATH . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

class SkautisIntegration {

	public function __construct() {
		$this->initHooks();

		// if incompatible version of WP or deactivating plugin right now => don´t init
		if ( ! $this->isCompatibleVersionOfWp() ||
		     ( isset( $_GET['action'], $_GET['plugin'] ) &&
		       'deactivate' == $_GET['action'] &&
		       SKAUTISINTEGRATION_PLUGIN_BASENAME == $_GET['plugin'] )
		) {
			return;
		}
		$this->init();
	}

	private function initHooks() {
		add_action( 'admin_init', [ $this, 'checkVersionAndPossiblyDeactivatePlugin' ] );

		register_activation_hook( __FILE__, [ $this, 'activation' ] );
		register_deactivation_hook( __FILE__, [ $this, 'deactivation' ] );
		register_uninstall_hook( __FILE__, [ __CLASS__, 'uninstall' ] );
	}

	private function init() {
		Services::getServicesContainer()['general'];
		if ( is_admin() ) {
			( Services::getServicesContainer()['admin'] );
		} else {
			( Services::getServicesContainer()['frontend'] );
		}
		Services::getServicesContainer()['modulesManager'];
	}

	private function isCompatibleVersionOfWp() {
		if ( isset( $GLOBALS['wp_version'] ) && version_compare( $GLOBALS['wp_version'], '4.8', '>=' ) ) {
			return true;
		}

		return false;
	}

	public function activation() {
		if ( ! $this->isCompatibleVersionOfWp() ) {
			deactivate_plugins( SKAUTISINTEGRATION_PLUGIN_BASENAME );
			wp_die( __( 'Plugin skautIS integrace vyžaduje verzi WordPress 4.8 nebo vyšší!', 'skautis-integration' ) );
		}

		if ( ! get_option( 'skautis_rewrite_rules_need_to_flush' ) ) {
			add_option( 'skautis_rewrite_rules_need_to_flush', true );
		}

		if ( ! get_option( 'skautis_integration_login_page_url' ) ) {
			update_option( 'skautis_integration_login_page_url', 'skautis/prihlaseni' );
		}

		Rules\RulesInit::registerCapabilitiesToRole( 'administrator' );
	}

	public function deactivation() {
		delete_option( 'skautis_rewrite_rules_need_to_flush' );
		flush_rewrite_rules();

		Rules\RulesInit::unregisterCapabilitiesFromRole( 'administrator' );
	}

	public static function uninstall() {
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
	}
}

global $skautisIntegration;
$skautisIntegration = new SkautisIntegration();