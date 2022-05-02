<?php
/**
 * Contains the Frontend class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Modules\Visibility\Frontend;

use Skautis_Integration\Rules\Rules_Manager;
use Skautis_Integration\Auth\Skautis_Login;
use Skautis_Integration\Auth\WP_Login_Logout;
use Skautis_Integration\Utils\Helpers;

/**
 * Handles the frontend part of the visibility rules - hides posts or their contents, shows notices and a login form.
 */
final class Frontend {

	/**
	 * A list of post types to activate the Visibility module for.
	 *
	 * @var array
	 */
	private $post_types;

	/**
	 * A link to the Rules_Manager service instance.
	 *
	 * @var Rules_Manager
	 */
	private $rules_manager;

	/**
	 * A link to the Skautis_Login service instance.
	 *
	 * @var Skautis_Login
	 */
	private $skautis_login;

	/**
	 * A link to the WP_Login_Logout service instance.
	 *
	 * @var WP_Login_Logout
	 */
	private $wp_login_logout;

	/**
	 * Constructs the service and saves all dependencies.
	 *
	 * @param array           $post_types A list of post types to activate the Visibility module for.
	 * @param Rules_Manager   $rules_manager An injected Rules_Manager service instance.
	 * @param Skautis_Login   $skautis_login An injected Skautis_Login service instance.
	 * @param WP_Login_Logout $wp_login_logout An injected WP_Login_Logout service instance.
	 */
	public function __construct( array $post_types, Rules_Manager $rules_manager, Skautis_Login $skautis_login, WP_Login_Logout $wp_login_logout ) {
		$this->post_types      = $post_types;
		$this->rules_manager   = $rules_manager;
		$this->skautis_login   = $skautis_login;
		$this->wp_login_logout = $wp_login_logout;
	}

	/**
	 * Intializes all hooks used by the object.
	 */
	public function init_hooks() {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_styles' ) );
		add_action( 'posts_results', array( $this, 'filter_posts' ), 10, 2 );
	}

	/**
	 * Returns HTML code for the frontend SkautIS login button.
	 *
	 * @param bool $force_logout_from_skautis Whether to log the user out of SkautIS before attempting to log them in.
	 */
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

	/**
	 * Returns HTML code for the frontend message telling the user they need to log in to SkautIS to view the content.
	 */
	private static function get_login_required_message(): string {
		return '<p>' . __( 'To view this content you must be logged in skautIS', 'skautis-integration' ) . '</p>';
	}

	/**
	 * Returns HTML code for the frontend message telling the user they didn't pass the visibility rules.
	 */
	private static function get_unauthorized_message(): string {
		return '<p>' . __( 'You do not have permission to access this content', 'skautis-integration' ) . '</p>';
	}

	/**
	 * Returns a list of post ancestors.
	 *
	 * @param int    $post_id The ID of the root post.
	 * @param string $post_type The type of the root post.
	 */
	private static function get_posts_hierarchy_tree_with_rules( int $post_id, $post_type ): array {
		$ancestors = get_ancestors( $post_id, $post_type, 'post_type' );
		$ancestors = array_map(
			static function ( $ancestor_post_id ) {
				return array(
					'id'              => $ancestor_post_id,
					'rules'           => (array) get_post_meta( $ancestor_post_id, SKAUTIS_INTEGRATION_NAME . '_rules', true ),
					'includeChildren' => get_post_meta( $ancestor_post_id, SKAUTIS_INTEGRATION_NAME . '_rules_includeChildren', true ),
					'visibilityMode'  => get_post_meta( $ancestor_post_id, SKAUTIS_INTEGRATION_NAME . '_rules_visibilityMode', true ),
				);
			},
			$ancestors
		);

		return array_reverse( $ancestors );
	}

	/**
	 * Returns a list of post ancestors that have rules that should apply to the current post.
	 *
	 * @param int    $child_post_id The ID of the root post.
	 * @param string $post_type The type of the root post.
	 */
	private static function get_rules_from_parent_posts_with_impact_by_child_post_id( int $child_post_id, $post_type ): array {
		$ancestors = self::get_posts_hierarchy_tree_with_rules( $child_post_id, $post_type );

		$ancestors = array_filter(
			$ancestors,
			static function ( $ancestor ) {
				if ( ! empty( $ancestor['rules'] ) && isset( $ancestor['rules'][0][ SKAUTIS_INTEGRATION_NAME . '_rules' ] ) ) {
					if ( '1' === $ancestor['includeChildren'] ) {
						return true;
					}
				}

				return false;
			}
		);

		return array_values( $ancestors );
	}

	/**
	 * Hides post comments and replaces its excerpt and content.
	 *
	 * @param int    $post_id The ID of the post to modify.
	 * @param string $new_content The replacement post content.
	 * @param string $new_excerpt The replacement post excerpt.
	 */
	private static function hide_content_excerpt_comments( int $post_id, string $new_content = '', string $new_excerpt = '' ) {
		add_filter(
			'the_content',
			static function ( string $content = '' ) use ( $post_id, $new_content ) {
				if ( get_the_ID() === $post_id ) {
					return $new_content;
				}

				return $content;
			}
		);

		add_filter(
			'the_excerpt',
			static function ( string $excerpt = '' ) use ( $post_id, $new_excerpt ) {
				if ( get_the_ID() === $post_id ) {
					return $new_excerpt;
				}

				return $excerpt;
			}
		);

		add_action(
			'pre_get_comments',
			static function ( \WP_Comment_Query $wp_comment_query ) use ( $post_id ) {
				$query_vars = $wp_comment_query->query_vars;
				if ( array_key_exists( 'post_id', $query_vars ) && $query_vars['post_id'] === $post_id ) {
					if ( ! isset( $query_vars['post__not_in'] ) || empty( $query_vars['post__not_in'] ) ) {
						$query_vars['post__not_in'] = array();
					} elseif ( ! is_array( $query_vars['post__not_in'] ) ) {
						$query_vars['post__not_in'] = array( $query_vars['post__not_in'] );
					}
					$query_vars['post__not_in'][] = $post_id;
				}
			}
		);
	}

