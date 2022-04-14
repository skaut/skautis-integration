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

/**
 * Rule operator for filtering users based on their SkautIS unit membership.
 */
class Membership implements Rule {

	public static $id           = 'membership';
	protected static $type      = 'string';
	protected static $input     = 'membershipInput';
	protected static $multiple  = true;
	protected static $operators = array( 'in' );

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
		return __( 'Typ členství', 'skautis-integration' );
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

	/**
	 * Returns an optional additional description of the rule.
	 */
	public function get_description(): string {
		return '';
	}

	/**
	 * Returns the current values of the rule.
	 */
	public function get_values(): array {
		$result      = array();
		$memberships = $this->skautis_gateway->get_skautis_instance()->OrganizationUnit->MembershipTypeAll();

		foreach ( $memberships as $membership ) {
			$result[ $membership->ID ] = $membership->DisplayName;
		}

		return $result;
	}

	/**
	 * Removes special characters ("." and "-") from SkautIS unit IDs.
	 *
	 * TODO: Duplicated in Role and Func.
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

	/**
	 * Returns an array of arrays where for each user unit membership ID, there are listed units asssociated with that membership.
	 *
	 * @throws \Exception The SkautIS API returned an unexpected value.
	 */
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

	/**
	 * Checks whether the rule is fulfilled.
	 *
	 * TODO: Unused first parameter?
	 *
	 * @throws \Exception An operator is undefined.
	 */
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
