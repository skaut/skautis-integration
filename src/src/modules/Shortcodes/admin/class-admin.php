<?php

declare( strict_types=1 );

namespace SkautisIntegration\Modules\Shortcodes\Admin;

use SkautisIntegration\Rules\Rules_Manager;

final class Admin {

	private $rulesManager;
	private $settings;

	public function __construct( Rules_Manager $rulesManager ) {
		$this->rulesManager = $rulesManager;
		$this->settings     = new Settings();
		$this->initHooks();
	}

	private function initHooks() {
		add_action( 'admin_footer', array( $this, 'initAvailableRules' ) );

		add_action(
			'admin_init',
			function () {
				if ( get_user_option( 'rich_editing' ) ) {
					add_filter( 'mce_external_plugins', array( $this, 'registerTinymcePlugin' ) );
					add_filter( 'mce_buttons', array( $this, 'addTinymceButton' ) );
				}
			}
		);
	}

	public function registerTinymcePlugin( array $plugins = array() ): array {
		$plugins['skautis_rules'] = plugin_dir_url( dirname( __FILE__, 4 ) ) . 'modules/Shortcodes/admin/js/skautis-modules-shortcodes-tinymceRulesButton.min.js';

		return $plugins;
	}

	public function addTinymceButton( array $buttons = array() ): array {
		$buttons[] = 'skautis_rules';

		return $buttons;
	}

	public function initAvailableRules() {
		?>
		<script>
			window.rulesOptions = [];
			window.visibilityOptions = [];

			<?php
			if ( get_option( SKAUTISINTEGRATION_NAME . '_modules_shortcodes_visibilityMode', 'hide' ) === 'hide' ) {
				echo 'window.visibilityOptions.push({text: "hideContent", value: "hide"});';
				echo 'window.visibilityOptions.push({text: "showLogin", value: "showLogin"});';
			} else {
				echo 'window.visibilityOptions.push({text: "showLogin", value: "showLogin"});';
				echo 'window.visibilityOptions.push({text: "hideContent", value: "hide"});';
			}

			$rules = array();
			foreach ( (array) $this->rulesManager->getAllRules() as $rule ) {
				$rules[ $rule->ID ] = $rule->post_title;
			}
			?>
			<script>
				window.rulesOptions = <?php wp_json_encode( $rules ); ?>;
			</script>
			?>
		</script>
		<?php
	}

}
