<?php

declare( strict_types=1 );

namespace SkautisIntegration\Repository;

use SkautisIntegration\Auth\Skautis_Gateway;
use SkautisIntegration\Utils\Helpers;

class Users {

	protected $skautisGateway;

	public function __construct( Skautis_Gateway $skautisGateway ) {
		$this->skautisGateway = $skautisGateway;
	}

	protected function get_search_user_string(): string {
		$searchUserString = '';

		$returnUrl = Helpers::getReturnUrl();
		if (
			isset( $_GET[ SKAUTISINTEGRATION_NAME . '_skautis_search_user_nonce' ] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET[ SKAUTISINTEGRATION_NAME . '_skautis_search_user_nonce' ] ) ), SKAUTISINTEGRATION_NAME . '_skautis_search_user' ) &&
			isset( $_GET['skautisSearchUsers'] ) &&
			'' !== $_GET['skautisSearchUsers']
		) {
			$searchUserString = sanitize_text_field( wp_unslash( $_GET['skautisSearchUsers'] ) );
		} elseif ( ! is_null( $returnUrl ) ) {
			$searchUserString = Helpers::getVariableFromUrl( $returnUrl, 'skautisSearchUsers' );
		}

		return $searchUserString;
	}

	public function get_connected_wp_users(): array {
		$usersData = array();

		$connectedWpUsers = new \WP_User_Query(
			array(
				'meta_query'  => array(
					array(
						'key'     => 'skautisUserId_' . $this->skautisGateway->get_env(),
						'type'    => 'numeric',
						'value'   => 0,
						'compare' => '>',
					),
				),
				'count_total' => false,
			)
		);

		foreach ( $connectedWpUsers->get_results() as $user ) {
			$usersData[ get_user_meta( $user->ID, 'skautisUserId_' . $this->skautisGateway->get_env(), true ) ] = array(
				'id'   => $user->ID,
				'name' => $user->display_name,
			);
		}

		return $usersData;
	}

	public function get_connectable_wp_users() {
		$ConnectableWpUsers = new \WP_User_Query(
			array(
				'meta_query'  => array(
					'relation' => 'OR',
					array(
						'key'     => 'skautisUserId_' . $this->skautisGateway->get_env(),
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => 'skautisUserId_' . $this->skautisGateway->get_env(),
						'value'   => '',
						'compare' => '=',
					),
				),
				'count_total' => false,
			)
		);

		return $ConnectableWpUsers->get_results();
	}

	public function get_users(): array {
		$users     = array();
		$eventType = '';
		$eventId   = 0;

		if ( ! $this->skautisGateway->isInitialized() ) {
			return array(
				'users'     => $users,
				'eventType' => $eventType,
			);
		}

		$currentUserRoles = $this->skautisGateway->get_skautis_instance()->UserManagement->UserRoleAll(
			array(
				'ID_Login' => $this->skautisGateway->get_skautis_instance()->getUser()->getLoginId(),
				'ID_User'  => $this->skautisGateway->get_skautis_instance()->UserManagement->UserDetail()->ID,
			)
		);
		$currentUserRole  = $this->skautisGateway->get_skautis_instance()->getUser()->getRoleId();

		// different procedure for roles associated with events
		foreach ( $currentUserRoles as $role ) {
			if ( $role->ID === $currentUserRole && isset( $role->Key ) ) {
				$words = preg_split( '~(?=[A-Z])~', $role->Key );
				if ( ! empty( $words ) && isset( $words[1], $words[2] ) && 'Event' === $words[1] ) {
					$eventType = $words[2];

					$userDetail        = $this->skautisGateway->get_skautis_instance()->UserManagement->UserDetail();
					$currentUserEvents = $this->skautisGateway->get_skautis_instance()->Events->EventAllPerson(
						array(
							'ID_Person' => $userDetail->ID_Person,
						)
					);

					foreach ( $currentUserEvents as $event ) {
						if ( $event->ID_Group === $role->ID_Group ) {
							$eventUrl = $this->skautisGateway->get_skautis_instance()->Events->EventDetail(
								array(
									'ID' => $event->ID,
								)
							);
							if ( isset( $eventUrl->UrlDetail ) ) {
								preg_match( '~ID=(\d+)$~', $eventUrl->UrlDetail, $regResult );
								if ( $regResult && isset( $regResult[1] ) ) {
									$eventId = $regResult[1];
								}
							}
						}
					}
				}
			}
		}

		// different procedure for roles associated with events
		if ( $eventType && $eventId ) {
			if ( 'Congress' === $eventType ) {
				$participants = null;
			} else {
				$methodName   = 'Participant' . $eventType . 'All';
				$participants = $this->skautisGateway->get_skautis_instance()->Events->$methodName(
					array(
						'ID_Event' . $eventType => $eventId,
					)
				);
			}

			if ( is_array( $participants ) ) {
				$users = array_map(
					function ( $participant ) {
						$user = new \stdClass();

						$user->id        = $participant->ID;
						$user->personId  = $participant->ID_Person;
						$user->firstName = $participant->Person;
						$user->lastName  = '';
						$user->nickName  = '';

						preg_match( '~([^\s]+)\s([^\s]+)(\s\((.*)\))~', $participant->Person, $regResult );

						if ( $regResult && isset( $regResult[1], $regResult[2] ) ) {
							$user->firstName = $regResult[2];
							$user->lastName  = $regResult[1];
							if ( isset( $regResult[4] ) && $regResult[4] ) {
								$user->nickName = $regResult[4];
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

		// standard get all users procedure
		if ( empty( $users ) ) {
			$searchUserString = $this->get_search_user_string();

			$skautisUsers = $this->skautisGateway->get_skautis_instance()->UserManagement->userAll(
				array(
					'DisplayName' => $searchUserString,
				)
			);

			if ( is_array( $skautisUsers ) ) {
				$users = array_map(
					function ( $skautisUser ) {
						$user = new \stdClass();

						$user->id        = $skautisUser->ID;
						$user->UserName  = $skautisUser->UserName;
						$user->personId  = $skautisUser->ID_Person;
						$user->firstName = $skautisUser->DisplayName;
						$user->lastName  = '';
						$user->nickName  = '';

						preg_match( '~([^\s]+)\s([^\s]+)(\s\((.*)\))~', $skautisUser->DisplayName, $regResult );

						if ( $regResult && isset( $regResult[1], $regResult[2] ) ) {
							$user->firstName = $regResult[2];
							$user->lastName  = $regResult[1];
						}
						if ( isset( $regResult[4] ) && $regResult[4] ) {
							$user->nickName = $regResult[4];
						}

						$user->email = '';

						return $user;
					},
					$skautisUsers
				);
			}
		}

		return array(
			'users'     => $users,
			'eventType' => $eventType,
		);
	}

	public function get_user_detail( int $skautisUserId ): array {
		$userDetail = array();

		$users = $this->get_users();

		if ( $users['eventType'] ) {
			foreach ( (array) $users['users'] as $user ) {
				if ( $user->id === $skautisUserId ) {
					$userDetail = array(
						'id'        => $skautisUserId,
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
				if ( $user->id === $skautisUserId ) {
					$personDetail = $this->skautisGateway->get_skautis_instance()->OrganizationUnit->PersonDetail(
						array(
							'ID' => $user->personId,
						)
					);

					$userDetail = array(
						'id'        => $skautisUserId,
						'UserName'  => $user->UserName,
						'personId'  => $user->personId,
						'email'     => $personDetail->Email,
						'firstName' => $user->firstName,
						'lastName'  => $user->lastName,
						'nickName'  => $user->nickName,
					);
				}
			}
		}

		if ( empty( $userDetail ) ) {
			throw new \Exception( __( 'Nepodařilo se získat informace o uživateli ze skautISu', 'skautis-integration' ) );
		}

		return $userDetail;
	}

}
