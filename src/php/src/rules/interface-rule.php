<?php
/**
 * Contains the Rule interface.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Rules;

interface Rule {
	/**
	 * Returns the rule ID.
	 */
	public static function get_id(): string;

	/**
	 * Returns the localized rule name.
	 */
	public function get_label(): string;

	/**
	 * Returns the rule value type.
	 *
	 * @return string
	 */
	public function get_type(): string;

	/**
	 * Returns the rule input field type type.
	 *
	 * @return string
	 */
	public function get_input(): string;

	/**
	 * Returns whether the rule accepts multiple values at once.
	 */
	public function get_multiple(): bool;

	/**
	 * Returns all the operators that are applicable for the rule.
	 *
	 * @return array<string>
	 */
	public function get_operators(): array;

	/**
	 * Returns the placeholder value for the rule.
	 */
	public function get_placeholder(): string;

	/**
	 * Returns an optional additional description of the rule.
	 */
	public function get_description(): string;

	/**
	 * Returns the current values of the rule.
	 *
	 * @return array<int|string, string> The current values.
	 */
	public function get_values(): array;

	/**
	 * Checks whether the rule is fulfilled.
	 *
	 * @param string               $operator The operator used with the rule.
	 * @param string|array{0: int} $data The rule data.
	 */
	public function is_rule_passed( string $operator, $data ): bool;
}
