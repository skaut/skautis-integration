<?php
/**
 * Contains the Block_Editor class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Modules\Visibility\Admin;

use PHPMD\Attribute\SuppressWarnings;
use PHPMD\Rule\UnusedFormalParameter;
use Skautis_Integration\Modules\Visibility\Frontend\Frontend;
use Skautis_Integration\Rules\Rules_Init;
use Skautis_Integration\Rules\Rules_Manager;
use Skautis_Integration\Utils\Helpers;

/**
 * Adds the visibility settings to the block editor.
 *
 * The rules for a whole post are edited in a panel in the post sidebar and the rules for individual blocks in the block sidebar.
 */
final class Block_Editor {

	/**
	 * A list of post types to activate the Visibility module for.
	 *
	 * @var array<string>
	 */
	private $post_types;

	/**
	 * A link to the Rules_Manager service instance.
	 *
	 * @var Rules_Manager
	 */
	private $rules_manager;

	/**
	 * A link to the Frontend service instance.
	 *
	 * @var Frontend
	 */
	private $frontend;

	/**
	 * Constructs the service and saves all dependencies.
	 *
	 * @param array<string> $post_types A list of post types to activate the Visibility module for.
	 * @param Rules_Manager $rules_manager An injected Rules_Manager service instance.
	 * @param Frontend      $frontend An injected Frontend service instance.
	 */
	public function __construct( array $post_types, Rules_Manager $rules_manager, Frontend $frontend ) {
		$this->post_types    = $post_types;
		$this->rules_manager = $rules_manager;
		$this->frontend      = $frontend;
		$this->init_hooks();
	}

