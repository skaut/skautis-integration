<?php

namespace SkautisIntegration\Admin;

use SkautisIntegration\Auth\SkautisGateway;
use SkautisIntegration\Auth\WpLoginLogout;
use SkautisIntegration\Rules\RulesManager;

final class Admin {

	private $settings;
	private $users;
	private $rulesManager;
	private $wpLoginLogout;
	private $skautisGateway;
	private $usersManagement;
	private $adminDirUrl = '';

	public function __construct( Settings $settings, Users $users, RulesManager $rulesManager, UsersManagement $usersManagement, WpLoginLogout $wpLoginLogout, SkautisGateway $skautisGateway ) {
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
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueStyles' ] );

		if ( ! empty( $_GET['page'] ) && $_GET['page'] == 'skautis-integration_usersManagement' ) {
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueueScripts' ] );
		}

		if ( $this->skautisGateway->isInitialized() ) {
			if ( $this->skautisGateway->getSkautisInstance()->getUser()->isLoggedIn() ) {
				add_action( 'admin_bar_menu', [ $this, 'addLogoutLinkToAdminBar' ], 20 );
			}
		}
	}

	public function enqueueStyles() {
		wp_enqueue_style(
			'select2',
			'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css',
			[],
			'4.0.3',
			'all'
		);

		wp_enqueue_style(
			SKAUTISINTEGRATION_NAME,
			$this->adminDirUrl . 'css/skautis-admin.css',
			[],
			SKAUTISINTEGRATION_VERSION,
			'all'
		);
	}

	public function enqueueScripts() {
		wp_enqueue_script( 'thickbox' );
		wp_enqueue_style( 'thickbox' );
		if ( is_network_admin() ) {
			add_action( 'admin_head', '_thickbox_path_admin_subfolder' );
		}

		wp_enqueue_script(
			'select2',
			'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js',
			[ 'jquery' ],
			'4.0.3',
			true
		);

		wp_enqueue_script(
			SKAUTISINTEGRATION_NAME,
			$this->adminDirUrl . 'js/skautis-admin.js',
			[ 'jquery' ],
			SKAUTISINTEGRATION_VERSION,
			true
		);
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
		} else if ( get_option( 'show_avatars' ) ) {
			$parent = 'my-account-with-avatar';
		} else {
			$parent = 'my-account';
		}

		$wpAdminBar->add_menu( array(
			'parent' => $parent,
			'id'     => SKAUTISINTEGRATION_NAME . '_adminBar_logout',
			'title'  => esc_html__( 'Odhlásit se (i ze skautISu)', 'skautis-integration' ),
			'href'   => $this->wpLoginLogout->getLogoutUrl(),
		) );
	}

}
