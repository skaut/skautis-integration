<?php
/**
 * Contains the Frontend class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Modules\Register\Frontend;

/**
 * TODO: An unused service?
 *
 * @phan-constructor-used-for-side-effects
 */
final class Frontend {

	/**
	 * A link to the Login_Form service instance.
	 *
	 * TODO: Unused?
	 *
	 * @var Login_Form
	 */
	private $login_form;

	/**
	 * Constructs the service and saves all dependencies.
	 *
	 * @param Login_Form $login_form An injected Login_Form service instance.
	 */
	public function __construct( Login_Form $login_form ) {
		$this->login_form = $login_form;
	}

}
