<?php

declare( strict_types=1 );

namespace SkautisIntegration\Admin;

use SkautisIntegration\Auth\SkautisGateway;
use SkautisIntegration\Auth\WP_Login_Logout;
use SkautisIntegration\Rules\Rules_Manager;
use SkautisIntegration\Utils\Helpers;

final class Admin {

	private $settings;
	private $users;
	private $rulesManager;
	private $wpLoginLogout;
	private $skautisGateway;
	private $usersManagement;
	private $adminDirUrl = '';

	public function __construct( Settings $settings, Users $users, Rules_Manager $rulesManager, UsersManagement $usersManagement, WP_Login_Logout $wpLoginLogout, SkautisGateway $skautisGateway ) {
		$this->settings        = $settings;
		$this->users           = $users;
		$this->rulesManager    = $rulesManager;
		$this->usersManagement = $usersManagement;
		$this->wpLoginLogout   = $wpLoginLogout;
		$this->skautisGateway  = $skautisGateway;
		$this->adminDirUrl     = plugin_dir_url( __FILE__ ) . 'public/';
		$this->initHooks();
	}

	private function initHooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueScriptsAndStyles' ) );
		add_action( 'admin_print_scripts', array( $this, 'printInlineJs' ) );

		if ( $this->skautisGateway->isInitialized() ) {
			if ( $this->skautisGateway->getSkautisInstance()->getUser()->isLoggedIn() ) {
				add_action( 'admin_bar_menu', array( $this, 'addLogoutLinkToAdminBar' ), 20 );
			}
		}
	}

	public function enqueueScriptsAndStyles() {
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

	public function printInlineJs() {
		?>
		<script type="text/javascript">
			//<![CDATA[
			window.skautis = window.skautis || {};
			//]]>
		</script>
		<?php
	}

	public function addLogoutLinkToAdminBar( \WP_Admin_Bar $wpAdminBar ) {
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
				'href'   => $this->wpLoginLogout->getLogoutUrl(),
			)
		);
	}

}
