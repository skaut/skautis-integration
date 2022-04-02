<?php
/**
 * Contains the Login_Form class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Modules\Register\Frontend;

use Skautis_Integration\Modules\Register\WP_Register;

final class Login_Form {

	private $wp_register;

	public function __construct( WP_Register $wp_register ) {
		$this->wp_register = $wp_register;
		$this->init_hooks();
	}

	private function init_hooks() {
		add_action( 'login_form', array( $this, 'login_link_in_login_form' ) );
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_login_styles' ) );
		add_filter( 'login_form_bottom', array( $this, 'login_link_in_login_form_return' ) );
	}

	public function enqueue_login_styles() {
		wp_enqueue_style( SKAUTIS_INTEGRATION_NAME . '_frontend' );
	}

	public function login_link_in_login_form() {
		?>
		<p style="margin-bottom: 0.3em;">
			<a class="button button-primary button-hero button-skautis" style="float: none; width: 100%; text-align: center;"
				href="<?php echo esc_url( $this->wp_register->get_register_url() ); ?>"><?php esc_html_e( 'Log in with skautIS', 'skautis-integration' ); ?></a>
			<br/>
		</p>
		<br/>
		<?php
	}

	public function login_link_in_login_form_return( string $html ): string {
		return '
				<p style="margin-bottom: 0.3em;">
						<a class="button button-primary button-hero button-skautis" style="float: none; width: 100%; text-align: center;"
						   href="' . $this->wp_register->get_register_url() . '">' . __( 'Log in with skautIS', 'skautis-integration' ) . '</a>
						   <br/>
				</p>
				<br/>
				';
	}

}
