<?php

declare( strict_types=1 );

namespace Skautis_Integration\Modules\Visibility\Admin;

use Skautis_Integration\Rules\Rules_Manager;
use Skautis_Integration\Modules\Visibility\Frontend\Frontend;

final class Metabox {

	private $post_types;
	private $rules_manager;
	private $frontend;

	public function __construct( array $post_types, Rules_Manager $rules_manager, Frontend $frontend ) {
		$this->post_types    = $post_types;
		$this->rules_manager = $rules_manager;
		$this->frontend      = $frontend;
		$this->init_hooks();
	}

	private function init_hooks() {
		add_action( 'add_meta_boxes', array( $this, 'add_metabox_for_rules_field' ) );
		add_action( 'save_post', array( $this, 'save_rules_custom_field' ) );
	}

	public function add_metabox_for_rules_field() {
		foreach ( $this->post_types as $post_type ) {
			add_meta_box(
				SKAUTISINTEGRATION_NAME . '_modules_visibility_rules_metabox',
				__( 'SkautIS pravidla', 'skautis-integration' ),
				array( $this, 'rules_repeater' ),
				$post_type
			);
		}
	}

	public function save_rules_custom_field( int $post_id ) {
		if ( ! isset( $_POST[ SKAUTISINTEGRATION_NAME . '_visibility_metabox_nonce' ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ SKAUTISINTEGRATION_NAME . '_visibility_metabox_nonce' ] ) ), SKAUTISINTEGRATION_NAME . '_visibility_metabox' ) ) {
			return;
		}

		if ( isset( $_POST[ SKAUTISINTEGRATION_NAME . '_rules_visibilityMode' ] ) ) {
			if ( isset( $_POST[ SKAUTISINTEGRATION_NAME . '_rules' ] ) ) {
				$rules = sanitize_meta( SKAUTISINTEGRATION_NAME . '_rules', wp_unslash( $_POST[ SKAUTISINTEGRATION_NAME . '_rules' ] ), 'post' );
			} else {
				$rules = array();
			}
			update_post_meta(
				$post_id,
				SKAUTISINTEGRATION_NAME . '_rules',
				$rules
			);

			if ( isset( $_POST[ SKAUTISINTEGRATION_NAME . '_rules_includeChildren' ] ) ) {
				$include_children = sanitize_meta( SKAUTISINTEGRATION_NAME . '_rules_includeChildren', wp_unslash( $_POST[ SKAUTISINTEGRATION_NAME . '_rules_includeChildren' ] ), 'post' );
			} else {
				$include_children = 0;
			}
			update_post_meta(
				$post_id,
				SKAUTISINTEGRATION_NAME . '_rules_includeChildren',
				$include_children
			);

			$visibility_mode = sanitize_meta( SKAUTISINTEGRATION_NAME . '_rules_visibilityMode', wp_unslash( $_POST[ SKAUTISINTEGRATION_NAME . '_rules_visibilityMode' ] ), 'post' );
			update_post_meta(
				$post_id,
				SKAUTISINTEGRATION_NAME . '_rules_visibilityMode',
				$visibility_mode
			);
		}
	}

	public function rules_repeater( \WP_Post $post ) {
		$post_type_object = get_post_type_object( $post->post_type );
		$include_children = get_post_meta( $post->ID, SKAUTISINTEGRATION_NAME . '_rules_includeChildren', true );
		if ( '0' !== $include_children && '1' !== $include_children ) {
			$include_children = get_option( SKAUTISINTEGRATION_NAME . '_modules_visibility_includeChildren', 0 );
		}

		$visibility_mode = get_post_meta( $post->ID, SKAUTISINTEGRATION_NAME . '_rules_visibilityMode', true );
		if ( 'content' !== $visibility_mode && 'full' !== $visibility_mode ) {
			$visibility_mode = get_option( SKAUTISINTEGRATION_NAME . '_modules_visibility_visibilityMode', 0 );
		}

		wp_nonce_field( SKAUTISINTEGRATION_NAME . '_visibility_metabox', SKAUTISINTEGRATION_NAME . '_visibility_metabox_nonce' );

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
									<li data-rule="<?php echo esc_attr( $rule_id ); ?>"><?php echo esc_html( $rule ); ?></li>
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
			<div data-repeater-list="<?php echo esc_attr( SKAUTISINTEGRATION_NAME ); ?>_rules">
				<div data-repeater-item>

					<select name="<?php echo esc_attr( SKAUTISINTEGRATION_NAME ); ?>_rules" class="rule select2">
						<?php
						foreach ( (array) $this->rules_manager->get_all_rules() as $rule ) {
							echo '<option value="' . esc_attr( $rule->ID ) . '">' . esc_html( $rule->post_title ) . '</option>';
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
				<input type="hidden" name="<?php echo esc_attr( SKAUTISINTEGRATION_NAME ); ?>_rules_includeChildren"
					value="0"/>
				<input type="checkbox" name="<?php echo esc_attr( SKAUTISINTEGRATION_NAME ); ?>_rules_includeChildren"
					value="1" <?php checked( 1, $include_children ); ?> /><span>
												<?php
												if ( $post_type_object->hierarchical ) {
													/* translators: the type of the SkautIS unit */
													printf( esc_html__( 'Použít vybraná pravidla i na podřízené %s', 'skautis-integration' ), esc_html( lcfirst( $post_type_object->labels->name ) ) );
												} else {
													esc_html_e( 'Použít vybraná pravidla i na podřízený obsah (média - obrázky, videa, přílohy,...)', 'skautis-integration' );
												}
												?>
					.</span></label>
		</p>
		<p>
			<label><input type="radio" name="<?php echo esc_attr( SKAUTISINTEGRATION_NAME ); ?>_rules_visibilityMode"
						value="full" <?php checked( 'full', $visibility_mode ); ?> /><span><?php esc_html_e( 'Úplně skrýt', 'skautis-integration' ); ?></span></label>
			<br/>
			<label><input type="radio" name="<?php echo esc_attr( SKAUTISINTEGRATION_NAME ); ?>_rules_visibilityMode"
						value="content" <?php checked( 'content', $visibility_mode ); ?> /><span><?php esc_html_e( 'Skrýt pouze obsah', 'skautis-integration' ); ?></span></label>
		</p>
		<?php
	}

}
