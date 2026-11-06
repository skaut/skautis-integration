<?php
/**
 * PHAN configuration
 *
 * @package skautis-integration
 */

return array(
	'target_php_version'                        => '8.3',
	'minimum_target_php_version'                => '8.3',
	'warn_about_undocumented_throw_statements'  => true,
	'strict_method_checking'                    => true,
	'strict_object_checking'                    => false, // TODO: Enable.
	'strict_property_checking'                  => false, // TODO: Enable.
	'strict_return_checking'                    => false, // TODO: Enable.
	'constant_variable_detection'               => true,
	'redundant_condition_detection'             => true,
	'unused_variable_detection'                 => true,
	'warn_about_redundant_use_namespaced_class' => true,
	'directory_list'                            => array(
		'src',
		// 'tests',
		'.phan',
		'dist/vendor',
		'vendor/skaut/wordpress-stubs/stubs',
		'vendor/phpmd/phpmd/src',
	),
	'file_list'                                 => array(
		'scoper.inc.php',
		'phpstan-bootstrap.php',
	),
	'exclude_analysis_directory_list'           => array(
		'dist/vendor/',
		'vendor/skaut/wordpress-stubs/stubs',
		'vendor/phpmd/phpmd/src',
	),
	'suppress_issue_types'                      => array(
		'PhanPluginDuplicateConditionalNullCoalescing',
		'PhanPluginMixedKeyNoKey',
		'PhanUnusedVariableDeclarationCaughtException', // Omitting the variable requires PHP 8.0.
	),
	'plugins'                                   => array(
		'AlwaysReturnPlugin',
		'DollarDollarPlugin',
		'DuplicateArrayKeyPlugin',
		'DuplicateExpressionPlugin',
		'EmptyStatementListPlugin',
		'InvalidVariableIssetPlugin',
		'NoAssertPlugin',
		'NonBoolBranchPlugin',
		'NonBoolInLogicalArithPlugin',
		'PossiblyStaticMethodPlugin',
		'PreferNamespaceUsePlugin',
		'PregRegexCheckerPlugin',
		'StrictComparisonPlugin',
		'SuspiciousParamOrderPlugin',
		'UnreachableCodePlugin',
		'UnusedSuppressionPlugin',
		'UseReturnValuePlugin',
	),
);
