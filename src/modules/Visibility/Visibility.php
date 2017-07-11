<?php

declare( strict_types=1 );

namespace SkautisIntegration\Modules\Visibility;

use SkautisIntegration\Modules\IModule;
use SkautisIntegration\Modules\Visibility\Admin\Admin;
use SkautisIntegration\Modules\Visibility\Frontend\Frontend;
use SkautisIntegration\Rules\RulesManager;

final class Visibility implements IModule {

	const REGISTER_ACTION = 'visibility';

	private $rulesManager;

	public static $id = 'module_Visibility';

	public function __construct( RulesManager $rulesManager ) {
		$this->rulesManager = $rulesManager;
		$postTypes          = get_option( SKAUTISINTEGRATION_NAME . '_modules_visibility_postTypes' );
		if ( is_admin() ) {
			( new Admin( $postTypes, $this->rulesManager ) );
		} else {
			( new Frontend( $postTypes, $this->rulesManager ) );
		}
	}

	public static function getId(): string {
		return self::$id;
	}

	public static function getLabel(): string {
		return __( 'Viditelnost obsahu', 'skautis-integration' );
	}

	public static function getPath(): string {
		return plugin_dir_path( __FILE__ );
	}

	public static function getUrl(): string {
		return plugin_dir_url( __FILE__ );
	}

}