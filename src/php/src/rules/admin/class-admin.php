<?php
/**
 * Contains the Admin class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Rules;

use Skautis_Integration\Auth\Skautis_Gateway;
use Skautis_Integration\Auth\WP_Login_Logout;
use Skautis_Integration\Utils\Helpers;

/**
 * Adds the UI for rules management.
 *
 * @phan-constructor-used-for-side-effects
 */
final class Admin {

	/**
	 * A link to the Rules_Manager service instance.
	 *
	 * @var Rules_Manager
	 */
	private $rules_manager;

	/**
	 * A link to the WP_Login_Logout service instance.
	 *
	 * @var WP_Login_Logout
	 */
	private $wp_login_logout;

	/**
	 * A link to the Skautis_Gateway service instance.
	 *
	 * @var Skautis_Gateway
	 */
	private $skautis_gateway;

	/**
	 * TODO: Unused?
	 *
	 * @var string
	 */
	private $admin_dir_url = '';

	/**
	 * Constructs the service and saves all dependencies.
	 *
	 * @param Rules_Manager   $rules_manager An injected Rules_Manager service instance.
	 * @param WP_Login_Logout $wp_login_logout An injected WP_Login_Logout service instance.
	 * @param Skautis_Gateway $skautis_gateway An injected Skautis_Gateway service instance.
	 */
	public function __construct( Rules_Manager $rules_manager, WP_Login_Logout $wp_login_logout, Skautis_Gateway $skautis_gateway ) {
		$this->rules_manager   = $rules_manager;
		$this->wp_login_logout = $wp_login_logout;
		$this->skautis_gateway = $skautis_gateway;
		$this->admin_dir_url   = plugin_dir_url( __FILE__ ) . 'public/';
		new Columns();
		$this->init_hooks();
	}

	/**
	 * Intializes all hooks used by the object.
	 */
	private function init_hooks() {
		add_action( 'add_meta_boxes', array( self::class, 'add_metabox_for_rules_field' ) );
		add_action( 'save_post', array( self::class, 'save_rules_custom_field' ) );

		add_action( 'edit_form_after_title', array( $this, 'add_rules_ui' ) );

		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_scripts' ) );

		add_action( 'admin_footer', array( $this, 'init_rules_builder' ), 100 );
	}

	/**
	 * Adds the rules metabox to WordPress.
	 *
	 * This function gets called for all post types, so it needs to check before adding the metabox.
	 *
	 * @param string $post_type The current post type.
	 */
	public static function add_metabox_for_rules_field( string $post_type ) {
		if ( Rules_Init::RULES_TYPE_SLUG === $post_type ) {
			add_meta_box(
				SKAUTIS_INTEGRATION_NAME . '_rules_metabox',
				__( 'skautIS pravidla', 'skautis-integration' ),
				array( self::class, 'rules_field_content' ),
				Rules_Init::RULES_TYPE_SLUG
			);
		}
	}

