<?php
/**
 * Contains the Users class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Repository;

use Skautis_Integration\Auth\Skautis_Gateway;
use Skautis_Integration\Utils\Helpers;
use Skautis_Integration\Utils\Request_Parameter_Helpers;

/**
 * Contains helper functions for management of SkautIS users.
 */
class Users {

	/**
	 * A link to the Skautis_Gateway service instance.
	 *
	 * @var Skautis_Gateway
	 */
	protected $skautis_gateway;

	/**
	 * Constructs the service and saves all dependencies.
	 *
	 * @param Skautis_Gateway $skautis_gateway An injected Skautis_Gateway service instance.
	 */
	public function __construct( Skautis_Gateway $skautis_gateway ) {
		$this->skautis_gateway = $skautis_gateway;
	}

	/**
	 * Parses the "skautisSearchUsers" GET variable from the URL.
	 */
	protected static function get_search_user_string(): string {
		$nonce = Request_Parameter_Helpers::get_string_variable( SKAUTIS_INTEGRATION_NAME . '_skautis_search_user_nonce' );
		if ( false === wp_verify_nonce( $nonce, SKAUTIS_INTEGRATION_NAME . '_skautis_search_user' ) ) {
			return '';
		}

		$return_url         = Helpers::get_return_url();
		$search_user_string = Request_Parameter_Helpers::get_string_variable( 'skautisSearchUsers' );
		if ( '' === $search_user_string && ! is_null( $return_url ) ) {
			$search_user_string = Helpers::get_variable_from_url( $return_url, 'skautisSearchUsers' );
		}

		return $search_user_string;
	}

	/**
	 * Lists all users with a connected SkautIS account under the current environment (testing or production).
	 *
	 * @return array<int, array{id: int, name: string}> The users as an associative array, where the array key is the SkautIS user ID and the value contains the WordPress user ID.
	 */
	public function get_connected_wp_users(): array {
		$users_data = array();

		// TODO: Replace with a call to get_users()?
		$connected_wp_users = new \WP_User_Query(
			array(
				'meta_query'  => array(
					array(
						'key'     => 'skautisUserId_' . $this->skautis_gateway->get_env(),
						'type'    => 'numeric',
						'value'   => 0,
						'compare' => '>',
					),
				),
				'count_total' => false,
			)
		);

		foreach ( $connected_wp_users->get_results() as $user ) {
			if ( ! ( $user instanceof \WP_User ) ) {
				continue;
			}
			$users_data[ intval( get_user_meta( $user->ID, 'skautisUserId_' . $this->skautis_gateway->get_env(), true ) ) ] = array(
				'id'   => $user->ID,
				'name' => $user->display_name ?? '',
			);
		}

		return $users_data;
	}

	/**
	 * Lists all users without a connected SkautIS account under the current environment (testing or production).
	 *
	 * @return array<\WP_User> The list of users
	 */
	public function get_connectable_wp_users() {
		// TODO: Replace with a call to get_users()?
		$connectable_wp_users = new \WP_User_Query(
			array(
				'meta_query'  => array(
					'relation' => 'OR',
					array(
						'key'     => 'skautisUserId_' . $this->skautis_gateway->get_env(),
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => 'skautisUserId_' . $this->skautis_gateway->get_env(),
						'value'   => '',
						'compare' => '=',
					),
				),
				'count_total' => false,
			)
		);

		// @phpstan-ignore-next-line
		return $connectable_wp_users->get_results();
	}

