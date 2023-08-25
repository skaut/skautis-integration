<?php
/**
 * Contains the Columns class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Rules;

/**
 * Adds the "Last modified" column to the rule table view.
 *
 * @phan-constructor-used-for-side-effects
 */
class Columns {

	/**
	 * Intializes all hooks used by the object.
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
		add_filter( 'manage_edit-' . Rules_Init::RULES_TYPE_SLUG . '_columns', array( self::class, 'last_modified_admin_column' ) );
		add_filter(
			'manage_edit-' . Rules_Init::RULES_TYPE_SLUG . '_sortable_columns',
			array(
				self::class,
				'sortable_last_modified_column',
			)
		);
		add_action(
			'manage_' . Rules_Init::RULES_TYPE_SLUG . '_posts_custom_column',
			array(
				self::class,
				'last_modified_admin_column_content',
			),
			10,
			2
		);
	}

	/**
	 * Adds the header for the "Last modified" column in the rules overview.
	 *
	 * @param array<string, string> $columns A list of already present column headers, keyed by their ID.
	 *
	 * @return array<string, string> The updated list.
	 */
	public static function last_modified_admin_column( array $columns = array() ): array {
		$columns['modified_last'] = __( 'Naposledy upraveno', 'skautis-integration' );

		return $columns;
	}

	/**
	 * Adds the "Last modified" column to the rules overview.
	 *
	 * TODO: What are the parameter keys and values?
	 *
	 * @param array<string, string> $columns A list of already present columns.
	 *
	 * @return array<string, string> The updated list.
	 */
	public static function sortable_last_modified_column( array $columns = array() ): array {
		$columns['modified_last'] = 'modified';

		return $columns;
	}

	/**
	 * Prints the content for the "Last modified" column in the rules overview.
	 *
	 * This function gets called for each cell of the table, so it needs to check before printing the value.
	 *
	 * @param string $column_name The current column name.
	 * @param int    $post_id The current post ID (the current row).
	 *
	 * @return void
	 */
	public static function last_modified_admin_column_content( string $column_name, int $post_id ) {
		if ( 'modified_last' !== $column_name ) {
			return;
		}

		$post = get_post( $post_id );
		if ( ! ( $post instanceof \WP_Post ) ) {
			return;
		}
		$time = strtotime( $post->post_modified );
		if ( false === $time ) {
			return;
		}
		/* translators: human-readable time difference */
		$modified_date   = sprintf( _x( 'Před %s', '%s = human-readable time difference', 'skautis-integration' ), human_time_diff( $time, time() ) );
		$modified_author = get_the_modified_author();
		if ( null === $modified_author ) {
			return;
		}

		echo esc_html( $modified_date );
		echo '<br>';
		echo '<strong>' . esc_html( $modified_author ) . '</strong>';
	}
}
