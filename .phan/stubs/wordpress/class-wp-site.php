<?php
/**
 * Contains a stub of the WordPress WP_Site class for phan and PHPStan
 *
 * The skaut/wordpress-stubs package only contains the parts of WordPress that its plugins use. These stubs add the ones this plugin needs on top of it, with the signatures and types from WordPress 7.0.
 *
 * @package skautis-integration
 *
 * phpcs:disable Squiz.Commenting.FunctionComment.InvalidNoReturn
 * phpcs:disable Squiz.Commenting.FunctionComment.MissingParamComment
 * phpcs:disable Generic.Commenting.DocComment.MissingShort
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
 * phpcs:disable WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
 * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.Found
 * phpcs:disable Squiz.Commenting.VariableComment.Missing
 */

declare( strict_types=1 );

/**
 * A site in a multisite network.
 */
final class WP_Site {

	/**
	 * @var string
	 */
	public $blog_id;
}
