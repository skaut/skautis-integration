<?php

declare( strict_types=1 );

namespace SkautisIntegration\Modules\Visibility\Frontend;

use SkautisIntegration\Rules\RulesManager;
use SkautisIntegration\Auth\SkautisLogin;

final class Frontend {

	private $postTypes;
	private $rulesManager;
	private $skautisLogin;

	public function __construct( array $postTypes = [], RulesManager $rulesManager, SkautisLogin $skautisLogin ) {
		$this->postTypes    = $postTypes;
		$this->rulesManager = $rulesManager;
		$this->skautisLogin = $skautisLogin;
		$this->initHooks();
	}

	private function initHooks() {
		add_action( 'posts_results', [ $this, 'filterPosts' ], 10, 2 );
	}

	private function getPostsHierarchyTreeWithRules( int $postId, $postType ) {
		$ancestors = get_ancestors( $postId, $postType, 'post_type' );
		$ancestors = array_map( function ( $ancestorPostId ) {
			return [
				'id'              => $ancestorPostId,
				'rules'           => (array) get_post_meta( $ancestorPostId, SKAUTISINTEGRATION_NAME . '_rules', true ),
				'includeChildren' => get_post_meta( $ancestorPostId, SKAUTISINTEGRATION_NAME . '_rules_includeChildren', true )
			];
		}, $ancestors );

		return array_reverse( $ancestors );
	}

	private function getRulesFromTopParentPostByChildPostId( int $childPostId, $postType ): array {
		$ancestors = $this->getPostsHierarchyTreeWithRules( $childPostId, $postType );
		foreach ( $ancestors as $ancestor ) {
			if ( ! empty( $ancestor['rules'] ) && isset( $ancestor['rules'][0][ SKAUTISINTEGRATION_NAME . '_rules' ] ) ) {
				if ( $ancestor['includeChildren'] === '1' ) {
					return $ancestor['rules'];
				}
			}
		}

		return [];
	}

	private function proccessRules( bool $userIsLoggedInSkautis, array $rules = [], array &$posts, int $postKey, \WP_Query $wpQuery, string $postType, &$postsWereFiltered = false ) {
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
					if ( $post->post_parent > 0 ) {
						$rules = $this->getRulesFromTopParentPostByChildPostId( $post->ID, $postType );
						if ( empty( $rules ) || ! isset( $rules[0][ SKAUTISINTEGRATION_NAME . '_rules' ] ) ) {
							$rules = (array) get_post_meta( $post->ID, SKAUTISINTEGRATION_NAME . '_rules', true );
						}
					} else {
						$rules = (array) get_post_meta( $post->ID, SKAUTISINTEGRATION_NAME . '_rules', true );
					}

					$this->proccessRules( $userIsLoggedInSkautis, $rules, $posts, $key, $wpQuery, $postType, $postsWereFiltered );
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
