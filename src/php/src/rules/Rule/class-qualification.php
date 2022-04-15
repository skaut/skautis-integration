<?php
/**
 * Contains the Qualification class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Rules\Rule;

use Skautis_Integration\Rules\Rule;
use Skautis_Integration\Auth\Skautis_Gateway;

/**
 * Rule operator for filtering users based on their SkautIS qualifications.
 */
class Qualification implements Rule {

	/**
	 * The rule ID
	 *
	 * @var string
	 */
	public static $id = 'qualification';

	/**
	 * The rule value type.
	 *
	 * @var "string"|"integer"|"double"|"date"|"time"|"datetime"|"boolean"
	 */
	protected static $type = 'string';

	/**
	 * The rule input field type type.
	 *
	 * @var "roleInput"|"membershipInput"|"funcInput"|"qualificationInput"|"text"|"number"|"textarea"|"radio"|"checkbox"|"select"
	 */
	protected static $input = 'qualificationInput';

	/**
	 * Whether the rule accepts multiple values at once.
	 *
	 * @var boolean
	 */
	protected static $multiple = true;

	/**
	 * All the operators that are applicable for the rule.
	 *
	 * @var array<"equal"|"not_equal"|"in"|"not_in"|"less"|"less_or_equal"|"greater"|"greater_or_equal"|"between"|"not_between"|"begins_with"|"not_begins_with"|"contains"|"not_contains"|"ends_with"|"not_ends_with"|"is_empty"|"is_not_empty"|"is_null"|"is_not_null">
	 */
	protected static $operators = array( 'in' );

	/**
	 * A link to the Skautis_Gateway service instance.
	 *
	 * @var Skautis_Gateway
	 */
	protected $skautis_gateway;

	/**
	 * Constructs the service and saves all dependencies.
	 *
	 * @param Skautis_Gateway $skautis_gateway An injected Skautis_Gateway service instance.
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
		return __( 'Kvalifikace', 'skautis-integration' );
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
		$result         = array();
		$qualifications = $this->skautis_gateway->get_skautis_instance()->OrganizationUnit->QualificationTypeAll();

		foreach ( $qualifications as $qualification ) {
			$result[ $qualification->ID ] = $qualification->DisplayName;
		}

		return $result;
	}

	/**
	 * Returns an array of user qualification IDs.
	 */
	protected function getUserQualifications(): array {
		static $user_qualifications = null;

		if ( is_null( $user_qualifications ) ) {
			$user_detail         = $this->skautis_gateway->get_skautis_instance()->UserManagement->UserDetail();
			$user_qualifications = $this->skautis_gateway->get_skautis_instance()->OrganizationUnit->QualificationAll(
				array(
					'ID_Person'   => $user_detail->ID_Person,
					'ShowHistory' => true,
					'isValid'     => true,
				)
			);

			$result = array();

			if ( ! is_array( $user_qualifications ) || empty( $user_qualifications ) ) {
				return array();
			}

			foreach ( $user_qualifications as $user_qualification ) {
				$result[] = $user_qualification->ID_QualificationType;
			}

			$user_qualifications = $result;
		}

		if ( ! is_array( $user_qualifications ) ) {
			return array();
		}

		return $user_qualifications;
	}

	/**
	 * Checks whether the rule is fulfilled.
	 *
	 * TODO: Unused first parameter?
	 *
	 * @param "equal"|"not_equal"|"in"|"not_in"|"less"|"less_or_equal"|"greater"|"greater_or_equal"|"between"|"not_between"|"begins_with"|"not_begins_with"|"contains"|"not_contains"|"ends_with"|"not_ends_with"|"is_empty"|"is_not_empty"|"is_null"|"is_not_null" $operator The operator used with the rule.
	 * @param string $data The rule data.
	 */
	public function is_rule_passed( string $operator, $data ): bool {
		// Parse and prepare data from rules UI.
		$output = array();
		preg_match_all( '|[^~]+|', $data, $output );
		if ( isset( $output[0], $output[0][0] ) ) {
			$qualifications = $output[0][0];
			$qualifications = explode( ',', $qualifications );
		} else {
			return false;
		}

		$user_qualifications = $this->getUserQualifications();
		$user_pass           = 0;
		foreach ( $qualifications as $qualification ) {
			if ( in_array( $qualification, $user_qualifications, true ) ) {
				++$user_pass;
			}
		}

		if ( is_int( $user_pass ) && $user_pass > 0 ) {
			return true;
		}

		return false;
	}

}