	/**
	 * Saves the rules data.
	 *
	 * @param int $post_id The ID of the rule post.
	 */
	public static function save_rules_custom_field( int $post_id ) {
		if ( ! isset( $_POST[ SKAUTIS_INTEGRATION_NAME . '_rules_metabox_nonce' ] ) || false === wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ SKAUTIS_INTEGRATION_NAME . '_rules_metabox_nonce' ] ) ), SKAUTIS_INTEGRATION_NAME . '_rules_metabox' ) ) {
			return;
		}

		if ( array_key_exists( SKAUTIS_INTEGRATION_NAME . '_rules_data', $_POST ) ) {
			update_post_meta(
				$post_id,
				SKAUTIS_INTEGRATION_NAME . '_rules_data',
				sanitize_meta( SKAUTIS_INTEGRATION_NAME . '_rules_data', wp_unslash( $_POST[ SKAUTIS_INTEGRATION_NAME . '_rules_data' ] ), 'post' )
			);
		}
	}

	/**
	 * Prints the rules metabox.
	 *
	 * TODO: This box is hidden, why is it here?
	 *
	 * @param \WP_Post $post The post to print the metabox for.
	 */
	public static function rules_field_content( \WP_Post $post ) {
		wp_nonce_field( SKAUTIS_INTEGRATION_NAME . '_rules_metabox', SKAUTIS_INTEGRATION_NAME . '_rules_metabox_nonce' );
		?>
		<textarea id="query_builder_values" class=""
				name="<?php echo esc_attr( SKAUTIS_INTEGRATION_NAME ); ?>_rules_data"><?php echo esc_html( get_post_meta( $post->ID, SKAUTIS_INTEGRATION_NAME . '_rules_data', true ) ); ?></textarea>
		<?php
	}

	/**
	 * TODO: Unused?
	 *
	 * @param array $fields A list of already registered fields.
	 */
	public static function add_rules_field_to_revisions( array $fields ): array {
		$fields[ SKAUTIS_INTEGRATION_NAME . '_rules_data' ] = __( 'skautIS Pravidla', 'skautis-integration' );

		return $fields;
	}

	/**
	 * TODO: Unused?
	 *
	 * @param never    $value Unused.
	 * @param never    $field_name Unused.
	 * @param \WP_Post $post The post to get the metadata from.
	 */
	public static function get_rules_field_value( $value, $field_name, \WP_Post $post ) {
		return get_metadata( 'post', $post->ID, SKAUTIS_INTEGRATION_NAME . '_rules_data', true );
	}

	/**
	 * TODO: Unused?
	 *
	 * @param int $post_id The ID of the post in question.
	 * @param int $revision_id The ID of the revision to restore.
	 */
	public static function restore_revision_for_rules_field( int $post_id, int $revision_id ) {
		$post     = get_post( $post_id );
		$revision = get_post( $revision_id );
		if ( Rules_Init::RULES_TYPE_SLUG === $post->post_type ) {
			$meta = get_metadata( 'post', $revision->ID, SKAUTIS_INTEGRATION_NAME . '_rules_data', true );
			if ( false !== $meta ) {
				update_post_meta( $post_id, SKAUTIS_INTEGRATION_NAME . '_rules_data', $meta );
			}
		}
	}

	/**
	 * Prints the rules query builder UI.
	 *
	 * @param \WP_Post $post Unused. @unused-param
	 */
	public function add_rules_ui( \WP_Post $post ) {
		if ( get_current_screen()->id !== Rules_Init::RULES_TYPE_SLUG || get_post_type() !== Rules_Init::RULES_TYPE_SLUG ) {
			return;
		}
		?>
		<div class="meta-box-sortables">
			<div class="postbox" style="margin-top: 2.5em;">
				<button type="button" class="handlediv" aria-expanded="true"><span
						class="screen-reader-text"><?php esc_html_e( 'Zobrazit / skrýt panel: Pravidla', 'skautis-integration' ); ?></span><span
						class="toggle-indicator" aria-hidden="true"></span></button>
				<h2 class="hndle ui-sortable-handle">
					<span><?php esc_html_e( 'Zadejte podmínky pro splnění pravidla', 'skautis-integration' ); ?></span>
				</h2>
				<div class="inside" style="padding: 0.75em 1.5em 1.25em 1.5em;">
					<label class="screen-reader-text"
						for="post_author_override"><?php esc_html_e( 'Zadejte podmínky pro splnění pravidla', 'skautis-integration' ); ?></label>
					<?php
					if ( ! $this->skautis_gateway->is_initialized() ) {
						/* translators: 1: Start of link to the settings 2: End of link to the settings */
						printf( esc_html__( 'Vyberte v %1$snastavení%2$s pluginu typ prostředí skautISu', 'skautis-integration' ), '<a href="' . esc_url( admin_url( 'admin.php?page=' . SKAUTIS_INTEGRATION_NAME ) ) . '">', '</a>' );
					} elseif ( ! $this->skautis_gateway->get_skautis_instance()->getUser()->isLoggedIn( true ) ) {
						echo '<h4><a href="' . esc_url( $this->wp_login_logout->get_login_url( add_query_arg( 'noWpLogin', true, Helpers::get_current_url() ) ) ) . '">' . esc_html__( 'Pro správu podmínek je nutné se přihlásit do skautISu', 'skautis-integration' ) . '</a></h4>';
					} else {
						echo '<div id="query_builder"></div>';
					}
					?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Enqueues styles for rules management.
	 */
	public static function enqueue_styles() {
		if ( get_current_screen()->id !== Rules_Init::RULES_TYPE_SLUG || get_post_type() !== Rules_Init::RULES_TYPE_SLUG ) {
			return;
		}

		wp_enqueue_style(
			SKAUTIS_INTEGRATION_NAME . '_font-awesome',
			SKAUTIS_INTEGRATION_URL . 'bundled/font-awesome/css/font-awesome.min.css',
			array(),
			SKAUTIS_INTEGRATION_VERSION,
			'all'
		);

		Helpers::enqueue_style( 'query-builder', 'rules/admin/css/skautis-rules-admin.min.css' );

		wp_enqueue_style(
			SKAUTIS_INTEGRATION_NAME . '_query-builder-main',
			SKAUTIS_INTEGRATION_URL . 'bundled/query-builder.default.min.css',
			array(),
			SKAUTIS_INTEGRATION_VERSION,
			'all'
		);
	}

	/**
	 * Enqueues scripts for rules management.
	 */
	public static function enqueue_scripts() {
		if ( get_current_screen()->id !== Rules_Init::RULES_TYPE_SLUG || get_post_type() !== Rules_Init::RULES_TYPE_SLUG ) {
			return;
		}

		$localization = array(
			'select_placeholder' => esc_html__( 'Vyberte...', 'skautis-integration' ),
			'unitNumber'         => esc_html__( 'číslo jednotky (např. 411.12)', 'skautis-integration' ),
			'inUnitWithNumber'   => esc_html__( 'v jednotce, jejíž evidenční číslo', 'skautis-integration' ),
		);

		Helpers::enqueue_script(
			'rules_role',
			'rules/admin/js/skautis-rules-role.min.js',
			array(),
			false
		);
		wp_localize_script(
			SKAUTIS_INTEGRATION_NAME . '_rules_role',
			'skautisIntegrationRulesLocalize',
			$localization,
		);

		Helpers::enqueue_script(
			'rules_membership',
			'rules/admin/js/skautis-rules-membership.min.js',
			array(),
			false
		);
		wp_localize_script(
			SKAUTIS_INTEGRATION_NAME . '_rules_membership',
			'skautisIntegrationRulesLocalize',
			$localization,
		);

		Helpers::enqueue_script(
			'rules_func',
			'rules/admin/js/skautis-rules-func.min.js',
			array(),
			false
		);
		wp_localize_script(
			SKAUTIS_INTEGRATION_NAME . '_rules_func',
			'skautisIntegrationRulesLocalize',
			$localization,
		);

		Helpers::enqueue_script(
			'rules_qualification',
			'rules/admin/js/skautis-rules-qualification.min.js',
			array(),
			false
		);

		wp_enqueue_script(
			SKAUTIS_INTEGRATION_NAME . '_interact',
			SKAUTIS_INTEGRATION_URL . 'bundled/interact.min.js',
			array(),
			SKAUTIS_INTEGRATION_VERSION,
			false
		);

		wp_enqueue_script(
			SKAUTIS_INTEGRATION_NAME . '_query-builder',
			SKAUTIS_INTEGRATION_URL . 'bundled/query-builder.standalone.min.js',
			array( 'jquery' ),
			SKAUTIS_INTEGRATION_VERSION,
			true
		);

		Helpers::enqueue_script(
			'query-builder_lang',
			'rules/admin/js/query-builder.cs.min.js',
			array( SKAUTIS_INTEGRATION_NAME . '_query-builder' )
		);
		Helpers::enqueue_script(
			'rules',
			'rules/admin/js/skautis-rules-admin.min.js',
			array( SKAUTIS_INTEGRATION_NAME . '_query-builder' )
		);

		wp_localize_script(
			SKAUTIS_INTEGRATION_NAME . '_rules',
			'skautisIntegrationRulesLocalize',
			$localization,
		);
	}

	/**
	 * Initializes dynamic options for the rules JS code.
	 */
	public function init_rules_builder() {
		if ( get_current_screen()->id !== Rules_Init::RULES_TYPE_SLUG || get_post_type() !== Rules_Init::RULES_TYPE_SLUG ) {
			return;
		}

		if ( ! $this->skautis_gateway->is_initialized() || ! $this->skautis_gateway->get_skautis_instance()->getUser()->isLoggedIn( true ) ) {
			return;
		}

		?>
		<script>
			window.skautisQueryBuilderFilters = [];

			var data = {};
			<?php
			foreach ( $this->rules_manager->get_rules() as $rule ) {
				$data = array(
					'id'          => $rule->get_id(),
					'label'       => $rule->get_label(),
					'type'        => $rule->get_type(),
					'input'       => $rule->get_input(),
					'multiple'    => $rule->get_multiple(),
					'values'      => $rule->get_values(),
					'operators'   => $rule->get_operators(),
					'placeholder' => $rule->get_placeholder(),
					'description' => $rule->get_description(),
				);
				?>
			data = <?php echo wp_json_encode( $data ); ?>;

			if (data.input === "roleInput") {
				var role = new Role(data.values);
				data.input = role.input.bind(role);
				data.validation = role.validation.call(role);
				data.valueGetter = role.valueGetter.bind(role);
				data.valueSetter = role.valueSetter.bind(role);
			} else if (data.input === "membershipInput") {
				var membership = new Membership(data.values);
				data.input = membership.input.bind(membership);
				data.validation = membership.validation.call(membership);
				data.valueGetter = membership.valueGetter.bind(membership);
				data.valueSetter = membership.valueSetter.bind(membership);
			} else if (data.input === "funcInput") {
				var func = new Func(data.values);
				data.input = func.input.bind(func);
				data.validation = func.validation.call(func);
				data.valueGetter = func.valueGetter.bind(func);
				data.valueSetter = func.valueSetter.bind(func);
			} else if (data.input === "qualificationInput") {
				var qualification = new Qualification(data.values);
				data.input = qualification.input.bind(qualification);
				data.validation = qualification.validation.call(qualification);
				data.valueGetter = qualification.valueGetter.bind(qualification);
				data.valueSetter = qualification.valueSetter.bind(qualification);
			}

				<?php

				// TODO: Unused action?
				do_action( SKAUTIS_INTEGRATION_NAME . '_rules_admin_js_conditions', $data );

				?>

			window.skautisQueryBuilderFilters.push(data);
				<?php
			}
			?>
		</script>
		<?php
	}

}
