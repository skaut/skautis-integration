<?php

namespace SkautisIntegration\Utils;

class Helpers {

	public static function isSessionStarted() {
		if ( php_sapi_name() !== 'cli' ) {
			if ( version_compare( phpversion(), '5.4.0', '>=' ) ) {
				return session_status() === PHP_SESSION_ACTIVE ? true : false;
			} else {
				return session_id() === '' ? false : true;
			}
		}

		return false;
	}

	public static function generateBackLinkUrl() {
		global $wp;

		return home_url( add_query_arg( [], $wp->request ) );
	}

	public static function showAdminNotice( $message, $type = 'warning', $hideNoticeOnPage = '' ) {
		add_action( 'admin_notices', function () use ( $message, $type, $hideNoticeOnPage ) {
			if ( ! $hideNoticeOnPage || $hideNoticeOnPage != get_current_screen()->id ) {
				$class = 'notice notice-' . $type . ' is-dismissible';
				printf( '<div class="%1$s"><p>%2$s</p><button type="button" class="notice-dismiss">
		<span class="screen-reader-text">' . __( 'Zavřít' ) . '</span>
	</button></div>', esc_attr( $class ), $message );
			}
		} );
	}

	public static function getSkautisManagerCapability() {
		static $capability = '';

		if ( $capability === '' ) {
			$capability = apply_filters( SKAUTISINTEGRATION_NAME . '_manager_capability', 'manage_options' );
		}

		return $capability;
	}

	public static function userIsSkautisManager() {
		return current_user_can( self::getSkautisManagerCapability() );
	}

	public static function getCurrentUrl() {
		return ( isset( $_SERVER['HTTPS'] ) ? "https" : "http" ) . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	}

	public static function validateNonceFromUrl( $url, $nonceName ) {
		if ( ! wp_verify_nonce( self::getNonceFromUrl( urldecode( $url ), $nonceName ), $nonceName ) ) {
			wp_nonce_ays( $nonceName );
		}
	}

	public static function getNonceFromUrl( $url, $nonceName ) {
		$result = [];
		if ( preg_match( "~" . $nonceName . "=([^\&,\s,\/,\#,\%,\?]*)~", $url, $result ) ) {
			if ( is_array( $result ) && isset( $result[1] ) && $result[1] ) {
				return $result[1];
			}
		}

		return false;
	}

}
