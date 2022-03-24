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

namespace Skautis_Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SKAUTIS_INTEGRATION_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
// TODO: Unused?
define( 'SKAUTIS_INTEGRATION_PATH', plugin_dir_path( __FILE__ ) );
define( 'SKAUTIS_INTEGRATION_URL', plugin_dir_url( __FILE__ ) );
define( 'SKAUTIS_INTEGRATION_NAME', 'skautis-integration' );
define( 'SKAUTIS_INTEGRATION_VERSION', '1.1.25' );

require __DIR__ . '/class-skautis-integration.php';

global $skautis_integration;
$skautis_integration = new Skautis_Integration();
