<?php

declare( strict_types=1 );

namespace SkautisIntegration\Modules\Visibility\Admin;

use SkautisIntegration\Rules\RulesManager;

final class Metabox {

	private $postTypes;
	private $rulesManager;

	public function __construct( array $postTypes, RulesManager $rulesManager ) {
		$this->postTypes    = $postTypes;
		$this->rulesManager = $rulesManager;
		$this->initHooks();
	}

	private function initHooks() {
		add_action( 'add_meta_boxes', [ $this, 'addMetaboxForRulesField' ] );
		add_action( 'save_post', [ $this, 'saveRulesCustomField' ] );
	}

	public function addMetaboxForRulesField( string $postType ) {
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
		if ( array_key_exists( SKAUTISINTEGRATION_NAME . '_rules', $_POST ) ) {
			update_post_meta(
				$postId,
				SKAUTISINTEGRATION_NAME . '_rules',
				$_POST[ SKAUTISINTEGRATION_NAME . '_rules' ]
			);
		}

		if ( array_key_exists( SKAUTISINTEGRATION_NAME . '_rules_includeChildren', $_POST ) ) {
			update_post_meta(
				$postId,
				SKAUTISINTEGRATION_NAME . '_rules_includeChildren',
				$_POST[ SKAUTISINTEGRATION_NAME . '_rules_includeChildren' ]
			);
		}

		if ( array_key_exists( SKAUTISINTEGRATION_NAME . '_rules_visibilityMode', $_POST ) ) {
			update_post_meta(
				$postId,
				SKAUTISINTEGRATION_NAME . '_rules_visibilityMode',
				$_POST[ SKAUTISINTEGRATION_NAME . '_rules_visibilityMode' ]
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
						_e( 'Použít vybraná pravidla i na podřízený obsah (média - obrázky, videa, přílohy, ...)', 'skautis-integration' );
					}
					?>.</span></label>
		</p>
		<p>
			<label><input type="radio" name="<?php echo SKAUTISINTEGRATION_NAME; ?>_rules_visibilityMode"
			              value="full" <?php checked( 'full', $visibilityMode ); ?> /><span><?php printf( __( 'Skrýt celý %s', 'skautis-integration' ), lcfirst( $postTypeObject->labels->singular_name ) ); ?></span></label>
			<br/>
			<label><input type="radio" name="<?php echo SKAUTISINTEGRATION_NAME; ?>_rules_visibilityMode"
			              value="content" <?php checked( 'content', $visibilityMode ); ?> /><span><?php _e( 'Skrýt pouze obsah', 'skautis-integration' ); ?></span></label>
		</p>
		<?php
	}

}
