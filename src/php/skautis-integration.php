<?php
/**
 * Main plugin file
 *
 * Contains plugin constants and instantiates the plugin.
 *
 * @package skautis-integration
 */

/*
Plugin Name:       skautIS integration
Plugin URI:        https://github.com/skaut/skautis-integration
Description:       Integrace WordPressu se skautISem
Version:           1.1.30
Author:            Junák - český skaut
Author URI:        https://github.com/skaut
License:           GPLv3
License URI:       https://github.com/skaut/skautis-integration/blob/master/LICENSE
Text Domain:       skautis-integration
 */

namespace Skautis_Integration;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

define( 'SKAUTIS_INTEGRATION_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
// TODO: Unused?
define( 'SKAUTIS_INTEGRATION_PATH', plugin_dir_path( __FILE__ ) );
define( 'SKAUTIS_INTEGRATION_URL', plugin_dir_url( __FILE__ ) );
define( 'SKAUTIS_INTEGRATION_NAME', 'skautis-integration' );
define( 'SKAUTIS_INTEGRATION_VERSION', '1.1.30' );

require __DIR__ . '/class-skautis-integration.php';

global $skautis_integration;
$skautis_integration = new Skautis_Integration();
