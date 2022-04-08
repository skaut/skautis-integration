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

class Role implements Rule {

	public static $id           = 'role';
	protected static $type      = 'string';
	protected static $input     = 'roleInput';
	protected static $multiple  = true;
	protected static $operators = array( 'in', 'not_in' );

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
		return __( 'Role', 'skautis-integration' );
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

	public function get_description(): string {
		return '';
	}

	/**
	 * Returns the current values of the rule.
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
	 */
	protected function clearUnitId( string $unit_id ): string {
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

	protected function getUserRolesWithUnitIds(): array {
		static $user_roles = null;

		if ( is_null( $user_roles ) ) {
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
				} catch ( \Exception $e ) {
					continue;
				}
			}

			$user_roles = $result;
		}

		if ( ! is_array( $user_roles ) ) {
			return array();
		}

		return $user_roles;
	}

	public function is_rule_passed( string $roles_operator, $data ): bool {
		// Parse and prepare data from rules UI.
		$output = array();
		preg_match_all( '|[^~]+|', $data, $output );
		if ( isset( $output[0], $output[0][0], $output[0][1], $output[0][2] ) ) {
			list( $roles, $unit_operator, $unit_id ) = $output[0];
			$roles                                   = explode( ',', $roles );
			$unit_id                                 = $this->clearUnitId( $unit_id );
		} else {
			return false;
		}

		// Logic to determine in / not_in range.
		$in_not_in_negation = 2;
		switch ( $roles_operator ) {
			case 'in':
				$in_not_in_negation = 0;
				break;
			case 'not_in':
				$in_not_in_negation = 1;
				break;
			default:
				$in_not_in_negation = 2;
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					throw new \Exception( 'Roles operator: "' . $roles_operator . '" is not declared.' );
				}
				break;
		}

		$user_roles = $this->getUserRolesWithUnitIds();
		$user_pass  = 0;
		foreach ( $roles as $role ) {
			// in / not_in range check.
			if ( ( $in_not_in_negation + array_key_exists( $role, $user_roles ) ) === 1 ) {
				foreach ( $user_roles[ $role ] as $user_role_unit_id ) {
					$user_role_unit_id = $this->clearUnitId( $user_role_unit_id );

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
