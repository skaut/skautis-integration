<?php

declare( strict_types=1 );

namespace SkautisIntegration\Repository;

use SkautisIntegration\Auth\SkautisGateway;
use SkautisIntegration\Utils\Helpers;

class Users {

	protected $skautisGateway;

	public function __construct( SkautisGateway $skautisGateway ) {
		$this->skautisGateway = $skautisGateway;
	}

	protected function getSearchUserString(): string {
		$searchUserString = '';

		if ( isset( $_GET['skautisSearchUsers'] ) && $_GET['skautisSearchUsers'] ) {
			$searchUserString = sanitize_text_field( $_GET['skautisSearchUsers'] );
		} elseif ( isset( $_GET['ReturnUrl'] ) ) {
			$searchUserString = Helpers::getVariableFromUrl( $_GET['ReturnUrl'], 'skautisSearchUsers' );
		}

		return $searchUserString;
	}

	public function getConnectedWpUsers(): array {
		$usersData = [];

		$connectedWpUsers = new \WP_User_Query( [
			'meta_query'  => [
				[
					'key'     => 'skautisUserId_' . $this->skautisGateway->getEnv(),
					'type'    => 'numeric',
					'value'   => 0,
					'compare' => '>'
				]
			],
			'count_total' => false
		] );

		foreach ( $users = $connectedWpUsers->get_results() as $user ) {
			$usersData[ get_user_meta( $user->ID, 'skautisUserId_' . $this->skautisGateway->getEnv(), true ) ] = [
				'id'   => $user->ID,
				'name' => $user->display_name
			];
		}

		return $usersData;
	}

	public function getConnectableWpUsers() {
		$ConnectableWpUsers = new \WP_User_Query( [
			'meta_query'  => [
				'relation' => 'OR',
				[
					'key'     => 'skautisUserId_' . $this->skautisGateway->getEnv(),
					'compare' => 'NOT EXISTS'
				],
				[
					'key'     => 'skautisUserId_' . $this->skautisGateway->getEnv(),
					'value'   => '',
					'compare' => '='
				]
			],
			'count_total' => false
		] );

		return $ConnectableWpUsers->get_results();
	}

