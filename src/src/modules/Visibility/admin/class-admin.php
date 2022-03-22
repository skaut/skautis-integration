<?php

declare( strict_types=1 );

namespace SkautisIntegration\Modules\Visibility\Admin;

use SkautisIntegration\Rules\Rules_Manager;
use SkautisIntegration\Modules\Visibility\Frontend\Frontend;
use SkautisIntegration\Utils\Helpers;

final class Admin {

	private $postTypes;
	private $rulesManager;
	private $frontend;
	private $settings;
	private $metabox;
	private $adminDirUrl = '';

	public function __construct( array $postTypes, Rules_Manager $rulesManager, Frontend $frontend ) {
		$this->postTypes    = $postTypes;
		$this->rulesManager = $rulesManager;
		$this->frontend     = $frontend;
		$this->settings     = new Settings();
		$this->metabox      = new Metabox( $this->postTypes, $this->rulesManager, $frontend );
		$this->adminDirUrl  = plugin_dir_url( __FILE__ ) . 'public/';
		$this->init_hooks();
	}

	private function init_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_and_styles' ) );
		add_action( 'admin_footer', array( $this, 'init_rules_options' ) );
		add_action( 'admin_footer', array( $this, 'initRulesData' ) );
	}

	public function enqueue_scripts_and_styles() {
		if ( in_array( get_current_screen()->id, $this->postTypes, true ) ||
			get_current_screen()->id === 'skautis_page_' . SKAUTISINTEGRATION_NAME . '_modules_visibility' ) {
			wp_enqueue_script( 'jquery-ui-sortable' );

			Helpers::enqueue_style( 'modules_visibility', 'modules/Visibility/admin/css/skautis-modules-visibility-admin.min.css' );

			wp_enqueue_script(
				SKAUTISINTEGRATION_NAME . '_jquery.repeater',
				SKAUTISINTEGRATION_URL . 'bundled/jquery.repeater.min.js',
				array( 'jquery' ),
				SKAUTISINTEGRATION_VERSION,
				true
			);

			Helpers::enqueue_script(
				'modules_visibility',
				'modules/Visibility/admin/js/skautis-modules-visibility-admin.min.js',
				array( 'jquery', SKAUTISINTEGRATION_NAME . '_jquery.repeater', SKAUTISINTEGRATION_NAME . '_select2' )
			);
		}
	}

	public function init_rules_options() {
		if ( in_array( get_current_screen()->id, $this->postTypes, true ) ) {
			$rules = array();

			foreach ( (array) $this->rulesManager->get_all_rules() as $rule ) {
				$rules[ $rule->ID ] = $rule->post_title;
			}
			?>
			<script>
				window.rulesOptions = <?php wp_json_encode( $rules ); ?>;
			</script>
			<?php
		}
	}

	public function initRulesData() {
		if ( in_array( get_current_screen()->id, $this->postTypes, true ) ) {
			$data = get_post_meta( get_the_ID(), SKAUTISINTEGRATION_NAME . '_rules', true );
			?>
			<script>
				window.rulesData = <?php echo wp_json_encode( $data ); ?>;
			</script>
			<?php
		}
	}

}
