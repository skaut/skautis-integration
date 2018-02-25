<?php

declare( strict_types=1 );

namespace SkautisIntegration\Modules\Visibility\Admin;

use SkautisIntegration\Rules\RulesManager;
use SkautisIntegration\Modules\Visibility\Frontend\Frontend;

final class Metabox {

	private $postTypes;
	private $rulesManager;
	private $frontend;

	public function __construct( array $postTypes, RulesManager $rulesManager, Frontend $frontend ) {
		$this->postTypes    = $postTypes;
		$this->rulesManager = $rulesManager;
		$this->frontend     = $frontend;
		$this->initHooks();
	}

	private function initHooks() {
		add_action( 'add_meta_boxes', [ $this, 'addMetaboxForRulesField' ] );
		add_action( 'save_post', [ $this, 'saveRulesCustomField' ] );
	}

	public function addMetaboxForRulesField() {
		foreach ( $this->postTypes as $postType ) {
			add_meta_box(
				SKAUTISINTEGRATION_NAME . '_modules_visibility_rules_metabox',
				__( 'SkautIS pravidla', 'skautis-integration' ),
				[ $this, 'rulesRepeater' ],
				$postType
			);
		}
	}

	public function saveRulesCustomField( int $postId ) {
		if ( isset( $_POST[ SKAUTISINTEGRATION_NAME . '_rules_visibilityMode' ] ) ) {

			if ( isset( $_POST[ SKAUTISINTEGRATION_NAME . '_rules' ] ) ) {
				$rules = $_POST[ SKAUTISINTEGRATION_NAME . '_rules' ];
			} else {
				$rules = [];
			}
			update_post_meta(
				$postId,
				SKAUTISINTEGRATION_NAME . '_rules',
				$rules
			);

			if ( isset( $_POST[ SKAUTISINTEGRATION_NAME . '_rules_includeChildren' ] ) ) {
				$includeChildren = $_POST[ SKAUTISINTEGRATION_NAME . '_rules_includeChildren' ];
			} else {
				$includeChildren = 0;
			}
			update_post_meta(
				$postId,
				SKAUTISINTEGRATION_NAME . '_rules_includeChildren',
				$includeChildren
			);

			$visibilityMode = $_POST[ SKAUTISINTEGRATION_NAME . '_rules_visibilityMode' ];
			update_post_meta(
				$postId,
				SKAUTISINTEGRATION_NAME . '_rules_visibilityMode',
				$visibilityMode
			);

		}
	}

	public function rulesRepeater( \WP_Post $post ) {
		$postTypeObject  = get_post_type_object( $post->post_type );
		$includeChildren = get_post_meta( $post->ID, SKAUTISINTEGRATION_NAME . '_rules_includeChildren', true );
		if ( $includeChildren !== '0' && $includeChildren !== '1' ) {
			$includeChildren = get_option( SKAUTISINTEGRATION_NAME . '_modules_visibility_includeChildren', 0 );
		}

		$visibilityMode = get_post_meta( $post->ID, SKAUTISINTEGRATION_NAME . '_rules_visibilityMode', true );
		if ( $visibilityMode !== 'content' && $visibilityMode !== 'full' ) {
			$visibilityMode = get_option( SKAUTISINTEGRATION_NAME . '_modules_visibility_visibilityMode', 0 );
		}
		?>

		<?php
		if ( $post->post_parent > 0 ) {
			$parentRules = $this->frontend->getParentPostsWithRules( absint( $post->ID ), $post->post_type );
			if ( ! empty( $parentRules ) ) {
				?>
				<h4><?php _e( 'Pravidla převzatá z nadřazených stránek', 'skautis-integration' ); ?>:</h4>
				<ul class="skautis-admin-list">
					<?php
					foreach ( $parentRules as $parentRule ) {
						?>
						<li>
							<strong><?php echo esc_html( $parentRule['parentPostTitle'] ); ?></strong>
							<ul>
								<?php
								foreach ( $parentRule['rules'] as $rule ) {
									?>
									<li><?php echo esc_html( $rule ); ?></li>
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

		<p><?php _e( 'Obsah bude pro uživatele viditelný pouze při splnění alespoň jednoho z následujících pravidel.', 'skautis-integration' ); ?></p>
		<p><?php _e( 'Ponecháte-li prázdné - obsah bude viditelný pro všechny uživatele.', 'skautis-integration' ); ?></p>
		<div id="repeater_post">
			<div data-repeater-list="<?php echo SKAUTISINTEGRATION_NAME; ?>_rules">
				<div data-repeater-item>

					<select name="<?php echo SKAUTISINTEGRATION_NAME; ?>_rules" class="rule select2">
						<?php
						foreach ( (array) $this->rulesManager->getAllRules() as $rule ) {
							echo '<option value="' . $rule->ID . '">' . $rule->post_title . '</option>';
						}
						?>
					</select>

					<input data-repeater-delete type="button"
					       value="<?php _e( 'Odstranit', 'skautis-integration' ); ?>"/>
				</div>
			</div>
			<input data-repeater-create type="button" value="<?php _e( 'Přidat', 'skautis-integration' ); ?>"/>
		</div>
		<p>
			<label>
				<input type="hidden" name="<?php echo SKAUTISINTEGRATION_NAME; ?>_rules_includeChildren"
				       value="0"/>
				<input type="checkbox" name="<?php echo SKAUTISINTEGRATION_NAME; ?>_rules_includeChildren"
				       value="1" <?php checked( 1, $includeChildren ); ?> /><span><?php
					if ( $postTypeObject->hierarchical ) {
						printf( __( 'Použít vybraná pravidla i na podřízené %s', 'skautis-integration' ), lcfirst( $postTypeObject->labels->name ) );
					} else {
						_e( 'Použít vybraná pravidla i na podřízený obsah (média - obrázky, videa, přílohy,...)', 'skautis-integration' );
					}
					?>.</span></label>
		</p>
		<p>
			<label><input type="radio" name="<?php echo SKAUTISINTEGRATION_NAME; ?>_rules_visibilityMode"
			              value="full" <?php checked( 'full', $visibilityMode ); ?> /><span><?php _e( 'Úplně skrýt', 'skautis-integration' ); ?></span></label>
			<br/>
			<label><input type="radio" name="<?php echo SKAUTISINTEGRATION_NAME; ?>_rules_visibilityMode"
			              value="content" <?php checked( 'content', $visibilityMode ); ?> /><span><?php _e( 'Skrýt pouze obsah', 'skautis-integration' ); ?></span></label>
		</p>
		<?php
	}

}
