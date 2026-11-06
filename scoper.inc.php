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
			$underscore_prefix = mb_ereg_replace( '\\\\', '_', $prefix );

			if ( __DIR__ . '/vendor/composer/autoload_real.php' === $file_path ) {
				$contents = safe_replace( "if \\('Composer\\\\{1,2}Autoload\\\\{1,2}ClassLoader' === \\\$class\\)", "if ('{$replace_prefix}\\\\Composer\\\\Autoload\\\\ClassLoader' === \$class)", $contents );
				$contents = safe_replace( "\\\\spl_autoload_unregister\\(array\\('ComposerAutoloaderInit", "\\spl_autoload_unregister(array('{$replace_prefix}\\\\ComposerAutoloaderInit", $contents );
				$contents = safe_replace(
					"\\\$GLOBALS\['__composer_autoload_files'\]",
					"\$GLOBALS['__composer_autoload_files_{$underscore_prefix}']",
					$contents
				);
			}

			// skautis/skautis 2.x uses implicitly nullable parameter types (`Type $param = null`), which are deprecated since PHP 8.4.
			if ( 0 === strpos( $file_path, __DIR__ . '/vendor/skautis/skautis/src/' ) ) {
				$explicitly_nullable = preg_replace( '/([(,]\s*)(\\\\?[A-Za-z_][A-Za-z0-9_\\\\]*)(\s+\$[A-Za-z_][A-Za-z0-9_]*\s*=\s*null\b)/', '$1?$2$3', $contents );
				if ( is_string( $explicitly_nullable ) ) {
					$contents = $explicitly_nullable;
				}
			}

			return $contents;
		},
	),
);
