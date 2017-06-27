<?php

namespace SkautisIntegration\Modules\Register\Admin;

use SkautisIntegration\Rules\RulesManager;

final class Admin {

	private $rulesManager;
	private $adminDirUrl = '';

	public function __construct( RulesManager $rulesManager ) {
		$this->rulesManager = $rulesManager;
		$this->adminDirUrl  = plugin_dir_url( __FILE__ ) . 'public/';
		( new Settings( $this->rulesManager ) );
		$this->initHooks();
	}

	private function initHooks() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueStyles' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueScripts' ] );
		add_action( 'admin_footer', [ $this, 'initRulesOptions' ] );
		add_action( 'admin_footer', [ $this, 'initRulesData' ] );
	}

	public function enqueueStyles() {
		if ( get_current_screen()->id == 'skautis_page_skautis-integration_modules_register' ) {
			wp_enqueue_style(
				'select2',
				'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css',
				[],
				'4.0.3',
				'all'
			);

			wp_enqueue_style(
				SKAUTISINTEGRATION_NAME . '_modules_register',
				$this->adminDirUrl . 'css/skautis-modules-register-admin.css',
				[],
				SKAUTISINTEGRATION_VERSION,
				'all'
			);
		}
	}

	public function enqueueScripts() {
		if ( get_current_screen()->id == 'skautis_page_skautis-integration_modules_register' ) {
			wp_enqueue_script( 'jquery-ui-sortable' );

			wp_enqueue_script(
				'select2',
				'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js',
				[ 'jquery' ],
				'4.0.3',
				true
			);

			wp_enqueue_script(
				'jquery-repeater',
				'https://cdnjs.cloudflare.com/ajax/libs/jquery.repeater/1.2.1/jquery.repeater.min.js',
				[ 'jquery' ],
				'1.2.1',
				true
			);

			wp_enqueue_script(
				SKAUTISINTEGRATION_NAME . '_modules_register',
				$this->adminDirUrl . 'js/skautis-modules-register-admin.js',
				[ 'jquery-repeater' ],
				SKAUTISINTEGRATION_VERSION,
				true
			);
		}
	}

	public function initRulesOptions() {
		if ( get_current_screen()->id == 'skautis_page_skautis-integration_modules_register' ) {
			$data  = '';
			$rules = [];

			foreach ( (array) $this->rulesManager->getAllRules() as $rule ) {
				$rules[ $rule->ID ] = $rule->post_title;
			}
			$data = json_encode( $rules );
			?>
			<script>
                window.rulesOptions = <?php echo $data; ?>;
			</script>
			<?php
		}
	}

	public function initRulesData() {
		if ( get_current_screen()->id == 'skautis_page_skautis-integration_modules_register' ) {
			$data = json_encode( get_option( SKAUTISINTEGRATION_NAME . '_modules_register_rules' ) );
			?>
			<script>
                window.rulesData = <?php echo $data; ?>;
			</script>
			<?php
		}
	}

}
