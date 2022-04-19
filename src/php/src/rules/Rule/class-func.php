<?php
/**
 * Contains the Func class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Rules\Rule;

use Skautis_Integration\Rules\Rule;
use Skautis_Integration\Auth\Skautis_Gateway;

/**
 * Rule operator for filtering users based on their SkautIS function.
 */
class Func implements Rule {

	/**
	 * The rule ID
	 *
	 * @var string
	 */
	public static $id = 'func';

	/**
	 * The rule value type.
	 *
	 * @var "string"|"integer"|"double"|"date"|"time"|"datetime"|"boolean"
	 */
	protected static $type = 'string';

	/**
	 * The rule input field type type.
	 *
	 * @var "roleInput"|"membershipInput"|"funcInput"|"qualificationInput"|"text"|"number"|"textarea"|"radio"|"checkbox"|"select"
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
	 * @var array<"equal"|"not_equal"|"in"|"not_in"|"less"|"less_or_equal"|"greater"|"greater_or_equal"|"between"|"not_between"|"begins_with"|"not_begins_with"|"contains"|"not_contains"|"ends_with"|"not_ends_with"|"is_empty"|"is_not_empty"|"is_null"|"is_not_null">
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
	public function get_id(): string {
		return self::$id;
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
		return '';
	}

	/**
	 * Returns the current values of the rule.
	 */
	public function get_values(): array {
		$values = array();
		$funcs  = $this->skautis_gateway->get_skautis_instance()->OrganizationUnit->FunctionTypeAll();

		foreach ( $funcs as $func ) {
			$values[ $func->ID ] = $func->ShortName;
		}

		return $values;
	}

	/**
	 * Removes special characters ("." and "-") from SkautIS unit IDs.
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
	 */
	protected function getUserFuncsWithUnitIds(): array {
		static $user_funcs = null;

		if ( is_null( $user_funcs ) ) {
			$user_detail = $this->skautis_gateway->get_skautis_instance()->UserManagement->UserDetail();
			$user_funcs  = $this->skautis_gateway->get_skautis_instance()->OrganizationUnit->FunctionAllPerson(
				array(
					'ID_Person' => $user_detail->ID_Person,
				)
			);

			$result = array();

			if ( ! $user_funcs || ! property_exists( $user_funcs, 'FunctionAllOutput' ) || empty( $user_funcs->FunctionAllOutput ) || ! is_array( $user_funcs->FunctionAllOutput ) || empty( $user_funcs->FunctionAllOutput[0] ) ) {
				return $result;
			}

			foreach ( $user_funcs->FunctionAllOutput as $user_func ) {
				$unit_detail = $this->skautis_gateway->get_skautis_instance()->OrganizationUnit->UnitDetail(
					array(
						'ID' => $user_func->ID_Unit,
					)
				);
				if ( $unit_detail ) {
					if ( ! isset( $result[ $user_func->ID_FunctionType ] ) ) {
						$result[ $user_func->ID_FunctionType ] = array();
					}
					$result[ $user_func->ID_FunctionType ][] = $unit_detail->RegistrationNumber;
				}
			}

			$user_funcs = $result;
		}

		if ( ! is_array( $user_funcs ) ) {
			return array();
		}

		return $user_funcs;
	}

	/**
	 * Checks whether the rule is fulfilled.
	 *
	 * @throws \Exception An operator is undefined.
	 *
	 * @param "equal"|"not_equal"|"in"|"not_in"|"less"|"less_or_equal"|"greater"|"greater_or_equal"|"between"|"not_between"|"begins_with"|"not_begins_with"|"contains"|"not_contains"|"ends_with"|"not_ends_with"|"is_empty"|"is_not_empty"|"is_null"|"is_not_null" $funcs_operator The operator used with the rule.
	 * @param string                                                                                                                                                                                                                                                $data The rule data.
	 */
	public function is_rule_passed( string $funcs_operator, $data ): bool {
		// Parse and prepare data from rules UI.
		$output = array();
		preg_match_all( '|[^~]+|', $data, $output );
		if ( isset( $output[0], $output[0][0], $output[0][1], $output[0][2] ) ) {
			list( $funcs, $unit_operator, $unit_id ) = $output[0];
			$funcs                                   = explode( ',', $funcs );
			$unit_id                                 = self::clearUnitId( $unit_id );
		} else {
			return false;
		}

		// Logic to determine in / not_in range.
		switch ( $funcs_operator ) {
			case 'in':
				$in_not_in_negation = 0;
				break;
			case 'not_in':
				$in_not_in_negation = 1;
				break;
			default:
				$in_not_in_negation = 2;
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					throw new \Exception( 'Function operator: "' . $funcs_operator . '" is not declared.' );
				}
				break;
		}

		$user_funcs = $this->getUserFuncsWithUnitIds();
		$user_pass  = 0;
		foreach ( $funcs as $func ) {
			// in / not_in range check.
			if ( ( $in_not_in_negation + array_key_exists( $func, $user_funcs ) ) === 1 ) {
				foreach ( $user_funcs[ $func ] as $user_func_unit_id ) {
					$user_func_unit_id = self::clearUnitId( $user_func_unit_id );

					switch ( $unit_operator ) {
						case 'equal':
							$user_pass += ( $user_func_unit_id === $unit_id );
							break;
						case 'begins_with':
							$user_pass += ( substr( $user_func_unit_id, 0, strlen( $unit_id ) ) === $unit_id );
							break;
						case 'any':
							++$user_pass;
							break;
						default:
							if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
								throw new \Exception( 'Unit operator: "' . $unit_operator . '" is not declared.' );
							}
							return false;
					}
				}
			}
		}

		if ( is_int( $user_pass ) && $user_pass > 0 ) {
			return true;
		}

		return false;
	}

}
