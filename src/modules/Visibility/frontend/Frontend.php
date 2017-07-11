<?php

declare( strict_types=1 );

namespace SkautisIntegration\Modules\Visibility\Frontend;

use SkautisIntegration\Rules\RulesManager;

final class Frontend {

	private $postTypes;
	private $rulesManager;

	public function __construct( array $postTypes = [], RulesManager $rulesManager ) {
		$this->postTypes    = $postTypes;
		$this->rulesManager = $rulesManager;
		$this->initHooks();
	}

	private function initHooks() {
		add_action( 'posts_results', [ $this, 'filterPosts' ], 10, 2 );
	}

	public function filterPosts( array $posts = [], \WP_Query $wpQuery ): array {
		$postType = '';
		foreach ( $wpQuery->posts as $key => $post ) {
			$postType = get_post_type( $post );
			if ( in_array( $postType, $this->postTypes ) ) {
				if ( ! current_user_can( 'edit_' . $postType, $post->ID ) ) {
					if ( ! $this->rulesManager->checkIfUserPassedRules( (array) get_post_meta( $post->ID, SKAUTISINTEGRATION_NAME . '_rules', true ) ) ) {
						unset( $posts[ $key ] );
						unset( $wpQuery->posts[ $key ] );
						if ( $wpQuery->found_posts > 0 ) {
							$wpQuery->found_posts --;
						}
					}
				}
			}
		}

		return $posts;
	}

}
