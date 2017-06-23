<?php

namespace SkautisIntegration\Rules;

class Revisions {

	public function __construct() {
		$this->initHooks();
	}

	protected function initHooks() {
		add_action( 'save_post', [ $this, 'savePost' ], 10, 2 );
		add_action( 'wp_restore_post_revision', [ $this, 'restoreRevision' ], 10, 2 );
		add_filter( 'wp_save_post_revision_post_has_changed', [ $this, 'postHasChanged' ], 10, 3 );

		add_filter( '_wp_post_revision_fields', [ $this, 'fields' ], 10, 1 );
		add_filter( '_wp_post_revision_field_custom_fields', [ $this, 'field' ], 10, 3 );
	}

	public function filterMeta( $meta ) {
		$metaFiltered = [];
		foreach ( $meta as $key => $value ) {
			if ( $key{0} != "_" ) {
				$metaFiltered[ $key ] = $value;
			}
		}

		return $metaFiltered;
	}

	public function getMeta( $postId ) {
		$meta = get_metadata( 'post', $postId );
		$meta = $this->filterMeta( $meta );

		return $meta;
	}

	public function insertMeta( $postId, $meta ) {
		foreach ( $meta as $metaKey => $metaValue ) {
			if ( is_array( $metaValue ) ) {
				foreach ( $metaValue as $singleMetaValue ) {
					add_metadata( 'post', $postId, $metaKey, $singleMetaValue );
				}
			} else {
				add_metadata( 'post', $postId, $metaKey, $metaValue );
			}
		}
	}

	public function deleteMeta( $postId ) {
		$meta = $this->getMeta( $postId );

		foreach ( $meta as $metaKey => $metaValue ) {
			delete_metadata( 'post', $postId, $metaKey );
		}
	}

	public function field( $value, $field, $revision ) {
		$revisionId = $revision->ID;
		$meta       = $this->getMeta( $revisionId );

		// format response as single string with all custom fields / metadata
		$return = '';
		foreach ( $meta as $metaKey => $metaValue ) {
			$return .= $metaKey . ": " . join( ", ", $metaValue ) . "\n";
		}

		return $return;
	}

	public function fields( $fields ) {
		$fields['custom_fields'] = __( 'Další pole', 'skautis-integration' );

		return $fields;
	}

	public function restoreRevision( $postId, $revisionId ) {
		$meta = $this->getMeta( $revisionId );
		$this->deleteMeta( $postId );
		$this->insertMeta( $postId, $meta );

		// also update last revision custom fields
		$revisions = wp_get_post_revisions( $postId );
		if ( count( $revisions ) > 0 ) {
			$lastRevision = current( $revisions );
			$this->deleteMeta( $lastRevision->ID );
			$this->insertMeta( $lastRevision->ID, $meta );
		}
	}

	public function savePost( $postId, $post ) {
		if ( $parentId = wp_is_post_revision( $postId ) ) {
			$meta = $this->getMeta( get_post( $parentId )->ID );
			if ( $meta === false ) {
				return;
			}

			$this->insertMeta( $postId, $meta );
		}
	}

	public function postHasChanged( $postHasChanged, $lastRevision, $post ) {
		if ( ! $postHasChanged ) {
			$meta    = $this->getMeta( get_post( $lastRevision )->ID );
			$metaNew = $this->getMeta( $post->ID );

			if ( $meta === $metaNew ) {
				return $postHasChanged;
			}

			// Post changed
			return true;
		}

		return $postHasChanged;
	}

}
