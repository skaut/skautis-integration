<?php
/**
 * Contains the Admin class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Modules\Register\Admin;

use Skautis_Integration\Rules\Rules_Manager;
use Skautis_Integration\Utils\Helpers;

/**
 * Handles administration scripts and styles for the Register module.
 *
 * @phan-constructor-used-for-side-effects
 */
final class Admin {

	/**
	 * A link to the Rules_Manager service instance.
	 *
	 * @var Rules_Manager
	 */
	private $rules_manager;

	/**
	 * Constructs the service and saves all dependencies.
	 *
	 * @param Rules_Manager $rules_manager An injected Rules_Manager service instance.
	 */
	public function __construct( Rules_Manager $rules_manager ) {
		$this->rules_manager = $rules_manager;
		new Settings( $this->rules_manager );
		$this->init_hooks();
	}

	/**
	 * Intializes all hooks used by the object.
	 *
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_scripts' ) );
		add_action( 'admin_footer', array( $this, 'init_rules_options' ) );
		add_action( 'admin_footer', array( self::class, 'init_rules_data' ) );
	}

	/**
	 * Enqueues all styles needed for the user registration admin page.
	 *
	 * @return void
	 */
	public static function enqueue_styles() {
		$screen = get_current_screen();
		if ( null === $screen || 'skautis_page_skautis-integration_modules_register' !== $screen->id ) {
			return;
		}
		Helpers::enqueue_style( 'modules_register', 'modules/Register/admin/css/skautis-modules-register-admin.min.css' );
	}

	/**
	 * Enqueues all scripts needed for the user registration admin page.
	 *
	 * @return void
	 */
	public static function enqueue_scripts() {
		$screen = get_current_screen();
		if ( null === $screen || 'skautis_page_skautis-integration_modules_register' !== $screen->id ) {
			return;
		}
		wp_enqueue_script( 'jquery-ui-sortable' );

		wp_enqueue_script(
			SKAUTIS_INTEGRATION_NAME . '_jquery.repeater',
			SKAUTIS_INTEGRATION_URL . 'bundled/jquery.repeater.min.js',
			array( 'jquery' ),
			SKAUTIS_INTEGRATION_VERSION,
			true
		);

		Helpers::enqueue_script(
			'modules_register',
			'modules/Register/admin/js/skautis-modules-register-admin.min.js',
			array( SKAUTIS_INTEGRATION_NAME . '_jquery.repeater' ),
		);
	}

	/**
	 * Initializes dynamic options for the register JS code.
	 *
	 * @return void
	 */
	public function init_rules_options() {
		$screen = get_current_screen();
		if ( null === $screen || 'skautis_page_skautis-integration_modules_register' !== $screen->id ) {
			return;
		}
		$rules = array();
		foreach ( $this->rules_manager->get_all_rules() as $rule ) {
			$rules[ $rule->ID ] = $rule->post_title;
		}
		?>
		<script>
			window.rulesOptions = <?php echo wp_json_encode( $rules ); ?>;
		</script>
		<?php
	}

	/**
	 * Initializes dynamic data for the register JS code.
	 *
	 * @return void
	 */
	public static function init_rules_data() {
		$screen = get_current_screen();
		if ( null === $screen || 'skautis_page_skautis-integration_modules_register' !== $screen->id ) {
			return;
		}
		$data = get_option( SKAUTIS_INTEGRATION_NAME . '_modules_register_rules' );
		?>
		<script>
			window.rulesData = <?php echo wp_json_encode( $data ); ?>;
		</script>
		<?php
	}

}
