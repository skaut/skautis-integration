<?php
/**
 * Contains the Rules_Manager class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Rules;

use Skautis_Integration\Auth\Skautis_Gateway;
use Skautis_Integration\Auth\WP_Login_Logout;

/**
 * Contains functions for checking whether a user passes rules.
 */
final class Rules_Manager {

	/**
	 * A link to the Skautis_Gateway service instance.
	 *
	 * @var Skautis_Gateway
	 */
	private $skautis_gateway;

	/**
	 * A link to the WP_Login_Logout service instance.
	 *
	 * TODO: Unused?
	 *
	 * @var WP_Login_Logout
	 */
	private $wp_login_logout;

	/**
	 * A list of all the avialable rule blocks.
	 *
	 * @var array<string, Rule>
	 */
	private $rules = array();

	/**
	 * Constructs the service and saves all dependencies.
	 *
	 * @param Skautis_Gateway $skautis_gateway An injected Skautis_Gateway service instance.
	 * @param WP_Login_Logout $wp_login_logout An injected WP_Login_Logout service instance.
	 */
	public function __construct( Skautis_Gateway $skautis_gateway, WP_Login_Logout $wp_login_logout ) {
		$this->skautis_gateway = $skautis_gateway;
		$this->wp_login_logout = $wp_login_logout;
		$this->rules           = $this->init_rules();
		if ( is_admin() ) {
			( new Admin( $this, $wp_login_logout, $this->skautis_gateway ) );
		}
	}

	/**
	 * Initializes all available rule blocks and stores them in this object.
	 */
	private function init_rules(): array {
		return apply_filters(
			SKAUTIS_INTEGRATION_NAME . '_rules',
			array(
				Rule\Role::$id          => new Rule\Role( $this->skautis_gateway ),
				Rule\Membership::$id    => new Rule\Membership( $this->skautis_gateway ),
				Rule\Func::$id          => new Rule\Func( $this->skautis_gateway ),
				Rule\Qualification::$id => new Rule\Qualification( $this->skautis_gateway ),
				Rule\All::$id           => new Rule\All( $this->skautis_gateway ),
			)
		);
	}

	/**
	 * Checks whether a user passed a rule.
	 *
	 * @throws \Exception An undefined rule was passed to the function.
	 *
	 * @param array $rule The rule to check against.
	 */
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

	/**
	 * Checks whether a user passed a rule group.
	 *
	 * @param "AND"|"OR" $condition The logical operator used by the group.
	 * @param array      $rules A list of rules in the group.
	 */
	private function parse_rules_groups( string $condition, array $rules ): bool {
		$result = 0;

		if ( 'AND' === $condition ) {
			$result = 1;
			foreach ( $rules as $rule ) {
				if ( isset( $rule->rules ) ) {
					$result *= $this->parse_rules_groups( $rule->condition, $rule->rules );
				}
				$result *= $this->process_rule( $rule );
			}
		} else { // OR.
			foreach ( $rules as $rule ) {
				if ( isset( $rule->rules ) ) {
					$result += $this->parse_rules_groups( $rule->condition, $rule->rules );
				}
				$result += $this->process_rule( $rule );
			}
		}

		if ( $result > 0 ) {
			return true;
		}

		return false;
	}

	/**
	 * Returns a list of all available rule blocks.
	 */
	public function get_rules(): array {
		return $this->rules;
	}

	/**
	 * Checks whether a user passed plugin rules and returns their role if they did.
	 *
	 * TODO: Deduplicate with the other method in this class.
	 */
	public function check_if_user_passed_rules_and_get_his_role(): string {
		$result = '';

		$rules = get_option( SKAUTIS_INTEGRATION_NAME . '_modules_register_rules' );
		if ( empty( $rules ) ) {
			return (string) get_option( SKAUTIS_INTEGRATION_NAME . '_modules_register_defaultwpRole' );
		}

		foreach ( (array) $rules as $rule ) {
			if ( isset( $rule['rule'] ) ) {
				$rules_groups = json_decode( get_post_meta( $rule['rule'], SKAUTIS_INTEGRATION_NAME . '_rules_data', true ) );
			} else {
				return '';
			}

			if ( isset( $rules_groups->condition ) && isset( $rules_groups->rules ) && ! empty( $rules_groups->rules ) ) {
				$result = $this->parse_rules_groups( $rules_groups->condition, $rules_groups->rules );
			}

			if ( true === $result ) {
				return $rule['role'];
			}
		}

		return '';
	}

	/**
	 * Returns all rules (posts of type rule).
	 */
	public function get_all_rules(): array {
		$rules_wp_query = new \WP_Query(
			array(
				'post_type'     => Rules_Init::RULES_TYPE_SLUG,
				'nopaging'      => true,
				'no_found_rows' => true,
			)
		);

		if ( $rules_wp_query->have_posts() ) {
			return $rules_wp_query->posts;
		}

		return array();
	}

	/**
	 * Checks whether a user passed plugin rules
	 *
	 * TODO: Deduplicate with the other method in this class.
	 *
	 * @param array $rules_ids A list of IDs of rules or rule groups to check.
	 */
	public function check_if_user_passed_rules( array $rules_ids ): bool {
		static $rules_groups = null;
		$result              = false;

		foreach ( $rules_ids as $rule_id ) {
			if ( is_array( $rule_id ) ) {
				$rule_id = reset( $rule_id );
			}

			if ( is_null( $rules_groups ) ) {
				$rules_groups = json_decode( (string) get_post_meta( $rule_id, SKAUTIS_INTEGRATION_NAME . '_rules_data', true ) );
			}

			if ( isset( $rules_groups->condition ) && isset( $rules_groups->rules ) && ! empty( $rules_groups->rules ) ) {
				$result = $this->parse_rules_groups( $rules_groups->condition, $rules_groups->rules );
			}

			if ( true === $result ) {
				return true;
			}
		}

		return false;
	}

}
