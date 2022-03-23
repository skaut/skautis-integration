<?php

declare( strict_types=1 );

namespace SkautisIntegration\Modules\Visibility;

use SkautisIntegration\Auth\WP_Login_Logout;
use SkautisIntegration\Modules\Module;
use SkautisIntegration\Rules\Rules_Manager;
use SkautisIntegration\Auth\Skautis_Login;
use SkautisIntegration\Modules\Visibility\Admin\Admin;
use SkautisIntegration\Modules\Visibility\Frontend\Frontend;

final class Visibility implements Module {

	const REGISTER_ACTION = 'visibility';

	private $rulesManager;
	private $skautisLogin;
	private $wpLoginLogout;
	private $frontend;

	public static $id = 'module_Visibility';

	public function __construct( Rules_Manager $rulesManager, Skautis_Login $skautisLogin, WP_Login_Logout $wpLoginLogout ) {
		$this->rulesManager  = $rulesManager;
		$this->skautisLogin  = $skautisLogin;
		$this->wpLoginLogout = $wpLoginLogout;
		$postTypes           = (array) get_option( SKAUTISINTEGRATION_NAME . '_modules_visibility_postTypes', array() );
		$this->frontend      = new Frontend( $postTypes, $this->rulesManager, $this->skautisLogin, $this->wpLoginLogout );
		if ( is_admin() ) {
			( new Admin( $postTypes, $this->rulesManager, $this->frontend ) );
		} else {
			$this->frontend->init_hooks();
		}
	}

	public static function get_id(): string {
		return self::$id;
	}

	public static function get_label(): string {
		return __( 'Viditelnost obsahu', 'skautis-integration' );
	}

	public static function get_path(): string {
		return plugin_dir_path( __FILE__ );
	}

	public static function getUrl(): string {
		return plugin_dir_url( __FILE__ );
	}

}
