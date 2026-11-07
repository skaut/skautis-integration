<?php
/**
 * Contains the Func class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Rules\Rule;

use PHPMD\Attribute\SuppressWarnings;
use PHPMD\Rule\Naming\BooleanGetMethodName;
use Skautis_Integration\Rules\Rule;
use Skautis_Integration\Auth\Skautis_Gateway;

/**
 * Rule operator for filtering users based on their skautIS function.
 */
class Func implements Rule {

	/**
	 * The rule ID
	 *
	 * @var string
	 */
	private static $rule_id = 'func';

	/**
	 * The rule value type.
	 *
	 * @var string
	 */
	protected static $type = 'string';

	/**
	 * The rule input field type type.
	 *
	 * @var string
	 */
	protected static $input = 'funcInput';

	/**
	 * Whether the rule accepts multiple values at once.
	 *
	 * @var bool
	 */
	protected static $multiple = true;

	/**
	 * All the operators that are applicable for the rule.
	 *
	 * @var array<string>
	 */
	protected static $operators = array( 'in', 'not_in' );

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
	public static function get_id(): string {
		return self::$rule_id;
	}

	/**
	 * Returns the localized rule name.
	 */
	public function get_label(): string {
		return __( 'Funkce', 'skautis-integration' );
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
	#[SuppressWarnings( BooleanGetMethodName::class )]
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
		return '';
	}

	/**
	 * Returns the current values of the rule.
	 *
	 * @return array<string, string> The current values.
	 */
	public function get_values(): array {
		$values = array();
		$funcs  = $this->skautis_gateway->get_skautis_instance()->OrganizationUnit->FunctionTypeAll();

		foreach ( $funcs as $func ) {
			$values[ strval( $func->ID ) ] = $func->ShortName;
		}

		return $values;
	}

	/**
	 * Removes special characters ("." and "-") from skautIS unit IDs.
	 *
	 * TODO: Duplicated in Membership and Role.
	 *
	 * @param string $unit_id The raw unit ID.
	 */
	protected static function clearUnitId( string $unit_id ): string {
		return trim(
			str_replace(
				array(
					'.',
					'-',
				),
				'',
				$unit_id
			)
		);
	}

	/**
	 * Returns an array of arrays where for each user function ID, there are listed units asssociated with that function.
	 *
	 * @return array<int, array<string>> The function IDs.
	 */
	protected function getUserFuncsWithUnitIds(): array {
		static $user_funcs = null;

		if ( ! is_null( $user_funcs ) ) {
			return $user_funcs;
		}
		$user_detail = $this->skautis_gateway->get_skautis_instance()->UserManagement->UserDetail();
		$user_funcs  = $this->skautis_gateway->get_skautis_instance()->OrganizationUnit->FunctionAllPerson(
			array(
				'ID_Person' => $user_detail->ID_Person,
			)
		);

		if ( ! is_object( $user_funcs ) || ! isset( $user_funcs->FunctionAllOutput ) ) {
			$user_funcs = array();
			return array();
		}

		$functions = $user_funcs->FunctionAllOutput;
		// skautIS returns a single object instead of an array when the person has only one function.
		if ( is_object( $functions ) ) {
			$functions = array( $functions );
		}
		if ( ! is_array( $functions ) ) {
			$user_funcs = array();
			return array();
		}

		$result = array();

		foreach ( $functions as $user_func ) {
			if ( ! is_object( $user_func ) || ! isset( $user_func->ID_Unit, $user_func->ID_FunctionType ) ) {
				continue;
			}
			$unit_detail = $this->skautis_gateway->get_skautis_instance()->OrganizationUnit->UnitDetail(
				array(
					'ID' => $user_func->ID_Unit,
				)
			);
			if ( $unit_detail ) {
				if ( ! isset( $result[ $user_func->ID_FunctionType ] ) ) {
					$result[ intval( $user_func->ID_FunctionType ) ] = array();
				}
				$result[ intval( $user_func->ID_FunctionType ) ][] = $unit_detail->RegistrationNumber;
			}
		}

		$user_funcs = $result;
		return $user_funcs;
	}

	/**
	 * Checks whether the rule is fulfilled.
	 *
	 * @throws \Exception An operator is undefined.
	 *
	 * @param string $funcs_operator The operator used with the rule.
	 * @param string $data The rule data.
	 */
	public function is_rule_passed( string $funcs_operator, $data ): bool {
		// Parse and prepare data from rules UI.
		$output = array();
		preg_match_all( '|[^~]+|', $data, $output );
		if ( isset( $output[0][0], $output[0][1], $output[0][2] ) ) {
			list( $funcs, $unit_operator, $unit_id ) = $output[0];
			$funcs                                   = explode( ',', $funcs );
			$unit_id                                 = self::clearUnitId( $unit_id );
		} else {
			return false;
		}

		// Logic to determine in / not_in range.
		switch ( $funcs_operator ) {
			case 'in':
				$assume_in = true;
				break;
			case 'not_in':
				$assume_in = false;
				break;
			default:
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					throw new \Exception( esc_html( 'Function operator: "' . $funcs_operator . '" is not declared.' ) );
				}
				return false;
		}

		if ( ! in_array( $unit_operator, array( 'equal', 'begins_with', 'any' ), true ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				throw new \Exception( esc_html( 'Unit operator: "' . $unit_operator . '" is not declared.' ) );
			}
			return false;
		}

		// Whether the user has at least one of the funcs in a unit matching the unit operator.
		$user_funcs = $this->getUserFuncsWithUnitIds();
		$has_match  = false;
		foreach ( $funcs as $func ) {
			foreach ( $user_funcs[ intval( $func ) ] ?? array() as $user_func_unit_id ) {
				$user_func_unit_id = self::clearUnitId( $user_func_unit_id );
				if (
					'any' === $unit_operator ||
					( 'equal' === $unit_operator && $user_func_unit_id === $unit_id ) ||
					( 'begins_with' === $unit_operator && substr( $user_func_unit_id, 0, strlen( $unit_id ) ) === $unit_id )
				) {
					$has_match = true;
					break 2;
				}
			}
		}

		// "in" passes when the user has one of the funcs, "not_in" when they have none of them.
		return $assume_in ? $has_match : ! $has_match;
	}
}
