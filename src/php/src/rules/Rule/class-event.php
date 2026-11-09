<?php
/**
 * Contains the Event class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Rules\Rule;

use PHPMD\Attribute\SuppressWarnings;
use PHPMD\Rule\Naming\BooleanGetMethodName;
use Skautis_Integration\Rules\Rule;
use Skautis_Integration\Auth\Skautis_Gateway;
use Skautis_Integration\Utils\Helpers;

/**
 * Rule operator for filtering users based on the SkautIS events they take part in.
 *
 * The rule is passed by the people connected with the selected events in SkautIS. It can be limited to the event team, i.e. the people who have a SkautIS role in the event (the leadership and the instructors), or to the other people of the event (the participants).
 */
class Event implements Rule {

	/**
	 * The rule ID
	 *
	 * @var string
	 */
	private static $rule_id = 'event';

	/**
	 * The rule value type.
	 *
	 * @var string
	 */
	protected static $type = 'string';

	/**
	 * The rule input field type type.
	 *
	 * @var string
	 */
	protected static $input = 'eventInput';

	/**
	 * Whether the rule accepts multiple values at once.
	 *
	 * @var bool
	 */
	protected static $multiple = true;

	/**
	 * All the operators that are applicable for the rule.
	 *
	 * @var array<string>
	 */
	protected static $operators = array( 'in', 'not_in' );

	/**
	 * A link to the Skautis_Gateway service instance.
	 *
	 * @var Skautis_Gateway
	 */
	protected $skautis_gateway;

	/**
	 * The events of the current user, computed once per request.
	 *
	 * @var array<string, array{name: string, group: int}>|null
	 */
	private $user_events = null;

	/**
	 * The groups of the SkautIS roles of the current user, computed once per request.
	 *
	 * @var array<int>|null
	 */
	private $user_role_groups = null;

	/**
	 * Constructs the service and saves all dependencies.
	 *
	 * @param Skautis_Gateway $skautis_gateway An injected Skautis_Gateway service instance.
	 */
	public function __construct( Skautis_Gateway $skautis_gateway ) {
		$this->skautis_gateway = $skautis_gateway;
	}

	/**
	 * Returns the rule ID.
	 */
	public static function get_id(): string {
		return self::$rule_id;
	}

	/**
	 * Returns the localized rule name.
	 */
	public function get_label(): string {
		return __( 'Event', 'skautis-integration' );
	}

	/**
	 * Returns the rule value type.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return self::$type;
	}

	/**
	 * Returns the rule input field type type.
	 *
	 * @return string
	 */
	public function get_input(): string {
		return self::$input;
	}

	/**
	 * Returns whether the rule accepts multiple values at once.
	 */
	#[SuppressWarnings( BooleanGetMethodName::class )]
	public function get_multiple(): bool {
		return self::$multiple;
	}

	/**
	 * Returns all the operators that are applicable for the rule.
	 *
	 * @return array<string>
	 */
	public function get_operators(): array {
		return self::$operators;
	}

	/**
	 * Returns the placeholder value for the rule.
	 */
	public function get_placeholder(): string {
		return '';
	}

	/**
	 * Returns an optional additional description of the rule.
	 */
	public function get_description(): string {
		return __( 'The events of SkautIS that you take part in. The event team are the people with a SkautIS role in the event, e.g. the leadership and the instructors.', 'skautis-integration' );
	}

	/**
	 * Returns the events that can be selected in the rule: the events of the current user.
	 *
	 * @return array<string, string> The events in the form `ID => name`.
	 */
	public function get_values(): array {
		$result = array();
		foreach ( $this->get_user_events() as $event_id => $event ) {
			$result[ $event_id ] = $event['name'];
		}
		asort( $result );

		return $result;
	}

