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
use Skautis_Integration\Utils\Helpers;

/**
 * Adds the "Log in with SkautIS" card below the login form.
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
		if ( ! Services::is_registration_enabled() ) {
			add_action( 'login_form', array( $this, 'login_link_in_login_form' ) );
			add_filter( 'login_form_bottom', array( $this, 'login_link_in_login_form_return' ) );
			add_action( 'login_footer', array( self::class, 'print_move_script' ) );
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
	 * Prints the SkautIS login card in the login form of the login page.
	 *
	 * @return void
	 */
	public function login_link_in_login_form() {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo self::get_login_card( $this->wp_login_logout->get_login_url(), true );
	}

	/**
	 * Returns the SkautIS login card for forms printed by wp_login_form().
	 */
	public function login_link_in_login_form_return(): string {
		return self::get_login_card( $this->wp_login_logout->get_login_url(), false );
	}

	/**
	 * Returns the HTML code of the SkautIS login card.
	 *
	 * On the login page, the card is moved below the login form by a script (see print_move_script()), so that it is clearly separated from the WordPress username and password. Without JavaScript, it stays in the form.
	 *
	 * @param string $url The URL of the SkautIS login.
	 * @param bool   $movable Whether the card is on the login page and should be moved below the login form.
	 */
	public static function get_login_card( string $url, bool $movable ): string {
		return '<div class="skautis-login-card"' . ( $movable ? ' id="skautis-login-card"' : '' ) . '>'
			. '<p class="skautis-login-card-title">' . esc_html__( 'Do you have a SkautIS account?', 'skautis-integration' ) . '</p>'
			. '<a class="button button-primary button-large button-skautis" href="' . esc_url( $url ) . '">' . esc_html__( 'Log in with SkautIS', 'skautis-integration' ) . '</a>'
			. '<p class="skautis-login-card-note">' . esc_html__( 'You will enter your SkautIS username and password on the SkautIS login page.', 'skautis-integration' ) . '</p>'
			. '</div>';
	}

	/**
	 * Prints the script that moves the SkautIS login card below the login form on the login page.
	 *
	 * WordPress has no hook below the login form, so the card is printed in the form and moved by this script.
	 *
	 * @return void
	 */
	public static function print_move_script() {
		wp_print_inline_script_tag( '(function(){var card=document.getElementById("skautis-login-card"),form=document.getElementById("loginform");if(card&&form){form.insertAdjacentElement("afterend",card);}})();' );
	}
}
