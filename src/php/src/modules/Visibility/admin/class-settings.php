<?php
/**
 * Contains the Settings class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Modules\Visibility\Admin;

use Skautis_Integration\Utils\Helpers;

/**
 * Registers, handles and shows all settings for the Visibility module.
 *
 * @phan-constructor-used-for-side-effects
 */
final class Settings {

	/**
	 * Constructs the service and saves all dependencies.
	 */
	public function __construct() {
		self::init_hooks();
	}

	/**
	 * Intializes all hooks used by the object.
	 *
	 * @return void
	 */
	private static function init_hooks() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_menu', array( self::class, 'setup_setting_page' ), 25 );
		add_action( 'admin_init', array( self::class, 'setup_setting_fields' ) );
	}

	/**
	 * Adds an admin settings page for the Visibility module.
	 *
	 * @return void
	 */
	public static function setup_setting_page() {
		add_submenu_page(
			SKAUTIS_INTEGRATION_NAME,
			__( 'Viditelnost obsahu', 'skautis-integration' ),
			__( 'Viditelnost obsahu', 'skautis-integration' ),
			Helpers::get_skautis_manager_capability(),
			SKAUTIS_INTEGRATION_NAME . '_modules_visibility',
			array( self::class, 'print_setting_page' )
		);
	}

	/**
	 * Prints the admin settings page for the Visibility module.
	 *
	 * @return void
	 */
	public static function print_setting_page() {
		if ( ! Helpers::user_is_skautis_manager() ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'skautis-integration' ) );
		}

		settings_errors();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Nastavení viditelnosti obsahu', 'skautis-integration' ); ?></h1>
			<form method="POST" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
				<?php
				settings_fields( SKAUTIS_INTEGRATION_NAME . '_modules_visibility' );
				do_settings_sections( SKAUTIS_INTEGRATION_NAME . '_modules_visibility' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Adds Visibility module seetings to WordPress.
	 *
	 * @return void
	 */
	public static function setup_setting_fields() {
		add_settings_section(
			SKAUTIS_INTEGRATION_NAME . '_modules_visibility',
			'',
			static function () {
				echo '';
			},
			SKAUTIS_INTEGRATION_NAME . '_modules_visibility'
		);

		add_settings_field(
			SKAUTIS_INTEGRATION_NAME . '_modules_visibility_postTypes',
			__( 'Typy obsahu', 'skautis-integration' ),
			array( self::class, 'field_post_types' ),
			SKAUTIS_INTEGRATION_NAME . '_modules_visibility',
			SKAUTIS_INTEGRATION_NAME . '_modules_visibility'
		);

		add_settings_field(
			SKAUTIS_INTEGRATION_NAME . '_modules_visibility_visibilityMode',
			__( 'Způsob skrytí', 'skautis-integration' ),
			array( self::class, 'field_visibility_mode' ),
			SKAUTIS_INTEGRATION_NAME . '_modules_visibility',
			SKAUTIS_INTEGRATION_NAME . '_modules_visibility'
		);

		add_settings_field(
			SKAUTIS_INTEGRATION_NAME . '_modules_visibility_includeChildren',
			__( 'Podřízený obsah', 'skautis-integration' ),
			array( self::class, 'field_include_children' ),
			SKAUTIS_INTEGRATION_NAME . '_modules_visibility',
			SKAUTIS_INTEGRATION_NAME . '_modules_visibility'
		);

		register_setting(
			SKAUTIS_INTEGRATION_NAME . '_modules_visibility',
			SKAUTIS_INTEGRATION_NAME . '_modules_visibility_postTypes',
			array(
				'type'         => 'string',
				'show_in_rest' => false,
			)
		);

		register_setting(
			SKAUTIS_INTEGRATION_NAME . '_modules_visibility',
			SKAUTIS_INTEGRATION_NAME . '_modules_visibility_visibilityMode',
			array(
				'type'         => 'string',
				'show_in_rest' => false,
			)
		);

		register_setting(
			SKAUTIS_INTEGRATION_NAME . '_modules_visibility',
			SKAUTIS_INTEGRATION_NAME . '_modules_visibility_includeChildren',
			array(
				'type'         => 'string',
				'show_in_rest' => false,
			)
		);
	}

	/**
	 * Prints the settings field for choosing which post types to apply the Visibility module to.
	 *
	 * @return void
	 */
	public static function field_post_types() {
		$available_post_types = get_post_types(
			array(
				'public' => true,
			),
			'objects'
		);
		// TODO: Unused?
		$post_types = (array) get_option( SKAUTIS_INTEGRATION_NAME . '_modules_visibility_postTypes', array() );
		?>
		<?php
		foreach ( $available_post_types as $post_type ) {
			echo '<label><input type="checkbox" name="' . esc_attr( SKAUTIS_INTEGRATION_NAME ) . '_modules_visibility_postTypes[]" value="' . esc_attr( $post_type->name ) . '" ' . checked( true, in_array( $post_type->name, $post_types, true ), false ) . '/><span>' . esc_html( $post_type->label ) . '</span></label><br/>';
		}
		?>
		<div>
			<em><?php esc_html_e( 'U vybraných typů obsahu bude možné zadávat pravidla pro viditelnost obsahu.', 'skautis-integration' ); ?></em><br/>
			<em><?php esc_html_e( 'Pokud není uživatel přihlášen ve skautISu nebo nesplní daná pravidla - bude pro něj obsah skrytý.', 'skautis-integration' ); ?></em><br/>
			<em><?php esc_html_e( 'Uživatelé přihlášení do WordPressu s právy pro úpravu daného obsahu jej uvidí vždy, bez ohledu na jejich přihlášení do skautISu či splnění daných pravidel.', 'skautis-integration' ); ?></em>
		</div>
		<?php
	}

	/**
	 * Prints the settings field for choosing between hiding the whole post or page, or just its content.
	 *
	 * @return void
	 */
	public static function field_visibility_mode() {
		$visibility_mode = get_option( SKAUTIS_INTEGRATION_NAME . '_modules_visibility_visibilityMode', 'full' );
		?>
		<label><input type="radio" name="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_modules_visibility_visibilityMode"
					value="full" <?php checked( 'full', $visibility_mode ); ?> /><span><?php esc_html_e( 'Skrýt celý příspěvek / stránku / ...', 'skautis-integration' ); ?></span></label>
		<br/>
		<label><input type="radio" name="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_modules_visibility_visibilityMode"
					value="content" <?php checked( 'content', $visibility_mode ); ?> /><span><?php esc_html_e( 'Skrýt pouze obsah', 'skautis-integration' ); ?></span></label>
		<p>
			<em><?php esc_html_e( 'Nastavení můžete změnit u jednotlivých typů obsahu dle potřeby.', 'skautis-integration' ); ?></em>
		</p>
		<?php
	}

	/**
	 * Prints the settings field for choosing whether to apply visibility rules to child posts and pages.
	 *
	 * @return void
	 */
	public static function field_include_children() {
		$include_children = get_option( SKAUTIS_INTEGRATION_NAME . '_modules_visibility_includeChildren', 0 );
		?>
		<label><input type="checkbox" name="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_modules_visibility_includeChildren"
					value="1" <?php checked( 1, $include_children ); ?> /><span><?php esc_html_e( 'Použít vybraná pravidla i na podřízený obsah', 'skautis-integration' ); ?></span></label>
		<br/>
		<p>
			<em><?php esc_html_e( 'Nastavení můžete změnit u jednotlivých typů obsahu dle potřeby.', 'skautis-integration' ); ?></em>
		</p>
		<?php
	}


}
