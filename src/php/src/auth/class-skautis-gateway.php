<?php
/**
 * Contains the Skautis_Gateway class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Auth;

use Skautis_Integration\Vendor\Skautis;

/**
 * An adapter for the SkautIS library.
 */
class Skautis_Gateway {

	const PROD_ENV = 'prod';
	const TEST_ENV = 'test';

	/**
	 * The SkautIS app ID.
	 *
	 * TODO: Private?
	 * TODO: Unused?
	 *
	 * @var string
	 */
	protected $app_id = '';

	/**
	 * An instance of the SkautIS library.
	 *
	 * @var Skautis\Skautis|null
	 */
	protected $skautis;

	/**
	 * Whether the SkautIS library instance is initialized.
	 *
	 * @var bool
	 */
	protected $skautis_initialized = false;

	/**
	 * Whether the current SkautIS environment is testing.
	 *
	 * @var bool
	 */
	protected $test_mode;

	/**
	 * The current SkautIS environment (testing or production).
	 *
	 * @var 'prod'|'test'
	 */
	protected $env;

	/**
	 * Constructs the service and saves all dependencies.
	 *
	 * TODO: Replace elseif with else and remove useless checks
	 */
	public function __construct() {
		$env_type = get_option( 'skautis_integration_appid_type' );
		if ( self::PROD_ENV === $env_type ) {
			$this->app_id    = get_option( 'skautis_integration_appid_prod' );
			$this->env       = $env_type;
			$this->test_mode = false;
		} elseif ( self::TEST_ENV === $env_type ) {
			$this->app_id    = get_option( 'skautis_integration_appid_test' );
			$this->env       = $env_type;
			$this->test_mode = true;
		}

		if ( '' !== $this->app_id && '' !== $env_type ) {
			$session_adapter           = new Transient_Session_Adapter();
			$wsdl_manager              = new Skautis\Wsdl\WsdlManager( new Skautis\Wsdl\WebServiceFactory(), new Skautis\Config( $this->app_id, $this->test_mode ) );
			$user                      = new Skautis\User( $wsdl_manager, $session_adapter );
			$this->skautis             = new Skautis\Skautis( $wsdl_manager, $user );
			$this->skautis_initialized = true;

			if ( $this->test_mode ) {
				$this->skautis->enableDebugLog();
			}
		}
	}

	/**
	 * Returns the current SkautIS environment (testing or production).
	 */
	public function get_env(): string {
		return $this->env;
	}

	/**
	 * Returns the raw SkauIS library instance
	 */
	public function get_skautis_instance(): Skautis\Skautis {
		return $this->skautis;
	}

	/**
	 * Checks whether the SkautIS library instance is initialized.
	 */
	public function is_initialized(): bool {
		return $this->skautis_initialized;
	}

	/**
	 * Logs the user out of SkautIS.
	 */
	public function logout() {
		$this->skautis->setLoginData( array() );
		wp_remote_get( esc_url_raw( $this->get_skautis_instance()->getLogoutUrl() ) );
	}

	/**
	 * Performs a dummy request to SkautIS to check whether the library is initialized correctly.
	 */
	public function test_active_app_id() {
		try {
			if ( isset( $this->skautis ) ) {
				if ( $this->skautis->OrganizationUnit->UnitDetail() ) {
					return true;
				}
			}
		} catch ( \Exception $_ ) {
			return false;
		}

		return false;
	}

}
