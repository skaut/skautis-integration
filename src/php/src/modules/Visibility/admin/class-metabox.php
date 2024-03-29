<?php
/**
 * Contains the Metabox class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Modules\Visibility\Admin;

use Skautis_Integration\Rules\Rules_Manager;
use Skautis_Integration\Modules\Visibility\Frontend\Frontend;
use Skautis_Integration\Utils\Request_Parameter_Helpers;

/**
 * Adds the visibility metabox to the post editor.
 *
 * @phan-constructor-used-for-side-effects
 */
final class Metabox {

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
		add_action( 'add_meta_boxes', array( $this, 'add_metabox_for_rules_field' ) );
		add_action( 'save_post', array( self::class, 'save_rules_custom_field' ) );
	}

	/**
	 * Adds the metabox to WordPress.
	 *
	 * @return void
	 */
	public function add_metabox_for_rules_field() {
		foreach ( $this->post_types as $post_type ) {
			add_meta_box(
				SKAUTIS_INTEGRATION_NAME . '_modules_visibility_rules_metabox',
				__( 'SkautIS pravidla', 'skautis-integration' ),
				array( $this, 'rules_repeater' ),
				$post_type
			);
		}
	}

	/**
	 * Saves the data from the metabox to the post meta.
	 *
	 * @param int $post_id The ID of the post.
	 *
	 * @return void
	 */
	public static function save_rules_custom_field( int $post_id ) {
		if ( false === wp_verify_nonce( Request_Parameter_Helpers::post_string_variable( SKAUTIS_INTEGRATION_NAME . '_visibility_metabox_nonce' ), SKAUTIS_INTEGRATION_NAME . '_visibility_metabox' ) ) {
			return;
		}

		$visibility_mode = Request_Parameter_Helpers::post_meta_variable( SKAUTIS_INTEGRATION_NAME . '_rules_visibilityMode', SKAUTIS_INTEGRATION_NAME . '_rules_visibilityMode' );
		if ( '' !== $visibility_mode ) {
			$rules = Request_Parameter_Helpers::post_meta_variable( SKAUTIS_INTEGRATION_NAME . '_rules', SKAUTIS_INTEGRATION_NAME . '_rules', array() );
			update_post_meta(
				$post_id,
				SKAUTIS_INTEGRATION_NAME . '_rules',
				$rules
			);

			$include_children = Request_Parameter_Helpers::post_meta_variable( SKAUTIS_INTEGRATION_NAME . '_rules_includeChildren', SKAUTIS_INTEGRATION_NAME . '_rules_includeChildren', 0 );
			update_post_meta(
				$post_id,
				SKAUTIS_INTEGRATION_NAME . '_rules_includeChildren',
				$include_children
			);

			update_post_meta(
				$post_id,
				SKAUTIS_INTEGRATION_NAME . '_rules_visibilityMode',
				$visibility_mode
			);
		}
	}

	/**
	 * Prints the rules metabox.
	 *
	 * @param \WP_Post $post The post for which the metabox is printed.
	 *
	 * @return void
	 */
	public function rules_repeater( \WP_Post $post ) {
		$post_type_object = get_post_type_object( $post->post_type );
		$include_children = get_post_meta( $post->ID, SKAUTIS_INTEGRATION_NAME . '_rules_includeChildren', true );
		if ( '0' !== $include_children && '1' !== $include_children ) {
			$include_children = get_option( SKAUTIS_INTEGRATION_NAME . '_modules_visibility_includeChildren', 0 );
		}

		$visibility_mode = get_post_meta( $post->ID, SKAUTIS_INTEGRATION_NAME . '_rules_visibilityMode', true );
		if ( 'content' !== $visibility_mode && 'full' !== $visibility_mode ) {
			$visibility_mode = get_option( SKAUTIS_INTEGRATION_NAME . '_modules_visibility_visibilityMode', 0 );
		}

		wp_nonce_field( SKAUTIS_INTEGRATION_NAME . '_visibility_metabox', SKAUTIS_INTEGRATION_NAME . '_visibility_metabox_nonce' );

		if ( $post->post_parent > 0 ) {
			$parent_rules = $this->frontend->get_parent_posts_with_rules( absint( $post->ID ), $post->post_type );
			if ( ! empty( $parent_rules ) ) {
				?>
				<h4><?php esc_html_e( 'Pravidla převzatá z nadřazených stránek', 'skautis-integration' ); ?>:</h4>
				<ul id="skautis_modules_visibility_parentRules" class="skautis-admin-list">
					<?php
					foreach ( $parent_rules as $parent_rule ) {
						?>
						<li>
							<strong><?php echo esc_html( $parent_rule['parentPostTitle'] ); ?></strong>
							<ul>
								<?php
								foreach ( $parent_rule['rules'] as $rule_id => $rule ) {
									?>
									<li data-rule="<?php echo esc_attr( strval( $rule_id ) ); ?>"><?php echo esc_html( $rule ); ?></li>
									<?php
								}
								?>
							</ul>
						</li>
						<?php
					}
					?>
				</ul>
				<hr/>
				<?php
			}
		}
		?>

		<p><?php esc_html_e( 'Obsah bude pro uživatele viditelný pouze při splnění alespoň jednoho z následujících pravidel.', 'skautis-integration' ); ?></p>
		<p><?php esc_html_e( 'Ponecháte-li prázdné - obsah bude viditelný pro všechny uživatele.', 'skautis-integration' ); ?></p>
		<div id="repeater_post">
			<div data-repeater-list="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_rules">
				<div data-repeater-item>

					<select name="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_rules" class="rule select2">
						<?php
						foreach ( $this->rules_manager->get_all_rules() as $rule ) {
							echo '<option value="' . esc_attr( strval( $rule->ID ) ) . '">' . esc_html( $rule->post_title ) . '</option>';
						}
						?>
					</select>

					<input data-repeater-delete type="button"
						value="<?php esc_attr_e( 'Odstranit', 'skautis-integration' ); ?>"/>
				</div>
			</div>
			<input data-repeater-create type="button" value="<?php esc_attr_e( 'Přidat', 'skautis-integration' ); ?>"/>
		</div>
		<p>
			<label>
				<input type="hidden" name="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_rules_includeChildren"
					value="0"/>
				<input type="checkbox" name="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_rules_includeChildren"
					value="1" <?php checked( 1, $include_children ); ?> /><span>
												<?php
												if ( null !== $post_type_object && $post_type_object->hierarchical ) {
													/* translators: the type of the SkautIS unit */
													printf( esc_html__( 'Použít vybraná pravidla i na podřízené %s', 'skautis-integration' ), esc_html( lcfirst( $post_type_object->labels->name ) ) );
												} else {
													esc_html_e( 'Použít vybraná pravidla i na podřízený obsah (média - obrázky, videa, přílohy,...)', 'skautis-integration' );
												}
												?>
					.</span></label>
		</p>
		<p>
			<label><input type="radio" name="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_rules_visibilityMode"
						value="full" <?php checked( 'full', $visibility_mode ); ?> /><span><?php esc_html_e( 'Úplně skrýt', 'skautis-integration' ); ?></span></label>
			<br/>
			<label><input type="radio" name="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_rules_visibilityMode"
						value="content" <?php checked( 'content', $visibility_mode ); ?> /><span><?php esc_html_e( 'Skrýt pouze obsah', 'skautis-integration' ); ?></span></label>
		</p>
		<?php
	}
}
