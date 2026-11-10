<?php
/**
 * Contains the Visibility class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Modules\Visibility;

use Skautis_Integration\Auth\WP_Login_Logout;
use Skautis_Integration\Rules\Rules_Manager;
use Skautis_Integration\Auth\Skautis_Login;
use Skautis_Integration\Modules\Visibility\Admin\Admin;
use Skautis_Integration\Modules\Visibility\Admin\Block_Editor;
use Skautis_Integration\Modules\Visibility\Frontend\Block_Visibility;
use Skautis_Integration\Modules\Visibility\Frontend\Frontend;

/**
 * Hides posts, pages and blocks from users who do not pass the selected rules.
 */
final class Visibility {

	const REGISTER_ACTION = 'visibility';

	/**
	 * A link to the Rules_Manager service instance.
	 *
	 * TODO: Unused?
	 *
	 * @var Rules_Manager
	 */
	private $rules_manager;

	/**
	 * A link to the Skautis_Login service instance.
	 *
	 * TODO: Unused?
	 *
	 * @var Skautis_Login
	 */
	private $skautis_login;

	/**
	 * A link to the WP_Login_Logout service instance.
	 *
	 * TODO: Unused?
	 *
	 * @var WP_Login_Logout
	 */
	private $wp_login_logout;

	/**
	 * An instance of the module Frontend service.
	 *
	 * @var Frontend
	 */
	private $frontend;

	/**
	 * Constructs the service and saves all dependencies.
	 *
	 * @param Rules_Manager   $rules_manager An injected Rules_Manager service instance.
	 * @param Skautis_Login   $skautis_login An injected Skautis_Login service instance.
	 * @param WP_Login_Logout $wp_login_logout An injected WP_Login_Logout service instance.
	 */
	public function __construct( Rules_Manager $rules_manager, Skautis_Login $skautis_login, WP_Login_Logout $wp_login_logout ) {
		$this->rules_manager   = $rules_manager;
		$this->skautis_login   = $skautis_login;
		$this->wp_login_logout = $wp_login_logout;
		$post_types            = (array) get_option( SKAUTIS_INTEGRATION_NAME . '_modules_visibility_postTypes', array() );
		$this->frontend        = new Frontend( $post_types, $this->rules_manager, $this->skautis_login, $this->wp_login_logout );
		// Both are needed in all requests, the block editor uses the REST API.
		new Block_Visibility( $this->rules_manager, $this->skautis_login, $this->frontend );
		new Block_Editor( $post_types, $this->rules_manager, $this->frontend );
		if ( is_admin() ) {
			new Admin( $post_types, $this->rules_manager, $this->frontend );
		} else {
			$this->frontend->init_hooks();
		}
	}
}
