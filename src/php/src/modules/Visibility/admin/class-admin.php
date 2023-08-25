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
 *
 * @phan-constructor-used-for-side-effects
 */
final class Admin {

	/**
	 * A list of post types to activate the Visibility module for.
	 *
	 * @var array<string>
	 */
	private $post_types;

	/**
	 * A link to the Rules_Manager service instance.
	 *
	 * @var Rules_Manager
	 */
	private $rules_manager;

	/**
	 * Constructs the service and saves all dependencies.
	 *
	 * @param array<string> $post_types A list of post types to activate the Visibility module for.
	 * @param Rules_Manager $rules_manager An injected Rules_Manager service instance.
	 * @param Frontend      $frontend An injected Frontend service instance.
	 */
	public function __construct( array $post_types, Rules_Manager $rules_manager, Frontend $frontend ) {
		$this->post_types    = $post_types;
		$this->rules_manager = $rules_manager;
		new Settings();
		new Metabox( $this->post_types, $this->rules_manager, $frontend );
		$this->init_hooks();
	}

	/**
	 * Intializes all hooks used by the object.
	 *
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_and_styles' ) );
		add_action( 'admin_footer', array( $this, 'init_rules_options' ) );
		add_action( 'admin_footer', array( $this, 'init_rules_data' ) );
	}

	/**
	 * Enqueues all scripts and styles needed for the visibility module.
	 *
	 * @return void
	 */
	public function enqueue_scripts_and_styles() {
		$screen = get_current_screen();
		if (
			null === $screen ||
			(
				! in_array( $screen->id, $this->post_types, true ) &&
				'skautis_page_' . SKAUTIS_INTEGRATION_NAME . '_modules_visibility' !== $screen->id
			)
		) {
				return;
		}
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

	/**
	 * Initializes dynamic options for the visibility JS code.
	 *
	 * @return void
	 */
	public function init_rules_options() {
		$screen = get_current_screen();
		if ( null === $screen || ! in_array( $screen->id, $this->post_types, true ) ) {
			return;
		}
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

	/**
	 * Initializes dynamic options for the visibility JS code.
	 *
	 * @return void
	 */
	public function init_rules_data() {
		$screen = get_current_screen();
		if ( null === $screen || ! in_array( $screen->id, $this->post_types, true ) ) {
			return;
		}
		$post_id = get_the_ID();
		if ( false === $post_id ) {
			return;
		}
		$data = get_post_meta( $post_id, SKAUTIS_INTEGRATION_NAME . '_rules', true );
		?>
		<script>
			window.rulesData = <?php echo wp_json_encode( $data ); ?>;
		</script>
		<?php
	}
}
