<?php

declare( strict_types=1 );

namespace SkautisIntegration\Rules\Rule;

use SkautisIntegration\Rules\IRule;
use SkautisIntegration\Auth\SkautisGateway;

class Membership implements IRule {

	public static $id           = 'membership';
	protected static $type      = 'string';
	protected static $input     = 'membershipInput';
	protected static $multiple  = true;
	protected static $operators = array( 'in' );

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
		$result      = array();
		$memberships = $this->skautisGateway->getSkautisInstance()->OrganizationUnit->MembershipTypeAll();

		foreach ( $memberships as $membership ) {
			$result[ $membership->ID ] = $membership->DisplayName;
		}

		return $result;
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

	protected function getUserMembershipsWithUnitIds(): array {
		static $userMemberships = null;

		if ( is_null( $userMemberships ) ) {
			$userDetail      = $this->skautisGateway->getSkautisInstance()->UserManagement->UserDetail();
			$userMemberships = $this->skautisGateway->getSkautisInstance()->OrganizationUnit->MembershipAllPerson(
				array(
					'ID_Person'   => $userDetail->ID_Person,
					'ShowHistory' => false,
					'isValid'     => true,
				)
			);

			if ( ! isset( $userMemberships->MembershipAllOutput ) ) {
				return array();
			}

			if ( is_object( $userMemberships->MembershipAllOutput ) && isset( $userMemberships->MembershipAllOutput->ID_MembershipType ) ) {
				$userMemberships->MembershipAllOutput = array(
					$userMemberships->MembershipAllOutput,
				);
			}

			if ( ! is_array( $userMemberships->MembershipAllOutput ) ) {
				return array();
			}

			// user has more valid memberships
			$result = array();
			foreach ( $userMemberships->MembershipAllOutput as $userMembership ) {
				if ( ! is_object( $userMembership ) ) {
					continue;
				}

				if ( isset( $userMembership->ValidTo ) && gettype( $userMembership->ValidTo ) !== 'NULL' ) {
					continue;
				}

				$unitDetail = $this->skautisGateway->getSkautisInstance()->OrganizationUnit->UnitDetail(
					array(
						'ID' => $userMembership->ID_Unit,
					)
				);
				if ( $unitDetail ) {
					if ( ! isset( $result[ $userMembership->ID_MembershipType ] ) ) {
						$result[ $userMembership->ID_MembershipType ] = array();
					}
					$result[ $userMembership->ID_MembershipType ][] = $unitDetail->RegistrationNumber;
				}
			}
			$userMemberships = $result;
		}

		if ( ! is_array( $userMemberships ) ) {
			if ( is_a( $userMemberships, '\stdClass' ) ) {
				wp_die(
					sprintf(
						/* translators: 1: Start of a link to the documentation 2: End of the link to the documentation */
						esc_html__(
							'Pravděpodobně nemáte propojený skautIS účet se svojí osobou. %1$sPostupujte podle tohoto návodu%2$s',
							'skautis-integration'
						),
						'<a href="https://napoveda.skaut.cz/skautis/informacni-system/uzivatel/propojeni-uctu">',
						'</a>'
					)
				);
			} else {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					throw new \Exception( __( 'Nastala neočekávaná chyba.', 'skautis-integration' ) );
				}
			}
		}

		return $userMemberships;
	}

	public function isRulePassed( string $rolesOperator, $data ): bool {
		// parse and prepare data from rules UI
		$output = array();
		preg_match_all( '|[^~]+|', $data, $output );
		if ( isset( $output[0], $output[0][0], $output[0][1], $output[0][2] ) ) {
			list( $memberships, $membershipOperator, $unitId ) = $output[0];
			$memberships                                       = explode( ',', $memberships );
			$unitId = $this->clearUnitId( $unitId );
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
						case 'equal':
							{
								$userPass += ( $userMembershipUnitId === $unitId );
								break;
						}
						case 'begins_with':
							{
								$userPass += ( substr( $userMembershipUnitId, 0, strlen( $unitId ) ) === $unitId );
								break;
						}
						case 'any':
							{
								++$userPass;
								break;
						}
						default:
							{
							if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
								throw new \Exception( 'Unit operator: "' . $membershipOperator . '" is not declared.' );
							}
							return false;
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
