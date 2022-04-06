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

final class Frontend {

	private $skautis_login;
	private $rules_manager;
	private $wp_login_logout;

	/**
	 * Constructs the service and saves all dependencies.
	 */
	public function __construct( Skautis_Login $skautis_login, Rules_Manager $rules_manager, WP_Login_Logout $wp_login_logout ) {
		$this->skautis_login   = $skautis_login;
		$this->rules_manager   = $rules_manager;
		$this->wp_login_logout = $wp_login_logout;
		$this->init_hooks();
	}

	/**
	 * Intializes all hooks used by the object.
	 */
	private function init_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_shortcode( 'skautis', array( $this, 'process_shortcode' ) );
	}

	/**
	 * Prints a login form to access the content in a shortcode.
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
	private function get_login_required_message(): string {
		return '<p>' . __( 'To view this content you must be logged in skautIS', 'skautis-integration' ) . '</p>';
	}

	/**
	 * Return a localized message about the user not having permission to access the content.
	 */
	private function get_unauthorized_message(): string {
		return '<p>' . __( 'You do not have permission to access this content', 'skautis-integration' ) . '</p>';
	}

	/**
	 * Enqueues all styles needed for the shortcode frontend view.
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'buttons' );
		Helpers::enqueue_style( 'frontend', 'frontend/css/skautis-frontend.min.css' );
	}

	public function process_shortcode( array $atts = array(), string $content = '' ): string {
		if ( isset( $atts['rules'] ) && isset( $atts['content'] ) ) {
			if ( current_user_can( 'edit_' . get_post_type() . 's' ) ) {
				return $content;
			}

			if ( ! $this->skautis_login->is_user_logged_in_skautis() ) {
				if ( 'showLogin' === $atts['content'] ) {
					return $this->get_login_required_message() . $this->get_login_form();
				} else {
					return '';
				}
			}

			if ( $this->rules_manager->check_if_user_passed_rules( explode( ',', $atts['rules'] ) ) ) {
				return $content;
			} else {
				if ( 'showLogin' === $atts['content'] ) {
					return $this->get_unauthorized_message() . $this->get_login_form( true );
				} else {
					return '';
				}
			}
		}

		return '';
	}

}
