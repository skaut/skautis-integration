<?php
/**
 * Contains the Admin class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Modules\Visibility\Admin;

use Skautis_Integration\Rules\Rules_Manager;
use Skautis_Integration\Modules\Visibility\Frontend\Frontend;
use Skautis_Integration\Utils\Helpers;

/**
 * Enqueues all scripts and styles for the Visibility module.
 */
final class Admin {

	/**
	 * A list of post types to activate the Visibility module for.
	 *
	 * @var array
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
	 * TODO: Unused?
	 *
	 * @var Frontend
	 */
	private $frontend;

	/**
	 * An instance of the module Settings service.
	 *
	 * TODO: Unused?
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * An instance of the module Metabox service.
	 *
	 * TODO: Unused?
	 *
	 * @var Metabox
	 */
	private $metabox;

	/**
	 * TODO: Unused?
	 *
	 * @var string
	 */
	private $admin_dir_url = '';

	/**
	 * Constructs the service and saves all dependencies.
	 *
	 * @param array         $post_types A list of post types to activate the Visibility module for.
	 * @param Rules_Manager $rules_manager An injected Rules_Manager service instance.
	 * @param Frontend      $frontend An injected Frontend service instance.
	 */
	public function __construct( array $post_types, Rules_Manager $rules_manager, Frontend $frontend ) {
		$this->post_types    = $post_types;
		$this->rules_manager = $rules_manager;
		$this->frontend      = $frontend;
		$this->settings      = new Settings();
		$this->metabox       = new Metabox( $this->post_types, $this->rules_manager, $frontend );
		$this->admin_dir_url = plugin_dir_url( __FILE__ ) . 'public/';
		$this->init_hooks();
	}

	/**
	 * Intializes all hooks used by the object.
	 */
	private function init_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_and_styles' ) );
		add_action( 'admin_footer', array( $this, 'init_rules_options' ) );
		add_action( 'admin_footer', array( $this, 'init_rules_data' ) );
	}

	/**
	 * Enqueues all scripts and styles needed for the visibility module.
	 */
	public function enqueue_scripts_and_styles() {
		if ( in_array( get_current_screen()->id, $this->post_types, true ) ||
			get_current_screen()->id === 'skautis_page_' . SKAUTIS_INTEGRATION_NAME . '_modules_visibility' ) {
			wp_enqueue_script( 'jquery-ui-sortable' );

			Helpers::enqueue_style( 'modules_visibility', 'modules/Visibility/admin/css/skautis-modules-visibility-admin.min.css' );

			wp_enqueue_script(
				SKAUTIS_INTEGRATION_NAME . '_jquery.repeater',
				SKAUTIS_INTEGRATION_URL . 'bundled/jquery.repeater.min.js',
				array( 'jquery' ),
				SKAUTIS_INTEGRATION_VERSION,
				true
			);

			Helpers::enqueue_script(
				'modules_visibility',
				'modules/Visibility/admin/js/skautis-modules-visibility-admin.min.js',
				array( 'jquery', SKAUTIS_INTEGRATION_NAME . '_jquery.repeater', SKAUTIS_INTEGRATION_NAME . '_select2' )
			);
		}
	}

	/**
	 * Initializes dynamic options for the visibility JS code.
	 */
	public function init_rules_options() {
		if ( in_array( get_current_screen()->id, $this->post_types, true ) ) {
			$rules = array();

			foreach ( $this->rules_manager->get_all_rules() as $rule ) {
				$rules[ $rule->ID ] = $rule->post_title;
			}
			?>
			<script>
				window.rulesOptions = <?php wp_json_encode( $rules ); ?>;
			</script>
			<?php
		}
	}

	/**
	 * Initializes dynamic options for the visibility JS code.
	 */
	public function init_rules_data() {
		if ( in_array( get_current_screen()->id, $this->post_types, true ) ) {
			$data = get_post_meta( get_the_ID(), SKAUTIS_INTEGRATION_NAME . '_rules', true );
			?>
			<script>
				window.rulesData = <?php echo wp_json_encode( $data ); ?>;
			</script>
			<?php
		}
	}

}
