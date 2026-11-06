<?php
/**
 * Contains the Block_Visibility class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Modules\Visibility\Frontend;

use Skautis_Integration\Auth\Skautis_Login;
use Skautis_Integration\Rules\Rules_Manager;

/**
 * Hides individual blocks based on the skautIS rules selected in the block settings.
 */
final class Block_Visibility {

	/**
	 * The name of the block attribute containing the IDs of the rules.
	 */
	const RULES_ATTRIBUTE = 'skautisRules';

	/**
	 * The name of the block attribute containing what to show to users who don't pass the rules ("hide" or "showLogin").
	 */
	const MODE_ATTRIBUTE = 'skautisVisibilityMode';

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
	 * A link to the Frontend service instance, used for the login form and messages.
	 *
	 * @var Frontend
	 */
	private $frontend;

	/**
	 * Constructs the service and saves all dependencies.
	 *
	 * @param Rules_Manager $rules_manager An injected Rules_Manager service instance.
	 * @param Skautis_Login $skautis_login An injected Skautis_Login service instance.
	 * @param Frontend      $frontend An injected Frontend service instance.
	 */
	public function __construct( Rules_Manager $rules_manager, Skautis_Login $skautis_login, Frontend $frontend ) {
		$this->rules_manager = $rules_manager;
		$this->skautis_login = $skautis_login;
		$this->frontend      = $frontend;
		$this->init_hooks();
	}

	/**
	 * Intializes all hooks used by the object.
	 *
	 * @return void
	 */
	private function init_hooks() {
		add_filter( 'register_block_type_args', array( self::class, 'add_block_attributes' ) );
		add_filter( 'render_block', array( $this, 'filter_block' ), 10, 2 );
		// Before wp_trim_excerpt(), which generates the excerpt at priority 10.
		add_filter( 'get_the_excerpt', array( $this, 'filter_generated_excerpt' ), 9, 2 );
	}

	/**
	 * Registers the visibility attributes for all blocks.
	 *
	 * The attributes are added to all blocks in the editor by JavaScript, they also need to be registered on the server so that server-side rendered blocks accept them.
	 *
	 * @param array<string, mixed> $args The block type arguments.
	 *
	 * @return array<string, mixed> The updated arguments.
	 */
	public static function add_block_attributes( $args ) {
		if ( ! is_array( $args ) ) {
			return $args;
		}
		if ( ! isset( $args['attributes'] ) || ! is_array( $args['attributes'] ) ) {
			$args['attributes'] = array();
		}

		$args['attributes'][ self::RULES_ATTRIBUTE ] = array(
			'type'    => 'array',
			'items'   => array( 'type' => 'integer' ),
			'default' => array(),
		);
		$args['attributes'][ self::MODE_ATTRIBUTE ]  = array(
			'type'    => 'string',
			'enum'    => array( 'hide', 'showLogin' ),
			'default' => 'hide',
		);

		return $args;
	}

	/**
	 * Returns the IDs of the rules selected for a block.
	 *
	 * @param array<string, mixed> $block The parsed block.
	 *
	 * @return array<int> The rule IDs.
	 */
	public static function get_block_rules( array $block ): array {
		if ( ! isset( $block['attrs'] ) || ! is_array( $block['attrs'] ) || ! isset( $block['attrs'][ self::RULES_ATTRIBUTE ] ) || ! is_array( $block['attrs'][ self::RULES_ATTRIBUTE ] ) ) {
			return array();
		}

		return array_values(
			array_filter(
				array_map( 'intval', $block['attrs'][ self::RULES_ATTRIBUTE ] ),
				static function ( $rule_id ) {
					return $rule_id > 0;
				}
			)
		);
	}

	/**
	 * Checks whether the current user can see blocks hidden by rules without passing them.
	 *
	 * Users who can edit the post see all of its blocks, the same as for whole posts hidden by rules.
	 */
	private static function current_user_can_bypass_rules(): bool {
		$post_id = get_the_ID();
		if ( false !== $post_id && $post_id > 0 ) {
			return current_user_can( 'edit_post', $post_id );
		}

		return current_user_can( 'edit_others_posts' );
	}

	/**
	 * Checks whether the current user passes the rules of a block.
	 *
	 * @param array<int> $rules The IDs of the rules of the block.
	 */
	private function user_passes_rules( array $rules ): bool {
		if ( empty( $rules ) || self::current_user_can_bypass_rules() ) {
			return true;
		}

		return $this->skautis_login->is_user_logged_in_skautis() && $this->rules_manager->check_if_user_passed_rules( $rules );
	}

