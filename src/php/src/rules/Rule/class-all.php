<?php
/**
 * Contains the All class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Rules\Rule;

use Skautis_Integration\Rules\Rule;
use Skautis_Integration\Auth\Skautis_Gateway;

class All implements Rule {

	public static $id           = 'all';
	protected static $type      = 'integer';
	protected static $input     = 'checkbox';
	protected static $multiple  = false;
	protected static $operators = array( 'equal' );

	protected $skautis_gateway;

	/**
	 * Constructs the service and saves all dependencies.
	 */
	public function __construct( Skautis_Gateway $skautis_gateway ) {
		$this->skautis_gateway = $skautis_gateway;
	}

	/**
	 * Returns the rule ID.
	 */
	public function get_id(): string {
		return self::$id;
	}

	/**
	 * Returns the localized rule name.
	 */
	public function get_label(): string {
		return __( 'Všichni bez omezení', 'skautis-integration' );
	}

	/**
	 * Returns the rule value type.
	 *
	 * @return "string"|"integer"|"double"|"date"|"time"|"datetime"|"boolean"
	 */
	public function get_type(): string {
		return self::$type;
	}

	/**
	 * Returns the rule input field type type.
	 *
	 * @return "roleInput"|"membershipInput"|"funcInput"|"qualificationInput"|"text"|"number"|"textarea"|"radio"|"checkbox"|"select"
	 */
	public function get_input(): string {
		return self::$input;
	}

	/**
	 * Returns whether the rule accepts multiple values at once.
	 */
	public function get_multiple(): bool {
		return self::$multiple;
	}

	/**
	 * Returns all the operators that are applicable for the rule.
	 *
	 * @return array<"equal"|"not_equal"|"in"|"not_in"|"less"|"less_or_equal"|"greater"|"greater_or_equal"|"between"|"not_between"|"begins_with"|"not_begins_with"|"contains"|"not_contains"|"ends_with"|"not_ends_with"|"is_empty"|"is_not_empty"|"is_null"|"is_not_null">
	 */
	public function get_operators(): array {
		return self::$operators;
	}

	/**
	 * Returns the placeholder value for the rule.
	 */
	public function get_placeholder(): string {
		return '';
	}

	/**
	 * Returns an optional additional description of the rule.
	 */
	public function get_description(): string {
		return __( 'Při použití tohoto pravidla se budou moci všichni uživatelé s účtem ve skautISu, propojeným se svojí osobou, registrovat. Nemá tedy smysl tuto podmínku kombinovat s dalšími podmínkami (role, typ členství, ...). Doporučujeme použít tuto podmínku jako jedinou v celém pravidle a žádné další zde nemít.', 'skautis-integration' );
	}

	/**
	 * Returns the current values of the rule.
	 */
	public function get_values(): array {
		$result = array(
			1 => __( 'Ano', 'skautis-integration' ),
		);

		return $result;
	}

	public function is_rule_passed( string $operator, $data ): bool {
		if ( ! empty( $data[0] ) && 1 === $data[0] && $this->skautis_gateway->get_skautis_instance()->UserManagement->UserDetail()->ID > 0 ) {
			return true;
		}

		return false;
	}

}
