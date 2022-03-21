<?php

declare( strict_types=1 );

namespace SkautisIntegration\Modules\Shortcodes;

use SkautisIntegration\Auth\Skautis_Login;
use SkautisIntegration\Modules\IModule;
use SkautisIntegration\Modules\Shortcodes\Admin\Admin;
use SkautisIntegration\Modules\Shortcodes\Frontend\Frontend;
use SkautisIntegration\Rules\Rules_Manager;
use SkautisIntegration\Auth\WP_Login_Logout;

final class Shortcodes implements IModule {

	const REGISTER_ACTION = 'shortcodes';

	private $rulesManager;
	private $skautisLogin;
	private $wpLoginLogout;

	public static $id = 'module_Shortcodes';

	public function __construct( Rules_Manager $rulesManager, Skautis_Login $skautisLogin, WP_Login_Logout $wpLoginLogout ) {
		$this->rulesManager  = $rulesManager;
		$this->skautisLogin  = $skautisLogin;
		$this->wpLoginLogout = $wpLoginLogout;
		if ( is_admin() ) {
			( new Admin( $this->rulesManager ) );
		} else {
			( new Frontend( $this->skautisLogin, $this->rulesManager, $this->wpLoginLogout ) );
		}
	}

	public static function getId(): string {
		return self::$id;
	}

	public static function getLabel(): string {
		return __( 'Shortcodes', 'skautis-integration' );
	}

	public static function getPath(): string {
		return plugin_dir_path( __FILE__ );
	}

	public static function getUrl(): string {
		return plugin_dir_url( __FILE__ );
	}

}
