<?php
/**
 * Contains the Settings class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Modules\Visibility\Admin;

use Skautis_Integration\Admin\Settings as Main_Settings;

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

		add_action( 'admin_init', array( self::class, 'setup_setting_fields' ) );
	}

	/**
	 * Adds Visibility module seetings to WordPress.
	 *
	 * @return void
	 */
	public static function setup_setting_fields() {
		add_settings_section(
			SKAUTIS_INTEGRATION_NAME . '_modules_visibility',
			__( 'Content visibility', 'skautis-integration' ),
			static function () {
				echo '<p>' . esc_html__( 'If a user is not logged in to SkautIS or does not pass the rules, the content will be hidden from them.', 'skautis-integration' ) . ' ' . esc_html__( 'Users logged in to WordPress who can edit the content will always see it, regardless of their SkautIS login or the rules.', 'skautis-integration' ) . '</p>';
				echo '<p>' . esc_html__( 'The rules of a whole post or page are set in the post settings of the block editor, the rules of a single block in its block settings.', 'skautis-integration' ) . '</p>';
			},
			SKAUTIS_INTEGRATION_NAME . '_modules_visibility',
			Main_Settings::get_card_section_args()
		);

		add_settings_field(
			SKAUTIS_INTEGRATION_NAME . '_modules_visibility_postTypes',
			__( 'Content types', 'skautis-integration' ),
			array( self::class, 'field_post_types' ),
			SKAUTIS_INTEGRATION_NAME . '_modules_visibility',
			SKAUTIS_INTEGRATION_NAME . '_modules_visibility'
		);

		add_settings_field(
			SKAUTIS_INTEGRATION_NAME . '_modules_visibility_visibilityMode',
			__( 'Hiding method', 'skautis-integration' ),
			array( self::class, 'field_visibility_mode' ),
			SKAUTIS_INTEGRATION_NAME . '_modules_visibility',
			SKAUTIS_INTEGRATION_NAME . '_modules_visibility'
		);

		add_settings_field(
			SKAUTIS_INTEGRATION_NAME . '_modules_visibility_includeChildren',
			__( 'Child content', 'skautis-integration' ),
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
		echo '<fieldset class="skautis-admin-checkbox-list"><legend class="screen-reader-text">' . esc_html__( 'Content types', 'skautis-integration' ) . '</legend>';
		foreach ( $available_post_types as $post_type ) {
			if ( $post_type instanceof \WP_Post_Type ) {
				echo '<label><input type="checkbox" name="' . esc_attr( SKAUTIS_INTEGRATION_NAME ) . '_modules_visibility_postTypes[]" value="' . esc_attr( $post_type->name ) . '" ' . checked( true, in_array( $post_type->name, $post_types, true ), false ) . '/><span>' . esc_html( $post_type->label ) . '</span></label>';
			}
		}
		echo '</fieldset>';
		?>
		<p class="description"><?php esc_html_e( 'Visibility rules can be set for the selected content types.', 'skautis-integration' ); ?></p>
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
		<fieldset>
		<legend class="screen-reader-text"><?php esc_html_e( 'Hiding method', 'skautis-integration' ); ?></legend>
		<label><input type="radio" name="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_modules_visibility_visibilityMode"
					value="full" <?php checked( 'full', $visibility_mode ); ?> /><span><?php esc_html_e( 'Hide the whole post / page / …', 'skautis-integration' ); ?></span></label>
		<br/>
		<label><input type="radio" name="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_modules_visibility_visibilityMode"
					value="content" <?php checked( 'content', $visibility_mode ); ?> /><span><?php esc_html_e( 'Hide only the content', 'skautis-integration' ); ?></span></label>
		<p class="description"><?php esc_html_e( 'You can change this setting for individual content as needed.', 'skautis-integration' ); ?></p>
		</fieldset>
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
		<fieldset>
		<legend class="screen-reader-text"><?php esc_html_e( 'Child content', 'skautis-integration' ); ?></legend>
		<label><input type="checkbox" name="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_modules_visibility_includeChildren"
					value="1" <?php checked( 1, $include_children ); ?> /><span><?php esc_html_e( 'Apply the selected rules to child content as well', 'skautis-integration' ); ?></span></label>
		<p class="description"><?php esc_html_e( 'You can change this setting for individual content as needed.', 'skautis-integration' ); ?></p>
		</fieldset>
		<?php
	}
}
