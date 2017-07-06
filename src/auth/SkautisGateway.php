<?php

declare( strict_types=1 );

namespace SkautisIntegration\Auth;

use SkautisIntegration\Utils\Helpers;
use Skautis\Skautis;

class SkautisGateway {

	const PROD_ENV = 'prod';
	const TEST_ENV = 'test';

	protected $appId = '';
	protected $skautis;
	protected $skautisInitialized = false;
	protected $testMode = WP_DEBUG;
	protected $env = '';

	public function __construct() {
		if ( ! headers_sent() ) {
			if ( Helpers::isSessionStarted() === false ) {
				session_start();
			}
		}

		$envType = get_option( 'skautis_integration_appid_type' );
		if ( $envType === self::PROD_ENV ) {
			$this->appId    = get_option( 'skautis_integration_appid_prod' );
			$this->env      = $envType;
			$this->testMode = false;
		} else if ( $envType === self::TEST_ENV ) {
			$this->appId    = get_option( 'skautis_integration_appid_test' );
			$this->env      = $envType;
			$this->testMode = true;
		}

		if ( $this->appId && $envType ) {
			$this->skautis            = Skautis::getInstance( $this->appId, $this->testMode, true, true );
			$this->skautisInitialized = true;

			if ( $this->testMode ) {
				$this->skautis->enableDebugLog();
			}
		}

	}

	public function getEnv(): string {
		return $this->env;
	}

	public function getSkautisInstance(): Skautis {
		return $this->skautis;
	}

	public function isInitialized(): bool {
		return $this->skautisInitialized;
	}

	public function logout() {
		$this->skautis->setLoginData( [] );
		wp_remote_get( esc_url_raw( $this->getSkautisInstance()->getLogoutUrl() ) );
	}

}
