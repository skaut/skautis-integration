<?php
/**
 * Contains the Login_Form class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Frontend;

use Skautis_Integration\Auth\WP_Login_Logout;
use Skautis_Integration\Services\Services;
use Skautis_Integration\Modules\Register\Register;
use Skautis_Integration\Utils\Helpers;

final class Login_Form {

	private $wp_login_logout;
	// TODO: Unused?
	private $frontend_dir_url = '';

	/**
	 * Constructs the service and saves all dependencies.
	 */
	public function __construct( WP_Login_Logout $wp_login_logout ) {
		$this->wp_login_logout  = $wp_login_logout;
		$this->frontend_dir_url = plugin_dir_url( __FILE__ ) . 'public/';
		$this->init_hooks();
	}

	/**
	 * Intializes all hooks used by the object.
	 */
	private function init_hooks() {
		if ( ! Services::get_modules_manager()->is_module_activated( Register::get_id() ) ) {
			add_action( 'login_form', array( $this, 'login_link_in_login_form' ) );
			add_filter( 'login_form_bottom', array( $this, 'login_link_in_login_form_return' ) );
		}
	}

	// TODO: Unused?
	public function enqueue_styles() {
		Helpers::enqueue_style( 'frontend', 'frontend/css/skautis-frontened.min.css' );
	}

	/**
	 * Prints the "Log in with SkautIS" button as part of the login page.
	 */
	public function login_link_in_login_form() {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->login_link_in_login_form_return();
	}

	/**
	 * Returns the "Log in with SkautIS" button HTML code.
	 *
	 * TODO: Remove this function.
	 */
	public function login_link_in_login_form_return(): string {
		return '
		<p style="margin-bottom: 0.3em;">
			<a class="button button-primary button-hero button-skautis" style="float: none; width: 100%; text-align: center;"
			   href="' . esc_attr( $this->wp_login_logout->get_login_url() ) . '">' . esc_html__( 'Log in with skautIS', 'skautis-integration' ) . '</a>
			   <br/>
		</p>
		<br/>
		';
	}

}
