<?php
/**
 * Contains the Transient_Session_Adapter class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Auth;

use Skautis_Integration\Vendor\Skautis\SessionAdapter\AdapterInterface;

/**
 * Enables the SkautIS library to use WordPress transiens for user session management.
 */
class Transient_Session_Adapter implements AdapterInterface {
	const COOKIE_CHARACTERS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

	/**
	 * A helper function generating random string and saving it in a cookie.
	 */
	private static function get_cookie_id(): string {
		if ( isset( $_COOKIE[ SKAUTIS_INTEGRATION_NAME . '-skautis-session' ] ) ) {
			return sanitize_text_field( wp_unslash( $_COOKIE[ SKAUTIS_INTEGRATION_NAME . '-skautis-session' ] ) );
		} else {
			$cookie_id = '';
			for ( $i = 0; $i < 32; $i++ ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_rand
				$cookie_id .= substr( self::COOKIE_CHARACTERS, rand( 0, strlen( self::COOKIE_CHARACTERS ) - 1 ), 1 );
			}
			setcookie( SKAUTIS_INTEGRATION_NAME . '-skautis-session', $cookie_id, time() + 40 * \MINUTE_IN_SECONDS, '/', '', true, true );
			return $cookie_id;
		}
	}

	/**
	 * Saves the SkautIS login object in a WordPress transient.
	 *
	 * @param string $name The key.
	 * @param mixed  $object The value.
	 */
	public function set( $name, $object ) {
		set_transient( SKAUTIS_INTEGRATION_NAME . '_session_' . self::get_cookie_id() . '_' . $name, $object, 40 * \MINUTE_IN_SECONDS );
	}

	/**
	 * Checks whether the SkautIS login object is present in a WordPress transient.
	 *
	 * @param string $name The key.
	 */
	public function has( $name ): bool {
		return $this->get( $name ) !== false;
	}

	/**
	 * Retrieves the SkautIS login object from a WordPress transient.
	 *
	 * @param string $name The key.
	 */
	public function get( $name ) {
		return get_transient( SKAUTIS_INTEGRATION_NAME . '_session_' . self::get_cookie_id() . '_' . $name );
	}
}
