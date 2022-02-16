<?php

declare( strict_types=1 );

namespace SkautisIntegration\Auth;

use SkautisIntegration\Utils\Helpers;
use SkautisIntegration\Vendor\Skautis;

class SkautisGateway {

    const PROD_ENV = 'prod';
    const TEST_ENV = 'test';

    protected $appId = '';
    protected $skautis;
    protected $skautisInitialized = false;
    protected $testMode           = WP_DEBUG;
    protected $env                = '';

    public function __construct() {
        $envType = get_option( 'skautis_integration_appid_type' );
        if ( self::PROD_ENV === $envType ) {
            $this->appId    = get_option( 'skautis_integration_appid_prod' );
            $this->env      = $envType;
            $this->testMode = false;
        } elseif ( self::TEST_ENV === $envType ) {
            $this->appId    = get_option( 'skautis_integration_appid_test' );
            $this->env      = $envType;
            $this->testMode = true;
        }

        if ( $this->appId && $envType ) {
            $sessionAdapter           = new TransientSessionAdapter();
            $wsdlManager              = new Skautis\Wsdl\WsdlManager( new Skautis\Wsdl\WebServiceFactory(), new Skautis\Config( $this->appId, $this->testMode ) );
            $user                     = new Skautis\User( $wsdlManager, $sessionAdapter );
            $this->skautis            = new Skautis\Skautis( $wsdlManager, $user );
            $this->skautisInitialized = true;

            if ( $this->testMode ) {
                $this->skautis->enableDebugLog();
            }
        }
    }

    public function getEnv(): string
    {
        return $this->env;
    }

    public function getSkautisInstance(): Skautis\Skautis
    {
		return $this->skautis;
	}

	public function isInitialized(): bool
	{
		return $this->skautisInitialized;
	}

	public function logout() {
		$this->skautis->setLoginData( array() );
		wp_remote_get( esc_url_raw( $this->getSkautisInstance()->getLogoutUrl() ) );
	}

	public function testActiveAppId() {
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

	public function isMaintenance(): bool
	{
		return $this->skautis->isMaintenance();
	}

}
