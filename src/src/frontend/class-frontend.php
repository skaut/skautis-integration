<?php

declare( strict_types=1 );

namespace SkautisIntegration\Frontend;

use SkautisIntegration\Auth\Skautis_Gateway;
use SkautisIntegration\Auth\WP_Login_Logout;
use SkautisIntegration\Utils\Helpers;

final class Frontend {

	// TODO: Unused?
	private $login_form;
	private $wp_login_logout;
	private $skautis_gateway;
	// TODO: Unused?
	private $frontend_dir_url = '';

	public function __construct( Login_Form $loginForm, WP_Login_Logout $wpLoginLogout, Skautis_Gateway $skautisGateway ) {
		$this->login_form       = $loginForm;
		$this->wp_login_logout  = $wpLoginLogout;
		$this->skautis_gateway  = $skautisGateway;
		$this->frontend_dir_url = plugin_dir_url( __FILE__ ) . 'public/';
		$this->pluginLoginView  = false;
		$this->init_hooks();
	}

	private function init_hooks() {
		if ( get_option( SKAUTISINTEGRATION_NAME . '_login_page_url' ) ) {
			add_filter( 'query_vars', array( $this, 'register_query_vars' ) );
			add_filter( 'template_include', array( $this, 'register_templates' ) );
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_login_styles' ) );
		if ( $this->skautis_gateway->is_initialized() ) {
			if ( $this->skautis_gateway->get_skautis_instance()->getUser()->isLoggedIn() ) {
				add_action( 'admin_bar_menu', array( $this, 'add_logout_link_to_admin_bar' ), 20 );
			}
		}
	}

	public function register_query_vars( array $vars = array() ): array {
		$vars[] = 'skautis_login';

		return $vars;
	}

	public function register_templates( string $path = '' ): string {
		$queryValue = get_query_var( 'skautis_login' );
		if ( $queryValue && ! empty( $queryValue ) ) {
			if ( file_exists( get_stylesheet_directory() . '/skautis/login.php' ) ) {
				return get_stylesheet_directory() . '/skautis/login.php';
			} elseif ( file_exists( get_template_directory() . '/skautis/login.php' ) ) {
				return get_template_directory() . '/skautis/login.php';
			} else {
				$this->pluginLoginView = true;

				return plugin_dir_path( __FILE__ ) . 'public/views/login.php';
			}
		}

		return $path;
	}

	public function enqueue_styles() {
		if ( $this->pluginLoginView ) {
			wp_enqueue_style( 'buttons' );
		}

		Helpers::enqueue_style( 'frontend', 'frontend/css/skautis-frontend.min.css' );
	}

	public function enqueue_login_styles() {
		Helpers::enqueue_style( 'frontend', 'frontend/css/skautis-frontend.min.css' );
	}

	public function add_logout_link_to_admin_bar( \WP_Admin_Bar $wpAdminBar ) {
		if ( ! function_exists( 'is_admin_bar_showing' ) ) {
			return;
		}
		if ( ! is_admin_bar_showing() ) {
			return;
		}

		if ( method_exists( $wpAdminBar, 'get_node' ) ) {
			if ( $wpAdminBar->get_node( 'user-actions' ) ) {
				$parent = 'user-actions';
			} else {
				return;
			}
		} elseif ( get_option( 'show_avatars' ) ) {
			$parent = 'my-account-with-avatar';
		} else {
			$parent = 'my-account';
		}

		$wpAdminBar->add_menu(
			array(
				'parent' => $parent,
				'id'     => SKAUTISINTEGRATION_NAME . '_adminBar_logout',
				'title'  => esc_html__( 'Odhlásit se (i ze skautISu)', 'skautis-integration' ),
				'href'   => $this->wp_login_logout->get_logout_url(),
			)
		);
	}

}
