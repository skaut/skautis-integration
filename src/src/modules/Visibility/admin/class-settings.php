<?php

declare( strict_types=1 );

namespace SkautisIntegration\Modules\Visibility\Admin;

use SkautisIntegration\Utils\Helpers;

final class Settings {

	public function __construct() {
		$this->init_hooks();
	}

	private function init_hooks() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_menu', array( $this, 'setup_setting_page' ), 25 );
		add_action( 'admin_init', array( $this, 'setup_setting_fields' ) );
	}

	public function setup_setting_page() {
		add_submenu_page(
			SKAUTISINTEGRATION_NAME,
			__( 'Viditelnost obsahu', 'skautis-integration' ),
			__( 'Viditelnost obsahu', 'skautis-integration' ),
			Helpers::getSkautisManagerCapability(),
			SKAUTISINTEGRATION_NAME . '_modules_visibility',
			array( $this, 'print_setting_page' )
		);
	}

	public function print_setting_page() {
		if ( ! Helpers::userIsSkautisManager() ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'skautis-integration' ) );
		}

		settings_errors();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Nastavení viditelnosti obsahu', 'skautis-integration' ); ?></h1>
			<form method="POST" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
				<?php
				settings_fields( SKAUTISINTEGRATION_NAME . '_modules_visibility' );
				do_settings_sections( SKAUTISINTEGRATION_NAME . '_modules_visibility' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public function setup_setting_fields() {
		add_settings_section(
			SKAUTISINTEGRATION_NAME . '_modules_visibility',
			'',
			function () {
				echo '';
			},
			SKAUTISINTEGRATION_NAME . '_modules_visibility'
		);

		add_settings_field(
			SKAUTISINTEGRATION_NAME . '_modules_visibility_postTypes',
			__( 'Typy obsahu', 'skautis-integration' ),
			array( $this, 'field_post_types' ),
			SKAUTISINTEGRATION_NAME . '_modules_visibility',
			SKAUTISINTEGRATION_NAME . '_modules_visibility'
		);

		add_settings_field(
			SKAUTISINTEGRATION_NAME . '_modules_visibility_visibilityMode',
			__( 'Způsob skrytí', 'skautis-integration' ),
			array( $this, 'field_visibility_mode' ),
			SKAUTISINTEGRATION_NAME . '_modules_visibility',
			SKAUTISINTEGRATION_NAME . '_modules_visibility'
		);

		add_settings_field(
			SKAUTISINTEGRATION_NAME . '_modules_visibility_includeChildren',
			__( 'Podřízený obsah', 'skautis-integration' ),
			array( $this, 'field_include_children' ),
			SKAUTISINTEGRATION_NAME . '_modules_visibility',
			SKAUTISINTEGRATION_NAME . '_modules_visibility'
		);

		register_setting(
			SKAUTISINTEGRATION_NAME . '_modules_visibility',
			SKAUTISINTEGRATION_NAME . '_modules_visibility_postTypes',
			array(
				'type'         => 'string',
				'show_in_rest' => false,
			)
		);

		register_setting(
			SKAUTISINTEGRATION_NAME . '_modules_visibility',
			SKAUTISINTEGRATION_NAME . '_modules_visibility_visibilityMode',
			array(
				'type'         => 'string',
				'show_in_rest' => false,
			)
		);

		register_setting(
			SKAUTISINTEGRATION_NAME . '_modules_visibility',
			SKAUTISINTEGRATION_NAME . '_modules_visibility_includeChildren',
			array(
				'type'         => 'string',
				'show_in_rest' => false,
			)
		);
	}

	public function field_post_types() {
		$availablePostTypes = get_post_types(
			array(
				'public' => true,
			),
			'objects'
		);
		$postTypes          = (array) get_option( SKAUTISINTEGRATION_NAME . '_modules_visibility_postTypes', array() );
		?>
		<?php
		foreach ( $availablePostTypes as $postType ) {
			echo '<label><input type="checkbox" name="' . esc_attr( SKAUTISINTEGRATION_NAME ) . '_modules_visibility_postTypes[]" value="' . esc_attr( $postType->name ) . '" ' . checked( true, in_array( $postType->name, $postTypes, true ), false ) . '/><span>' . esc_html( $postType->label ) . '</span></label><br/>';
		}
		?>
		<div>
			<em><?php esc_html_e( 'U vybraných typů obsahu bude možné zadávat pravidla pro viditelnost obsahu.', 'skautis-integration' ); ?></em><br/>
			<em><?php esc_html_e( 'Pokud není uživatel přihlášen ve skautISu nebo nesplní daná pravidla - bude pro něj obsah skrytý.', 'skautis-integration' ); ?></em><br/>
			<em><?php esc_html_e( 'Uživatelé přihlášení do WordPressu s právy pro úpravu daného obsahu jej uvidí vždy, bez ohledu na jejich přihlášení do skautISu či splnění daných pravidel.', 'skautis-integration' ); ?></em>
		</div>
		<?php
	}

	public function field_visibility_mode() {
		$visibilityMode = get_option( SKAUTISINTEGRATION_NAME . '_modules_visibility_visibilityMode', 'full' );
		?>
		<label><input type="radio" name="<?php echo esc_attr( SKAUTISINTEGRATION_NAME ); ?>_modules_visibility_visibilityMode"
					value="full" <?php checked( 'full', $visibilityMode ); ?> /><span><?php esc_html_e( 'Skrýt celý příspěvek / stránku / ...', 'skautis-integration' ); ?></span></label>
		<br/>
		<label><input type="radio" name="<?php echo esc_attr( SKAUTISINTEGRATION_NAME ); ?>_modules_visibility_visibilityMode"
					value="content" <?php checked( 'content', $visibilityMode ); ?> /><span><?php esc_html_e( 'Skrýt pouze obsah', 'skautis-integration' ); ?></span></label>
		<p>
			<em><?php esc_html_e( 'Nastavení můžete změnit u jednotlivých typů obsahu dle potřeby.', 'skautis-integration' ); ?></em>
		</p>
		<?php
	}

	public function field_include_children() {
		$includeChildren = get_option( SKAUTISINTEGRATION_NAME . '_modules_visibility_includeChildren', 0 );
		?>
		<label><input type="checkbox" name="<?php echo esc_attr( SKAUTISINTEGRATION_NAME ); ?>_modules_visibility_includeChildren"
					value="1" <?php checked( 1, $includeChildren ); ?> /><span><?php esc_html_e( 'Použít vybraná pravidla i na podřízený obsah', 'skautis-integration' ); ?></span></label>
		<br/>
		<p>
			<em><?php esc_html_e( 'Nastavení můžete změnit u jednotlivých typů obsahu dle potřeby.', 'skautis-integration' ); ?></em>
		</p>
		<?php
	}


}
