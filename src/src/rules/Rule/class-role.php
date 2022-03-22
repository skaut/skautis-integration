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

	protected $skautisGateway;

	public function __construct( Skautis_Gateway $skautisGateway ) {
		$this->skautisGateway = $skautisGateway;
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

	public function getValues(): array {
		$values = array();
		$roles  = $this->skautisGateway->getSkautisInstance()->UserManagement->RoleAll();

		foreach ( $roles as $role ) {
			$values[ $role->ID ] = $role->DisplayName;
		}

		return $values;
	}

	protected function clearUnitId( string $unitId ): string {
		return trim(
			str_replace(
				array(
					'.',
					'-',
				),
				'',
				$unitId
			)
		);
	}

	protected function getUserRolesWithUnitIds(): array {
		static $userRoles = null;

		if ( is_null( $userRoles ) ) {
			$userRoles = $this->skautisGateway->getSkautisInstance()->UserManagement->UserRoleAll(
				array(
					'ID_Login' => $this->skautisGateway->getSkautisInstance()->getUser()->getLoginId(),
					'ID_User'  => $this->skautisGateway->getSkautisInstance()->UserManagement->UserDetail()->ID,
					'IsActive' => true,
				)
			);

			$result = array();
			foreach ( $userRoles as $userRole ) {
				try {
					$unitDetail = $this->skautisGateway->getSkautisInstance()->OrganizationUnit->UnitDetail(
						array(
							'ID' => $userRole->ID_Unit,
						)
					);

					if ( $unitDetail ) {
						if ( ! isset( $result[ $userRole->ID_Role ] ) ) {
							$result[ $userRole->ID_Role ] = array();
						}
						$result[ $userRole->ID_Role ][] = $unitDetail->RegistrationNumber;
					}
				} catch ( \Exception $e ) {
					continue;
				}
			}

			$userRoles = $result;
		}

		if ( ! is_array( $userRoles ) ) {
			return array();
		}

		return $userRoles;
	}

	public function isRulePassed( string $rolesOperator, $data ): bool {
		// parse and prepare data from rules UI
		$output = array();
		preg_match_all( '|[^~]+|', $data, $output );
		if ( isset( $output[0], $output[0][0], $output[0][1], $output[0][2] ) ) {
			list( $roles, $unitOperator, $unitId ) = $output[0];
			$roles                                 = explode( ',', $roles );
			$unitId                                = $this->clearUnitId( $unitId );
		} else {
			return false;
		}

		// logic for determine in / not_in range
		$inNotinNegation = 2;
		switch ( $rolesOperator ) {
			case 'in':
				$inNotinNegation = 0;
				break;
			case 'not_in':
				$inNotinNegation = 1;
				break;
			default:
				$inNotinNegation = 2;
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					throw new \Exception( 'Roles operator: "' . $rolesOperator . '" is not declared.' );
				}
				break;
		}

		$userRoles = $this->getUserRolesWithUnitIds();
		$userPass  = 0;
		foreach ( $roles as $role ) {
			// in / not_in range check
			if ( ( $inNotinNegation + array_key_exists( $role, $userRoles ) ) === 1 ) {
				foreach ( $userRoles[ $role ] as $userRoleUnitId ) {
					$userRoleUnitId = $this->clearUnitId( $userRoleUnitId );

					switch ( $unitOperator ) {
						case 'equal':
							$userPass += ( $userRoleUnitId === $unitId );
							break;
						case 'begins_with':
							$userPass += ( substr( $userRoleUnitId, 0, strlen( $unitId ) ) === $unitId );
							break;
						case 'any':
							++$userPass;
							break;
						default:
							if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
								throw new \Exception( 'Unit operator: "' . $unitOperator . '" is not declared.' );
							}
							return false;
					}
				}
			}
		}

		if ( is_int( $userPass ) && $userPass > 0 ) {
			return true;
		}

		return false;
	}

}
