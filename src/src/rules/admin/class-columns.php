<?php

declare( strict_types=1 );

namespace SkautisIntegration\Rules;

class Columns {

	public function __construct() {
		$this->init_hooks();
	}

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
				'lastModifiedAdminColumnContent',
			),
			10,
			2
		);
	}

	public function last_modified_admin_column( array $columns = array() ): array {
		$columns['modified_last'] = __( 'Naposledy upraveno', 'skautis-integration' );

		return $columns;
	}

	public function sortable_last_modified_column( array $columns = array() ): array {
		$columns['modified_last'] = 'modified';

		return $columns;
	}

	public function lastModifiedAdminColumnContent( string $columnName, int $postId ) {
		if ( 'modified_last' !== $columnName ) {
			return;
		}

		$post = get_post( $postId );
		/* translators: human-readable time difference */
		$modifiedDate   = sprintf( _x( 'Před %s', '%s = human-readable time difference', 'skautis-integration' ), human_time_diff( strtotime( $post->post_modified ), time() ) );
		$modifiedAuthor = get_the_modified_author();

		echo esc_html( $modifiedDate );
		echo '<br>';
		echo '<strong>' . esc_html( $modifiedAuthor ) . '</strong>';
	}

}
