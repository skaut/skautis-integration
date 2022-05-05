<?php
/**
 * Contains the Helpers class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Utils;

/**
 * Helper functions for the plugin.
 */
class Helpers {

	/**
	 * Registers a script file
	 *
	 * Registers a script so that it can later be enqueued by `wp_enqueue_script()`.
	 *
	 * @param string        $handle A unique handle to identify the script with. This handle should be passed to `wp_enqueue_script()`.
	 * @param string        $src Path to the file, relative to the plugin directory.
	 * @param array<string> $deps A list of dependencies of the script. These can be either system dependencies like jquery, or other registered scripts. Default [].
	 * @param bool          $in_footer  Whether to enqueue the script before </body> instead of in the <head>. Default 'true'.
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
	 * @param bool          $in_footer Whether to enqueue the script in the page footer.
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

	/**
	 * Parses and sanitizes a login or logout redirect URL from GET variablea.
	 */
	public static function get_login_logout_redirect() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['redirect_to'] ) && '' !== $_GET['redirect_to'] ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return esc_url_raw( wp_unslash( $_GET['redirect_to'] ) );
		}
		$return_url = self::get_return_url();
		return is_null( $return_url ) ? self::get_current_url() : $return_url;
	}

	/**
	 * Parses and sanitizes the `ReturnUrl` GET variable.
	 */
	public static function get_return_url() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['ReturnUrl'] ) || '' === $_GET['ReturnUrl'] ) {
			return null;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return esc_url_raw( wp_unslash( $_GET['ReturnUrl'] ) );
	}

	/**
	 * Shows a notice in the administration.
	 *
	 * @param string $message The notice text.
	 * @param string $type The type of the notice. Accepted values are "error", "warning", "success", "info". Default "warning".
	 * @param string $hide_notice_on_page An ID of a screen where the notice shouldn't get shown. Optional.
	 */
	public static function show_admin_notice( string $message, string $type = 'warning', string $hide_notice_on_page = '' ) {
		add_action(
			'admin_notices',
			static function () use ( $message, $type, $hide_notice_on_page ) {
				if ( '' === $hide_notice_on_page || get_current_screen()->id !== $hide_notice_on_page ) {
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

	/**
	 * Returns the capability level needed to manage SkautIS options.
	 *
	 * TODO: This function just returns "manage_options".
	 */
	public static function get_skautis_manager_capability(): string {
		static $capability = '';

		if ( '' === $capability ) {
			// TODO: Unused hook?
			$capability = apply_filters( SKAUTIS_INTEGRATION_NAME . '_manager_capability', 'manage_options' );
		}

		return $capability;
	}

	/**
	 * Returns whether the current user has the capability level needed to manage SkautIS options.
	 */
	public static function user_is_skautis_manager(): bool {
		return current_user_can( self::get_skautis_manager_capability() );
	}

	/**
	 * Returns the current URL.
	 */
	public static function get_current_url(): string {
		if ( isset( $_SERVER['HTTP_HOST'] ) && isset( $_SERVER['REQUEST_URI'] ) ) {
			return esc_url_raw( ( isset( $_SERVER['HTTPS'] ) ? 'https' : 'http' ) . '://' . wp_unslash( $_SERVER['HTTP_HOST'] ) . wp_unslash( $_SERVER['REQUEST_URI'] ) );
		}
		return '';
	}

	/**
	 * Parses a nonce from a URL and verifies it.
	 *
	 * @param string $url The URL to parse the nonce from.
	 * @param string $nonce_name The name of the nonce.
	 */
	public static function validate_nonce_from_url( string $url, string $nonce_name ) {
		if ( false === wp_verify_nonce( self::get_nonce_from_url( urldecode( $url ), $nonce_name ), $nonce_name ) ) {
			wp_nonce_ays( $nonce_name );
		}
	}

	/**
	 * Parses a GET variable from a URL.
	 *
	 * @param string $url The URL to parse the variable from.
	 * @param string $variable_name The name of the variable.
	 */
	public static function get_variable_from_url( string $url, string $variable_name ): string {
		$result = array();
		$url    = esc_url_raw( $url );
		if ( 1 === preg_match( '~' . $variable_name . '=([^\&,\s,\/,\#,\%,\?]*)~', $url, $result ) ) {
			if ( is_array( $result ) && isset( $result[1] ) && '' !== $result[1] ) {
				return sanitize_text_field( $result[1] );
			}
		}

		return '';
	}

	/**
	 * Parses a nonce from a URL.
	 *
	 * @param string $url The URL to parse the nonce from.
	 * @param string $nonce_name The name of the nonce.
	 */
	public static function get_nonce_from_url( string $url, string $nonce_name ): string {
		return self::get_variable_from_url( $url, $nonce_name );
	}

}
