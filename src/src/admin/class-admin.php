<?php

declare( strict_types=1 );

namespace SkautisIntegration\Admin;

use SkautisIntegration\Auth\Skautis_Gateway;
use SkautisIntegration\Auth\WP_Login_Logout;
use SkautisIntegration\Rules\Rules_Manager;
use SkautisIntegration\Utils\Helpers;

final class Admin {

	private $settings;
	private $users;
	// TODO: Unused?
	private $rules_manager;
	private $wp_login_logout;
	private $skautis_gateway;
	// TODO: Unused?
	private $users_management;
	// TODO: Unused?
	private $admin_dir_url = '';

	public function __construct( Settings $settings, Users $users, Rules_Manager $rulesManager, Users_Management $usersManagement, WP_Login_Logout $wpLoginLogout, Skautis_Gateway $skautisGateway ) {
		$this->settings         = $settings;
		$this->users            = $users;
		$this->rules_manager    = $rulesManager;
		$this->users_management = $usersManagement;
		$this->wp_login_logout  = $wpLoginLogout;
		$this->skautis_gateway  = $skautisGateway;
		$this->admin_dir_url    = plugin_dir_url( __FILE__ ) . 'public/';
		$this->init_hooks();
	}

	private function init_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_and_styles' ) );
		add_action( 'admin_print_scripts', array( $this, 'print_inline_js' ) );

		if ( $this->skautis_gateway->is_initialized() ) {
			if ( $this->skautis_gateway->get_skautis_instance()->getUser()->isLoggedIn() ) {
				add_action( 'admin_bar_menu', array( $this, 'add_logout_link_to_admin_bar' ), 20 );
			}
		}
	}

	public function enqueue_scripts_and_styles() {
		wp_enqueue_style(
			SKAUTISINTEGRATION_NAME . '_select2',
			SKAUTISINTEGRATION_URL . 'bundled/select2.min.css',
			array(),
			SKAUTISINTEGRATION_VERSION,
			'all'
		);

		wp_enqueue_script(
			SKAUTISINTEGRATION_NAME . '_select2',
			SKAUTISINTEGRATION_URL . 'bundled/select2.min.js',
			array( 'jquery' ),
			SKAUTISINTEGRATION_VERSION,
			false
		);

		Helpers::enqueue_style( 'admin', 'admin/css/skautis-admin.min.css' );
	}

	public function print_inline_js() {
		?>
		<script type="text/javascript">
			//<![CDATA[
			window.skautis = window.skautis || {};
			//]]>
		</script>
		<?php
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
				'title'  => esc_html__( 'Log Out (too from skautIS)', 'skautis-integration' ),
				'href'   => $this->wp_login_logout->get_logout_url(),
			)
		);
	}

}
