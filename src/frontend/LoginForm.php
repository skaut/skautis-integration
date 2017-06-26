<?php

namespace SkautisIntegration\Frontend;

use SkautisIntegration\Auth\WpLoginLogout;
use SkautisIntegration\Services\Services;
use SkautisIntegration\Modules\Register\Register;

final class LoginForm {

	private $wpLoginLogout;
	private $frontendDirUrl = '';

	public function __construct( WpLoginLogout $wpLoginLogout ) {
		$this->wpLoginLogout  = $wpLoginLogout;
		$this->frontendDirUrl = plugin_dir_url( __FILE__ ) . 'public/';
		$this->initHooks();
	}

	private function initHooks() {
		if ( ! Services::getServicesContainer()['modulesManager']->isModuleActivated( Register::getId() ) ) {
			add_action( 'login_form', [ $this, 'loginLinkInLoginForm' ] );
			add_filter( 'login_form_bottom', [ $this, 'loginLinkInLoginFormReturn' ] );
		}
	}

	public function enqueueStyles() {
		wp_enqueue_style( SKAUTISINTEGRATION_NAME, $this->frontendDirUrl . 'css/skautis-frontend.css', [], SKAUTISINTEGRATION_VERSION, 'all' );
	}

	public function enqueueScripts() {
		wp_enqueue_script( SKAUTISINTEGRATION_NAME, $this->frontendDirUrl . 'js/skautis-frontend.js', [ 'jquery' ], SKAUTISINTEGRATION_VERSION, false );
	}

	public function loginLinkInLoginForm() {
		?>
		<p style="margin-bottom: 0.3em;">
			<a class="button button-primary button-hero pic-lilie" style="float: none; width: 100%; text-align: center;""
			   href="<?php echo $this->wpLoginLogout->getLoginUrl(); ?>"><?php _e( 'Přihlásit se přes skautIS', 'skautis-integration' ); ?></a>
			<br/>
		</p><br/>
		<?php
	}

	public function loginLinkInLoginFormReturn( $html ) {
		return '
		<p style="margin-bottom: 0.3em;">
			<a class="button button-primary button-hero pic-lilie" style="float: none; width: 100%; text-align: center;"
			   href="' . $this->wpLoginLogout->getLoginUrl() . '">' . __( 'Přihlásit se přes skautIS', 'skautis-integration' ) . '</a>
			   <br/>
		</p><br/>
		';
	}

}
