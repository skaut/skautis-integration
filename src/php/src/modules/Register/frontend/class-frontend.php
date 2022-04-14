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
 */
final class Frontend {

	// TODO: Unused?
	private $login_form;

	/**
	 * Constructs the service and saves all dependencies.
	 */
	public function __construct( Login_Form $login_form ) {
		$this->login_form = $login_form;
	}

}
