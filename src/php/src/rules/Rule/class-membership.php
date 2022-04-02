<?php
/**
 * Contains the Membership class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Rules\Rule;

use Skautis_Integration\Rules\Rule;
use Skautis_Integration\Auth\Skautis_Gateway;

class Membership implements Rule {

	public static $id           = 'membership';
	protected static $type      = 'string';
	protected static $input     = 'membershipInput';
	protected static $multiple  = true;
	protected static $operators = array( 'in' );

	protected $skautis_gateway;

	public function __construct( Skautis_Gateway $skautis_gateway ) {
		$this->skautis_gateway = $skautis_gateway;
	}

	public function get_id(): string {
		return self::$id;
	}

	public function get_label(): string {
		return __( 'Typ členství', 'skautis-integration' );
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
		$result      = array();
		$memberships = $this->skautis_gateway->get_skautis_instance()->OrganizationUnit->MembershipTypeAll();

		foreach ( $memberships as $membership ) {
			$result[ $membership->ID ] = $membership->DisplayName;
		}

		return $result;
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

	protected function getUserMembershipsWithUnitIds(): array {
		static $user_memberships = null;

		if ( is_null( $user_memberships ) ) {
			$user_detail      = $this->skautis_gateway->get_skautis_instance()->UserManagement->UserDetail();
			$user_memberships = $this->skautis_gateway->get_skautis_instance()->OrganizationUnit->MembershipAllPerson(
				array(
					'ID_Person'   => $user_detail->ID_Person,
					'ShowHistory' => false,
					'isValid'     => true,
				)
			);

			if ( ! isset( $user_memberships->MembershipAllOutput ) ) {
				return array();
			}

			if ( is_object( $user_memberships->MembershipAllOutput ) && isset( $user_memberships->MembershipAllOutput->ID_MembershipType ) ) {
				$user_memberships->MembershipAllOutput = array(
					$user_memberships->MembershipAllOutput,
				);
			}

			if ( ! is_array( $user_memberships->MembershipAllOutput ) ) {
				return array();
			}

			// User has more valid memberships.
			$result = array();
			foreach ( $user_memberships->MembershipAllOutput as $user_membership ) {
				if ( ! is_object( $user_membership ) ) {
					continue;
				}

				if ( isset( $user_membership->ValidTo ) && gettype( $user_membership->ValidTo ) !== 'NULL' ) {
					continue;
				}

				$unit_detail = $this->skautis_gateway->get_skautis_instance()->OrganizationUnit->UnitDetail(
					array(
						'ID' => $user_membership->ID_Unit,
					)
				);
				if ( $unit_detail ) {
					if ( ! isset( $result[ $user_membership->ID_MembershipType ] ) ) {
						$result[ $user_membership->ID_MembershipType ] = array();
					}
					$result[ $user_membership->ID_MembershipType ][] = $unit_detail->RegistrationNumber;
				}
			}
			$user_memberships = $result;
		}

		if ( ! is_array( $user_memberships ) ) {
			if ( is_a( $user_memberships, '\stdClass' ) ) {
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

		return $user_memberships;
	}

	// TODO: First parameter unused?
	public function is_rule_passed( string $roles_operator, $data ): bool {
		// Parse and prepare data from rules UI.
		$output = array();
		preg_match_all( '|[^~]+|', $data, $output );
		if ( isset( $output[0], $output[0][0], $output[0][1], $output[0][2] ) ) {
			list( $memberships, $membership_operator, $unit_id ) = $output[0];
			$memberships = explode( ',', $memberships );
			$unit_id     = $this->clearUnitId( $unit_id );
		} else {
			return false;
		}

		$user_memberships = $this->getUserMembershipsWithUnitIds();
		$user_pass        = 0;
		foreach ( $memberships as $membership ) {
			// in / not_in range check.
			if ( array_key_exists( $membership, $user_memberships ) ) {
				foreach ( $user_memberships[ $membership ] as $user_membership_unit_id ) {
					$user_membership_unit_id = $this->clearUnitId( $user_membership_unit_id );

					switch ( $membership_operator ) {
						case 'equal':
							$user_pass += ( $user_membership_unit_id === $unit_id );
							break;
						case 'begins_with':
							$user_pass += ( substr( $user_membership_unit_id, 0, strlen( $unit_id ) ) === $unit_id );
							break;
						case 'any':
							++$user_pass;
							break;
						default:
							if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
								throw new \Exception( 'Unit operator: "' . $membership_operator . '" is not declared.' );
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
