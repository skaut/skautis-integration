<?php

declare( strict_types=1 );

namespace SkautisIntegration\Auth;

use SkautisIntegration\Vendor\Skautis\SessionAdapter\AdapterInterface;

class Transient_Session_Adapter implements AdapterInterface {
	private function get_cookie_id(): string {
		if ( isset( $_COOKIE[ SKAUTISINTEGRATION_NAME . '-skautis-session' ] ) ) {
			return sanitize_text_field( wp_unslash( $_COOKIE[ SKAUTISINTEGRATION_NAME . '-skautis-session' ] ) );
		} else {
			$chars     = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
			$cookie_id = '';
			for ( $i = 0; $i < 32; $i++ ) {
				$cookie_id .= substr( $chars, wp_rand( 0, strlen( $chars ) - 1 ), 1 );
			}
			setcookie( SKAUTISINTEGRATION_NAME . '-skautis-session', $cookie_id, time() + 40 * \MINUTE_IN_SECONDS, '/', '', true, true );
			return $cookie_id;
		}
	}

	public function set( $name, $object ) {
		set_transient( SKAUTISINTEGRATION_NAME . '_session_' . $this->get_cookie_id() . '_' . $name, $object, 40 * \MINUTE_IN_SECONDS );
	}

	public function has( $name ): bool {
		return get_transient( SKAUTISINTEGRATION_NAME . '_session_' . $this->get_cookie_id() . '_' . $name ) !== false;
	}

	public function get( $name ) {
		return get_transient( SKAUTISINTEGRATION_NAME . '_session_' . $this->get_cookie_id() . '_' . $name );
	}
}
