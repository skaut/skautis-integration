<?php

declare( strict_types=1 );

namespace SkautisIntegration\Rules;

use SkautisIntegration\Auth\Skautis_Gateway;
use SkautisIntegration\Auth\WP_Login_Logout;

final class Rules_Manager {

	private $skautis_gateway;
	// TODO: Unused?
	private $wp_login_logout;
	private $rules = array();

	public function __construct( Skautis_Gateway $skautisGateway, WP_Login_Logout $wpLoginLogout ) {
		$this->skautis_gateway = $skautisGateway;
		$this->wp_login_logout = $wpLoginLogout;
		$this->rules           = $this->init_rules();
		if ( is_admin() ) {
			( new Admin( $this, $wpLoginLogout, $this->skautis_gateway ) );
		}
	}

	private function init_rules(): array {
		return apply_filters(
			SKAUTISINTEGRATION_NAME . '_rules',
			array(
				Rule\Role::$id          => new Rule\Role( $this->skautis_gateway ),
				Rule\Membership::$id    => new Rule\Membership( $this->skautis_gateway ),
				Rule\Func::$id          => new Rule\Func( $this->skautis_gateway ),
				Rule\Qualification::$id => new Rule\Qualification( $this->skautis_gateway ),
				Rule\All::$id           => new Rule\All( $this->skautis_gateway ),
			)
		);
	}

	private function process_rule( $rule ): bool {
		if ( ! isset( $rule->field ) ) {
			if ( isset( $rule->condition ) && isset( $rule->rules ) ) {
				return $this->parse_rules_groups( $rule->condition, $rule->rules );
			}

			return false;
		}

		if ( isset( $this->rules[ $rule->field ] ) ) {
			return $this->rules[ $rule->field ]->is_rule_passed( $rule->operator, $rule->value );
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			throw new \Exception( 'Rule: "' . $rule->field . '" is not declared.' );
		}

		return false;
	}

	private function parse_rules_groups( string $condition, array $rules ): bool {
		$result = 0;

		if ( 'AND' === $condition ) {
			$result = 1;
			foreach ( $rules as $rule ) {
				if ( isset( $rule->rules ) ) {
					$result = $result * $this->parse_rules_groups( $rule->condition, $rule->rules );
				}
				$result = $result * $this->process_rule( $rule );
			}
		} else { // OR
			foreach ( $rules as $rule ) {
				if ( isset( $rule->rules ) ) {
					$result = $result + $this->parse_rules_groups( $rule->condition, $rule->rules );
				}
				$result = $result + $this->process_rule( $rule );
			}
		}

		if ( $result > 0 ) {
			return true;
		}

		return false;
	}

	public function get_rules(): array {
		return $this->rules;
	}

	public function check_if_user_passed_rules_and_get_his_role(): string {
		$result = '';

		$rules = get_option( SKAUTISINTEGRATION_NAME . '_modules_register_rules' );
		if ( ! $rules ) {
			return (string) get_option( SKAUTISINTEGRATION_NAME . '_modules_register_defaultwpRole' );
		}

		foreach ( (array) $rules as $rule ) {
			if ( isset( $rule['rule'] ) ) {
				$rulesGroups = json_decode( get_post_meta( $rule['rule'], SKAUTISINTEGRATION_NAME . '_rules_data', true ) );
			} else {
				return '';
			}

			if ( isset( $rulesGroups->condition ) && isset( $rulesGroups->rules ) && ! empty( $rulesGroups->rules ) ) {
				$result = $this->parse_rules_groups( $rulesGroups->condition, $rulesGroups->rules );
			}

			if ( true === $result ) {
				return $rule['role'];
			}
		}

		return '';
	}

	public function get_all_rules(): array {
		$rulesWpQuery = new \WP_Query(
			array(
				'post_type'     => Rules_Init::RULES_TYPE_SLUG,
				'nopaging'      => true,
				'no_found_rows' => true,
			)
		);

		if ( $rulesWpQuery->have_posts() ) {
			return $rulesWpQuery->posts;
		}

		return array();
	}

	public function check_if_user_passed_rules( array $rulesIds ): bool {
		static $rulesGroups = null;
		$result             = false;

		foreach ( $rulesIds as $ruleId ) {
			if ( is_array( $ruleId ) ) {
				$ruleId = reset( $ruleId );
			}

			if ( is_null( $rulesGroups ) ) {
				$rulesGroups = json_decode( (string) get_post_meta( $ruleId, SKAUTISINTEGRATION_NAME . '_rules_data', true ) );
			}

			if ( isset( $rulesGroups->condition ) && isset( $rulesGroups->rules ) && ! empty( $rulesGroups->rules ) ) {
				$result = $this->parse_rules_groups( $rulesGroups->condition, $rulesGroups->rules );
			}

			if ( true === $result ) {
				return $result;
			}
		}

		return false;
	}

}
