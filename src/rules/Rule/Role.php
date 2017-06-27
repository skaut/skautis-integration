<?php

namespace SkautisIntegration\Rules\Rule;

use SkautisIntegration\Rules\IRule;
use SkautisIntegration\Auth\SkautisGateway;

class Role implements IRule {

	public static $id = 'role';
	protected static $type = 'string';
	protected static $input = 'roleInput';
	protected static $multiple = true;
	protected static $operators = [ 'in', 'not_in' ];

	protected $skautisGateway;

	public function __construct( SkautisGateway $skautisGateway ) {
		$this->skautisGateway = $skautisGateway;
	}

	public function getId() {
		return self::$id;
	}

	public function getLabel() {
		return __( 'Role', 'skautis-integration' );
	}

	public function getType() {
		return self::$type;
	}

	public function getInput() {
		return self::$input;
	}

	public function getMultiple() {
		return self::$multiple;
	}

	public function getOperators() {
		return self::$operators;
	}

	public function getPlaceholder() {
		return null;
	}

	public function getDescription() {
		return null;
	}

	public function getValues() {
		$result = [];
		$roles  = $this->skautisGateway->getSkautisInstance()->UserManagement->RoleAll();

		foreach ( $roles as $role ) {
			$result[ $role->ID ] = $role->DisplayName;
		}

		return $result;
	}

	protected function clearUnitId( $unitId ) {
		return trim( str_replace( [
			'.',
			'-'
		], '', $unitId ) );
	}

	protected function getUserRolesWithUnitIds() {
		static $userRoles = null;

		if ( $userRoles === null ) {
			$userRoles = $this->skautisGateway->getSkautisInstance()->UserManagement->UserRoleAll( [
				'ID_Login' => $this->skautisGateway->getSkautisInstance()->getUser()->getLoginId(),
				'ID_User'  => $this->skautisGateway->getSkautisInstance()->UserManagement->UserDetail()->ID
			] );

			$result = [];
			foreach ( $userRoles as $userRole ) {

				if ( $unitDetail = $this->skautisGateway->getSkautisInstance()->OrganizationUnit->UnitDetail( [
					'ID' => $userRole->ID_Unit
				] ) ) {
					if ( ! isset( $result[ $userRole->ID_Role ] ) ) {
						$result[ $userRole->ID_Role ] = [];
					}
					$result[ $userRole->ID_Role ][] = $unitDetail->RegistrationNumber;
				}

			}

			$userRoles = $result;

		}

		return $userRoles;
	}

	public function isRulePassed( $rolesOperator, $data ) {
		// parse and prepare data from rules UI
		$output = [];
		preg_match_all( "/[^~]+/", $data, $output );
		if ( isset( $output[0], $output[0][0], $output[0][1], $output[0][2] ) ) {
			list( $roles, $unitOperator, $unitId ) = $output[0];
			$roles  = explode( ',', $roles );
			$unitId = $this->clearUnitId( $unitId );
		} else {
			return false;
		}

		// logic for determine in / not_in range
		$inNotinNegation = 2;
		switch ( $rolesOperator ) {
			case 'in': {
				$inNotinNegation = 0;
				break;
			}
			case 'not_in': {
				$inNotinNegation = 1;
				break;
			}
			default: {
				$inNotinNegation = 2;
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					throw new \Exception( 'Roles operator: "' . $rolesOperator . '" is not declared.' );
				}
				break;
			}
		}

		$userRoles = $this->getUserRolesWithUnitIds();
		$userPass  = 0;
		foreach ( $roles as $role ) {
			// in / not_in range check
			if ( ( $inNotinNegation + array_key_exists( $role, $userRoles ) ) === 1 ) {

				foreach ( $userRoles[ $role ] as $userRoleUnitId ) {
					$userRoleUnitId = $this->clearUnitId( $userRoleUnitId );

					switch ( $unitOperator ) {
						case 'equal': {
							$userPass += ( $userRoleUnitId === $unitId );
							break;
						}
						case 'begins_with': {
							$userPass += ( substr( $userRoleUnitId, 0, strlen( $unitId ) ) === $unitId );
							break;
						}
						default: {
							if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
								throw new \Exception( 'Unit operator: "' . $unitOperator . '" is not declared.' );
							}
							break;
						}
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