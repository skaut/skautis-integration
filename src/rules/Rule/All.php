<?php

declare( strict_types=1 );

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

	public function getId(): string {
		return self::$id;
	}

	public function getLabel(): string {
		return __( 'Všichni bez omezení', 'skautis-integration' );
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
			1 => __( 'Ano' )
		];

		return $result;
	}

	public function isRulePassed( string $operator, $data ): bool {
		if ( ! empty( $data[0] ) && $data[0] == 1 && $this->skautisGateway->getSkautisInstance()->UserManagement->UserDetail()->ID > 0 ) {
			return true;
		}

		return false;
	}

}