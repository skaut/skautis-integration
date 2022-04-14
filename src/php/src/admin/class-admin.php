<?php
/**
 * Contains the Admin class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Admin;

use Skautis_Integration\Auth\Skautis_Gateway;
use Skautis_Integration\Auth\WP_Login_Logout;
use Skautis_Integration\Rules\Rules_Manager;
use Skautis_Integration\Utils\Helpers;

/**
 * Enqueues administration scripts and styles and adds a button to log the user out of both WordPress and SkautIS simultaneously to the admin bar.
 */
final class Admin {

	/**
	 * A link to the Settings service instance.
	 *
	 * TODO: Unused?
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * A link to the Users service instance.
	 *
	 * TODO: Unused?
	 *
	 * @var Users
	 */
	private $users;

	/**
	 * A link to the Rules_Manager service instance.
	 *
	 * TODO: Unused?
	 *
	 * @var Rules_Manager
	 */
	private $rules_manager;

	/**
	 * A link to the WP_Login_Logout service instance.
	 *
	 * @var WP_Login_Logout
	 */
	private $wp_login_logout;

	/**
	 * A link to the Skautis_Gateway service instance.
	 *
	 * @var Skautis_Gateway
	 */
	private $skautis_gateway;

	/**
	 * A link to the Users_Management service instance.
	 *
	 * TODO: Unused?
	 *
	 * @var Users_Management
	 */
	private $users_management;

	/**
	 * TODO: Unused?
	 *
	 * @var string
	 */
	private $admin_dir_url = '';

	/**
	 * Constructs the service and saves all dependencies.
	 */
	public function __construct( Settings $settings, Users $users, Rules_Manager $rules_manager, Users_Management $users_management, WP_Login_Logout $wp_login_logout, Skautis_Gateway $skautis_gateway ) {
		$this->settings         = $settings;
		$this->users            = $users;
		$this->rules_manager    = $rules_manager;
		$this->users_management = $users_management;
		$this->wp_login_logout  = $wp_login_logout;
		$this->skautis_gateway  = $skautis_gateway;
		$this->admin_dir_url    = plugin_dir_url( __FILE__ ) . 'public/';
		$this->init_hooks();
	}

	/**
	 * Intializes all hooks used by the object.
	 */
	private function init_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_and_styles' ) );
		add_action( 'admin_print_scripts', array( $this, 'print_inline_js' ) );

		if ( $this->skautis_gateway->is_initialized() ) {
			if ( $this->skautis_gateway->get_skautis_instance()->getUser()->isLoggedIn() ) {
				add_action( 'admin_bar_menu', array( $this, 'add_logout_link_to_admin_bar' ), 20 );
			}
		}
	}

	/**
	 * Enqueues administration scripts and styles.
	 */
	public function enqueue_scripts_and_styles() {
		wp_enqueue_style(
			SKAUTIS_INTEGRATION_NAME . '_select2',
			SKAUTIS_INTEGRATION_URL . 'bundled/select2.min.css',
			array(),
			SKAUTIS_INTEGRATION_VERSION,
			'all'
		);

		wp_enqueue_script(
			SKAUTIS_INTEGRATION_NAME . '_select2',
			SKAUTIS_INTEGRATION_URL . 'bundled/select2.min.js',
			array( 'jquery' ),
			SKAUTIS_INTEGRATION_VERSION,
			false
		);

		Helpers::enqueue_style( 'admin', 'admin/css/skautis-admin.min.css' );
	}

	/**
	 * Adds a default value to the JS `window.skautis` global.
	 *
	 * TODO: Is this really needed?
	 */
	public function print_inline_js() {
		?>
		<script type="text/javascript">
			//<![CDATA[
			window.skautis = window.skautis || {};
			//]]>
		</script>
		<?php
	}

	/**
	 * Adds a link to admin bar right-hand-side menu to log out from both WordPress and SkautIS at once.
	 *
	 * TODO: Duplicated code?
	 */
	public function add_logout_link_to_admin_bar( \WP_Admin_Bar $wp_admin_bar ) {
		if ( ! function_exists( 'is_admin_bar_showing' ) ) {
			return;
		}
		if ( ! is_admin_bar_showing() ) {
			return;
		}

		if ( method_exists( $wp_admin_bar, 'get_node' ) ) {
			if ( $wp_admin_bar->get_node( 'user-actions' ) ) {
				$parent = 'user-actions';
			} else {
				return;
			}
		} elseif ( get_option( 'show_avatars' ) ) {
			$parent = 'my-account-with-avatar';
		} else {
			$parent = 'my-account';
		}

		$wp_admin_bar->add_menu(
			array(
				'parent' => $parent,
				'id'     => SKAUTIS_INTEGRATION_NAME . '_adminBar_logout',
				'title'  => esc_html__( 'Log Out (too from skautIS)', 'skautis-integration' ),
				'href'   => $this->wp_login_logout->get_logout_url(),
			)
		);
	}

}
