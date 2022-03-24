<?php

declare( strict_types=1 );

namespace SkautisIntegration\Rules\Rule;

use SkautisIntegration\Rules\Rule;
use SkautisIntegration\Auth\Skautis_Gateway;

class Role implements Rule {

	public static $id           = 'role';
	protected static $type      = 'string';
	protected static $input     = 'roleInput';
	protected static $multiple  = true;
	protected static $operators = array( 'in', 'not_in' );

	protected $skautis_gateway;

	public function __construct( Skautis_Gateway $skautis_gateway ) {
		$this->skautis_gateway = $skautis_gateway;
	}

	public function get_id(): string {
		return self::$id;
	}

	public function get_label(): string {
		return __( 'Role', 'skautis-integration' );
	}

	public function get_type(): string {
		return self::$type;
	}

	public function get_input(): string {
		return self::$input;
	}

	public function get_multiple(): bool {
		return self::$multiple;
	}

	public function get_operators(): array {
		return self::$operators;
	}

	public function get_placeholder(): string {
		return '';
	}

	public function get_description(): string {
		return '';
	}

	public function get_values(): array {
		$values = array();
		$roles  = $this->skautis_gateway->get_skautis_instance()->UserManagement->RoleAll();

		foreach ( $roles as $role ) {
			$values[ $role->ID ] = $role->DisplayName;
		}

		return $values;
	}

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
		// parse and prepare data from rules UI
		$output = array();
		preg_match_all( '|[^~]+|', $data, $output );
		if ( isset( $output[0], $output[0][0], $output[0][1], $output[0][2] ) ) {
			list( $roles, $unit_operator, $unit_id ) = $output[0];
			$roles                                   = explode( ',', $roles );
			$unit_id                                 = $this->clearUnitId( $unit_id );
		} else {
			return false;
		}

		// logic for determine in / not_in range
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
			// in / not_in range check
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