	public function getUsers(): array {
		$users     = [];
		$eventType = '';
		$eventId   = 0;

		if ( ! $this->skautisGateway->isInitialized() ) {
			return [
				'users'     => $users,
				'eventType' => $eventType
			];
		}

		$currentUserRoles = $this->skautisGateway->getSkautisInstance()->UserManagement->UserRoleAll( [
			'ID_Login' => $this->skautisGateway->getSkautisInstance()->getUser()->getLoginId(),
			'ID_User'  => $this->skautisGateway->getSkautisInstance()->UserManagement->UserDetail()->ID
		] );
		$currentUserRole  = $this->skautisGateway->getSkautisInstance()->getUser()->getRoleId();

		// different procedure for roles associated with events
		foreach ( $currentUserRoles as $role ) {
			if ( $role->ID === $currentUserRole && isset( $role->Key ) ) {
				$words = preg_split( "~(?=[A-Z])~", $role->Key );
				if ( ! empty( $words ) && isset( $words[1], $words[2] ) && $words[1] === 'Event' ) {
					$eventType = $words[2];

					$userDetail        = $this->skautisGateway->getSkautisInstance()->UserManagement->UserDetail();
					$currentUserEvents = $this->skautisGateway->getSkautisInstance()->Events->EventAllPerson( [
						'ID_Person' => $userDetail->ID_Person
					] );

					foreach ( $currentUserEvents as $event ) {
						if ( $event->ID_Group === $role->ID_Group ) {
							$eventUrl = $this->skautisGateway->getSkautisInstance()->Events->EventDetail( [
								'ID' => $event->ID
							] );
							if ( isset( $eventUrl->UrlDetail ) ) {
								preg_match( "~ID=(\d+)$~", $eventUrl->UrlDetail, $regResult );
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
			if ( $eventType === 'Congress' ) {
				/*$participants = $this->skautisGateway->getSkautisInstance()->Events->ParticipantAllPerson( [
					'ID_Event' . $eventType => $eventId
				] );
				if ( ! is_array( $participants ) || count( $participants ) === 0 ) {
					$participants = $this->skautisGateway->getSkautisInstance()->Events->ParticipantAllUstredi( [
						'ID_Event' . $eventType => $eventId
					] );
				}*/
				$participants = null;
			} else {
				$methodName   = 'Participant' . $eventType . 'All';
				$participants = $this->skautisGateway->getSkautisInstance()->Events->$methodName( [
					'ID_Event' . $eventType => $eventId
				] );
			}

			if ( is_array( $participants ) ) {
				$users = array_map( function ( $participant ) {
					$user = new \stdClass();

					$user->id        = $participant->ID;
					$user->personId  = $participant->ID_Person;
					$user->firstName = $participant->Person;
					$user->lastName  = '';
					$user->nickName  = '';

					preg_match( "~([^\s]+)\s([^\s]+)(\s\((.*)\))~", $participant->Person, $regResult );

					if ( $regResult && isset( $regResult[1], $regResult[2] ) ) {
						$user->firstName = $regResult[2];
						$user->lastName  = $regResult[1];
						if ( isset( $regResult[4] ) && $regResult[4] ) {
							$user->nickName = $regResult[4];
						}
					}

					if ( isset( $participant->PersonEmail ) && ! empty( $participant->PersonEmail ) ) {
						$emails = preg_split( "~(?=\,)~x", $participant->PersonEmail );
						if ( ! empty( $emails ) && isset( $emails[0] ) ) {
							$user->email = $emails[0];
						}
					} else {
						$user->email = '';
					}

					$user->UserName = $user->email;

					return $user;
				}, $participants );
			}
		}

		// standard get all users procedure
		if ( empty( $users ) ) {

			$searchUserString = $this->getSearchUserString();

			$skautisUsers = $this->skautisGateway->getSkautisInstance()->UserManagement->userAll( [
				'DisplayName' => $searchUserString
			] );

			if ( is_array( $skautisUsers ) ) {
				$users = array_map( function ( $skautisUser ) {
					$user = new \stdClass();

					$user->id        = $skautisUser->ID;
					$user->UserName  = $skautisUser->UserName;
					$user->personId  = $skautisUser->ID_Person;
					$user->firstName = $skautisUser->DisplayName;
					$user->lastName  = '';
					$user->nickName  = '';

					preg_match( "~([^\s]+)\s([^\s]+)(\s\((.*)\))~", $skautisUser->DisplayName, $regResult );

					if ( $regResult && isset( $regResult[1], $regResult[2] ) ) {
						$user->firstName = $regResult[2];
						$user->lastName  = $regResult[1];
					}
					if ( isset( $regResult[4] ) && $regResult[4] ) {
						$user->nickName = $regResult[4];
					}

					$user->email = '';

					return $user;
				}, $skautisUsers );
			}
		}

		return [
			'users'     => $users,
			'eventType' => $eventType
		];
	}

	public function getUserDetail( int $skautisUserId ): array {
		$userDetail = [];

		$users = $this->getUsers();

		if ( $users['eventType'] ) {
			foreach ( (array) $users['users'] as $user ) {
				if ( $user->id === $skautisUserId ) {
					$userDetail = [
						'id'        => $skautisUserId,
						'UserName'  => $user->UserName,
						'personId'  => $user->personId,
						'email'     => $user->email,
						'firstName' => $user->firstName,
						'lastName'  => $user->lastName,
						'nickName'  => $user->nickName
					];
				}
			}
		} else {
			foreach ( (array) $users['users'] as $user ) {
				if ( $user->id === $skautisUserId ) {
					$personDetail = $this->skautisGateway->getSkautisInstance()->OrganizationUnit->PersonDetail( [
						'ID' => $user->personId
					] );

					$userDetail = [
						'id'        => $skautisUserId,
						'UserName'  => $user->UserName,
						'personId'  => $user->personId,
						'email'     => $personDetail->Email,
						'firstName' => $user->firstName,
						'lastName'  => $user->lastName,
						'nickName'  => $user->nickName
					];
				}
			}
		}

		if ( empty( $userDetail ) ) {
			throw new \Exception( __( 'Nepodařilo se získat informace o uživateli ze skautISu', 'skautis-integration' ) );
		}

		return $userDetail;
	}

}
