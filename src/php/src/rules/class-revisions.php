<?php
/**
 * Contains the Revisions class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Rules;

class Revisions {

	/**
	 * Constructs the service and saves all dependencies.
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Intializes all hooks used by the object.
	 */
	protected function init_hooks() {
		add_action( 'save_post', array( $this, 'save_post' ), 10 );
		add_action( 'wp_restore_post_revision', array( $this, 'restore_revision' ), 10, 2 );
		add_filter( 'wp_save_post_revision_post_has_changed', array( $this, 'post_has_changed' ), 10, 3 );

		add_filter( '_wp_post_revision_fields', array( $this, 'fields' ), 10, 1 );
		add_filter( '_wp_post_revision_field_custom_fields', array( $this, 'field' ), 10, 3 );
	}

	public function filter_meta( $meta ): array {
		$meta_filtered = array();
		foreach ( $meta as $key => $value ) {
			if ( '_' !== $key[0] ) {
				$meta_filtered[ $key ] = $value;
			}
		}

		return $meta_filtered;
	}

	public function get_meta( int $post_id ): array {
		$meta = get_metadata( 'post', $post_id );
		$meta = $this->filter_meta( $meta );

		return $meta;
	}

	public function insert_meta( int $post_id, $meta ) {
		foreach ( $meta as $meta_key => $meta_value ) {
			if ( is_array( $meta_value ) ) {
				foreach ( $meta_value as $single_meta_value ) {
					add_metadata( 'post', $post_id, $meta_key, $single_meta_value );
				}
			} else {
				add_metadata( 'post', $post_id, $meta_key, $meta_value );
			}
		}
	}

	public function delete_meta( int $post_id ) {
		$meta = $this->get_meta( $post_id );

		foreach ( $meta as $meta_key => $meta_value ) {
			delete_metadata( 'post', $post_id, $meta_key );
		}
	}

	public function field( $value, $field, $revision ) {
		$revision_id = $revision->ID;
		$meta        = $this->get_meta( $revision_id );

		// format response as single string with all custom fields / metadata.
		$return = '';
		foreach ( $meta as $meta_key => $meta_value ) {
			$return .= $meta_key . ': ' . join( ', ', $meta_value ) . "\n";
		}

		return $return;
	}

	public function fields( array $fields = array() ): array {
		$fields['custom_fields'] = __( 'Další pole', 'skautis-integration' );

		return $fields;
	}

	public function restore_revision( int $post_id, int $revision_id ) {
		$meta = $this->get_meta( $revision_id );
		$this->delete_meta( $post_id );
		$this->insert_meta( $post_id, $meta );

		// also update last revision custom fields.
		$revisions = wp_get_post_revisions( $post_id );
		if ( count( $revisions ) > 0 ) {
			$last_revision = current( $revisions );
			$this->delete_meta( $last_revision->ID );
			$this->insert_meta( $last_revision->ID, $meta );
		}
	}

	public function save_post( int $post_id ) {
		if ( wp_is_post_revision( $post_id ) ) {
			$meta = $this->get_meta( $post_id );
			if ( false === $meta ) {
				return;
			}

			$this->insert_meta( $post_id, $meta );
		}
	}

	public function post_has_changed( bool $post_has_changed, \WP_Post $last_revision, \WP_Post $post ): bool {
		if ( ! $post_has_changed ) {
			$meta     = $this->get_meta( $last_revision->ID );
			$meta_new = $this->get_meta( $post->ID );

			if ( $meta === $meta_new ) {
				return $post_has_changed;
			}

			// Post changed.
			return true;
		}

		return $post_has_changed;
	}

}
