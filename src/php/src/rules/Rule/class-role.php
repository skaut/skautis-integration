<?php
/**
 * Contains the Role class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Rules\Rule;

use Skautis_Integration\Rules\Rule;
use Skautis_Integration\Auth\Skautis_Gateway;

/**
 * Rule operator for filtering users based on their SkautIS role.
 */
class Role implements Rule {

	/**
	 * The rule ID
	 *
	 * @var string
	 */
	private static $rule_id = 'role';

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
	protected static $input = 'roleInput';

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
		return __( 'Role', 'skautis-integration' );
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
		return '';
	}

	/**
	 * Returns the current values of the rule.
	 *
	 * @return array<string, string> The current values.
	 */
	public function get_values(): array {
		$values = array();
		$roles  = $this->skautis_gateway->get_skautis_instance()->UserManagement->RoleAll();

		foreach ( $roles as $role ) {
			$values[ $role->ID ] = $role->DisplayName;
		}

		return $values;
	}

	/**
	 * Removes special characters ("." and "-") from SkautIS unit IDs.
	 *
	 * TODO: Duplicated in Membership and Func.
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
	 * Returns an array of arrays where for each user role ID, there are listed units asssociated with that role.
	 *
	 * @return array<int, array<string>> The unit list.
	 */
	protected function getUserRolesWithUnitIds(): array {
		static $user_roles = null;

		if ( ! is_null( $user_roles ) ) {
			return $user_roles;
		}

		$user_roles = $this->skautis_gateway->get_skautis_instance()->UserManagement->UserRoleAll(
			array(
				'ID_Login' => $this->skautis_gateway->get_skautis_instance()->getUser()->getLoginId(),
				'ID_User'  => $this->skautis_gateway->get_skautis_instance()->UserManagement->UserDetail()->ID,
				'IsActive' => true,
			)
		);

		$result = array();
		foreach ( $user_roles as $user_role ) {
			try {
				$unit_detail = $this->skautis_gateway->get_skautis_instance()->OrganizationUnit->UnitDetail(
					array(
						'ID' => $user_role->ID_Unit,
					)
				);

				if ( $unit_detail ) {
					if ( ! isset( $result[ $user_role->ID_Role ] ) ) {
						$result[ $user_role->ID_Role ] = array();
					}
					$result[ $user_role->ID_Role ][] = $unit_detail->RegistrationNumber;
				}
			} catch ( \Exception $_ ) {
				continue;
			}
		}

		$user_roles = $result;
		return $user_roles;
	}

	/**
	 * Checks whether the rule is fulfilled.
	 *
	 * @throws \Exception An operator is undefined.
	 *
	 * @param string $roles_operator The operator used with the rule.
	 * @param string $data The rule data.
	 */
	public function is_rule_passed( string $roles_operator, $data ): bool {
		// Parse and prepare data from rules UI.
		$output = array();
		preg_match_all( '|[^~]+|', $data, $output );
		if ( isset( $output[0], $output[0][0], $output[0][1], $output[0][2] ) ) {
			list( $roles, $unit_operator, $unit_id ) = $output[0];
			$roles                                   = explode( ',', $roles );
			$unit_id                                 = self::clearUnitId( $unit_id );
		} else {
			return false;
		}

		// Logic to determine in / not_in range.
		switch ( $roles_operator ) {
			case 'in':
				$assume_in = true;
				break;
			case 'not_in':
				$assume_in = false;
				break;
			default:
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					throw new \Exception( 'Roles operator: "' . $roles_operator . '" is not declared.' );
				}
				return false;
		}

		$user_roles = $this->getUserRolesWithUnitIds();
		$user_pass  = 0;
		foreach ( $roles as $role ) {
			// in / not_in range check.
			if ( array_key_exists( $role, $user_roles ) === $assume_in ) {
				foreach ( $user_roles[ $role ] as $user_role_unit_id ) {
					$user_role_unit_id = self::clearUnitId( $user_role_unit_id );

					switch ( $unit_operator ) {
						case 'equal':
							$user_pass += ( $user_role_unit_id === $unit_id );
							break;
						case 'begins_with':
							$user_pass += ( substr( $user_role_unit_id, 0, strlen( $unit_id ) ) === $unit_id );
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
