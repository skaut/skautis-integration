<?php

declare( strict_types=1 );

namespace SkautisIntegration\Rules\Rule;

use SkautisIntegration\Rules\Rule;
use SkautisIntegration\Auth\Skautis_Gateway;

class All implements Rule {

	public static $id           = 'all';
	protected static $type      = 'integer';
	protected static $input     = 'checkbox';
	protected static $multiple  = false;
	protected static $operators = array( 'equal' );

	protected $skautisGateway;

	public function __construct( Skautis_Gateway $skautisGateway ) {
		$this->skautisGateway = $skautisGateway;
	}

	public function get_id(): string {
		return self::$id;
	}

	public function get_label(): string {
		return __( 'Všichni bez omezení', 'skautis-integration' );
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
		return __( 'Při použití tohoto pravidla se budou moci všichni uživatelé s účtem ve skautISu, propojeným se svojí osobou, registrovat. Nemá tedy smysl tuto podmínku kombinovat s dalšími podmínkami (role, typ členství, ...). Doporučujeme použít tuto podmínku jako jedinou v celém pravidle a žádné další zde nemít.', 'skautis-integration' );
	}

	public function getValues(): array {
		$result = array(
			1 => __( 'Ano', 'skautis-integration' ),
		);

		return $result;
	}

	public function isRulePassed( string $operator, $data ): bool {
		if ( ! empty( $data[0] ) && 1 === $data[0] && $this->skautisGateway->getSkautisInstance()->UserManagement->UserDetail()->ID > 0 ) {
			return true;
		}

		return false;
	}

}
