<?php

declare( strict_types=1 );

namespace SkautisIntegration\Modules\Visibility\Frontend;

use SkautisIntegration\Rules\Rules_Manager;
use SkautisIntegration\Auth\Skautis_Login;
use SkautisIntegration\Auth\WP_Login_Logout;
use SkautisIntegration\Utils\Helpers;

final class Frontend {

	private $post_types;
	private $rules_manager;
	private $skautis_login;
	private $wp_login_logout;

	public function __construct( array $post_types, Rules_Manager $rules_manager, Skautis_Login $skautis_login, WP_Login_Logout $wp_login_logout ) {
		$this->post_types      = $post_types;
		$this->rules_manager   = $rules_manager;
		$this->skautis_login   = $skautis_login;
		$this->wp_login_logout = $wp_login_logout;
	}

	public function init_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'posts_results', array( $this, 'filter_posts' ), 10, 2 );
	}

	private function get_login_form( bool $force_logout_from_skautis = false ): string {
		$login_url_args = add_query_arg( 'noWpLogin', true, Helpers::get_current_url() );
		if ( $force_logout_from_skautis ) {
			$login_url_args = add_query_arg( 'logoutFromSkautis', true, $login_url_args );
		}

		return '
		<div class="wp-core-ui">
			<p style="margin-bottom: 0.3em;">
				<a class="button button-primary button-hero button-skautis"
				   href="' . $this->wp_login_logout->get_login_url( $login_url_args ) . '">' . __( 'Log in with skautIS', 'skautis-integration' ) . '</a>
			</p>
		</div>
		<br/>
		';
	}

	private function get_login_required_message(): string {
		return '<p>' . __( 'To view this content you must be logged in skautIS', 'skautis-integration' ) . '</p>';
	}

	private function get_unauthorized_message(): string {
		return '<p>' . __( 'You do not have permission to access this content', 'skautis-integration' ) . '</p>';
	}

	private function get_posts_hierarchy_tree_with_rules( int $post_id, $post_type ): array {
		$ancestors = get_ancestors( $post_id, $post_type, 'post_type' );
		$ancestors = array_map(
			function ( $ancestor_post_id ) {
				return array(
					'id'              => $ancestor_post_id,
					'rules'           => (array) get_post_meta( $ancestor_post_id, SKAUTISINTEGRATION_NAME . '_rules', true ),
					'includeChildren' => get_post_meta( $ancestor_post_id, SKAUTISINTEGRATION_NAME . '_rules_includeChildren', true ),
					'visibilityMode'  => get_post_meta( $ancestor_post_id, SKAUTISINTEGRATION_NAME . '_rules_visibilityMode', true ),
				);
			},
			$ancestors
		);

		return array_reverse( $ancestors );
	}

	private function get_rules_from_parent_posts_with_impact_by_child_post_id( int $child_post_id, $post_type ): array {
		$ancestors = $this->get_posts_hierarchy_tree_with_rules( $child_post_id, $post_type );

		$ancestors = array_filter(
			$ancestors,
			function ( $ancestor ) {
				if ( ! empty( $ancestor['rules'] ) && isset( $ancestor['rules'][0][ SKAUTISINTEGRATION_NAME . '_rules' ] ) ) {
					if ( '1' === $ancestor['includeChildren'] ) {
						return true;
					}
				}

				return false;
			}
		);

		return array_values( $ancestors );
	}

	private function hide_content_excerpt_comments( int $post_id, string $new_content = '', string $new_excerpt = '' ) {
		add_filter(
			'the_content',
			function ( string $content = '' ) use ( $post_id, $new_content ) {
				if ( get_the_ID() === $post_id ) {
					return $new_content;
				}

				return $content;
			}
		);

		add_filter(
			'the_excerpt',
			function ( string $excerpt = '' ) use ( $post_id, $new_excerpt ) {
				if ( get_the_ID() === $post_id ) {
					return $new_excerpt;
				}

				return $excerpt;
			}
		);

		add_action(
			'pre_get_comments',
			function ( \WP_Comment_Query $wp_comment_query ) use ( $post_id ) {
				if ( $wp_comment_query->query_vars['post_id'] === $post_id ) {
					if ( ! isset( $wp_comment_query->query_vars['post__not_in'] ) || empty( $wp_comment_query->query_vars['post__not_in'] ) ) {
						$wp_comment_query->query_vars['post__not_in'] = array();
					} elseif ( ! is_array( $wp_comment_query->query_vars['post__not_in'] ) ) {
						$wp_comment_query->query_vars['post__not_in'] = array( $wp_comment_query->query_vars['post__not_in'] );
					}
					$wp_comment_query->query_vars['post__not_in'][] = $post_id;
				}
			}
		);
	}

	private function process_rules_and_hide_posts( bool $user_is_logged_in_skautis, array $rule, array &$posts, int $post_key, \WP_Query $wp_query, string $post_type, &$posts_were_filtered = false ) {
		if ( ! empty( $rules ) && isset( $rules[0][ SKAUTISINTEGRATION_NAME . '_rules' ] ) ) {
			if ( ! $user_is_logged_in_skautis ||
				! $this->rules_manager->check_if_user_passed_rules( $rules ) ) {
				unset( $posts[ $post_key ] );
				unset( $wp_query->posts[ $post_key ] );
				if ( $wp_query->found_posts > 0 ) {
					$wp_query->found_posts --;
				}
				$posts_were_filtered = true;
			}
		}
	}

	private function process_rules_and_hide_content( bool $user_is_logged_in_skautis, array $rules, int $post_id ) {
		if ( ! empty( $rules ) && isset( $rules[0][ SKAUTISINTEGRATION_NAME . '_rules' ] ) ) {
			if ( ! $user_is_logged_in_skautis ) {
				$this->hide_content_excerpt_comments( $post_id, $this->get_login_required_message() . $this->get_login_form(), $this->get_login_required_message() );
			} elseif ( ! $this->rules_manager->check_if_user_passed_rules( $rules ) ) {
				$this->hide_content_excerpt_comments( $post_id, $this->get_unauthorized_message() . $this->get_login_form( true ), $this->get_unauthorized_message() );
			}
		}
	}

	public function enqueue_styles() {
		wp_enqueue_style( 'buttons' );
		wp_enqueue_style( SKAUTISINTEGRATION_NAME, SKAUTISINTEGRATION_URL . 'src/frontend/public/css/skautis-frontend.css', array(), SKAUTISINTEGRATION_VERSION, 'all' );
	}

	public function get_parent_posts_with_rules( int $child_post_id, string $child_post_type ): array {
		$result = array();

		$parent_posts_with_rules = $this->get_rules_from_parent_posts_with_impact_by_child_post_id( $child_post_id, $child_post_type );

		foreach ( $parent_posts_with_rules as $parent_post_with_rules ) {
			$result[ $parent_post_with_rules['id'] ] = array(
				'parentPostTitle' => get_the_title( $parent_post_with_rules['id'] ),
				'rules'           => array(),
			);

			foreach ( $parent_post_with_rules['rules'] as $rule ) {
				$result[ $parent_post_with_rules['id'] ]['rules'][ $rule['skautis-integration_rules'] ] = get_the_title( $rule['skautis-integration_rules'] );
			}
		}

		return $result;
	}

	public function filter_posts( array $posts, \WP_Query $wp_query ): array {
		if ( empty( $posts ) ) {
			return $posts;
		}

		$user_is_logged_in_skautis = $this->skautis_login->is_user_logged_in_skautis();

		$posts_were_filtered = false;

		foreach ( $wp_query->posts as $key => $post ) {
			if ( ! is_a( $post, 'WP_Post' ) ) {
				$wp_post = get_post( $post );
			} else {
				$wp_post = $post;
			}

			if ( in_array( $wp_post->post_type, $this->post_types, true ) ) {
				if ( ! current_user_can( 'edit_' . $wp_post->post_type . 's' ) ) {
					$rules_groups = array();

					if ( $wp_post->post_parent > 0 ) {
						$rules_groups = $this->get_rules_from_parent_posts_with_impact_by_child_post_id( $wp_post->ID, $wp_post->post_type );
					}

					$current_post_rules = (array) get_post_meta( $wp_post->ID, SKAUTISINTEGRATION_NAME . '_rules', true );
					if ( ! empty( $current_post_rules ) ) {
						$current_post_rule = array(
							'id'              => $wp_post->ID,
							'rules'           => $current_post_rules,
							'includeChildren' => get_post_meta( $wp_post->ID, SKAUTISINTEGRATION_NAME . '_rules_includeChildren', true ),
							'visibilityMode'  => get_post_meta( $wp_post->ID, SKAUTISINTEGRATION_NAME . '_rules_visibilityMode', true ),
						);
						$rules_groups[]    = $current_post_rule;
					}

					foreach ( $rules_groups as $rules_group ) {
						if ( 'content' === $rules_group['visibilityMode'] ) {
							$this->process_rules_and_hide_content( $user_is_logged_in_skautis, $rules_group['rules'], $wp_post->ID );
						} else {
							$this->process_rules_and_hide_posts( $user_is_logged_in_skautis, $rules_group['rules'], $posts, $key, $wp_query, $wp_post->post_type, $posts_were_filtered );
						}
					}
				}
			}
		}

		if ( $posts_were_filtered ) {
			$wp_query->posts = array_values( $wp_query->posts );
			$posts           = array_values( $posts );
		}

		return $posts;
	}

}
