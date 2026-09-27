<?php
/**
 * Contains the Autosave_Meta_Cleanup class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Migrations;

/**
 * Removes post metadata duplicated on autosaves by older versions of the plugin.
 *
 * This is a one-time migration, it can be removed once all sites have been updated past the version that introduced it.
 */
class Autosave_Meta_Cleanup {

	/**
	 * Constructs the service and saves all dependencies.
	 */
	public function __construct() {
		self::init_hooks();
	}

	/**
	 * Intializes all hooks used by the object.
	 *
	 * @return void
	 */
	protected static function init_hooks() {
		add_action( 'admin_init', array( self::class, 'cleanup' ) );
	}

	/**
	 * Removes metadata duplicated on autosaves by older versions of the plugin.
	 *
	 * Older versions of the plugin re-added all the metadata of an autosave every time it was overwritten, doubling the number of rows each time. This removes rows that duplicate another row with the same key and value on the same autosave. It runs once.
	 *
	 * @see https://github.com/skaut/skautis-integration/issues/1568
	 *
	 * @return void
	 */
	public static function cleanup() {
		if ( false !== get_option( 'skautis_integration_autosave_meta_cleaned' ) ) {
			return;
		}

		global $wpdb;

		// Finds all autosaves with at least one duplicated non-hidden meta row.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$autosave_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT duplicate.post_id FROM {$wpdb->postmeta} AS duplicate
				INNER JOIN {$wpdb->postmeta} AS original ON original.post_id = duplicate.post_id AND original.meta_key = duplicate.meta_key AND original.meta_value = duplicate.meta_value AND original.meta_id < duplicate.meta_id
				INNER JOIN {$wpdb->posts} AS autosave ON autosave.ID = duplicate.post_id
				WHERE autosave.post_type = 'revision' AND autosave.post_name LIKE %s AND duplicate.meta_key NOT LIKE %s",
				'%' . $wpdb->esc_like( '-autosave-v1' ),
				$wpdb->esc_like( '_' ) . '%'
			)
		);
		// phpcs:enable

		foreach ( $autosave_ids as $autosave_id ) {
			self::remove_duplicated_meta( intval( $autosave_id ) );
		}

		update_option( 'skautis_integration_autosave_meta_cleaned', true );
	}

	/**
	 * Removes duplicated non-hidden metadata values from an autosave.
	 *
	 * The `*_metadata()` functions are used instead of the `*_post_meta()` ones, as those operate on the parent post when given a revision.
	 *
	 * @param int $autosave_id The ID of the autosave.
	 *
	 * @return void
	 */
	private static function remove_duplicated_meta( int $autosave_id ) {
		$meta = get_metadata( 'post', $autosave_id );
		if ( ! is_array( $meta ) ) {
			return;
		}

		// Without a key, get_metadata() returns the values still serialized, so they can be compared as strings.
		foreach ( $meta as $meta_key => $meta_values ) {
			$unique_values = array_unique( $meta_values );
			if ( '_' === $meta_key[0] || count( $unique_values ) === count( $meta_values ) ) {
				continue;
			}

			delete_metadata( 'post', $autosave_id, $meta_key );
			foreach ( $unique_values as $meta_value ) {
				add_metadata( 'post', $autosave_id, $meta_key, wp_slash( maybe_unserialize( $meta_value ) ) );
			}
		}
	}
}
