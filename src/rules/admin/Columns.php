<?php

namespace SkautisIntegration\Rules;

class Columns {

	public function __construct() {
		$this->initHooks();
	}

	protected function initHooks() {
		add_filter( 'manage_edit-' . RulesInit::RULES_TYPE_SLUG . '_columns', [ $this, 'lastModifiedAdminColumn' ] );
		add_filter( 'manage_edit-' . RulesInit::RULES_TYPE_SLUG . '_sortable_columns', [
			$this,
			'sortableLastModifiedColumn'
		] );
		add_action( 'manage_' . RulesInit::RULES_TYPE_SLUG . '_posts_custom_column', [
			$this,
			'lastModifiedAdminColumnContent'
		], 10, 2 );
	}

	public function lastModifiedAdminColumn( $columns ) {
		$columns['modified_last'] = __( 'Naposledy upraveno', 'skautis-integration' );

		return $columns;
	}

	public function sortableLastModifiedColumn( $columns ) {
		$columns['modified_last'] = 'modified';

		return $columns;
	}

	public function lastModifiedAdminColumnContent( $columnName, $postId ) {
		if ( 'modified_last' != $columnName ) {
			return;
		}

		$post           = get_post( $postId );
		$modifiedDate   = sprintf( _x( 'Před %s', '%s = human-readable time difference', 'skautis-integration' ), human_time_diff( strtotime( $post->post_modified ), current_time( 'timestamp' ) ) );
		$modifiedAuthor = get_the_modified_author();

		echo $modifiedDate;
		echo '<br>';
		echo '<strong>' . $modifiedAuthor . '</strong>';

	}

}