	/**
	 * Returns all users for the current unit or event. Returns users whose name matches the "skautisSearchUsers" GET variable if it is present.
	 *
	 * @return array{users: array<\stdClass>, eventType: string} The users.
	 */
	public function get_users(): array {
		$users      = array();
		$event_type = '';
		$event_id   = 0;

		if ( ! $this->skautis_gateway->is_initialized() ) {
			return array(
				'users'     => $users,
				'eventType' => $event_type,
			);
		}

		$current_user_roles = $this->skautis_gateway->get_skautis_instance()->UserManagement->UserRoleAll(
			array(
				'ID_Login' => $this->skautis_gateway->get_skautis_instance()->getUser()->getLoginId(),
				'ID_User'  => $this->skautis_gateway->get_skautis_instance()->UserManagement->UserDetail()->ID,
			)
		);
		$current_user_role  = $this->skautis_gateway->get_skautis_instance()->getUser()->getRoleId();

		// Different procedure for roles associated with events.
		foreach ( $current_user_roles as $role ) {
			if ( $role->ID === $current_user_role && isset( $role->Key ) ) {
				$words = preg_split( '~(?=[A-Z])~', $role->Key );
				if ( ! empty( $words ) && isset( $words[1], $words[2] ) && 'Event' === $words[1] ) {
					$event_type = $words[2];

					$user_detail         = $this->skautis_gateway->get_skautis_instance()->UserManagement->UserDetail();
					$current_user_events = $this->skautis_gateway->get_skautis_instance()->Events->EventAllPerson(
						array(
							'ID_Person' => $user_detail->ID_Person,
						)
					);

					foreach ( $current_user_events as $event ) {
						// @phpstan-ignore-next-line
						if ( $event->ID_Group === $role->ID_Group ) {
							$event_url = $this->skautis_gateway->get_skautis_instance()->Events->EventDetail(
								array(
									'ID' => $event->ID,
								)
							);
							if ( isset( $event_url->UrlDetail ) ) {
								$reg_result = array();
								preg_match( '~ID=(\d+)$~', $event_url->UrlDetail, $reg_result );
								if ( isset( $reg_result[1] ) ) {
									$event_id = $reg_result[1];
								}
							}
						}
					}
				}
			}
		}

		// Different procedure for roles associated with events.
		if ( '' !== $event_type && 0 !== $event_id ) {
			if ( 'Congress' === $event_type ) {
				$participants = null;
			} else {
				$method_name  = 'Participant' . $event_type . 'All';
				$participants = $this->skautis_gateway->get_skautis_instance()->Events->$method_name(
					array(
						'ID_Event' . $event_type => $event_id,
					)
				);
			}

			if ( is_array( $participants ) ) {
				$users = array_map(
					static function ( $participant ) {
						$user = new \stdClass();

						$user->id        = $participant->ID;
						$user->personId  = $participant->ID_Person;
						$user->firstName = $participant->Person;
						$user->lastName  = '';
						$user->nickName  = '';

						$reg_result = array();
						preg_match( '~([^\s]+)\s([^\s]+)(\s\((.*)\))~', $participant->Person, $reg_result );

						if ( isset( $reg_result[1], $reg_result[2] ) ) {
							$user->firstName = $reg_result[2];
							$user->lastName  = $reg_result[1];
							if ( isset( $reg_result[4] ) && '' !== $reg_result[4] ) {
								$user->nickName = $reg_result[4];
							}
						}

						if ( isset( $participant->PersonEmail ) && ! empty( $participant->PersonEmail ) ) {
							$emails = preg_split( '~(?=\,)~x', $participant->PersonEmail );
							if ( ! empty( $emails ) && isset( $emails[0] ) ) {
								$user->email = $emails[0];
							}
						} else {
							$user->email = '';
						}

						$user->UserName = $user->email;

						return $user;
					},
					$participants
				);
			}
		}

		// Standard get all users procedure.
		if ( empty( $users ) ) {
			$search_user_string = self::get_search_user_string();

			$skautis_users = $this->skautis_gateway->get_skautis_instance()->UserManagement->userAll(
				array(
					'DisplayName' => $search_user_string,
				)
			);

			if ( is_array( $skautis_users ) ) {
				$users = array_map(
					static function ( $skautis_user ) {
						$user = new \stdClass();

						$user->id        = $skautis_user->ID;
						$user->UserName  = $skautis_user->UserName;
						$user->personId  = $skautis_user->ID_Person;
						$user->firstName = $skautis_user->DisplayName;
						$user->lastName  = '';
						$user->nickName  = '';

						$reg_result = array();
						preg_match( '~([^\s]+)\s([^\s]+)(\s\((.*)\))~', $skautis_user->DisplayName, $reg_result );

						if ( isset( $reg_result[1], $reg_result[2] ) ) {
							$user->firstName = $reg_result[2];
							$user->lastName  = $reg_result[1];
						}
						if ( isset( $reg_result[4] ) && '' !== $reg_result[4] ) {
							$user->nickName = $reg_result[4];
						}

						$user->email = '';

						return $user;
					},
					$skautis_users
				);
			}
		}

		return array(
			'users'     => $users,
			'eventType' => $event_type,
		);
	}

	/**
	 * Returns info about a SkautIS user.
	 *
	 * @throws \Exception Couldn't get the user info from SkautIS.
	 *
	 * @param int $skautis_user_id The SkautIS user ID.
	 *
	 * @return array{id: int, UserName: string, personId: int, email: string, firstName: string, lastName: string, nickName: string} The information about a user.
	 */
	public function get_user_detail( int $skautis_user_id ): array {
		$user_detail = array();

		$users = $this->get_users();

		if ( '' !== $users['eventType'] ) {
			foreach ( (array) $users['users'] as $user ) {
				if ( $user->id === $skautis_user_id ) {
					$user_detail = array(
						'id'        => $skautis_user_id,
						'UserName'  => $user->UserName,
						'personId'  => $user->personId,
						'email'     => $user->email,
						'firstName' => $user->firstName,
						'lastName'  => $user->lastName,
						'nickName'  => $user->nickName,
					);
				}
			}
		} else {
			foreach ( (array) $users['users'] as $user ) {
				if ( $user->id === $skautis_user_id ) {
					$person_detail = $this->skautis_gateway->get_skautis_instance()->OrganizationUnit->PersonDetail(
						array(
							'ID' => $user->personId,
						)
					);

					$user_detail = array(
						'id'        => $skautis_user_id,
						'UserName'  => $user->UserName,
						'personId'  => $user->personId,
						'email'     => $person_detail->Email,
						'firstName' => $user->firstName,
						'lastName'  => $user->lastName,
						'nickName'  => $user->nickName,
					);
				}
			}
		}

		if ( empty( $user_detail ) ) {
			throw new \Exception( __( 'Nepodařilo se získat informace o uživateli ze skautISu', 'skautis-integration' ) );
		}

		return $user_detail;
	}

}