	/**
	 * Returns the events that the current user takes part in.
	 *
	 * @return array<string, array{name: string, group: int}> The events, keyed by their ID.
	 */
	private function get_user_events(): array {
		if ( ! is_null( $this->user_events ) ) {
			return $this->user_events;
		}
		$this->user_events = array();

		try {
			$user_detail = $this->skautis_gateway->get_skautis_instance()->UserManagement->UserDetail();
			if ( ! is_object( $user_detail ) || ! isset( $user_detail->ID_Person ) ) {
				return $this->user_events;
			}
			$events = $this->skautis_gateway->get_skautis_instance()->Events->EventAllPerson(
				array(
					'ID_Person' => $user_detail->ID_Person,
				)
			);
		} catch ( \Exception $_ ) {
			return $this->user_events;
		}
		Helpers::log_skautis_response( 'EventAllPerson', $events );

		if ( is_object( $events ) ) {
			$events = array( $events );
		}
		if ( ! is_array( $events ) ) {
			return $this->user_events;
		}

		foreach ( $events as $event ) {
			if ( ! is_object( $event ) || ! isset( $event->ID ) ) {
				continue;
			}
			$this->user_events[ strval( $event->ID ) ] = array(
				'name'  => isset( $event->DisplayName ) && is_string( $event->DisplayName ) ? $event->DisplayName : '#' . strval( $event->ID ),
				'group' => isset( $event->ID_Group ) ? intval( $event->ID_Group ) : 0,
			);
		}

		return $this->user_events;
	}

	/**
	 * Returns the groups of the SkautIS roles of the current user. A role in an event has the same group as the event.
	 *
	 * @return array<int> The groups.
	 */
	private function get_user_role_groups(): array {
		if ( ! is_null( $this->user_role_groups ) ) {
			return $this->user_role_groups;
		}
		$this->user_role_groups = array();

		try {
			$roles = $this->skautis_gateway->get_skautis_instance()->UserManagement->UserRoleAll(
				array(
					'ID_Login' => $this->skautis_gateway->get_skautis_instance()->getUser()->getLoginId(),
					'ID_User'  => $this->skautis_gateway->get_skautis_instance()->UserManagement->UserDetail()->ID,
					'IsActive' => true,
				)
			);
		} catch ( \Exception $_ ) {
			return $this->user_role_groups;
		}

		if ( is_object( $roles ) ) {
			$roles = array( $roles );
		}
		foreach ( is_array( $roles ) ? $roles : array() as $role ) {
			if ( is_object( $role ) && isset( $role->ID_Group ) && intval( $role->ID_Group ) > 0 ) {
				$this->user_role_groups[] = intval( $role->ID_Group );
			}
		}

		return $this->user_role_groups;
	}

	/**
	 * Checks whether the current user takes part in an event in the given way.
	 *
	 * @param string $event_id The ID of the event.
	 * @param string $relation "any" for anyone connected with the event, "team" for the event team, or "participant" for the people not in the event team.
	 */
	private function is_in_event( string $event_id, string $relation ): bool {
		$events = $this->get_user_events();
		if ( ! isset( $events[ $event_id ] ) ) {
			return false;
		}
		if ( 'any' === $relation ) {
			return true;
		}

		$group   = $events[ $event_id ]['group'];
		$is_team = $group > 0 && in_array( $group, $this->get_user_role_groups(), true );

		return 'team' === $relation ? $is_team : ! $is_team;
	}

	/**
	 * Checks whether the rule is fulfilled.
	 *
	 * @throws \Exception An operator is undefined.
	 *
	 * @param string $operator The operator used with the rule.
	 * @param string $data The rule data in the form `event IDs~relation`.
	 */
	public function is_rule_passed( string $operator, $data ): bool {
		$parts = explode( '~', strval( $data ) );
		if ( 2 !== count( $parts ) || '' === $parts[0] || ! in_array( $parts[1], array( 'any', 'team', 'participant' ), true ) ) {
			return false;
		}

		if ( ! in_array( $operator, self::$operators, true ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				throw new \Exception( esc_html( 'Event operator: "' . $operator . '" is not declared.' ) );
			}
			return false;
		}

		$has_match = false;
		foreach ( explode( ',', $parts[0] ) as $event_id ) {
			if ( $this->is_in_event( trim( $event_id ), $parts[1] ) ) {
				$has_match = true;
				break;
			}
		}

		// "in" passes when the user takes part in one of the events, "not_in" when in none of them.
		return 'in' === $operator ? $has_match : ! $has_match;
	}
}
