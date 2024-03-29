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

/**
 * Adds the "Log in with SkautIS" button to the login form.
 */
final class Login_Form {

	/**
	 * A link to the WP_Login_Logout service instance.
	 *
	 * @var WP_Login_Logout
	 */
	private $wp_login_logout;

	/**
	 * Constructs the service and saves all dependencies.
	 *
	 * @param WP_Login_Logout $wp_login_logout An injected WP_Login_Logout service instance.
	 */
	public function __construct( WP_Login_Logout $wp_login_logout ) {
		$this->wp_login_logout = $wp_login_logout;
		$this->init_hooks();
	}

	/**
	 * Intializes all hooks used by the object.
	 *
	 * @return void
	 */
	private function init_hooks() {
		if ( ! Services::get_modules_manager()->is_module_activated( Register::get_id() ) ) {
			add_action( 'login_form', array( $this, 'login_link_in_login_form' ) );
			add_filter( 'login_form_bottom', array( $this, 'login_link_in_login_form_return' ) );
		}
	}

	/**
	 * Enqueues frontend styles.
	 *
	 * TODO: Unused?
	 *
	 * @return void
	 */
	public static function enqueue_styles() {
		Helpers::enqueue_style( 'frontend', 'frontend/css/skautis-frontened.min.css' );
	}

	/**
	 * Prints the "Log in with SkautIS" button as part of the login page.
	 *
	 * @return void
	 */
	public function login_link_in_login_form() {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->login_link_in_login_form_return();
	}

	/**
	 * Returns the "Log in with SkautIS" button HTML code.
	 *
	 * TODO: Remove this function. Why is the button printed from 2 different hooks?
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
