<?php

declare( strict_types=1 );

namespace SkautisIntegration\Rules;

use SkautisIntegration\Auth\Skautis_Gateway;
use SkautisIntegration\Auth\WP_Login_Logout;
use SkautisIntegration\Utils\Helpers;

final class Admin {

	private $rulesManager;
	private $wpLoginLogout;
	private $skautisGateway;
	private $adminDirUrl = '';

	public function __construct( Rules_Manager $rulesManager, WP_Login_Logout $wpLoginLogout, Skautis_Gateway $skautisGateway ) {
		$this->rulesManager   = $rulesManager;
		$this->wpLoginLogout  = $wpLoginLogout;
		$this->skautisGateway = $skautisGateway;
		$this->skautisGateway = $skautisGateway;
		$this->adminDirUrl    = plugin_dir_url( __FILE__ ) . 'public/';
		( new Columns() );
		$this->init_hooks();
	}

	private function init_hooks() {
		add_action( 'add_meta_boxes', array( $this, 'add_metabox_for_rules_field' ) );
		add_action( 'save_post', array( $this, 'save_rules_custom_field' ) );

		add_action( 'edit_form_after_title', array( $this, 'add_rules_ui' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'admin_footer', array( $this, 'init_rules_builder' ), 100 );
	}

	public function add_metabox_for_rules_field( string $postType ) {
		if ( Rules_Init::RULES_TYPE_SLUG === $postType ) {
			add_meta_box(
				SKAUTISINTEGRATION_NAME . '_rules_metabox',
				__( 'skautIS pravidla', 'skautis-integration' ),
				array( $this, 'rules_field_content' ),
				Rules_Init::RULES_TYPE_SLUG
			);
		}
	}

	public function save_rules_custom_field( int $postId ) {
		if ( ! isset( $_POST[ SKAUTISINTEGRATION_NAME . '_rules_metabox_nonce' ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ SKAUTISINTEGRATION_NAME . '_rules_metabox_nonce' ] ) ), SKAUTISINTEGRATION_NAME . '_rules_metabox' ) ) {
			return;
		}

