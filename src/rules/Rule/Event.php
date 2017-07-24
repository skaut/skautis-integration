<?php

declare( strict_types=1 );

namespace SkautisIntegration\Rules\Rule;

use SkautisIntegration\Rules\IRule;
use SkautisIntegration\Auth\SkautisGateway;

class Event implements IRule {

	public static $id = 'event';
	protected static $type = 'string';
	protected static $input = 'eventInput';
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
		return __( 'Účastník akce', 'skautis-integration' );
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
		$result = [
			'participantTypes' => [],
			'events'           => []
		];

		$userDetail       = $this->skautisGateway->getSkautisInstance()->UserManagement->UserDetail();
		$participantTypes = $this->skautisGateway->getSkautisInstance()->Events->ParticipantTypeAll();

		foreach ( $participantTypes as $participantType ) {
			$result['participantTypes'][ $participantType->ID ] = $participantType->DisplayName;
		}

		$currentUserEvents = $this->skautisGateway->getSkautisInstance()->Events->EventAllPerson( [
			'ID_Person' => $userDetail->ID_Person
		] );

		foreach ( $currentUserEvents as $event ) {
			$result['events'][ $event->ID ] = $event->DisplayName;
		}

		return $result;
	}

	public function isRulePassed( string $rolesOperator, $data ): bool {
		// parse and prepare data from rules UI
		$output = [];
		preg_match_all( "/[^~]+/", $data, $output );
		if ( isset( $output[0], $output[0][0], $output[0][1], $output[0][2] ) ) {
			list( $participantTypes, $membershipOperator, $unitId ) = $output[0];
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