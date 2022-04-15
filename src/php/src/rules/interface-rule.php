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
	public function get_id(): string;

	/**
	 * Returns the localized rule name.
	 */
	public function get_label(): string;

	/**
	 * Returns the rule value type.
	 *
	 * @return "string"|"integer"|"double"|"date"|"time"|"datetime"|"boolean"
	 */
	public function get_type(): string;

	/**
	 * Returns the rule input field type type.
	 *
	 * @return "roleInput"|"membershipInput"|"funcInput"|"qualificationInput"|"text"|"number"|"textarea"|"radio"|"checkbox"|"select"
	 */
	public function get_input(): string;

	/**
	 * Returns whether the rule accepts multiple values at once.
	 */
	public function get_multiple(): bool;

	/**
	 * Returns all the operators that are applicable for the rule.
	 *
	 * @return array<"equal"|"not_equal"|"in"|"not_in"|"less"|"less_or_equal"|"greater"|"greater_or_equal"|"between"|"not_between"|"begins_with"|"not_begins_with"|"contains"|"not_contains"|"ends_with"|"not_ends_with"|"is_empty"|"is_not_empty"|"is_null"|"is_not_null">
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
	 */
	public function get_values(): array;

	/**
	 * Checks whether the rule is fulfilled.
	 *
	 * @param "equal"|"not_equal"|"in"|"not_in"|"less"|"less_or_equal"|"greater"|"greater_or_equal"|"between"|"not_between"|"begins_with"|"not_begins_with"|"contains"|"not_contains"|"ends_with"|"not_ends_with"|"is_empty"|"is_not_empty"|"is_null"|"is_not_null" $operator The operator used with the rule.
	 * @param string $data The rule data.
	 */
	public function is_rule_passed( string $operator, $data ): bool;
}
