<?php
/**
 * Contains the Frontend class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Modules\Shortcodes\Frontend;

use Skautis_Integration\Auth\Skautis_Login;
use Skautis_Integration\Rules\Rules_Manager;
use Skautis_Integration\Auth\WP_Login_Logout;
use Skautis_Integration\Utils\Helpers;

/**
 * Handles the frontend part of the shortcodes - runs the shortcode, shows notices and a login form.
 *
 * @phan-constructor-used-for-side-effects
 */
final class Frontend {

	/**
	 * A link to the Skautis_Login service instance.
	 *
	 * @var Skautis_Login
	 */
	private $skautis_login;

	/**
	 * A link to the Rules_Manager service instance.
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
	 * Constructs the service and saves all dependencies.
	 *
	 * @param Skautis_Login   $skautis_login An injected Skautis_Login service instance.
	 * @param Rules_Manager   $rules_manager An injected Rules_Manager service instance.
	 * @param WP_Login_Logout $wp_login_logout An injected WP_Login_Logout service instance.
	 */
	public function __construct( Skautis_Login $skautis_login, Rules_Manager $rules_manager, WP_Login_Logout $wp_login_logout ) {
		$this->skautis_login   = $skautis_login;
		$this->rules_manager   = $rules_manager;
		$this->wp_login_logout = $wp_login_logout;
		$this->init_hooks();
	}

	/**
	 * Intializes all hooks used by the object.
	 *
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_styles' ) );
		add_shortcode( 'skautis', array( $this, 'process_shortcode' ) );
	}

	/**
	 * Prints a login form to access the content in a shortcode.
	 *
	 * @param bool $force_logout_from_skautis Whether to force a logout from SkautIS before logging in.
	 */
	private function get_login_form( bool $force_logout_from_skautis = false ): string {
		$login_url_args = add_query_arg( 'noWpLogin', true, Helpers::get_current_url() );
		if ( $force_logout_from_skautis ) {
			$login_url_args = add_query_arg( 'logoutFromSkautis', true, $login_url_args );
		}

		return '
		<div class="wp-core-ui">
			<p style="margin-bottom: 0.3em;">
				<a class="button button-primary button-hero button-skautis"
				   href="' . $this->wp_login_logout->get_login_url( $login_url_args ) . '">' . __( 'Log in with skautIS', 'skautis-integration' ) . '</a>
			</p>
		</div>
		<br/>
		';
	}

	/**
	 * Return a localized message about the user needing to log in.
	 */
	private static function get_login_required_message(): string {
		return '<p>' . __( 'To view this content you must be logged in skautIS', 'skautis-integration' ) . '</p>';
	}

	/**
	 * Return a localized message about the user not having permission to access the content.
	 */
	private static function get_unauthorized_message(): string {
		return '<p>' . __( 'You do not have permission to access this content', 'skautis-integration' ) . '</p>';
	}

	/**
	 * Enqueues all styles needed for the shortcode frontend view.
	 *
	 * @return void
	 */
	public static function enqueue_styles() {
		wp_enqueue_style( 'buttons' );
		Helpers::enqueue_style( 'frontend', 'frontend/css/skautis-frontend.min.css' );
	}

	/**
	 * Runs the shortcode.
	 *
	 * This is the function that gets called to process and return the shortcode content.
	 *
	 * @param array{rules?: string, content?: string} $atts The shortcode attributes.
	 * @param string                                  $content The shortcode content.
	 */
	public function process_shortcode( array $atts = array(), string $content = '' ): string {
		if ( isset( $atts['rules'] ) && isset( $atts['content'] ) ) {
			if ( current_user_can( 'edit_' . get_post_type() . 's' ) ) {
				return $content;
			}

			if ( ! $this->skautis_login->is_user_logged_in_skautis() ) {
				if ( 'showLogin' === $atts['content'] ) {
					return self::get_login_required_message() . $this->get_login_form();
				} else {
					return '';
				}
			}

			if ( $this->rules_manager->check_if_user_passed_rules( explode( ',', $atts['rules'] ) ) ) {
				return $content;
			} else {
				if ( 'showLogin' === $atts['content'] ) {
					return self::get_unauthorized_message() . $this->get_login_form( true );
				} else {
					return '';
				}
			}
		}

		return '';
	}

}
