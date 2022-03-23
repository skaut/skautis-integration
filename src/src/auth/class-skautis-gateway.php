<?php

declare( strict_types=1 );

namespace SkautisIntegration\Auth;

use SkautisIntegration\Utils\Helpers;
use SkautisIntegration\Vendor\Skautis;

class Skautis_Gateway {

	const PROD_ENV = 'prod';
	const TEST_ENV = 'test';

	// TODO: Private?
	// TODO: Unused?
	protected $app_id = '';
	protected $skautis;
	protected $skautis_initialized = false;
	// TODO: Unused?
	protected $test_mode           = WP_DEBUG;
	protected $env                = '';

	public function __construct() {
		$envType = get_option( 'skautis_integration_appid_type' );
		if ( self::PROD_ENV === $envType ) {
			$this->app_id    = get_option( 'skautis_integration_appid_prod' );
			$this->env      = $envType;
			$this->test_mode = false;
		} elseif ( self::TEST_ENV === $envType ) {
			$this->app_id    = get_option( 'skautis_integration_appid_test' );
			$this->env      = $envType;
			$this->test_mode = true;
		}

		if ( $this->app_id && $envType ) {
			$sessionAdapter           = new Transient_Session_Adapter();
			$wsdlManager              = new Skautis\Wsdl\WsdlManager( new Skautis\Wsdl\WebServiceFactory(), new Skautis\Config( $this->app_id, $this->test_mode ) );
			$user                     = new Skautis\User( $wsdlManager, $sessionAdapter );
			$this->skautis            = new Skautis\Skautis( $wsdlManager, $user );
			$this->skautis_initialized = true;

			if ( $this->test_mode ) {
				$this->skautis->enableDebugLog();
			}
		}
	}

	public function get_env(): string {
		return $this->env;
	}

	public function get_skautis_instance(): Skautis\Skautis {
		return $this->skautis;
	}

	public function is_initialized(): bool {
		return $this->skautis_initialized;
	}

	public function logout() {
		$this->skautis->setLoginData( array() );
		wp_remote_get( esc_url_raw( $this->get_skautis_instance()->getLogoutUrl() ) );
	}

	public function test_active_app_id() {
		try {
			if ( isset( $this->skautis ) ) {
				if ( $this->skautis->OrganizationUnit->UnitDetail() ) {
					return true;
				}
			}
		} catch ( \Exception $e ) {
			return false;
		}

		return false;
	}

	// TODO: Unused?
	public function is_maintenance(): bool {
		return $this->skautis->isMaintenance();
	}

}
