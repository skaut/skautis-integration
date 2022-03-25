<?php

declare( strict_types=1 );

namespace Skautis_Integration\Utils;

class Helpers {

	/**
	 * Registers a script file
	 *
	 * Registers a script so that it can later be enqueued by `wp_enqueue_script()`.
	 *
	 * @param string        $handle A unique handle to identify the script with. This handle should be passed to `wp_enqueue_script()`.
	 * @param string        $src Path to the file, relative to the plugin directory.
	 * @param array<string> $deps A list of dependencies of the script. These can be either system dependencies like jquery, or other registered scripts. Default [].
	 * @param boolean       $in_footer  Whether to enqueue the script before </body> instead of in the <head>. Default 'true'.
	 *
	 * @return void
	 */
	public static function register_script( $handle, $src, $deps = array(), $in_footer = true ) {
		$handle = SKAUTIS_INTEGRATION_NAME . '_' . $handle;
		$src    = plugin_dir_url( dirname( __FILE__, 2 ) ) . $src;
		wp_register_script( $handle, $src, $deps, SKAUTIS_INTEGRATION_VERSION, $in_footer );
	}

	/**
	 * Enqueues a script file
	 *
	 * Registers and immediately enqueues a script. Note that you should **not** call this function if you've previously registered the script using `register_script()`.
	 *
	 * @param string        $handle A unique handle to identify the script with.
	 * @param string        $src Path to the file, relative to the plugin directory.
	 * @param array<string> $deps A list of dependencies of the script. These can be either system dependencies like jquery, or other registered scripts. Default [].
	 *
	 * @return void
	 */
	public static function enqueue_script( $handle, $src, $deps = array(), $in_footer = true ) {
		self::register_script( $handle, $src, $deps, $in_footer );
		wp_enqueue_script( SKAUTIS_INTEGRATION_NAME . '_' . $handle );
	}

	/**
	 * Registers a style file
	 *
	 * Registers a style so that it can later be enqueued by `wp_enqueue_style()`.
	 *
	 * @param string        $handle A unique handle to identify the style with. This handle should be passed to `wp_enqueue_style()`.
	 * @param string        $src Path to the file, relative to the plugin directory.
	 * @param array<string> $deps A list of dependencies of the style. These can be either system dependencies or other registered styles. Default [].
	 *
	 * @return void
	 */
	public static function register_style( $handle, $src, $deps = array() ) {
		$handle = SKAUTIS_INTEGRATION_NAME . '_' . $handle;
		$src    = plugin_dir_url( dirname( __FILE__, 2 ) ) . $src;
		wp_register_style( $handle, $src, $deps, SKAUTIS_INTEGRATION_VERSION );
	}

	/**
	 * Enqueues a style file
	 *
	 * Registers and immediately enqueues a style. Note that you should **not** call this function if you've previously registered the style using `register_style()`.
	 *
	 * @param string        $handle A unique handle to identify the style with.
	 * @param string        $src Path to the file, relative to the plugin directory.
	 * @param array<string> $deps A list of dependencies of the style. These can be either system dependencies or other registered styles. Default [].
	 *
	 * @return void
	 */
	public static function enqueue_style( $handle, $src, $deps = array() ) {
		self::register_style( $handle, $src, $deps );
		wp_enqueue_style( SKAUTIS_INTEGRATION_NAME . '_' . $handle );
	}

	public static function get_login_logout_redirect() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['redirect_to'] ) && '' !== $_GET['redirect_to'] ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return esc_url_raw( wp_unslash( $_GET['redirect_to'] ) );
		}
		$return_url = self::get_return_url();
		return is_null( $return_url ) ? self::get_current_url() : $return_url;
	}

	public static function get_return_url() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['ReturnUrl'] ) || '' === $_GET['ReturnUrl'] ) {
			return null;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return esc_url_raw( wp_unslash( $_GET['ReturnUrl'] ) );
	}

	public static function show_admin_notice( string $message, string $type = 'warning', string $hide_notice_on_page = '' ) {
		add_action(
			'admin_notices',
			function () use ( $message, $type, $hide_notice_on_page ) {
				if ( ! $hide_notice_on_page || get_current_screen()->id !== $hide_notice_on_page ) {
					$class = 'notice notice-' . $type . ' is-dismissible';
					printf(
						'<div class="%1$s"><p>%2$s</p><button type="button" class="notice-dismiss">
		<span class="screen-reader-text">' . esc_html__( 'Zavřít', 'skautis-integration' ) . '</span>
	</button></div>',
						esc_attr( $class ),
						esc_html( $message )
					);
				}
			}
		);
	}

	public static function get_skautis_manager_capability(): string {
		static $capability = '';

		if ( '' === $capability ) {
			$capability = apply_filters( SKAUTIS_INTEGRATION_NAME . '_manager_capability', 'manage_options' );
		}

		return $capability;
	}

	public static function user_is_skautis_manager(): bool {
		return current_user_can( self::get_skautis_manager_capability() );
	}

	public static function get_current_url(): string {
		if ( isset( $_SERVER['HTTP_HOST'] ) && isset( $_SERVER['REQUEST_URI'] ) ) {
			return esc_url_raw( ( isset( $_SERVER['HTTPS'] ) ? 'https' : 'http' ) . '://' . wp_unslash( $_SERVER['HTTP_HOST'] ) . wp_unslash( $_SERVER['REQUEST_URI'] ) );
		}
		return '';
	}

	public static function validate_nonce_from_url( string $url, string $nonce_name ) {
		if ( ! wp_verify_nonce( self::get_nonce_from_url( urldecode( $url ), $nonce_name ), $nonce_name ) ) {
			wp_nonce_ays( $nonce_name );
		}
	}

	public static function get_variable_from_url( string $url, string $variable_name ): string {
		$result = array();
		$url    = esc_url_raw( $url );
		if ( preg_match( '~' . $variable_name . '=([^\&,\s,\/,\#,\%,\?]*)~', $url, $result ) ) {
			if ( is_array( $result ) && isset( $result[1] ) && $result[1] ) {
				return sanitize_text_field( $result[1] );
			}
		}

		return '';
	}

	public static function get_nonce_from_url( string $url, string $nonce_name ): string {
		return self::get_variable_from_url( $url, $nonce_name );
	}

}