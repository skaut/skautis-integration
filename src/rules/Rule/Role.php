<?php

namespace SkautisIntegration\Rules\Rule;

use SkautisIntegration\Rules\IRule;
use SkautisIntegration\Auth\SkautisGateway;

class Role implements IRule {

	public static $id = 'role';
	protected static $type = 'string';
	protected static $input = 'select';
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
		return __( 'Role (ve SkautISu)', 'skautis-integration' );
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

	public function getValidation() {
		return null;
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

	public function isRulePassed( $operator, $roles ) {
		static $userRoles = null;

		if ( $userRoles === null ) {
			$userRoles = $this->skautisGateway->getSkautisInstance()->UserManagement->UserRoleAll( [
				'ID_Login' => $this->skautisGateway->getSkautisInstance()->getUser()->getLoginId(),
				'ID_User'  => $this->skautisGateway->getSkautisInstance()->UserManagement->UserDetail()->ID
			] );
			$result    = [];
			foreach ( $userRoles as $userRole ) {
				$result[] = $userRole->ID_Role;
			}
			$userRoles = $result;
		}

		switch ( $operator ) {
			case 'in': {
				return ( count( array_intersect( $userRoles, $roles ) ) > 0 );
			}
			case 'not_in': {
				return ( count( array_intersect( $userRoles, $roles ) ) == 0 );
			}
			default: {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					throw new \Exception( 'Operator: "' . $operator . '" is not declared.' );
				}
				break;
			}
		}

		return false;
	}

}