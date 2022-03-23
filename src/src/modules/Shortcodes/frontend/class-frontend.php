<?php

declare( strict_types=1 );

namespace SkautisIntegration\Modules\Shortcodes\Frontend;

use SkautisIntegration\Auth\Skautis_Login;
use SkautisIntegration\Rules\Rules_Manager;
use SkautisIntegration\Auth\WP_Login_Logout;
use SkautisIntegration\Utils\Helpers;

final class Frontend {

	private $skautisLogin;
	private $rulesManager;
	private $wpLoginLogout;

	public function __construct( Skautis_Login $skautisLogin, Rules_Manager $rulesManager, WP_Login_Logout $wpLoginLogout ) {
		$this->skautisLogin  = $skautisLogin;
		$this->rulesManager  = $rulesManager;
		$this->wpLoginLogout = $wpLoginLogout;
		$this->init_hooks();
	}

	private function init_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_shortcode( 'skautis', array( $this, 'process_shortcode' ) );
	}

	private function get_login_form( bool $forceLogoutFromSkautis = false ): string {
		$loginUrlArgs = add_query_arg( 'noWpLogin', true, Helpers::getCurrentUrl() );
		if ( $forceLogoutFromSkautis ) {
			$loginUrlArgs = add_query_arg( 'logoutFromSkautis', true, $loginUrlArgs );
		}

		return '
		<div class="wp-core-ui">
			<p style="margin-bottom: 0.3em;">
				<a class="button button-primary button-hero button-skautis"
				   href="' . $this->wpLoginLogout->get_login_url( $loginUrlArgs ) . '">' . __( 'Log in with skautIS', 'skautis-integration' ) . '</a>
			</p>
		</div>
		<br/>
		';
	}

	private function get_login_required_message(): string {
		return '<p>' . __( 'To view this content you must be logged in skautIS', 'skautis-integration' ) . '</p>';
	}

	private function get_unauthorized_message(): string {
		return '<p>' . __( 'You do not have permission to access this content', 'skautis-integration' ) . '</p>';
	}

	public function enqueue_styles() {
		wp_enqueue_style( 'buttons' );
		Helpers::enqueue_style( 'frontend', 'frontend/css/skautis-frontend.min.css' );
	}

	public function process_shortcode( array $atts = array(), string $content = '' ): string {
		if ( isset( $atts['rules'] ) && isset( $atts['content'] ) ) {
			if ( current_user_can( 'edit_' . get_post_type() . 's' ) ) {
				return $content;
			}

			if ( ! $this->skautisLogin->is_user_logged_in_skautis() ) {
				if ( 'showLogin' === $atts['content'] ) {
					return $this->get_login_required_message() . $this->get_login_form();
				} else {
					return '';
				}
			}

			if ( $this->rulesManager->checkIfUserPassedRules( explode( ',', $atts['rules'] ) ) ) {
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
