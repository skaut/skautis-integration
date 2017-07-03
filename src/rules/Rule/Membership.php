<?php

declare( strict_types=1 );

namespace SkautisIntegration\Rules\Rule;

use SkautisIntegration\Rules\IRule;
use SkautisIntegration\Auth\SkautisGateway;

class Membership implements IRule {

	public static $id = 'membership';
	protected static $type = 'string';
	protected static $input = 'membershipInput';
	protected static $multiple = true;
	protected static $operators = [ 'in' ];

	protected $skautisGateway;

	public function __construct( SkautisGateway $skautisGateway ) {
		$this->skautisGateway = $skautisGateway;
	}

	public function getId(): string {
		return self::$id;
	}

	public function getLabel(): string {
		return __( 'Typ členství', 'skautis-integration' );
	}

	public function getType(): string {
		return self::$type;
	}

	public function getInput(): string {
		return self::$input;
	}

	public function getMultiple(): bool {
		return self::$multiple;
	}

	public function getOperators(): array {
		return self::$operators;
	}

	public function getPlaceholder(): string {
		return '';
	}

	public function getDescription(): string {
		return '';
	}

	public function getValues(): array {
		$result      = [];
		$memberships = $this->skautisGateway->getSkautisInstance()->OrganizationUnit->MembershipTypeAll();

		foreach ( $memberships as $membership ) {
			$result[ $membership->ID ] = $membership->DisplayName;
		}

		return $result;
	}

	protected function clearUnitId( string $unitId ): string {
		return trim( str_replace( [
			'.',
			'-'
		], '', $unitId ) );
	}

	protected function getUserMembershipsWithUnitIds(): array {
		static $userMemberships = null;

		if ( $userMemberships === null ) {

			$userDetail      = $this->skautisGateway->getSkautisInstance()->UserManagement->UserDetail();
			$userMemberships = $this->skautisGateway->getSkautisInstance()->OrganizationUnit->MembershipAllPerson( [
				'ID_Person' => $userDetail->ID_Person,
				'isValid'   => true
			] );

			if ( ! isset( $userMemberships->MembershipAllOutput ) ) {
				return [];
			}

			if ( is_object( $userMemberships->MembershipAllOutput ) && isset( $userMemberships->MembershipAllOutput->ID_MembershipType ) ) {
				$dataObject                           = new \stdClass();
				$dataObject->ID_MembershipType        = $userMemberships->MembershipAllOutput->ID_MembershipType;
				$dataObject->ID_Unit                  = $userMemberships->MembershipAllOutput->ID_Unit;
				$userMemberships->MembershipAllOutput = [
					$dataObject
				];
			}

			if ( ! is_array( $userMemberships->MembershipAllOutput ) ) {
				return [];
			}

			// user has more valid memberships
			$result = [];
			foreach ( $userMemberships->MembershipAllOutput as $userMembership ) {
				if ( ! is_object( $userMembership ) ) {
					return [];
				}

				if ( $unitDetail = $this->skautisGateway->getSkautisInstance()->OrganizationUnit->UnitDetail( [
					'ID' => $userMembership->ID_Unit
				] ) ) {
					if ( ! isset( $result[ $userMembership->ID_MembershipType ] ) ) {
						$result[ $userMembership->ID_MembershipType ] = [];
					}
					$result[ $userMembership->ID_MembershipType ][] = $unitDetail->RegistrationNumber;
				}
			}
			$userMemberships = $result;
		}

		return $userMemberships;
	}

	public function isRulePassed( string $rolesOperator, $data ): bool {
		// parse and prepare data from rules UI
		$output = [];
		preg_match_all( "/[^~]+/", $data, $output );
		if ( isset( $output[0], $output[0][0], $output[0][1], $output[0][2] ) ) {
			list( $memberships, $membershipOperator, $unitId ) = $output[0];
			$memberships = explode( ',', $memberships );
			$unitId      = $this->clearUnitId( $unitId );
		} else {
			return false;
		}

		$userMemberships = $this->getUserMembershipsWithUnitIds();
		$userPass        = 0;
		foreach ( $memberships as $membership ) {
			// in / not_in range check
			if ( array_key_exists( $membership, $userMemberships ) ) {

				foreach ( $userMemberships[ $membership ] as $userMembershipUnitId ) {
					$userMembershipUnitId = $this->clearUnitId( $userMembershipUnitId );

					switch ( $membershipOperator ) {
						case 'equal': {
							$userPass += ( $userMembershipUnitId === $unitId );
							break;
						}
						case 'begins_with': {
							$userPass += ( substr( $userMembershipUnitId, 0, strlen( $unitId ) ) === $unitId );
							break;
						}
						default: {
							if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
								throw new \Exception( 'Unit operator: "' . $membershipOperator . '" is not declared.' );
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