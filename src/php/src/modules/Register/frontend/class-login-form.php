<?php
/**
 * Contains the Login_Form class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Modules\Register\Frontend;

use Skautis_Integration\Frontend\Login_Form as Frontend_Login_Form;
use Skautis_Integration\Modules\Register\WP_Register;

/**
 * Adds the "Log in with SkautIS" card below the login form.
 *
 * This class handles the version of the form that also supports registering new users on first login.
 *
 * @phan-constructor-used-for-side-effects
 */
final class Login_Form {

	/**
	 * A link to the WP_Register service instance.
	 *
	 * @var WP_Register
	 */
	private $wp_register;

	/**
	 * Constructs the service and saves all dependencies.
	 *
	 * @param WP_Register $wp_register An injected WP_Register service instance.
	 */
	public function __construct( WP_Register $wp_register ) {
		$this->wp_register = $wp_register;
		$this->init_hooks();
	}

	/**
	 * Intializes all hooks used by the object.
	 *
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'login_form', array( $this, 'login_link_in_login_form' ) );
		add_action( 'login_enqueue_scripts', array( self::class, 'enqueue_login_styles' ) );
		add_filter( 'login_form_bottom', array( $this, 'login_link_in_login_form_return' ) );
		add_action( 'login_footer', array( Frontend_Login_Form::class, 'print_move_script' ) );
	}

	/**
	 * Enqueues login page styles.
	 *
	 * @return void
	 */
	public static function enqueue_login_styles() {
		wp_enqueue_style( SKAUTIS_INTEGRATION_NAME . '_frontend' );
	}

	/**
	 * Prints the SkautIS login card with the registration URL in the login form of the login page.
	 *
	 * @return void
	 */
	public function login_link_in_login_form() {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo Frontend_Login_Form::get_login_card( $this->wp_register->get_register_url(), true );
	}

	/**
	 * Returns the SkautIS login card with the registration URL for forms printed by wp_login_form().
	 */
	public function login_link_in_login_form_return(): string {
		return Frontend_Login_Form::get_login_card( $this->wp_register->get_register_url(), false );
	}
}
