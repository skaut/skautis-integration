<?php
/**
 * Contains the Module interface.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Modules;

interface Module {
	/**
	 * Returns the localized module name.
	 */
	public static function get_label(): string;

	/**
	 * Returns the module ID.
	 */
	public static function get_id(): string;

	/**
	 * Returns the path to the module.
	 */
	public static function get_path(): string;

	/**
	 * Returns the URL of the module.
	 */
	public static function get_url(): string;
}
