<?php

declare( strict_types=1 );

namespace SkautisIntegration\Rules;

use SkautisIntegration\Auth\Skautis_Gateway;
use SkautisIntegration\Auth\WP_Login_Logout;

final class Rules_Manager {

	private $skautisGateway;
	private $wpLoginLogout;
	private $rules = array();

	public function __construct( Skautis_Gateway $skautisGateway, WP_Login_Logout $wpLoginLogout ) {
		$this->skautisGateway = $skautisGateway;
		$this->wpLoginLogout  = $wpLoginLogout;
		$this->rules          = $this->initRules();
		if ( is_admin() ) {
			( new Admin( $this, $wpLoginLogout, $skautisGateway ) );
		}
	}

	private function initRules(): array {
		return apply_filters(
			SKAUTISINTEGRATION_NAME . '_rules',
			array(
				Rule\Role::$id          => new Rule\Role( $this->skautisGateway ),
				Rule\Membership::$id    => new Rule\Membership( $this->skautisGateway ),
				Rule\Func::$id          => new Rule\Func( $this->skautisGateway ),
				Rule\Qualification::$id => new Rule\Qualification( $this->skautisGateway ),
				Rule\All::$id           => new Rule\All( $this->skautisGateway ),
			)
		);
	}

	private function processRule( $rule ): bool {
		if ( ! isset( $rule->field ) ) {
			if ( isset( $rule->condition ) && isset( $rule->rules ) ) {
				return $this->parseRulesGroups( $rule->condition, $rule->rules );
			}

			return false;
		}

		if ( isset( $this->rules[ $rule->field ] ) ) {
			return $this->rules[ $rule->field ]->isRulePassed( $rule->operator, $rule->value );
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			throw new \Exception( 'Rule: "' . $rule->field . '" is not declared.' );
		}

		return false;
	}

	private function parseRulesGroups( string $condition, array $rules ): bool {
		$result = 0;

		if ( 'AND' === $condition ) {
			$result = 1;
			foreach ( $rules as $rule ) {
				if ( isset( $rule->rules ) ) {
					$result = $result * $this->parseRulesGroups( $rule->condition, $rule->rules );
				}
				$result = $result * $this->processRule( $rule );
			}
		} else { // OR
			foreach ( $rules as $rule ) {
				if ( isset( $rule->rules ) ) {
					$result = $result + $this->parseRulesGroups( $rule->condition, $rule->rules );
				}
				$result = $result + $this->processRule( $rule );
			}
		}

		if ( $result > 0 ) {
			return true;
		}

		return false;
	}

	public function getRules(): array {
		return $this->rules;
	}

	public function checkIfUserPassedRulesAndGetHisRole(): string {
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
				$result = $this->parseRulesGroups( $rulesGroups->condition, $rulesGroups->rules );
			}

			if ( true === $result ) {
				return $rule['role'];
			}
		}

		return '';
	}

	public function getAllRules(): array {
		$rulesWpQuery = new \WP_Query(
			array(
				'post_type'     => RulesInit::RULES_TYPE_SLUG,
				'nopaging'      => true,
				'no_found_rows' => true,
			)
		);

		if ( $rulesWpQuery->have_posts() ) {
			return $rulesWpQuery->posts;
		}

		return array();
	}

	public function checkIfUserPassedRules( array $rulesIds ): bool {
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
				$result = $this->parseRulesGroups( $rulesGroups->condition, $rulesGroups->rules );
			}

			if ( true === $result ) {
				return $result;
			}
		}

		return false;
	}

}