	/**
	 * Hides posts if the current user isn't logged in to SkautIS or doesn't pass the visibility rules
	 *
	 * TODO: This function modifies its parameters.
	 *
	 * @param bool      $user_is_logged_in_skautis Whether the current user is logged in to SkautIS.
	 * @param array     $posts A list of posts to filter. This parameter is modified by the function.
	 * @param int       $post_key The ID of the post to hide.
	 * @param \WP_Query $wp_query The WordPress request.
	 * @param bool      $posts_were_filtered Whether the posts were already filtered.
	 */
	private function process_rules_and_hide_posts( bool $user_is_logged_in_skautis, array &$posts, int $post_key, \WP_Query $wp_query, &$posts_were_filtered = false ) {
		if ( ! empty( $rules ) && isset( $rules[0][ SKAUTIS_INTEGRATION_NAME . '_rules' ] ) ) {
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

	/**
	 * Hides posts' content if the current user isn't logged in to SkautIS or doesn't pass the visibility rules
	 *
	 * TODO: Deduplicate with the previous function.
	 *
	 * @param bool  $user_is_logged_in_skautis Whether the current user is logged in to SkautIS.
	 * @param array $rules A list of visibility rules to check.
	 * @param int   $post_id The ID of the post to show or hide.
	 */
	private function process_rules_and_hide_content( bool $user_is_logged_in_skautis, array $rules, int $post_id ) {
		if ( ! empty( $rules ) && isset( $rules[0][ SKAUTIS_INTEGRATION_NAME . '_rules' ] ) ) {
			if ( ! $user_is_logged_in_skautis ) {
				self::hide_content_excerpt_comments( $post_id, self::get_login_required_message() . $this->get_login_form(), self::get_login_required_message() );
			} elseif ( ! $this->rules_manager->check_if_user_passed_rules( $rules ) ) {
				self::hide_content_excerpt_comments( $post_id, self::get_unauthorized_message() . $this->get_login_form( true ), self::get_unauthorized_message() );
			}
		}
	}

	/**
	 * Enqueues styles for the frontend part of the Visibility module.
	 */
	public static function enqueue_styles() {
		wp_enqueue_style( 'buttons' );
		wp_enqueue_style( SKAUTIS_INTEGRATION_NAME, SKAUTIS_INTEGRATION_URL . 'src/frontend/public/css/skautis-frontend.css', array(), SKAUTIS_INTEGRATION_VERSION, 'all' );
	}

	/**
	 * Returns a list of post ancestors that have rules that should apply to the current post with said rules.
	 *
	 * TODO: How is this different from get_rules_from_parent_posts_with_impact_by_child_post_id?
	 *
	 * @param int    $child_post_id The ID of the root post.
	 * @param string $child_post_type The type of the root post.
	 *
	 * @suppress PhanPluginPossiblyStaticPublicMethod
	 */
	public function get_parent_posts_with_rules( int $child_post_id, string $child_post_type ): array {
		$result = array();

		$parent_posts_with_rules = self::get_rules_from_parent_posts_with_impact_by_child_post_id( $child_post_id, $child_post_type );

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

	/**
	 * Filters posts based on their visibility.
	 *
	 * Filters which posts are visible for the current user based on wheher they pass the visibility rules and whether whole posts should be hidden or just their contents
	 *
	 * @param array     $posts A list of posts to show.
	 * @param \WP_Query $wp_query The WordPress request.
	 */
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
						$rules_groups = self::get_rules_from_parent_posts_with_impact_by_child_post_id( $wp_post->ID, $wp_post->post_type );
					}

					$current_post_rules = (array) get_post_meta( $wp_post->ID, SKAUTIS_INTEGRATION_NAME . '_rules', true );
					if ( ! empty( $current_post_rules ) ) {
						$current_post_rule = array(
							'id'              => $wp_post->ID,
							'rules'           => $current_post_rules,
							'includeChildren' => get_post_meta( $wp_post->ID, SKAUTIS_INTEGRATION_NAME . '_rules_includeChildren', true ),
							'visibilityMode'  => get_post_meta( $wp_post->ID, SKAUTIS_INTEGRATION_NAME . '_rules_visibilityMode', true ),
						);
						$rules_groups[]    = $current_post_rule;
					}

					foreach ( $rules_groups as $rules_group ) {
						if ( 'content' === $rules_group['visibilityMode'] ) {
							$this->process_rules_and_hide_content( $user_is_logged_in_skautis, $rules_group['rules'], $wp_post->ID );
						} else {
							$this->process_rules_and_hide_posts( $user_is_logged_in_skautis, $posts, $key, $wp_query, $posts_were_filtered );
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
