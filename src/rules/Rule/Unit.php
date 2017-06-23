<?php

namespace SkautisIntegration\Rules\Rule;

use SkautisIntegration\Rules\IRule;
use SkautisIntegration\Auth\SkautisGateway;

class Unit implements IRule {

	public static $id = 'unit';
	protected static $type = 'string';
	protected static $input = 'text';
	protected static $multiple = false;
	protected static $operators = [
		'equal',
		'not_equal',
		'begins_with',
		'not_begins_with',
		'contains',
		'not_contains',
		'ends_with',
		'not_ends_with'
	];
	protected static $validation = [
		'format' => '^([0-9.-])+$'
	];
	protected static $placeholder = '';

	protected $skautisGateway;

	public function __construct( SkautisGateway $skautisGateway ) {
		$this->skautisGateway = $skautisGateway;
	}

	public function getId() {
		return self::$id;
	}

	public function getLabel() {
		return __( 'Jednotka (evidenční číslo)', 'skautis-integration' );
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

	public function getValues() {
		return [];
	}

	public function getOperators() {
		return self::$operators;
	}

	public function getValidation() {
		return self::$validation;
	}

	public function getPlaceholder() {
		return __( 'číslo jednotky (např. 411.12)', 'skautis-integration' );
	}

	public function getDescription() {
		return null;
	}

	public function isRulePassed( $operator, $unitRegistrationNumber ) {
		static $unit = null;
		static $externUnitRegistrationNumber = null;

		$unitRegistrationNumber = str_replace( [
			'.',
			'-'
		], '', $unitRegistrationNumber );

		if ( $unit === null ) {
			$unit                         = $this->skautisGateway->getSkautisInstance()->OrganizationUnit->UnitDetail();
			$externUnitRegistrationNumber = str_replace( [
				'.',
				'-'
			], '', $unit->RegistrationNumber );
		}

		switch ( $operator ) {
			case 'equal': {
				return ( $externUnitRegistrationNumber == $unitRegistrationNumber );
			}
			case 'not_equal': {
				return ( $externUnitRegistrationNumber != $unitRegistrationNumber );
			}
			case 'begins_with': {
				return ( substr( $externUnitRegistrationNumber, 0, strlen( $unitRegistrationNumber ) ) === $unitRegistrationNumber );
			}
			case 'not_begins_with': {
				return ( substr( $externUnitRegistrationNumber, 0, strlen( $unitRegistrationNumber ) ) !== $unitRegistrationNumber );
			}
			case 'contains': {
				return ( strpos( $externUnitRegistrationNumber, $unitRegistrationNumber ) !== false );
			}
			case 'not_contains': {
				return ( strpos( $externUnitRegistrationNumber, $unitRegistrationNumber ) === false );
			}
			case 'ends_with': {
				return ( substr( $externUnitRegistrationNumber, - strlen( $unitRegistrationNumber ) ) === $unitRegistrationNumber );
			}
			case 'not_ends_with': {
				return ( substr( $externUnitRegistrationNumber, - strlen( $unitRegistrationNumber ) ) !== $unitRegistrationNumber );
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