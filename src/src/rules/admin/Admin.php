<?php

declare( strict_types=1 );

namespace SkautisIntegration\Rules;

use SkautisIntegration\Auth\SkautisGateway;
use SkautisIntegration\Auth\WpLoginLogout;
use SkautisIntegration\Utils\Helpers;

final class Admin {

	private $rulesManager;
	private $wpLoginLogout;
	private $skautisGateway;
	private $adminDirUrl = '';

	public function __construct( RulesManager $rulesManager, WpLoginLogout $wpLoginLogout, SkautisGateway $skautisGateway ) {
		$this->rulesManager   = $rulesManager;
		$this->wpLoginLogout  = $wpLoginLogout;
		$this->skautisGateway = $skautisGateway;
		$this->skautisGateway = $skautisGateway;
		$this->adminDirUrl    = plugin_dir_url( __FILE__ ) . 'public/';
		( new Columns() );
		$this->initHooks();
	}

	private function initHooks() {
		add_action( 'add_meta_boxes', array( $this, 'addMetaboxForRulesField' ) );
		add_action( 'save_post', array( $this, 'saveRulesCustomField' ) );

		add_action( 'edit_form_after_title', array( $this, 'addRulesUi' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueStyles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueScripts' ) );

		add_action( 'admin_footer', array( $this, 'initRulesBuilder' ), 100 );
	}

	public function addMetaboxForRulesField( string $postType ) {
		if ( $postType == RulesInit::RULES_TYPE_SLUG ) {
			add_meta_box(
				SKAUTISINTEGRATION_NAME . '_rules_metabox',
				__( 'skautIS pravidla', 'skautis-integration' ),
				array( $this, 'RulesFieldContent' ),
				RulesInit::RULES_TYPE_SLUG
			);
		}
	}

	public function saveRulesCustomField( int $postId ) {
		if ( array_key_exists( SKAUTISINTEGRATION_NAME . '_rules_data', $_POST ) ) {
			update_post_meta(
				$postId,
				SKAUTISINTEGRATION_NAME . '_rules_data',
				$_POST[ SKAUTISINTEGRATION_NAME . '_rules_data' ]
			);
		}
	}

	public function RulesFieldContent( \WP_Post $post ) {
		?>
		<textarea id="query_builder_values" class=""
				  name="<?php echo esc_attr( SKAUTISINTEGRATION_NAME ); ?>_rules_data"><?php echo esc_html( get_post_meta( $post->ID, SKAUTISINTEGRATION_NAME . '_rules_data', true ) ); ?></textarea>
		<?php
	}

	public function addRulesFieldToRevisions( array $fields ): array {
		$fields[ SKAUTISINTEGRATION_NAME . '_rules_data' ] = __( 'skautIS Pravidla', 'skautis-integration' );

		return $fields;
	}

	public function getRulesFieldValue( $value, $fieldName, \WP_Post $post ) {
		return get_metadata( 'post', $post->ID, SKAUTISINTEGRATION_NAME . '_rules_data', true );
	}

	public function restoreRevisionForRulesField( int $postId, int $revisionId ) {
		$post     = get_post( $postId );
		$revision = get_post( $revisionId );
		if ( $post->post_type == RulesInit::RULES_TYPE_SLUG ) {
			$meta = get_metadata( 'post', $revision->ID, SKAUTISINTEGRATION_NAME . '_rules_data', true );
			if ( false !== $meta ) {
				update_post_meta( $postId, SKAUTISINTEGRATION_NAME . '_rules_data', $meta );
			}
		}
	}

	public function addRulesUi( \WP_Post $post ) {
		if ( get_current_screen()->id != RulesInit::RULES_TYPE_SLUG || get_post_type() != RulesInit::RULES_TYPE_SLUG ) {
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
						printf( esc_html__( 'Vyberte v %1$snastavení%2$s pluginu typ prostředí skautISu', 'skautis-integration' ), '<a href="' . esc_url( admin_url( 'admin.php?page=' . SKAUTISINTEGRATION_NAME ) ) . '">', '</a>' );
					} elseif ( ! $this->skautisGateway->getSkautisInstance()->getUser()->isLoggedIn( true ) ) {
						echo '<h4><a href="' . esc_url( $this->wpLoginLogout->getLoginUrl( add_query_arg( 'noWpLogin', true, Helpers::getCurrentUrl() ) ) ) . '">' . esc_html__( 'Pro správu podmínek je nutné se přihlásit do skautISu', 'skautis-integration' ) . '</a></h4>';
					} else {
						echo '<div id="query_builder"></div>';
					}
					?>
				</div>
			</div>
		</div>
		<?php
	}

	public function enqueueStyles() {
		if ( get_current_screen()->id != RulesInit::RULES_TYPE_SLUG || get_post_type() != RulesInit::RULES_TYPE_SLUG ) {
			return;
		}

		wp_enqueue_style(
			'font-awesome',
			'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css',
			array(),
			'4.7.0',
			'all'
		);

		wp_enqueue_style(
			SKAUTISINTEGRATION_NAME . '_query-builder',
			$this->adminDirUrl . 'css/skautis-rules-admin.css',
			array(),
			SKAUTISINTEGRATION_VERSION,
			'all'
		);

		wp_enqueue_style(
			SKAUTISINTEGRATION_NAME . '_query-builder-main',
			'https://cdn.jsdelivr.net/npm/jQuery-QueryBuilder@2.4.5/dist/css/query-builder.default.min.css',
			array(),
			false,
			'all'
		);
	}

	public function enqueueScripts() {
		if ( get_current_screen()->id != RulesInit::RULES_TYPE_SLUG || get_post_type() != RulesInit::RULES_TYPE_SLUG ) {
			return;
		}

		wp_enqueue_script(
			SKAUTISINTEGRATION_NAME . '_rules_role',
			$this->adminDirUrl . 'js/skautis-rules-role.js',
			array(),
			SKAUTISINTEGRATION_VERSION,
			false
		);

		wp_enqueue_script(
			SKAUTISINTEGRATION_NAME . '_rules_membership',
			$this->adminDirUrl . 'js/skautis-rules-membership.js',
			array(),
			SKAUTISINTEGRATION_VERSION,
			false
		);

		wp_enqueue_script(
			SKAUTISINTEGRATION_NAME . '_rules_func',
			$this->adminDirUrl . 'js/skautis-rules-func.js',
			array(),
			SKAUTISINTEGRATION_VERSION,
			false
		);

		wp_enqueue_script(
			SKAUTISINTEGRATION_NAME . '_rules_qualification',
			$this->adminDirUrl . 'js/skautis-rules-qualification.js',
			array(),
			SKAUTISINTEGRATION_VERSION,
			false
		);

		wp_enqueue_script(
			'interact',
			'https://cdnjs.cloudflare.com/ajax/libs/interact.js/1.2.9/interact.min.js',
			array(),
			'1.2.8',
			false
		);

		wp_enqueue_script(
			SKAUTISINTEGRATION_NAME . '_query-builder',
			'https://cdn.jsdelivr.net/npm/jQuery-QueryBuilder@2.4.5/dist/js/query-builder.standalone.min.js',
			array( 'jquery' ),
			false,
			true
		);

		wp_enqueue_script(
			SKAUTISINTEGRATION_NAME . '_query-builder_lang',
			$this->adminDirUrl . 'QueryBuilder/i18n/query-builder.cs.js',
			array( SKAUTISINTEGRATION_NAME . '_query-builder' ),
			SKAUTISINTEGRATION_VERSION,
			true
		);

		wp_enqueue_script(
			SKAUTISINTEGRATION_NAME . '_rules',
			$this->adminDirUrl . 'js/skautis-rules-admin.js',
			array( SKAUTISINTEGRATION_NAME . '_query-builder' ),
			SKAUTISINTEGRATION_VERSION,
			true
		);
	}

	public function initRulesBuilder() {
		if ( get_current_screen()->id != RulesInit::RULES_TYPE_SLUG || get_post_type() != RulesInit::RULES_TYPE_SLUG ) {
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
					'id'          => $rule->getId(),
					'label'       => $rule->getLabel(),
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
