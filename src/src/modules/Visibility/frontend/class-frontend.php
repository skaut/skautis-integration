<?php

declare( strict_types=1 );

namespace SkautisIntegration\Modules\Visibility\Frontend;

use SkautisIntegration\Rules\RulesManager;
use SkautisIntegration\Auth\SkautisLogin;
use SkautisIntegration\Auth\WP_Login_Logout;
use SkautisIntegration\Utils\Helpers;

final class Frontend {

	private $postTypes;
	private $rulesManager;
	private $skautisLogin;
	private $wpLoginLogout;

	public function __construct( array $postTypes, RulesManager $rulesManager, SkautisLogin $skautisLogin, WP_Login_Logout $wpLoginLogout ) {
		$this->postTypes     = $postTypes;
		$this->rulesManager  = $rulesManager;
		$this->skautisLogin  = $skautisLogin;
		$this->wpLoginLogout = $wpLoginLogout;
	}

	public function initHooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueueStyles' ) );
		add_action( 'posts_results', array( $this, 'filterPosts' ), 10, 2 );
	}

	private function getLoginForm( bool $forceLogoutFromSkautis = false ): string {
		$loginUrlArgs = add_query_arg( 'noWpLogin', true, Helpers::getCurrentUrl() );
		if ( $forceLogoutFromSkautis ) {
			$loginUrlArgs = add_query_arg( 'logoutFromSkautis', true, $loginUrlArgs );
		}

		return '
		<div class="wp-core-ui">
			<p style="margin-bottom: 0.3em;">
				<a class="button button-primary button-hero button-skautis"
				   href="' . $this->wpLoginLogout->getLoginUrl( $loginUrlArgs ) . '">' . __( 'Log in with skautIS', 'skautis-integration' ) . '</a>
			</p>
		</div>
		<br/>
		';
	}

	private function getLoginRequiredMessage(): string {
		return '<p>' . __( 'To view this content you must be logged in skautIS', 'skautis-integration' ) . '</p>';
	}

	private function getUnauthorizedMessage(): string {
		return '<p>' . __( 'You do not have permission to access this content', 'skautis-integration' ) . '</p>';
	}

	private function getPostsHierarchyTreeWithRules( int $postId, $postType ): array {
		$ancestors = get_ancestors( $postId, $postType, 'post_type' );
		$ancestors = array_map(
			function ( $ancestorPostId ) {
				return array(
					'id'              => $ancestorPostId,
					'rules'           => (array) get_post_meta( $ancestorPostId, SKAUTISINTEGRATION_NAME . '_rules', true ),
					'includeChildren' => get_post_meta( $ancestorPostId, SKAUTISINTEGRATION_NAME . '_rules_includeChildren', true ),
					'visibilityMode'  => get_post_meta( $ancestorPostId, SKAUTISINTEGRATION_NAME . '_rules_visibilityMode', true ),
				);
			},
			$ancestors
		);

		return array_reverse( $ancestors );
	}

	private function getRulesFromParentPostsWithImpactByChildPostId( int $childPostId, $postType ): array {
		$ancestors = $this->getPostsHierarchyTreeWithRules( $childPostId, $postType );

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

	private function hideContentExcerptComments( int $postId, string $newContent = '', string $newExcerpt = '' ) {
		add_filter(
			'the_content',
			function ( string $content = '' ) use ( $postId, $newContent ) {
				if ( get_the_ID() === $postId ) {
					return $newContent;
				}

				return $content;
			}
		);

		add_filter(
			'the_excerpt',
			function ( string $excerpt = '' ) use ( $postId, $newExcerpt ) {
				if ( get_the_ID() === $postId ) {
					return $newExcerpt;
				}

				return $excerpt;
			}
		);

		add_action(
			'pre_get_comments',
			function ( \WP_Comment_Query $wpCommentQuery ) use ( $postId ) {
				if ( $wpCommentQuery->query_vars['post_id'] === $postId ) {
					if ( ! isset( $wpCommentQuery->query_vars['post__not_in'] ) || empty( $wpCommentQuery->query_vars['post__not_in'] ) ) {
						$wpCommentQuery->query_vars['post__not_in'] = array();
					} elseif ( ! is_array( $wpCommentQuery->query_vars['post__not_in'] ) ) {
						$wpCommentQuery->query_vars['post__not_in'] = array( $wpCommentQuery->query_vars['post__not_in'] );
					}
					$wpCommentQuery->query_vars['post__not_in'][] = $postId;
				}
			}
		);
	}

	private function proccessRulesAndHidePosts( bool $userIsLoggedInSkautis, array $rule, array &$posts, int $postKey, \WP_Query $wpQuery, string $postType, &$postsWereFiltered = false ) {
		if ( ! empty( $rules ) && isset( $rules[0][ SKAUTISINTEGRATION_NAME . '_rules' ] ) ) {
			if ( ! $userIsLoggedInSkautis ||
				! $this->rulesManager->checkIfUserPassedRules( $rules ) ) {
				unset( $posts[ $postKey ] );
				unset( $wpQuery->posts[ $postKey ] );
				if ( $wpQuery->found_posts > 0 ) {
					$wpQuery->found_posts --;
				}
				$postsWereFiltered = true;
			}
		}
	}

	private function processRulesAndHideContent( bool $userIsLoggedInSkautis, array $rules, int $postId ) {
		if ( ! empty( $rules ) && isset( $rules[0][ SKAUTISINTEGRATION_NAME . '_rules' ] ) ) {
			if ( ! $userIsLoggedInSkautis ) {
				$this->hideContentExcerptComments( $postId, $this->getLoginRequiredMessage() . $this->getLoginForm(), $this->getLoginRequiredMessage() );
			} elseif ( ! $this->rulesManager->checkIfUserPassedRules( $rules ) ) {
				$this->hideContentExcerptComments( $postId, $this->getUnauthorizedMessage() . $this->getLoginForm( true ), $this->getUnauthorizedMessage() );
			}
		}
	}

	public function enqueueStyles() {
		wp_enqueue_style( 'buttons' );
		wp_enqueue_style( SKAUTISINTEGRATION_NAME, SKAUTISINTEGRATION_URL . 'src/frontend/public/css/skautis-frontend.css', array(), SKAUTISINTEGRATION_VERSION, 'all' );
	}

	public function getParentPostsWithRules( int $childPostId, string $childPostType ): array {
		$result = array();

		$parentPostsWithRules = $this->getRulesFromParentPostsWithImpactByChildPostId( $childPostId, $childPostType );

		foreach ( $parentPostsWithRules as $parentPostWithRules ) {
			$result[ $parentPostWithRules['id'] ] = array(
				'parentPostTitle' => get_the_title( $parentPostWithRules['id'] ),
				'rules'           => array(),
			);

			foreach ( $parentPostWithRules['rules'] as $rule ) {
				$result[ $parentPostWithRules['id'] ]['rules'][ $rule['skautis-integration_rules'] ] = get_the_title( $rule['skautis-integration_rules'] );
			}
		}

		return $result;
	}

	public function filterPosts( array $posts, \WP_Query $wpQuery ): array {
		if ( empty( $posts ) ) {
			return $posts;
		}

		$userIsLoggedInSkautis = $this->skautisLogin->isUserLoggedInSkautis();

		$postsWereFiltered = false;

		foreach ( $wpQuery->posts as $key => $post ) {
			if ( ! is_a( $post, 'WP_Post' ) ) {
				$wpPost = get_post( $post );
			} else {
				$wpPost = $post;
			}

			if ( in_array( $wpPost->post_type, $this->postTypes, true ) ) {
				if ( ! current_user_can( 'edit_' . $wpPost->post_type . 's' ) ) {
					$rulesGroups = array();

					if ( $wpPost->post_parent > 0 ) {
						$rulesGroups = $this->getRulesFromParentPostsWithImpactByChildPostId( $wpPost->ID, $wpPost->post_type );
					}

					$currentPostRules = (array) get_post_meta( $wpPost->ID, SKAUTISINTEGRATION_NAME . '_rules', true );
					if ( ! empty( $currentPostRules ) ) {
						$currentPostRule = array(
							'id'              => $wpPost->ID,
							'rules'           => $currentPostRules,
							'includeChildren' => get_post_meta( $wpPost->ID, SKAUTISINTEGRATION_NAME . '_rules_includeChildren', true ),
							'visibilityMode'  => get_post_meta( $wpPost->ID, SKAUTISINTEGRATION_NAME . '_rules_visibilityMode', true ),
						);
						$rulesGroups[]   = $currentPostRule;
					}

					foreach ( $rulesGroups as $rulesGroup ) {
						if ( 'content' === $rulesGroup['visibilityMode'] ) {
							$this->processRulesAndHideContent( $userIsLoggedInSkautis, $rulesGroup['rules'], $wpPost->ID );
						} else {
							$this->proccessRulesAndHidePosts( $userIsLoggedInSkautis, $rulesGroup['rules'], $posts, $key, $wpQuery, $wpPost->post_type, $postsWereFiltered );
						}
					}
				}
			}
		}

		if ( $postsWereFiltered ) {
			$wpQuery->posts = array_values( $wpQuery->posts );
			$posts          = array_values( $posts );
		}

		return $posts;
	}

}