	/**
	 * Removes the blocks the current user cannot see, including inner blocks.
	 *
	 * @param array<array<string, mixed>> $blocks The parsed blocks.
	 * @param bool                        $changed Set to true if any block was removed.
	 *
	 * @return array<array<string, mixed>> The visible blocks.
	 */
	private function remove_hidden_blocks( array $blocks, bool &$changed ): array {
		$visible = array();
		foreach ( $blocks as $block ) {
			if ( ! $this->user_passes_rules( self::get_block_rules( $block ) ) ) {
				$changed = true;
				continue;
			}
			if ( isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) && ! empty( $block['innerBlocks'] ) ) {
				$inner_changed = false;
				$inner_blocks  = $this->remove_hidden_blocks( $block['innerBlocks'], $inner_changed );
				if ( $inner_changed ) {
					$changed = true;
					// The inner content contains a null placeholder for each inner block, the removed ones must be dropped as well.
					$block = $this->remove_inner_block_placeholders( $block, $inner_blocks );
				}
			}
			$visible[] = $block;
		}

		return $visible;
	}

	/**
	 * Replaces the inner blocks of a block and drops the placeholders of the removed ones from its inner content.
	 *
	 * @param array<string, mixed>        $block The parsed block.
	 * @param array<array<string, mixed>> $inner_blocks The inner blocks to keep.
	 *
	 * @return array<string, mixed> The updated block.
	 */
	private function remove_inner_block_placeholders( array $block, array $inner_blocks ): array {
		$original_inner_blocks = is_array( $block['innerBlocks'] ) ? array_values( $block['innerBlocks'] ) : array();
		$inner_content         = isset( $block['innerContent'] ) && is_array( $block['innerContent'] ) ? $block['innerContent'] : array();
		$new_inner_content     = array();
		$inner_index           = 0;

		foreach ( $inner_content as $chunk ) {
			if ( ! is_null( $chunk ) ) {
				$new_inner_content[] = $chunk;
				continue;
			}
			$original = $original_inner_blocks[ $inner_index ] ?? array();
			++$inner_index;
			// The same check as in remove_hidden_blocks(), which kept or removed the inner block.
			if ( is_array( $original ) && $this->user_passes_rules( self::get_block_rules( $original ) ) ) {
				$new_inner_content[] = null;
			}
		}

		$block['innerBlocks']  = $inner_blocks;
		$block['innerContent'] = $new_inner_content;

		return $block;
	}

	/**
	 * Generates the excerpt without the blocks the current user cannot see.
	 *
	 * When a post has no manual excerpt, wp_trim_excerpt() generates it from the content with excerpt_remove_blocks(), which renders the inner blocks of groups, columns etc. directly, without rendering the wrapper block. The `render_block` filter therefore never sees the rules of the wrapper, so the hidden blocks are removed here before the excerpt is generated.
	 *
	 * @param string        $text The excerpt, empty if the post has no manual excerpt.
	 * @param \WP_Post|null $post The post.
	 *
	 * @return string The excerpt.
	 */
	public function filter_generated_excerpt( $text, $post = null ) {
		if ( '' !== $text || ! ( $post instanceof \WP_Post ) || ! has_blocks( $post->post_content ) ) {
			return $text;
		}

		$changed = false;
		$blocks  = $this->remove_hidden_blocks( parse_blocks( $post->post_content ), $changed );
		if ( ! $changed ) {
			return $text;
		}

		$visible_post               = clone $post;
		$visible_post->post_content = serialize_blocks( $blocks );
		$excerpt                    = wp_trim_excerpt( '', $visible_post );

		// An empty excerpt would make wp_trim_excerpt() generate it again from the full content.
		return '' === $excerpt ? ' ' : $excerpt;
	}

	/**
	 * Hides a block if the current user doesn't pass the rules selected for it.
	 *
	 * This is applied to every rendered block, including inner blocks, so hiding a group hides all of its contents. It also covers the content in the REST API, feeds and excerpts, as all of them render the blocks.
	 *
	 * @param string               $block_content The rendered block.
	 * @param array<string, mixed> $block The parsed block.
	 *
	 * @return string The rendered block, or a replacement if the user doesn't pass the rules.
	 */
	public function filter_block( $block_content, $block ) {
		if ( ! is_array( $block ) ) {
			return $block_content;
		}

		if ( $this->user_passes_rules( self::get_block_rules( $block ) ) ) {
			return $block_content;
		}

		$user_is_logged_in_skautis = $this->skautis_login->is_user_logged_in_skautis();

		$show_login = isset( $block['attrs'][ self::MODE_ATTRIBUTE ] ) && 'showLogin' === $block['attrs'][ self::MODE_ATTRIBUTE ];
		if ( ! $show_login ) {
			return '';
		}

		Frontend::enqueue_styles();
		if ( $user_is_logged_in_skautis ) {
			return Frontend::get_unauthorized_message() . $this->frontend->get_login_form( true );
		}

		return Frontend::get_login_required_message() . $this->frontend->get_login_form();
	}
}
