<?php
/**
 * PHP-Scoper configuration
 *
 * @package skautis-integration
 */

use Isolated\Symfony\Component\Finder\Finder;

/**
 * Safely replaces pattern with replacement in string.
 *
 * @param string $pattern The pattern to be replaced.
 * @param string $replacement The replacement.
 * @param string $value The string to replace in.
 *
 * @return string The string with replacement, if it can be replaced.
 */
function safe_replace( $pattern, $replacement, $value ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	$replacement = mb_ereg_replace( $pattern, $replacement, $value );
	if ( false === $replacement || null === $replacement ) {
		return $value;
	}
	return $replacement;
}

/**
 * Constructs a finder for composer dependencies.
 *
 * @return Finder The initialized Finder.
 */
function dependency_finder() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
	exec( 'composer show --no-dev --name-only', $dependencies );
	$finder = Finder::create()->files()->name( array( '*.php', '/LICENSE(.txt)?/' ) )->in( 'vendor' );

	foreach ( $dependencies as $dependency ) {
		$finder->path( '#^' . $dependency . '/#' );
	}

	return $finder;
}

return array(
	'prefix'                  => 'Skautis_Integration\\Vendor',
	'output-dir'              => 'dist/vendor',
	'expose-global-constants' => true,
	'expose-global-classes'   => false,
	'expose-global-functions' => false,
	'finders'                 => array(
		dependency_finder(),
		Finder::create()->files()
			->name( array( '*.php', '/LICENSE(.txt)?/' ) )
			->depth( 0 )
			->in( 'vendor/composer' ),
		Finder::create()->files()
			->name( 'autoload.php' )
			->depth( 0 )
			->in( 'vendor' ),
	),
	'patchers'                => array(
		static function ( $file_path, $prefix, $contents ) {
			$replace_prefix = mb_ereg_replace( '\\\\', '\\\\', $prefix );
			if ( __DIR__ . '/vendor/composer/autoload_real.php' === $file_path ) {
				$contents = safe_replace( "if \\('Composer\\\\\\\\Autoload\\\\\\\\ClassLoader' === \\\$class\\)", "if ('{$replace_prefix}\\\\Composer\\\\Autoload\\\\ClassLoader' === \$class)", $contents );
				$contents = safe_replace( "\\\\spl_autoload_unregister\\(array\\('ComposerAutoloaderInit", "\\spl_autoload_unregister(array('{$replace_prefix}\\\\ComposerAutoloaderInit", $contents );
			}

			return $contents;
		},
	),
);
