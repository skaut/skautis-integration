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
	/**
	 * A helper function generating random string and saving it in a cookie.
	 */
	private static function get_cookie_id(): string {
		static $cookie_id = null;
		if ( is_string( $cookie_id ) ) {
			return $cookie_id;
		}

		if ( isset( $_COOKIE[ SKAUTIS_INTEGRATION_NAME . '-skautis-session' ] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$candidate = sanitize_text_field( wp_unslash( strval( $_COOKIE[ SKAUTIS_INTEGRATION_NAME . '-skautis-session' ] ) ) );
			// Only accept IDs in the format generated below.
			if ( 1 === preg_match( '/^[a-zA-Z0-9]{32}$/', $candidate ) ) {
				$cookie_id = $candidate;
				return $cookie_id;
			}
		}

		// random_bytes() is a CSPRNG, so the session ID cannot be predicted (unlike with rand()).
		$cookie_id = bin2hex( random_bytes( 16 ) );
		setcookie( SKAUTIS_INTEGRATION_NAME . '-skautis-session', $cookie_id, time() + 40 * \MINUTE_IN_SECONDS, '/', '', true, true );
		return $cookie_id;
	}

	/**
	 * Saves the SkautIS login object in a WordPress transient.
	 *
	 * @param string $name The key.
	 * @param mixed  $object The value.
	 *
	 * phpcs:disable Universal.NamingConventions.NoReservedKeywordParameterNames.objectFound
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
