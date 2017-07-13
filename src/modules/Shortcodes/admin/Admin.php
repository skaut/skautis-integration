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
		//$this->settings     = new Settings();
		$this->adminDirUrl  = plugin_dir_url( __FILE__ ) . 'public/';
		$this->initHooks();
	}

	private function initHooks() {
		add_action( 'admin_footer', [ $this, 'initAvailableRules' ] );

		add_action( 'admin_init', function () {
			if ( get_user_option( 'rich_editing' ) ) {
				add_filter( 'mce_external_plugins', [ $this, 'registerTinymcePlugin' ] );
				add_filter( 'mce_buttons', [ $this, 'addTinymceButton' ] );
			}
		} );
	}

	public function registerTinymcePlugin( array $plugins = [] ): array {
		$plugins['skautis_rules'] = $this->adminDirUrl . 'js/skautis-modules-shortcodes-tinymceRulesButton.js';

		return $plugins;
	}

	public function addTinymceButton( array $buttons = [] ): array {
		$buttons[] = 'skautis_rules';

		return $buttons;
	}

	public function initAvailableRules() {
		?>
		<script>
            window.rules = [];

			<?php
			foreach ( (array) $this->rulesManager->getAllRules() as $rule ) {
				echo 'window.rules.push({text: "' . esc_js( $rule->post_title ) . '", value: "' . esc_js( $rule->ID ) . '"});';
			}
			?>
		</script>
		<?php
	}

}
