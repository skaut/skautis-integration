<?php

declare( strict_types=1 );

namespace SkautisIntegration\Modules\Shortcodes\Admin;

use SkautisIntegration\Rules\RulesManager;

final class Admin {

	private $rulesManager;
	private $settings;
	private $adminDirUrl = '';

	public function __construct( RulesManager $rulesManager ) {
		$this->rulesManager = $rulesManager;
		$this->settings     = new Settings();
		$this->adminDirUrl  = plugin_dir_url( __FILE__ ) . 'public/';
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
		$plugins['skautis_rules'] = $this->adminDirUrl . 'js/skautis-modules-shortcodes-tinymceRulesButton.js';

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

			foreach ( (array) $this->rulesManager->getAllRules() as $rule ) {
				echo 'window.rulesOptions.push({text: "' . esc_js( $rule->post_title ) . '", value: "' . esc_js( $rule->ID ) . '"});';
			}
			?>
		</script>
		<?php
	}

}
