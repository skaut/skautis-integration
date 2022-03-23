<?php

declare( strict_types=1 );

namespace SkautisIntegration\Rules;

class Revisions {

	public function __construct() {
		$this->init_hooks();
	}

	protected function init_hooks() {
		add_action( 'save_post', array( $this, 'save_post' ), 10 );
		add_action( 'wp_restore_post_revision', array( $this, 'restore_revision' ), 10, 2 );
		add_filter( 'wp_save_post_revision_post_has_changed', array( $this, 'post_has_changed' ), 10, 3 );

		add_filter( '_wp_post_revision_fields', array( $this, 'fields' ), 10, 1 );
		add_filter( '_wp_post_revision_field_custom_fields', array( $this, 'field' ), 10, 3 );
	}

	public function filter_meta( $meta ): array {
		$metaFiltered = array();
		foreach ( $meta as $key => $value ) {
			if ( '_' !== $key[0] ) {
				$metaFiltered[ $key ] = $value;
			}
		}

		return $metaFiltered;
	}

	public function get_meta( int $postId ): array {
		$meta = get_metadata( 'post', $postId );
		$meta = $this->filter_meta( $meta );

		return $meta;
	}

	public function insert_meta( int $postId, $meta ) {
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

	public function delete_meta( int $postId ) {
		$meta = $this->get_meta( $postId );

		foreach ( $meta as $metaKey => $metaValue ) {
			delete_metadata( 'post', $postId, $metaKey );
		}
	}

	public function field( $value, $field, $revision ) {
		$revisionId = $revision->ID;
		$meta       = $this->get_meta( $revisionId );

		// format response as single string with all custom fields / metadata
		$return = '';
		foreach ( $meta as $metaKey => $metaValue ) {
			$return .= $metaKey . ': ' . join( ', ', $metaValue ) . "\n";
		}

		return $return;
	}

	public function fields( array $fields = array() ): array {
		$fields['custom_fields'] = __( 'Další pole', 'skautis-integration' );

		return $fields;
	}

	public function restore_revision( int $postId, int $revisionId ) {
		$meta = $this->get_meta( $revisionId );
		$this->delete_meta( $postId );
		$this->insert_meta( $postId, $meta );

		// also update last revision custom fields
		$revisions = wp_get_post_revisions( $postId );
		if ( count( $revisions ) > 0 ) {
			$lastRevision = current( $revisions );
			$this->delete_meta( $lastRevision->ID );
			$this->insert_meta( $lastRevision->ID, $meta );
		}
	}

	public function save_post( int $postId ) {
		if ( wp_is_post_revision( $postId ) ) {
			$meta = $this->get_meta( $postId );
			if ( false === $meta ) {
				return;
			}

			$this->insert_meta( $postId, $meta );
		}
	}

	public function post_has_changed( bool $postHasChanged, \WP_Post $lastRevision, \WP_Post $post ): bool {
		if ( ! $postHasChanged ) {
			$meta    = $this->get_meta( $lastRevision->ID );
			$metaNew = $this->get_meta( $post->ID );

			if ( $meta === $metaNew ) {
				return $postHasChanged;
			}

			// Post changed
			return true;
		}

		return $postHasChanged;
	}

}
