<?php

declare( strict_types=1 );

namespace SkautisIntegration\Modules\Register\Frontend;

use SkautisIntegration\Modules\Register\WP_Register;

final class Login_Form {

	private $wpRegister;

	public function __construct( WP_Register $wpRegister ) {
		$this->wpRegister = $wpRegister;
		$this->init_hooks();
	}

	private function init_hooks() {
		add_action( 'login_form', array( $this, 'login_link_in_login_form' ) );
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_login_styles' ) );
		add_filter( 'login_form_bottom', array( $this, 'login_link_in_login_form_return' ) );
	}

	public function enqueue_login_styles() {
		wp_enqueue_style( SKAUTISINTEGRATION_NAME . '_frontend' );
	}

	public function login_link_in_login_form() {
		?>
		<p style="margin-bottom: 0.3em;">
			<a class="button button-primary button-hero button-skautis" style="float: none; width: 100%; text-align: center;"
				href="<?php echo esc_url( $this->wpRegister->getRegisterUrl() ); ?>"><?php esc_html_e( 'Log in with skautIS', 'skautis-integration' ); ?></a>
			<br/>
		</p>
		<br/>
		<?php
	}

	public function login_link_in_login_form_return( string $html ): string {
		return '
				<p style="margin-bottom: 0.3em;">
						<a class="button button-primary button-hero button-skautis" style="float: none; width: 100%; text-align: center;"
						   href="' . $this->wpRegister->getRegisterUrl() . '">' . __( 'Log in with skautIS', 'skautis-integration' ) . '</a>
						   <br/>
				</p>
				<br/>
				';
	}

}
