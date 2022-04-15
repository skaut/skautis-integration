<?php
/**
 * Contains the General class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\General;

use Skautis_Integration\Rules\Rules_Init;

/**
 * TODO: An unused service?
 */
final class General {

	/**
	 * A link to the Actions service instance.
	 *
	 * TODO: Unused?
	 *
	 * @var Actions
	 */
	private $actions;

	/**
	 * A link to the Rules_Init service instance.
	 *
	 * TODO: Unused?
	 *
	 * @var Rules_Init
	 */
	private $rules_init;

	/**
	 * Constructs the service and saves all dependencies.
	 *
	 * @param Actions    $actions An injected Actions service instance.
	 * @param Rules_Init $rules_init An injected Rules_Init service instance.
	 */
	public function __construct( Actions $actions, Rules_Init $rules_init ) {
		$this->actions    = $actions;
		$this->rules_init = $rules_init;
	}

}