		if ( array_key_exists( SKAUTISINTEGRATION_NAME . '_rules_data', $_POST ) ) {
			update_post_meta(
				$postId,
				SKAUTISINTEGRATION_NAME . '_rules_data',
				sanitize_meta( SKAUTISINTEGRATION_NAME . '_rules_data', wp_unslash( $_POST[ SKAUTISINTEGRATION_NAME . '_rules_data' ] ), 'post' )
			);
		}
	}

	public function rules_field_content( \WP_Post $post ) {
		wp_nonce_field( SKAUTISINTEGRATION_NAME . '_rules_metabox', SKAUTISINTEGRATION_NAME . '_rules_metabox_nonce' );
		?>
		<textarea id="query_builder_values" class=""
				name="<?php echo esc_attr( SKAUTISINTEGRATION_NAME ); ?>_rules_data"><?php echo esc_html( get_post_meta( $post->ID, SKAUTISINTEGRATION_NAME . '_rules_data', true ) ); ?></textarea>
		<?php
	}

	// TODO: Unused?
	public function add_rules_field_to_revisions( array $fields ): array {
		$fields[ SKAUTISINTEGRATION_NAME . '_rules_data' ] = __( 'skautIS Pravidla', 'skautis-integration' );

		return $fields;
	}

	// TODO: Unused?
	public function get_rules_field_value( $value, $fieldName, \WP_Post $post ) {
		return get_metadata( 'post', $post->ID, SKAUTISINTEGRATION_NAME . '_rules_data', true );
	}

	// TODO: Unused?
	public function restore_revision_for_rules_field( int $postId, int $revisionId ) {
		$post     = get_post( $postId );
		$revision = get_post( $revisionId );
		if ( Rules_Init::RULES_TYPE_SLUG === $post->post_type ) {
			$meta = get_metadata( 'post', $revision->ID, SKAUTISINTEGRATION_NAME . '_rules_data', true );
			if ( false !== $meta ) {
				update_post_meta( $postId, SKAUTISINTEGRATION_NAME . '_rules_data', $meta );
			}
		}
	}

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
					if ( ! $this->skautisGateway->isInitialized() ) {
						/* translators: 1: Start of link to the settings 2: End of link to the settings */
						printf( esc_html__( 'Vyberte v %1$snastavení%2$s pluginu typ prostředí skautISu', 'skautis-integration' ), '<a href="' . esc_url( admin_url( 'admin.php?page=' . SKAUTISINTEGRATION_NAME ) ) . '">', '</a>' );
					} elseif ( ! $this->skautisGateway->getSkautisInstance()->getUser()->isLoggedIn( true ) ) {
						echo '<h4><a href="' . esc_url( $this->wpLoginLogout->get_login_url( add_query_arg( 'noWpLogin', true, Helpers::getCurrentUrl() ) ) ) . '">' . esc_html__( 'Pro správu podmínek je nutné se přihlásit do skautISu', 'skautis-integration' ) . '</a></h4>';
					} else {
						echo '<div id="query_builder"></div>';
					}
					?>
				</div>
			</div>
		</div>
		<?php
	}

	public function enqueue_styles() {
		if ( get_current_screen()->id !== Rules_Init::RULES_TYPE_SLUG || get_post_type() !== Rules_Init::RULES_TYPE_SLUG ) {
			return;
		}

		wp_enqueue_style(
			SKAUTISINTEGRATION_NAME . '_font-awesome',
			SKAUTISINTEGRATION_URL . 'bundled/font-awesome/css/font-awesome.min.css',
			array(),
			SKAUTISINTEGRATION_VERSION,
			'all'
		);

		Helpers::enqueue_style( 'query-builder', 'rules/admin/css/skautis-rules-admin.min.css' );

		wp_enqueue_style(
			SKAUTISINTEGRATION_NAME . '_query-builder-main',
			SKAUTISINTEGRATION_URL . 'bundled/query-builder.default.min.css',
			array(),
			SKAUTISINTEGRATION_VERSION,
			'all'
		);
	}

	public function enqueue_scripts() {
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
			SKAUTISINTEGRATION_NAME . '_rules_role',
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
			SKAUTISINTEGRATION_NAME . '_rules_membership',
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
			SKAUTISINTEGRATION_NAME . '_rules_func',
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
			SKAUTISINTEGRATION_NAME . '_interact',
			SKAUTISINTEGRATION_URL . 'bundled/interact.min.js',
			array(),
			SKAUTISINTEGRATION_VERSION,
			false
		);

		wp_enqueue_script(
			SKAUTISINTEGRATION_NAME . '_query-builder',
			SKAUTISINTEGRATION_URL . 'bundled/query-builder.standalone.min.js',
			array( 'jquery' ),
			SKAUTISINTEGRATION_VERSION,
			true
		);

		Helpers::enqueue_script(
			'query-builder_lang',
			'rules/admin/js/query-builder.cs.min.js',
			array( SKAUTISINTEGRATION_NAME . '_query-builder' )
		);
		Helpers::enqueue_script(
			'rules',
			'rules/admin/js/skautis-rules-admin.min.js',
			array( SKAUTISINTEGRATION_NAME . '_query-builder' )
		);

		wp_localize_script(
			SKAUTISINTEGRATION_NAME . '_rules',
			'skautisIntegrationRulesLocalize',
			$localization,
		);
	}

	public function init_rules_builder() {
		if ( get_current_screen()->id !== Rules_Init::RULES_TYPE_SLUG || get_post_type() !== Rules_Init::RULES_TYPE_SLUG ) {
			return;
		}

		if ( ! $this->skautisGateway->isInitialized() || ! $this->skautisGateway->getSkautisInstance()->getUser()->isLoggedIn( true ) ) {
			return;
		}

		?>
		<script>
			window.skautisQueryBuilderFilters = [];

			var data = {};
			<?php
			foreach ( (array) $this->rulesManager->getRules() as $rule ) {
				$data = array(
					'id'          => $rule->get_id(),
					'label'       => $rule->get_label(),
					'type'        => $rule->getType(),
					'input'       => $rule->getInput(),
					'multiple'    => $rule->getMultiple(),
					'values'      => $rule->getValues(),
					'operators'   => $rule->getOperators(),
					'placeholder' => $rule->getPlaceholder(),
					'description' => $rule->getDescription(),
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

				do_action( SKAUTISINTEGRATION_NAME . '_rules_admin_jsConditions', $data );

				?>

			window.skautisQueryBuilderFilters.push(data);
				<?php
			}
			?>
		</script>
		<?php
	}

}
