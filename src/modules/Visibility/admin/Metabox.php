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
	}

	public function rulesRepeater( \WP_Post $post ) {
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
		<?php
	}

}
