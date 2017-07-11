<?php

declare( strict_types=1 );

namespace SkautisIntegration\Modules\Visibility\Frontend;

use SkautisIntegration\Rules\RulesManager;
use SkautisIntegration\Auth\SkautisLogin;
use SkautisIntegration\Auth\WpLoginLogout;
use SkautisIntegration\Utils\Helpers;

final class Frontend {

	private $postTypes;
	private $rulesManager;
	private $skautisLogin;
	private $wpLoginLogout;

	public function __construct( array $postTypes = [], RulesManager $rulesManager, SkautisLogin $skautisLogin, WpLoginLogout $wpLoginLogout ) {
		$this->postTypes     = $postTypes;
		$this->rulesManager  = $rulesManager;
		$this->skautisLogin  = $skautisLogin;
		$this->wpLoginLogout = $wpLoginLogout;
		$this->initHooks();
	}

	private function initHooks() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueueStyles' ] );
		add_action( 'posts_results', [ $this, 'filterPosts' ], 10, 2 );
	}

	private function getLoginForm() {
		return '
		<div class="wp-core-ui">
			<p style="margin-bottom: 0.3em;">
				<a class="button button-primary button-hero pic-lilie"
				   href="' . $this->wpLoginLogout->getLoginUrl( add_query_arg( 'noWpLogin', true, Helpers::getCurrentUrl() ) ) . '">' . __( 'Log in with skautIS', 'skautis-integration' ) . '</a>
			</p>
		</div>
		<br/>
		';
	}

	private function getLoginRequiredMessage() {
		return __( 'Pro zobrazení obsahu je nutné se přihlásit do skautISu', 'skautis-integration' );
	}

	private function getUnauthorizedMessage() {
		return 'Nemáte oprávnění k přístupu k tomuto obsahu ';
	}

	private function getPostsHierarchyTreeWithRules( int $postId, $postType ) {
		$ancestors = get_ancestors( $postId, $postType, 'post_type' );
		$ancestors = array_map( function ( $ancestorPostId ) {
			return [
				'id'              => $ancestorPostId,
				'rules'           => (array) get_post_meta( $ancestorPostId, SKAUTISINTEGRATION_NAME . '_rules', true ),
				'includeChildren' => get_post_meta( $ancestorPostId, SKAUTISINTEGRATION_NAME . '_rules_includeChildren', true ),
				'visibilityMode'  => get_post_meta( $ancestorPostId, SKAUTISINTEGRATION_NAME . '_rules_visibilityMode', true )
			];
		}, $ancestors );

		return array_reverse( $ancestors );
	}

	private function getRulesFromTopParentPostByChildPostId( int $childPostId, $postType ): array {
		$ancestors = $this->getPostsHierarchyTreeWithRules( $childPostId, $postType );
		foreach ( $ancestors as $ancestor ) {
			if ( ! empty( $ancestor['rules'] ) && isset( $ancestor['rules'][0][ SKAUTISINTEGRATION_NAME . '_rules' ] ) ) {
				if ( $ancestor['includeChildren'] === '1' ) {
					return $ancestor;
				}
			}
		}

		return [];
	}

	private function proccessRulesAndHidePosts( bool $userIsLoggedInSkautis, array $rules = [], array &$posts, int $postKey, \WP_Query $wpQuery, string $postType, &$postsWereFiltered = false ) {
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

	private function processRulesAndHideContent( bool $userIsLoggedInSkautis, array $rules = [], array &$posts, int $postKey, \WP_Query $wpQuery ) {
		if ( ! empty( $rules ) && isset( $rules[0][ SKAUTISINTEGRATION_NAME . '_rules' ] ) ) {
			if ( ! $userIsLoggedInSkautis ) {
				$posts[ $postKey ]->post_content = $this->getLoginForm();
				$posts[ $postKey ]->post_excerpt = $this->getLoginRequiredMessage();
				$wpQuery->posts['post_content']  = $this->getLoginForm();
				$wpQuery->posts['post_excerpt']  = $this->getLoginRequiredMessage();
			} else if ( ! $this->rulesManager->checkIfUserPassedRules( $rules ) ) {
				$posts[ $postKey ]->post_content = $this->getUnauthorizedMessage();
				$posts[ $postKey ]->post_excerpt = $this->getUnauthorizedMessage();
				$wpQuery->posts['post_content']  = $this->getUnauthorizedMessage();
				$wpQuery->posts['post_excerpt']  = $this->getUnauthorizedMessage();
			}
		}
	}

	public function enqueueStyles() {
		wp_enqueue_style( 'buttons' );
		wp_enqueue_style( SKAUTISINTEGRATION_NAME, SKAUTISINTEGRATION_URL . 'src/frontend/public/css/skautis-frontend.css', [], SKAUTISINTEGRATION_VERSION, 'all' );
	}

	public function filterPosts( array $posts = [], \WP_Query $wpQuery ): array {
		if ( empty( $posts ) ) {
			return $posts;
		}

		$userIsLoggedInSkautis = $this->skautisLogin->isUserLoggedInSkautis();

		$postsWereFiltered = false;

		foreach ( $wpQuery->posts as $key => $post ) {
			$postType = get_post_type( $post );

			if ( in_array( $postType, $this->postTypes ) ) {
				if ( ! current_user_can( 'edit_' . $postType . 's' ) ) {
					$rules          = [];
					$visibilityMode = '';

					if ( $post->post_parent > 0 ) {
						$topParentPost  = $this->getRulesFromTopParentPostByChildPostId( $post->ID, $postType );
						$rules          = $topParentPost['rules'];
						$visibilityMode = $topParentPost['visibilityMode'];
					}

					if ( empty( $rules ) || ! isset( $rules[0][ SKAUTISINTEGRATION_NAME . '_rules' ] ) ) {
						$rules = (array) get_post_meta( $post->ID, SKAUTISINTEGRATION_NAME . '_rules', true );
					}
					if ( empty( $visibilityMode ) ) {
						$visibilityMode = get_post_meta( $post->ID, SKAUTISINTEGRATION_NAME . '_rules_visibilityMode', true );
					}

					if ( $visibilityMode === 'content' ) {
						$this->processRulesAndHideContent( $userIsLoggedInSkautis, $rules, $posts, $key, $wpQuery );
					} else {
						$this->proccessRulesAndHidePosts( $userIsLoggedInSkautis, $rules, $posts, $key, $wpQuery, $postType, $postsWereFiltered );
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