	/**
	 * Intializes all hooks used by the object.
	 *
	 * @return void
	 */
	private function init_hooks() {
		// The meta must be registered on every request, the block editor saves it through the REST API.
		add_action( 'init', array( $this, 'register_meta' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_scripts' ) );
		add_action( 'enqueue_block_assets', array( self::class, 'enqueue_canvas_styles' ) );
	}

	/**
	 * Returns whether the current user may see the rules of a post.
	 */
	public static function can_see_rules(): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Hides the meta value in REST API responses from users who cannot edit posts.
	 *
	 * @param mixed $value The meta value.
	 *
	 * @return mixed The value or null.
	 */
	public static function prepare_meta_for_rest( $value ) {
		return self::can_see_rules() ? $value : null;
	}

	/**
	 * Allows editing the rules meta only to users who can edit the post.
	 *
	 * @param bool   $allowed Whether the user can edit the meta @unused-param.
	 * @param string $meta_key The meta key @unused-param.
	 * @param int    $post_id The ID of the post.
	 */
	#[SuppressWarnings( UnusedFormalParameter::class )]
	public static function can_edit_rules( $allowed, $meta_key, $post_id ): bool {
		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Registers the post meta with the visibility rules so that the block editor can edit them.
	 *
	 * The meta keys and their format are the same as the ones saved by the metabox in the classic editor.
	 *
	 * @return void
	 */
	public function register_meta() {
		$auth_callback = array( self::class, 'can_edit_rules' );

		foreach ( $this->post_types as $post_type ) {
			register_post_meta(
				$post_type,
				SKAUTIS_INTEGRATION_NAME . '_rules',
				array(
					'type'          => 'array',
					'single'        => true,
					'default'       => array(),
					'auth_callback' => $auth_callback,
					'show_in_rest'  => array(
						'schema'           => array(
							'type'  => 'array',
							'items' => array(
								'type'                 => 'object',
								'properties'           => array(
									SKAUTIS_INTEGRATION_NAME . '_rules' => array( 'type' => 'string' ),
								),
								'additionalProperties' => false,
							),
						),
						'prepare_callback' => array( self::class, 'prepare_meta_for_rest' ),
					),
				)
			);
			register_post_meta(
				$post_type,
				SKAUTIS_INTEGRATION_NAME . '_rules_includeChildren',
				array(
					'type'          => 'string',
					'single'        => true,
					'default'       => '',
					'auth_callback' => $auth_callback,
					'show_in_rest'  => array(
						'schema'           => array(
							'type' => 'string',
							'enum' => array( '', '0', '1' ),
						),
						'prepare_callback' => array( self::class, 'prepare_meta_for_rest' ),
					),
				)
			);
			register_post_meta(
				$post_type,
				SKAUTIS_INTEGRATION_NAME . '_rules_visibilityMode',
				array(
					'type'          => 'string',
					'single'        => true,
					'default'       => '',
					'auth_callback' => $auth_callback,
					'show_in_rest'  => array(
						'schema'           => array(
							'type' => 'string',
							'enum' => array( '', 'full', 'content' ),
						),
						'prepare_callback' => array( self::class, 'prepare_meta_for_rest' ),
					),
				)
			);
		}
	}

	/**
	 * Returns the rules inherited by the current post from its ancestors.
	 *
	 * @return array<array{title: string, rules: array<string>}> The ancestors with their rules.
	 */
	private function get_parent_rules(): array {
		$post = get_post();
		if ( ! ( $post instanceof \WP_Post ) || $post->post_parent <= 0 ) {
			return array();
		}

		$result = array();
		foreach ( $this->frontend->get_parent_posts_with_rules( $post->ID, $post->post_type ) as $parent ) {
			$result[] = array(
				'title' => $parent['parentPostTitle'],
				'rules' => array_values( $parent['rules'] ),
			);
		}

		return $result;
	}

	/**
	 * Enqueues the block editor script and passes it the available rules and default settings.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		$rules = array();
		foreach ( $this->rules_manager->get_all_rules() as $rule ) {
			$rules[] = array(
				'id'    => $rule->ID,
				'title' => $rule->post_title,
			);
		}

		Helpers::enqueue_script(
			'modules_visibility_block_editor',
			'modules/Visibility/admin/js/skautis-modules-visibility-block-editor.min.js',
			// wp-editor is not a dependency, because it must not be loaded in the widgets editor. The post settings panel is only registered when it is available.
			array( 'wp-block-editor', 'wp-blocks', 'wp-components', 'wp-compose', 'wp-data', 'wp-dom-ready', 'wp-element', 'wp-hooks', 'wp-i18n', 'wp-plugins' )
		);
		wp_set_script_translations( SKAUTIS_INTEGRATION_NAME . '_modules_visibility_block_editor', 'skautis-integration' );
		wp_add_inline_script(
			SKAUTIS_INTEGRATION_NAME . '_modules_visibility_block_editor',
			'window.skautisIntegrationBlockEditor = ' . wp_json_encode(
				array(
					'rules'       => $rules,
					'postTypes'   => array_values( $this->post_types ),
					'parentRules' => $this->get_parent_rules(),
					'defaults'    => array(
						'includeChildren' => '1' === strval( get_option( SKAUTIS_INTEGRATION_NAME . '_modules_visibility_includeChildren', '0' ) ) ? '1' : '0',
						'visibilityMode'  => 'content' === get_option( SKAUTIS_INTEGRATION_NAME . '_modules_visibility_visibilityMode', 'full' ) ? 'content' : 'full',
					),
					'rulesUrl'    => admin_url( 'edit.php?post_type=' . Rules_Init::RULES_TYPE_SLUG ),
				)
			) . ';',
			'before'
		);
	}

	/**
	 * Enqueues the styles marking blocks with rules inside the editor canvas.
	 *
	 * The editor canvas is an iframe, so these styles must be enqueued in the `enqueue_block_assets` action.
	 *
	 * @return void
	 */
	public static function enqueue_canvas_styles() {
		if ( ! is_admin() ) {
			return;
		}
		Helpers::enqueue_style( 'modules_visibility_block_editor', 'modules/Visibility/admin/css/skautis-modules-visibility-block-editor.min.css' );
	}
}
