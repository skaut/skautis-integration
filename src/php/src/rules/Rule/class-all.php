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

/**
 * Rule operator for applying a rule to all users.
 */
class All implements Rule {

	/**
	 * The rule ID
	 *
	 * @var string
	 */
	public static $rule_id = 'all';

	/**
	 * The rule value type.
	 *
	 * @var string
	 */
	protected static $type = 'integer';

	/**
	 * The rule input field type type.
	 *
	 * @var string
	 */
	protected static $input = 'checkbox';

	/**
	 * Whether the rule accepts multiple values at once.
	 *
	 * @var bool
	 */
	protected static $multiple = false;

	/**
	 * All the operators that are applicable for the rule.
	 *
	 * @var array<string>
	 */
	protected static $operators = array( 'equal' );

	/**
	 * A link to the Skautis_Gateway service instance.
	 *
	 * @var Skautis_Gateway
	 */
	protected $skautis_gateway;

	/**
	 * Constructs the service and saves all dependencies.
	 *
	 * @param Skautis_Gateway $skautis_gateway An injected Skautis_Gateway service instance.
	 */
	public function __construct( Skautis_Gateway $skautis_gateway ) {
		$this->skautis_gateway = $skautis_gateway;
	}

	/**
	 * Returns the rule ID.
	 */
	public function get_id(): string {
		return self::$rule_id;
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
	 * @return string
	 */
	public function get_type(): string {
		return self::$type;
	}

	/**
	 * Returns the rule input field type type.
	 *
	 * @return string
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
	 * @return array<string>
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

	/**
	 * Checks whether the rule is fulfilled.
	 *
	 * @param string $operator The operator used with the rule @unused-param.
	 * @param string $data The rule data.
	 */
	public function is_rule_passed( string $operator, $data ): bool {
		if ( ! empty( $data[0] ) && 1 === $data[0] && $this->skautis_gateway->get_skautis_instance()->UserManagement->UserDetail()->ID > 0 ) {
			return true;
		}

		return false;
	}

}
