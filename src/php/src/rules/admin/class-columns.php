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
 */
class Columns {

	/**
	 * Intializes all hooks used by the object.
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Intializes all hooks used by the object.
	 */
	protected function init_hooks() {
		add_filter( 'manage_edit-' . Rules_Init::RULES_TYPE_SLUG . '_columns', array( $this, 'last_modified_admin_column' ) );
		add_filter(
			'manage_edit-' . Rules_Init::RULES_TYPE_SLUG . '_sortable_columns',
			array(
				$this,
				'sortable_last_modified_column',
			)
		);
		add_action(
			'manage_' . Rules_Init::RULES_TYPE_SLUG . '_posts_custom_column',
			array(
				$this,
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
	 */
	public function last_modified_admin_column( array $columns = array() ): array {
		$columns['modified_last'] = __( 'Naposledy upraveno', 'skautis-integration' );

		return $columns;
	}

	/**
	 * Adds the "Last modified" column to the rules overview.
	 *
	 * TODO: What are the parameter keys and values?
	 *
	 * @param array<string, string> $columns A list of already present columns.
	 */
	public function sortable_last_modified_column( array $columns = array() ): array {
		$columns['modified_last'] = 'modified';

		return $columns;
	}

	/**
	 * Prints the content for the "Last modified" column in the rules overview.
	 *
	 * This function gets called for each cell of the table, so it needs to check before printing the value.
	 *
	 * @param string $column_name The current column name.
	 * @param string $post_id The current post ID (the current row).
	 */
	public function last_modified_admin_column_content( string $column_name, int $post_id ) {
		if ( 'modified_last' !== $column_name ) {
			return;
		}

		$post = get_post( $post_id );
		/* translators: human-readable time difference */
		$modified_date   = sprintf( _x( 'Před %s', '%s = human-readable time difference', 'skautis-integration' ), human_time_diff( strtotime( $post->post_modified ), time() ) );
		$modified_author = get_the_modified_author();

		echo esc_html( $modified_date );
		echo '<br>';
		echo '<strong>' . esc_html( $modified_author ) . '</strong>';
	}

}
