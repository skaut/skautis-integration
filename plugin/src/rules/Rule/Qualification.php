<?php

declare( strict_types=1 );

namespace SkautisIntegration\Rules\Rule;

use SkautisIntegration\Rules\IRule;
use SkautisIntegration\Auth\SkautisGateway;

class Qualification implements IRule {

	public static $id = 'qualification';
	protected static $type = 'string';
	protected static $input = 'qualificationInput';
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
		return __( 'Kvalifikace', 'skautis-integration' );
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
		$result         = [];
		$qualifications = $this->skautisGateway->getSkautisInstance()->OrganizationUnit->QualificationTypeAll();

		foreach ( $qualifications as $qualification ) {
			$result[ $qualification->ID ] = $qualification->DisplayName;
		}

		return $result;
	}

	protected function getUserQualifications(): array {
		static $userQualifications = null;

		if ( $userQualifications === null ) {
			$userDetail         = $this->skautisGateway->getSkautisInstance()->UserManagement->UserDetail();
			$userQualifications = $this->skautisGateway->getSkautisInstance()->OrganizationUnit->QualificationAll( [
				'ID_Person'   => $userDetail->ID_Person,
				'ShowHistory' => true,
				'isValid'     => true
			] );

			$result = [];

			if ( ! is_array( $userQualifications ) || empty( $userQualifications ) ) {
				return [];
			}

			foreach ( $userQualifications as $userQualification ) {
				$result[] = $userQualification->ID_QualificationType;
			}

			$userQualifications = $result;

		}

		if ( ! is_array( $userQualifications ) ) {
			return [];
		}

		return $userQualifications;
	}

	public function isRulePassed( string $rolesOperator, $data ): bool {
		// parse and prepare data from rules UI
		$output = [];
		preg_match_all( "|[^~]+|", $data, $output );
		if ( isset( $output[0], $output[0][0] ) ) {
			$qualifications = $output[0][0];
			$qualifications = explode( ',', $qualifications );
		} else {
			return false;
		}

		$userQualifications = $this->getUserQualifications();
		$userPass           = 0;
		foreach ( $qualifications as $qualification ) {
			if ( in_array( $qualification, $userQualifications ) ) {
				$userPass += 1;
			}
		}

		if ( is_int( $userPass ) && $userPass > 0 ) {
			return true;
		}

		return false;
	}

}