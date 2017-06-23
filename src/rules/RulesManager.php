<?php

namespace SkautisIntegration\Rules;

use SkautisIntegration\Auth\SkautisGateway;
use SkautisIntegration\Auth\WpLoginLogout;

final class RulesManager {

	private $skautisGateway;
	private $wpLoginLogout;
	private $rules = [];

	public function __construct( SkautisGateway $skautisGateway, WpLoginLogout $wpLoginLogout ) {
		$this->skautisGateway = $skautisGateway;
		$this->wpLoginLogout  = $wpLoginLogout;
		$this->rules          = $this->initRules();
		( new Admin( $this, $wpLoginLogout, $skautisGateway ) );
	}

	private function initRules() {
		return apply_filters( SKAUTISINTEGRATION_NAME . '_rules', [
			Rule\Unit::$id => new Rule\Unit( $this->skautisGateway ),
			Rule\Role::$id => new Rule\Role( $this->skautisGateway ),
			Rule\All::$id  => new Rule\All( $this->skautisGateway )
		] );
	}

	private function processRule( $rule ) {
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

	private function parseRulesGroups( $condition, $rules ) {
		$result = 0;

		if ( $condition == 'AND' ) {
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

		return $result;
	}

	public function getRules() {
		return $this->rules;
	}

	public function checkIfUserPassedRulesAndGetHisRole() {
		$result = 0;

		if ( ! $rules = get_option( SKAUTISINTEGRATION_NAME . '_modules_register_rules' ) ) {
			return get_option( SKAUTISINTEGRATION_NAME . '_modules_register_defaultwpRole' );
		}

		foreach ( (array) $rules as $rule ) {

			$rulesGroups = json_decode( get_post_meta( $rule['rule'], SKAUTISINTEGRATION_NAME . '_rules_data', true ) );

			if ( isset( $rulesGroups->condition ) && isset( $rulesGroups->rules ) && ! empty( $rulesGroups->rules ) ) {
				$result = $this->parseRulesGroups( $rulesGroups->condition, $rulesGroups->rules );
			}

			if ( $result ) {
				return $rule['role'];
			}
		}

		return $result;
	}

	public function getAllRules() {
		$rulesWpQuery = new \WP_Query( [
			'post_type'     => RulesInit::RULES_TYPE_SLUG,
			'nopaging'      => true,
			'no_found_rows' => true
		] );

		if ( $rulesWpQuery->have_posts() ) {
			return $rulesWpQuery->posts;
		}

		return [];
	}

}