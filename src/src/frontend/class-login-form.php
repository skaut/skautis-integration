<?php

declare( strict_types=1 );

namespace SkautisIntegration\Frontend;

use SkautisIntegration\Auth\WP_Login_Logout;
use SkautisIntegration\Services\Services;
use SkautisIntegration\Modules\Register\Register;
use SkautisIntegration\Utils\Helpers;

final class Login_Form {

	private $wp_login_logout;
	// TODO: Unused?
	private $frontend_dir_url = '';

	public function __construct( WP_Login_Logout $wpLoginLogout ) {
		$this->wp_login_logout  = $wpLoginLogout;
		$this->frontend_dir_url = plugin_dir_url( __FILE__ ) . 'public/';
		$this->init_hooks();
	}

	private function init_hooks() {
		if ( ! Services::get_services_container()['modulesManager']->is_module_activated( Register::get_id() ) ) {
			add_action( 'login_form', array( $this, 'login_link_in_login_form' ) );
			add_filter( 'login_form_bottom', array( $this, 'login_link_in_login_form_return' ) );
		}
	}

	// TODO: Unused?
	public function enqueue_styles() {
		Helpers::enqueue_style( 'frontend', 'frontend/css/skautis-frontened.min.css' );
	}

	public function login_link_in_login_form() {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->login_link_in_login_form_return();
	}

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
