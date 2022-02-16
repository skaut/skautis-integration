<?php

declare( strict_types=1 );

namespace SkautisIntegration\Rules\Rule;

use SkautisIntegration\Rules\IRule;
use SkautisIntegration\Auth\SkautisGateway;

class Func implements IRule {

	public static $id           = 'func';
	protected static $type      = 'string';
	protected static $input     = 'funcInput';
	protected static $multiple  = true;
	protected static $operators = array( 'in', 'not_in' );

	protected $skautisGateway;

	public function __construct( SkautisGateway $skautisGateway ) {
		$this->skautisGateway = $skautisGateway;
	}

	public function getId(): string {
		return self::$id;
	}

	public function getLabel(): string {
		return __( 'Funkce', 'skautis-integration' );
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
		$values = array();
		$funcs  = $this->skautisGateway->getSkautisInstance()->OrganizationUnit->FunctionTypeAll();

		foreach ( $funcs as $func ) {
			$values[ $func->ID ] = $func->ShortName;
		}

		return $values;
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

	protected function getUserFuncsWithUnitIds(): array {
		static $userFuncs = null;

		if ( is_null( $userFuncs ) ) {
			$userDetail = $this->skautisGateway->getSkautisInstance()->UserManagement->UserDetail();
			$userFuncs  = $this->skautisGateway->getSkautisInstance()->OrganizationUnit->FunctionAllPerson(
				array(
					'ID_Person' => $userDetail->ID_Person, /*
				,
				'isValid'   => true*/
				)
			);

			$result = array();

			if ( ! $userFuncs || ! property_exists( $userFuncs, 'FunctionAllOutput' ) || empty( $userFuncs->FunctionAllOutput ) || ! is_array( $userFuncs->FunctionAllOutput ) || empty( $userFuncs->FunctionAllOutput[0] ) ) {
				return $result;
			}

			foreach ( $userFuncs->FunctionAllOutput as $userFunc ) {
				if ( $unitDetail = $this->skautisGateway->getSkautisInstance()->OrganizationUnit->UnitDetail(
					array(
						'ID' => $userFunc->ID_Unit,
					)
				) ) {
					if ( ! isset( $result[ $userFunc->ID_FunctionType ] ) ) {
						$result[ $userFunc->ID_FunctionType ] = array();
					}
					$result[ $userFunc->ID_FunctionType ][] = $unitDetail->RegistrationNumber;
				}
			}

			$userFuncs = $result;
		}

		if ( ! is_array( $userFuncs ) ) {
			return array();
		}

		return $userFuncs;
	}

	public function isRulePassed( string $funcsOperator, $data ): bool {
		// parse and prepare data from rules UI
		$output = array();
		preg_match_all( '|[^~]+|', $data, $output );
		if ( isset( $output[0], $output[0][0], $output[0][1], $output[0][2] ) ) {
			list( $funcs, $unitOperator, $unitId ) = $output[0];
			$funcs                                 = explode( ',', $funcs );
			$unitId                                = $this->clearUnitId( $unitId );
		} else {
			return false;
		}

		// logic for determine in / not_in range
		$inNotinNegation = 2;
		switch ( $funcsOperator ) {
			case 'in':
				{
					$inNotinNegation = 0;
					break;
			}
			case 'not_in':
				{
					$inNotinNegation = 1;
					break;
			}
			default:
				{
					$inNotinNegation = 2;
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					throw new \Exception( 'Function operator: "' . $funcsOperator . '" is not declared.' );
				}
				break;
			}
		}

		$userFuncs = $this->getUserFuncsWithUnitIds();
		$userPass  = 0;
		foreach ( $funcs as $func ) {
			// in / not_in range check
			if ( ( $inNotinNegation + array_key_exists( $func, $userFuncs ) ) === 1 ) {
				foreach ( $userFuncs[ $func ] as $userFuncUnitId ) {
					$userFuncUnitId = $this->clearUnitId( $userFuncUnitId );

					switch ( $unitOperator ) {
						case 'equal':
							{
								$userPass += ( $userFuncUnitId === $unitId );
								break;
						}
						case 'begins_with':
							{
								$userPass += ( substr( $userFuncUnitId, 0, strlen( $unitId ) ) === $unitId );
								break;
						}
						case 'any':
							{
								$userPass += 1;
								break;
						}
						default:
							{
							if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
								throw new \Exception( 'Unit operator: "' . $unitOperator . '" is not declared.' );
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
