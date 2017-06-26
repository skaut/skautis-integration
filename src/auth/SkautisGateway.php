<?php

namespace SkautisIntegration\Auth;

use SkautisIntegration\Utils\Helpers;
use Skautis\Skautis;

class SkautisGateway {

	const PROD_ENV = 'prod';
	const TEST_ENV = 'test';

	private $appId = '';
	private $skautis;
	private $skautisInitialized = false;
	private $testMode = WP_DEBUG;
	private $env = '';

	public function __construct() {
		if ( ! headers_sent() ) {
			if ( Helpers::isSessionStarted() === false ) {
				session_start();
			}
		}

		$envType = get_option( 'skautis_integration_appid_type' );
		if ( $envType === self::PROD_ENV ) {
			$this->appId    = get_option( 'skautis_integration_appid_production' );
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

	public function getEnv() {
		return $this->env;
	}

	public function getSkautisInstance() {
		return $this->skautis;
	}

	public function isInitialized() {
		return $this->skautisInitialized;
	}

	public function logout() {
		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, $this->getSkautisInstance()->getLogoutUrl() );
		curl_setopt( $curl, CURLOPT_POST, false );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_HEADER, false );
		curl_setopt( $curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, 0 );
		$result = curl_exec( $curl );
		curl_close( $curl );
	}

}
