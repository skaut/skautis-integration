<?php

namespace SkautisIntegration\Rules\Rule;

use SkautisIntegration\Rules\IRule;
use SkautisIntegration\Auth\SkautisGateway;

class All implements IRule {

	public static $id = 'all';
	protected static $type = 'integer';
	protected static $input = 'checkbox';
	protected static $multiple = false;
	protected static $operators = [ 'equal' ];

	protected $skautisGateway;

	public function __construct( SkautisGateway $skautisGateway ) {
		$this->skautisGateway = $skautisGateway;
	}

	public function getId() {
		return self::$id;
	}

	public function getLabel() {
		return __( 'Všichni bez omezení', 'skautis-integration' );
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
		$result = [
			1 => __( 'Ano' )
		];

		return $result;
	}

	public function isRulePassed( $operator, $values ) {
		if ( ! empty( $values[0] ) && $values[0] == 1 && $this->skautisGateway->getSkautisInstance()->UserManagement->UserDetail()->ID > 0 ) {
			return true;
		}

		return false;
	}

}