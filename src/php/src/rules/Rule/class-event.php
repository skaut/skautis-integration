<?php
/**
 * Contains the Event class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Rules\Rule;

use PHPMD\Attribute\SuppressWarnings;
use PHPMD\Rule\Design\ExcessiveClassComplexity;
use PHPMD\Rule\Naming\BooleanGetMethodName;
use Skautis_Integration\Rules\Rule;
use Skautis_Integration\Auth\Skautis_Gateway;
use Skautis_Integration\Utils\Helpers;

/**
 * Rule operator for filtering users based on the SkautIS events they take part in.
 *
 * The rule is passed by the people connected with the selected events in SkautIS. It can be limited to the event team, i.e. the people who have a SkautIS role in the event other than a participant role (e.g. "VzA: vedoucí", "VzA: člen týmu"), or to the participants of the event. Participants may have a participant role in the event too (e.g. "VzA: účastník", "Tábor: účastník").
 */
#[SuppressWarnings( ExcessiveClassComplexity::class )]
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
	 * @var array<string, array{name: string, group: int, type: string, start: string}>|null
	 */
	private $user_events = null;

	/**
	 * The groups of the SkautIS roles of the current user, split into the roles of the event teams and the participant roles. Computed once per request.
	 *
	 * @var array{team: array<int>, participant: array<int>}|null
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
		return __( 'The events of SkautIS that you take part in. The event team are the people with a role in the event other than a participant role, e.g. "VzA: vedoucí" or "VzA: člen týmu".', 'skautis-integration' );
	}

	/**
	 * Returns the events that can be selected in the rule: the events of the current user.
	 *
	 * @return array<string, string> The events in the form `ID => name`.
	 */
	public function get_values(): array {
		$events = $this->get_user_events();
		// The newest events first.
		uasort(
			$events,
			static function ( array $first, array $second ): int {
				return strcmp( $second['start'], $first['start'] );
			}
		);

		$result = array();
		foreach ( $events as $event_id => $event ) {
			$result[ strval( $event_id ) ] = self::get_event_label( $event );
		}

		return $result;
	}

	/**
	 * Returns the label of an event in the form "Type: name (year)", e.g. "Tábor: Letní tábor u Smolova (2019)".
	 *
	 * The year is added only when the name does not contain it already.
	 *
	 * @param array{name: string, group: int, type: string, start: string} $event The event.
	 */
	private static function get_event_label( array $event ): string {
		$label = '' !== $event['type'] ? $event['type'] . ': ' . $event['name'] : $event['name'];
		$year  = 1 === preg_match( '/^(\d{4})-/', $event['start'], $matches ) ? $matches[1] : '';
		if ( '' !== $year && false === strpos( $event['name'], $year ) ) {
			$label .= ' (' . $year . ')';
		}

		return $label;
	}

	/**
	 * Returns the events that the current user takes part in.
	 *
	 * @return array<string, array{name: string, group: int, type: string, start: string}> The events, keyed by their ID.
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
		} catch ( \Exception $e ) {
			Helpers::log_skautis_response( 'EventAllPerson (error)', $e->getMessage() );
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
				'type'  => isset( $event->EventType ) && is_string( $event->EventType ) ? $event->EventType : '',
				'start' => isset( $event->StartDate ) && is_string( $event->StartDate ) ? $event->StartDate : '',
			);
		}

		return $this->user_events;
	}

	/**
	 * Returns whether a SkautIS role is the role of a participant of an event, e.g. "VzA: účastník", "Tábor: účastník" or "Sněm: účastník sněmu".
	 *
	 * @param object $role The role from UserRoleAll.
	 */
	private static function is_participant_role( object $role ): bool {
		$key  = isset( $role->Key ) && is_string( $role->Key ) ? $role->Key : '';
		$name = isset( $role->DisplayName ) && is_string( $role->DisplayName ) ? $role->DisplayName : '';

		return false !== stripos( $key, 'Participant' ) || false !== mb_stripos( $name, 'účastník' );
	}

	/**
	 * Returns the groups of the SkautIS roles of the current user. A role in an event has the same group as the event.
	 *
	 * @return array{team: array<int>, participant: array<int>} The groups of the event team roles and of the participant roles.
	 */
	private function get_user_role_groups(): array {
		if ( ! is_null( $this->user_role_groups ) ) {
			return $this->user_role_groups;
		}
		$this->user_role_groups = array(
			'team'        => array(),
			'participant' => array(),
		);

		try {
			$roles = $this->skautis_gateway->get_skautis_instance()->UserManagement->UserRoleAll(
				array(
					'ID_Login' => $this->skautis_gateway->get_skautis_instance()->getUser()->getLoginId(),
					'ID_User'  => $this->skautis_gateway->get_skautis_instance()->UserManagement->UserDetail()->ID,
					'IsActive' => true,
				)
			);
		} catch ( \Exception $e ) {
			Helpers::log_skautis_response( 'UserRoleAll (error)', $e->getMessage() );
			return $this->user_role_groups;
		}
		Helpers::log_skautis_response( 'UserRoleAll', $roles );

		if ( is_object( $roles ) ) {
			$roles = array( $roles );
		}
		foreach ( is_array( $roles ) ? $roles : array() as $role ) {
			if ( is_object( $role ) && isset( $role->ID_Group ) && intval( $role->ID_Group ) > 0 ) {
				$this->user_role_groups[ self::is_participant_role( $role ) ? 'participant' : 'team' ][] = intval( $role->ID_Group );
			}
		}

		return $this->user_role_groups;
	}

	/**
	 * Checks whether the current user takes part in an event in the given way.
	 *
	 * @param string $event_id The ID of the event.
	 * @param string $relation "any" for anyone connected with the event, "team" for the event team, or "participant" for the people of the event who are not in its team.
	 */
	private function is_in_event( string $event_id, string $relation ): bool {
		$events = $this->get_user_events();
		$groups = $this->get_user_role_groups();

		$group          = $events[ $event_id ]['group'] ?? 0;
		$is_team        = $group > 0 && in_array( $group, $groups['team'], true );
		$is_participant = $group > 0 && in_array( $group, $groups['participant'], true );
		$is_in_event    = isset( $events[ $event_id ] ) || $is_team || $is_participant;

		switch ( $relation ) {
			case 'any':
				return $is_in_event;
			case 'team':
				return $is_team;
			default:
				return $is_in_event && ! $is_team;
		}
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
