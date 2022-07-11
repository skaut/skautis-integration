<?php
/**
 * Contains the Request_Parameter_Helpers class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Utils;

/**
 * Contains helper functions for working with request parameters.
 */
class Request_Parameter_Helpers {
	/**
	 * Safely loads a string GET variable
	 *
	 * This function loads a GET variable, runs it through all the required WordPress sanitization and returns it.
	 *
	 * @param string $name The name of the GET variable.
	 * @param string $default The default value to use if the GET variable doesn't exist. Default empty string.
	 *
	 * @return string The GET variable value
	 */
	public static function get_string_variable( $name, $default = '' ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.NonceVerification.Recommended
		return isset( $_GET[ $name ] ) ? sanitize_text_field( wp_unslash( strval( $_GET[ $name ] ) ) ) : $default;
	}

	/**
	 * Safely loads an integer GET variable
	 *
	 * This function loads a GET variable, runs it through all the required WordPress sanitization and returns it.
	 *
	 * @param string $name The name of the GET variable.
	 * @param int    $default The default value to use if the GET variable doesn't exist.
	 *
	 * @return int The GET variable value
	 */
	public static function get_int_variable( $name, $default = -1 ) {
		$string_value = self::get_string_variable( $name );
		return '' !== $string_value ? intval( $string_value ) : $default;
	}

	/**
	 * Safely loads a string POST variable
	 *
	 * This function loads a POST variable, runs it through all the required WordPress sanitization and returns it.
	 *
	 * @param string $name The name of the POST variable.
	 * @param string $default The default value to use if the POST variable doesn't exist. Default empty string.
	 *
	 * @return string The POST variable value
	 */
	public static function post_string_variable( $name, $default = '' ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.NonceVerification.Recommended
		return isset( $_POST[ $name ] ) ? sanitize_text_field( wp_unslash( strval( $_POST[ $name ] ) ) ) : $default;
	}

	/**
	 * Safely loads an integer POST variable
	 *
	 * This function loads a POST variable, runs it through all the required WordPress sanitization and returns it.
	 *
	 * @param string $name The name of the POST variable.
	 * @param int    $default The default value to use if the POST variable doesn't exist.
	 *
	 * @return int The POST variable value
	 */
	public static function post_int_variable( $name, $default = -1 ) {
		$string_value = self::get_string_variable( $name );
		return '' !== $string_value ? intval( $string_value ) : $default;
	}

	/**
	 * Safely loads a POST variable containing a meta value
	 *
	 * This function loads a POST variable, runs it through all the required WordPress sanitization and returns it.
	 *
	 * @param string $name The name of the POST variable.
	 * @param string $meta_name The name of the meta.
	 * @param mixed  $default The default value to use if the POST variable doesn't exist. Default empty string.
	 *
	 * @return mixed The POST variable value
	 */
	public static function post_meta_variable( $name, $meta_name, $default = '' ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.NonceVerification.Recommended
		return isset( $_POST[ $name ] ) ? sanitize_meta( $meta_name, wp_unslash( strval( $_POST[ $name ] ) ), 'post' ) : $default;
	}
}
