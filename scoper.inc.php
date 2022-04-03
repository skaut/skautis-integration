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
 * @param string $string The string to replace in.
 *
 * @return string The string with replacement, if it can be replaced.
 */
function safe_replace( $pattern, $replacement, $string ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	$replacement = mb_ereg_replace( $pattern, $replacement, $string );
	if ( false === $replacement || null === $replacement ) {
		return $string;
	}
	return $replacement;
}

return array(
	'prefix'   => 'Skautis_Integration\\Vendor',
	'finders'  => array(
		Finder::create()->files()
			->name( array( '*.php', '/LICENSE(.txt)?/' ) )

			->path( '#^psr/container/#' )
			->path( '#^skautis/skautis/#' )
			->path( '#^composer/#' )
			->in( 'vendor' ),
		Finder::create()->files()
			->name( 'autoload.php' )
			->in( 'vendor' ),
	),
	'patchers' => array(
		static function ( $file_path, $prefix, $contents ) {
			$regex_prefix   = mb_ereg_replace( '\\\\', '\\\\\\\\', $prefix );
			$replace_prefix = mb_ereg_replace( '\\\\', '\\\\', $prefix );
			if ( __DIR__ . '/vendor/composer/autoload_real.php' === $file_path ) {
				$var_name_prefix = mb_ereg_replace( '\\\\', '_', $prefix );
				$contents = safe_replace( "if \\('Composer\\\\\\\\Autoload\\\\\\\\ClassLoader' === \\\$class\\)", "if ('{$replace_prefix}\\\\Composer\\\\Autoload\\\\ClassLoader' === \$class)", $contents );
				$contents = safe_replace( "\\\\spl_autoload_unregister\\(array\\('ComposerAutoloaderInit", "\\spl_autoload_unregister(array('{$replace_prefix}\\\\ComposerAutoloaderInit", $contents );
				$contents = safe_replace( "\\\$GLOBALS\['__composer_autoload_files'\]", "\$GLOBALS['__composer_autoload_files_" . $var_name_prefix . "']", $contents );
			}
			// PSR-0 support
			if ( __DIR__ . '/vendor/composer/ClassLoader.php' === $file_path ) {
				$contents = safe_replace( "// PSR-0 lookup\n", "// PSR-0 lookup\n        \$scoperPrefix = '{$replace_prefix}\\\\';\n        if (substr(\$class, 0, strlen(\$scoperPrefix)) == \$scoperPrefix) {\n            \$class = substr(\$class, strlen(\$scoperPrefix));\n            \$first = \$class[0];\n            \$logicalPathPsr4 = substr(\$logicalPathPsr4, strlen(\$scoperPrefix));\n        }\n", $contents );
			}

			return $contents;
		},
	),
);
