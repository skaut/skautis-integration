<?php

namespace SkautisIntegration;

use SkautisIntegration\Services\Services;

if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'SKAUTISINTEGRATION_NAME', 'skautis-integration' );
define( 'SKAUTISINTEGRATION_VERSION', '1.0' );
define( 'SKAUTISINTEGRATION_PATH', plugin_dir_path( __FILE__ ) );

require __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

Services::getServicesContainer()['general'];
if ( is_admin() ) {
	( Services::getServicesContainer()['admin'] );
} else {
	( Services::getServicesContainer()['frontend'] );
}
Services::getServicesContainer()['modulesManager'];