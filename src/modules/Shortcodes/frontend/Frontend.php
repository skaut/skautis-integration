<?php

declare( strict_types=1 );

namespace SkautisIntegration\Modules\Shortcodes\Frontend;

use SkautisIntegration\Auth\SkautisLogin;
use SkautisIntegration\Rules\RulesManager;
use SkautisIntegration\Auth\WpLoginLogout;
use SkautisIntegration\Utils\Helpers;

final class Frontend {

	private $skautisLogin;
	private $rulesManager;
	private $wpLoginLogout;

	public function __construct( SkautisLogin $skautisLogin, RulesManager $rulesManager, WpLoginLogout $wpLoginLogout ) {
		$this->skautisLogin  = $skautisLogin;
		$this->rulesManager  = $rulesManager;
		$this->wpLoginLogout = $wpLoginLogout;
		$this->initHooks();
	}

	private function initHooks() {
		add_shortcode( 'skautis', [ $this, 'processShortcode' ] );
	}

	private function getLoginForm( bool $forceLogoutFromSkautis = false ): string {
		$loginUrlArgs = add_query_arg( 'noWpLogin', true, Helpers::getCurrentUrl() );
		if ( $forceLogoutFromSkautis ) {
			$loginUrlArgs = add_query_arg( 'logoutFromSkautis', true, $loginUrlArgs );
		}

		return '
		<div class="wp-core-ui">
			<p style="margin-bottom: 0.3em;">
				<a class="button button-primary button-hero pic-lilie"
				   href="' . $this->wpLoginLogout->getLoginUrl( $loginUrlArgs ) . '">' . __( 'Log in with skautIS', 'skautis-integration' ) . '</a>
			</p>
		</div>
		<br/>
		';
	}

	private function getLoginRequiredMessage(): string {
		return '<p>' . __( 'To view this content you must be logged in skautIS', 'skautis-integration' ) . '</p>';
	}

	private function getUnauthorizedMessage(): string {
		return '<p>' . __( 'You do not have permission to access this content', 'skautis-integration' ) . '</p>';
	}

	public function processShortcode( array $atts = [], string $content = '' ): string {
		if ( isset( $atts['rules'] ) && isset( $atts['content'] ) ) {

			if ( current_user_can( 'edit_' . get_post_type() . 's' ) ) {
				return $content;
			}

			if ( ! $this->skautisLogin->isUserLoggedInSkautis() ) {
				if ( $atts['content'] == 'showLogin' ) {
					return $this->getLoginRequiredMessage() . $this->getLoginForm();
				} else {
					return '';
				}
			}

			if ( $this->rulesManager->checkIfUserPassedRules( explode( ',', $atts['rules'] ) ) ) {
				return $content;
			} else {
				if ( $atts['content'] == 'showLogin' ) {
					return $this->getUnauthorizedMessage() . $this->getLoginForm( true );
				} else {
					return '';
				}
			}
		}

		return '';
	}

}
