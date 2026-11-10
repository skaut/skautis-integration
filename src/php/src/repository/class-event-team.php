<?php
/**
 * Contains the Event_Team class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Repository;

use Skautis_Integration\Auth\Skautis_Gateway;
use Skautis_Integration\Utils\Helpers;

/**
 * Loads the instructors and the leadership of a SkautIS event.
 *
 * The participants of an event are loaded by Users::get_users(), this class adds the people who run the event.
 */
final class Event_Team {

	/**
	 * A link to the Skautis_Gateway service instance.
	 *
	 * @var Skautis_Gateway
	 */
	private $skautis_gateway;

	/**
	 * The SkautIS methods that could not be called, e.g. because the App ID is not allowed to use them.
	 *
	 * @var array<string>
	 */
	private $failed_methods = array();

	/**
	 * Constructs the service and saves all dependencies.
	 *
	 * @param Skautis_Gateway $skautis_gateway An injected Skautis_Gateway service instance.
	 */
	public function __construct( Skautis_Gateway $skautis_gateway ) {
		$this->skautis_gateway = $skautis_gateway;
	}

	/**
	 * Returns the SkautIS methods that load the team of an event of the given type.
	 *
	 * @param string $event_type The type of the event, e.g. "Education".
	 * @param int    $event_id The ID of the event of that type.
	 *
	 * @return array<array{method: string, args: array<string, int>, role: string}> The methods with their arguments and the role of the people they return.
	 */
	private static function get_team_methods( string $event_type, int $event_id ): array {
		switch ( $event_type ) {
			case 'Education':
				return array(
					array(
						'method' => 'InstructorAll',
						'args'   => array( 'ID_EventEducation' => $event_id ),
						'role'   => __( 'Instructor', 'skautis-integration' ),
					),
					array(
						'method' => 'EventFunctionAllEducationLeader',
						'args'   => array( 'ID_EventEducation' => $event_id ),
						'role'   => __( 'Event leadership', 'skautis-integration' ),
					),
				);
			case 'Camp':
				return array(
					array(
						'method' => 'EventFunctionAllCamp',
						'args'   => array( 'ID_EventCamp' => $event_id ),
						'role'   => __( 'Event leadership', 'skautis-integration' ),
					),
				);
			case 'General':
				return array(
					array(
						'method' => 'EventFunctionAllGeneral',
						'args'   => array( 'ID_EventGeneral' => $event_id ),
						'role'   => __( 'Event leadership', 'skautis-integration' ),
					),
				);
			default:
				return array();
		}
	}

	/**
	 * Returns the instructors and the leadership of an event.
	 *
	 * The people are identified by their person ID. A person who has more roles in the event is listed once with all the roles.
	 *
	 * @param string $event_type The type of the event, e.g. "Education".
	 * @param int    $event_id The ID of the event of that type.
	 *
	 * @return array<int, array{name: string, roles: array<string>}> The people, keyed by their person ID.
	 */
	public function get_team( string $event_type, int $event_id ): array {
		$team = array();

		foreach ( self::get_team_methods( $event_type, $event_id ) as $call ) {
			$method = $call['method'];
			try {
				$response = $this->skautis_gateway->get_skautis_instance()->Events->$method( $call['args'] );
			} catch ( \Exception $e ) {
				Helpers::log_skautis_response( $call['method'] . ' (error)', $e->getMessage() );
				$this->failed_methods[] = $call['method'];
				continue;
			}
			Helpers::log_skautis_response( $call['method'], $response );

			if ( is_object( $response ) ) {
				$response = array( $response );
			}
			if ( ! is_array( $response ) ) {
				continue;
			}

			foreach ( $response as $person ) {
				if ( ! is_object( $person ) || ! isset( $person->ID_Person ) ) {
					continue;
				}
				$person_id = intval( $person->ID_Person );
				if ( ! isset( $team[ $person_id ] ) ) {
					$team[ $person_id ] = array(
						'name'  => self::get_person_name( $person ),
						'roles' => array(),
					);
				}
				if ( ! in_array( $call['role'], $team[ $person_id ]['roles'], true ) ) {
					$team[ $person_id ]['roles'][] = $call['role'];
				}
			}
		}

		return $team;
	}

	/**
	 * Returns the name of a person from a SkautIS response, in the "Surname Name (Nickname)" format used for the event participants.
	 *
	 * @param object $person The person from the response.
	 */
	private static function get_person_name( object $person ): string {
		foreach ( array( 'Person', 'DisplayName', 'PersonDisplayName' ) as $property ) {
			if ( isset( $person->$property ) && is_string( $person->$property ) && '' !== $person->$property ) {
				return $person->$property;
			}
		}

		return '';
	}

	/**
	 * Returns the SkautIS methods that could not be called.
	 *
	 * @return array<string> The method names.
	 */
	public function get_failed_methods(): array {
		return array_values( array_unique( $this->failed_methods ) );
	}
}
