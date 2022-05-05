<?php
/**
 * Contains the Login_Form class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Modules\Register\Frontend;

use Skautis_Integration\Modules\Register\WP_Register;

/**
 * Adds the "Log in with SkautIS" button to the login form.
 *
 * This class handles the version of the form that also supports registering new users on first login.
 */
final class Login_Form {

	/**
	 * A link to the WP_Register service instance.
	 *
	 * @var WP_Register
	 */
	private $wp_register;

	/**
	 * Constructs the service and saves all dependencies.
	 *
	 * @param WP_Register $wp_register An injected WP_Register service instance.
	 */
	public function __construct( WP_Register $wp_register ) {
		$this->wp_register = $wp_register;
		$this->init_hooks();
	}

	/**
	 * Intializes all hooks used by the object.
	 */
	private function init_hooks() {
		add_action( 'login_form', array( $this, 'login_link_in_login_form' ) );
		add_action( 'login_enqueue_scripts', array( self::class, 'enqueue_login_styles' ) );
		add_filter( 'login_form_bottom', array( $this, 'login_link_in_login_form_return' ) );
	}

	/**
	 * Enqueues login page styles.
	 */
	public static function enqueue_login_styles() {
		wp_enqueue_style( SKAUTIS_INTEGRATION_NAME . '_frontend' );
	}

	/**
	 * Prints the Register module version of the "Log in with SkautIS" button as part of the login page.
	 */
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

	/**
	 * Returns the Register module version of the "Log in with SkautIS" button as part of the login page.
	 *
	 * TODO: Remove this function. Why is the button printed from 2 different hooks?
	 *
	 * @param string $html Unused @unused-param.
	 */
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
