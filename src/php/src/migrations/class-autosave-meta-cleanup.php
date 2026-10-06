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
	 * The maximum number of rows removed by one query.
	 */
	const BATCH_SIZE = 10000;

	/**
	 * The time in seconds after which the cleanup stops and continues on the next request.
	 */
	const TIME_LIMIT = 5;

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
	 * Older versions of the plugin re-added all the metadata of an autosave every time it was overwritten, doubling the number of rows each time. This removes rows that duplicate another row with the same key and value on the same autosave.
	 *
	 * An autosave can have millions of such rows, so they are never loaded into PHP and every query only touches a limited number of them. If the cleanup doesn't finish within the time limit, it continues on the next admin request. Once it finishes, it doesn't run again.
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

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$autosave_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'revision' AND post_name LIKE %s",
				'%' . $wpdb->esc_like( '-autosave-v1' )
			)
		);
		// phpcs:enable

		$deadline = microtime( true ) + self::TIME_LIMIT;
		foreach ( $autosave_ids as $autosave_id ) {
			if ( ! self::remove_duplicated_meta( intval( $autosave_id ), $deadline ) ) {
				return;
			}
		}

		update_option( 'skautis_integration_autosave_meta_cleaned', true );
	}

	/**
	 * Removes duplicated non-hidden metadata from an autosave.
	 *
	 * Goes through the metadata rows in the order they were added. For each row, all later rows with the same key and value are removed, so only the first occurrence of each value is kept. The key and value are compared as binary strings, as the database collation would treat e.g. values differing only in case as equal.
	 *
	 * @param int   $autosave_id The ID of the autosave.
	 * @param float $deadline The time (as returned by `microtime( true )`) after which no new query is started.
	 *
	 * @return bool Whether the autosave was fully cleaned up before the deadline.
	 */
	private static function remove_duplicated_meta( int $autosave_id, float $deadline ) {
		global $wpdb;

		$last_meta_id = 0;
		$finished     = false;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		while ( microtime( true ) <= $deadline ) {
			$meta = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT meta_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_id > %d AND meta_key NOT LIKE %s ORDER BY meta_id LIMIT 1",
					$autosave_id,
					$last_meta_id,
					$wpdb->esc_like( '_' ) . '%'
				),
				ARRAY_A
			);
			if ( ! is_array( $meta ) ) {
				$finished = true;
				break;
			}

			/**
			 * The query selects exactly these columns.
			 *
			 * @var array{meta_id: string, meta_key: string, meta_value: string} $meta
			 */
			$last_meta_id = intval( $meta['meta_id'] );
			do {
				if ( microtime( true ) > $deadline ) {
					break 2;
				}
				$removed = $wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_id > %d AND meta_key = BINARY %s AND meta_value = BINARY %s LIMIT %d",
						$autosave_id,
						$last_meta_id,
						$meta['meta_key'],
						$meta['meta_value'],
						self::BATCH_SIZE
					)
				);
				if ( false === $removed ) {
					break 2;
				}
			} while ( self::BATCH_SIZE === $removed );
		}
		// phpcs:enable

		wp_cache_delete( $autosave_id, 'post_meta' );
		return $finished;
	}
}
