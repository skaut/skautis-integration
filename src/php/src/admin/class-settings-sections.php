<?php
/**
 * Contains the Settings_Sections class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Admin;

/**
 * Prints the settings sections of the plugin settings pages.
 */
final class Settings_Sections {

	/**
	 * Prints the settings sections of a page, each one as a card.
	 *
	 * The same as do_settings_sections(), only with a card around each section.
	 *
	 * @param string $page The settings page.
	 *
	 * @return void
	 */
	public static function print( string $page ) {
		global $wp_settings_sections, $wp_settings_fields;

		if ( ! is_array( $wp_settings_sections ) || ! isset( $wp_settings_sections[ $page ] ) || ! is_array( $wp_settings_sections[ $page ] ) ) {
			return;
		}

		foreach ( $wp_settings_sections[ $page ] as $section ) {
			if ( ! is_array( $section ) || ! isset( $section['id'] ) || ! is_string( $section['id'] ) ) {
				continue;
			}
			echo '<div class="skautis-settings-card">';
			if ( isset( $section['title'] ) && is_string( $section['title'] ) && '' !== $section['title'] ) {
				echo '<h2>' . esc_html( $section['title'] ) . '</h2>';
			}
			if ( isset( $section['callback'] ) && is_callable( $section['callback'] ) ) {
				call_user_func( $section['callback'], $section );
			}
			if ( is_array( $wp_settings_fields ) && isset( $wp_settings_fields[ $page ][ $section['id'] ] ) ) {
				echo '<table class="form-table" role="presentation">';
				do_settings_fields( $page, $section['id'] );
				echo '</table>';
			}
			echo '</div>';
		}
	}
}
