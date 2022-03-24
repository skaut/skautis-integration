<?php

declare( strict_types=1 );

namespace Skautis_Integration\Rules\Rule;

use Skautis_Integration\Rules\Rule;
use Skautis_Integration\Auth\Skautis_Gateway;

class Func implements Rule {

	public static $id           = 'func';
	protected static $type      = 'string';
	protected static $input     = 'funcInput';
	protected static $multiple  = true;
	protected static $operators = array( 'in', 'not_in' );

	protected $skautis_gateway;

	public function __construct( Skautis_Gateway $skautis_gateway ) {
		$this->skautis_gateway = $skautis_gateway;
	}

	public function get_id(): string {
		return self::$id;
	}

	public function get_label(): string {
		return __( 'Funkce', 'skautis-integration' );
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
		$values = array();
		$funcs  = $this->skautis_gateway->get_skautis_instance()->OrganizationUnit->FunctionTypeAll();

		foreach ( $funcs as $func ) {
			$values[ $func->ID ] = $func->ShortName;
		}

		return $values;
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

	protected function getUserFuncsWithUnitIds(): array {
		static $user_funcs = null;

		if ( is_null( $user_funcs ) ) {
			$user_detail = $this->skautis_gateway->get_skautis_instance()->UserManagement->UserDetail();
			$user_funcs  = $this->skautis_gateway->get_skautis_instance()->OrganizationUnit->FunctionAllPerson(
				array(
					'ID_Person' => $user_detail->ID_Person,
				)
			);

			$result = array();

			if ( ! $user_funcs || ! property_exists( $user_funcs, 'FunctionAllOutput' ) || empty( $user_funcs->FunctionAllOutput ) || ! is_array( $user_funcs->FunctionAllOutput ) || empty( $user_funcs->FunctionAllOutput[0] ) ) {
				return $result;
			}

			foreach ( $user_funcs->FunctionAllOutput as $user_func ) {
				$unit_detail = $this->skautis_gateway->get_skautis_instance()->OrganizationUnit->UnitDetail(
					array(
						'ID' => $user_func->ID_Unit,
					)
				);
				if ( $unit_detail ) {
					if ( ! isset( $result[ $user_func->ID_FunctionType ] ) ) {
						$result[ $user_func->ID_FunctionType ] = array();
					}
					$result[ $user_func->ID_FunctionType ][] = $unit_detail->RegistrationNumber;
				}
			}

			$user_funcs = $result;
		}

		if ( ! is_array( $user_funcs ) ) {
			return array();
		}

		return $user_funcs;
	}

	public function is_rule_passed( string $funcs_operator, $data ): bool {
		// parse and prepare data from rules UI
		$output = array();
		preg_match_all( '|[^~]+|', $data, $output );
		if ( isset( $output[0], $output[0][0], $output[0][1], $output[0][2] ) ) {
			list( $funcs, $unit_operator, $unit_id ) = $output[0];
			$funcs                                   = explode( ',', $funcs );
			$unit_id                                 = $this->clearUnitId( $unit_id );
		} else {
			return false;
		}

		// logic for determine in / not_in range
		$in_not_in_negation = 2;
		switch ( $funcs_operator ) {
			case 'in':
				$in_not_in_negation = 0;
				break;
			case 'not_in':
				$in_not_in_negation = 1;
				break;
			default:
				$in_not_in_negation = 2;
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					throw new \Exception( 'Function operator: "' . $funcs_operator . '" is not declared.' );
				}
				break;
		}

		$user_funcs = $this->getUserFuncsWithUnitIds();
		$user_pass  = 0;
		foreach ( $funcs as $func ) {
			// in / not_in range check
			if ( ( $in_not_in_negation + array_key_exists( $func, $user_funcs ) ) === 1 ) {
				foreach ( $user_funcs[ $func ] as $user_func_unit_id ) {
					$user_func_unit_id = $this->clearUnitId( $user_func_unit_id );

					switch ( $unit_operator ) {
						case 'equal':
							$user_pass += ( $user_func_unit_id === $unit_id );
							break;
						case 'begins_with':
							$user_pass += ( substr( $user_func_unit_id, 0, strlen( $unit_id ) ) === $unit_id );
							break;
						case 'any':
							++$user_pass;
							break;
						default:
							if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
								throw new \Exception( 'Unit operator: "' . $unit_operator . '" is not declared.' );
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
