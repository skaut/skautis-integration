<?php

namespace SkautisIntegration\Rules;

use SkautisIntegration\Auth\SkautisGateway;
use SkautisIntegration\Auth\WpLoginLogout;

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
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'add_meta_boxes', [ $this, 'addMetaboxForRulesField' ] );
		add_action( 'save_post', [ $this, 'saveRulesCustomField' ] );

		add_action( 'edit_form_after_title', [ $this, 'addRulesUi' ] );

		add_action( 'admin_enqueue_scripts', function () {
			if ( get_current_screen()->id != RulesInit::RULES_TYPE_SLUG || get_post_type() != RulesInit::RULES_TYPE_SLUG ) {
				return;
			}

			add_action( 'admin_enqueue_scripts', [ $this, 'enqueueStyles' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueueScripts' ] );
			add_action( 'admin_footer', [ $this, 'initRulesBuilder' ] );
		}, 5 );
	}

	public function addMetaboxForRulesField( $postType ) {
		if ( $postType == RulesInit::RULES_TYPE_SLUG ) {
			add_meta_box(
				SKAUTISINTEGRATION_NAME . '_rules_metabox',
				__( 'skautIS pravidla', 'skautis-integration' ),
				[ $this, 'RulesFieldContent' ],
				RulesInit::RULES_TYPE_SLUG
			);
		}
	}

	public function saveRulesCustomField( $postId ) {
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
		          name="<?php echo SKAUTISINTEGRATION_NAME; ?>_rules_data"><?php echo get_post_meta( $post->ID, SKAUTISINTEGRATION_NAME . '_rules_data', true ); ?></textarea>
		<?php
	}

	public function addRulesFieldToRevisions( $fields ) {
		$fields[ SKAUTISINTEGRATION_NAME . '_rules_data' ] = __( 'skautIS Pravidla', 'skautis-integration' );

		return $fields;
	}

	public function getRulesFieldValue( $value, $field_name, \WP_Post $post ) {
		return get_metadata( 'post', $post->ID, SKAUTISINTEGRATION_NAME . '_rules_data', true );
	}

	public function restoreRevisionForRulesField( $postId, $revisionId ) {
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
		if ( $post->post_type != RulesInit::RULES_TYPE_SLUG ) {
			return;
		}
		?>
		<div class="meta-box-sortables">
			<div class="postbox" style="margin-top: 2.5em;">
				<button type="button" class="handlediv" aria-expanded="true"><span
						class="screen-reader-text"><?php _e( 'Zobrazit / skrýt panel: Pravidla', 'skautis-integration' ); ?></span><span
						class="toggle-indicator" aria-hidden="true"></span></button>
				<h2 class="hndle ui-sortable-handle"><span><?php _e( 'Pravidla', 'skautis-integration' ); ?></span>
				</h2>
				<div class="inside" style="padding: 0.75em 1.5em 1.25em 1.5em;">
					<label class="screen-reader-text"
					       for="post_author_override"><?php _e( 'Pravidla', 'skautis-integration' ); ?></label>
					<?php
					if ( ! $this->skautisGateway->isInitialized() || ! $this->skautisGateway->getSkautisInstance()->getUser()->isLoggedIn( true ) ) {
						$result = '<h4><a href="' . $this->wpLoginLogout->getLoginUrl() . '">' . __( 'Pro správu pravidel je nutné se přihlásit do skautISu', 'skautis-integration' ) . '</a></h4>';
						echo $result;
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
		wp_enqueue_style(
			'font-awesome',
			'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css',
			[],
			'4.7.0',
			'all'
		);

		wp_enqueue_style(
			SKAUTISINTEGRATION_NAME . '_query-builder',
			$this->adminDirUrl . 'css/skautis-rules-admin.css',
			[],
			SKAUTISINTEGRATION_VERSION,
			'all'
		);

		wp_enqueue_style(
			SKAUTISINTEGRATION_NAME . '_query-builder-main',
			'https://unpkg.com/jQuery-QueryBuilder@2.4.3/dist/css/query-builder.default.min.css',
			[],
			'2.4.3',
			'all'
		);

		wp_enqueue_style(
			'select2',
			'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css',
			[],
			'4.0.3',
			'all'
		);
	}

	public function enqueueScripts() {
		wp_enqueue_script(
			SKAUTISINTEGRATION_NAME . '_rules_role',
			$this->adminDirUrl . 'js/skautis-rules-role.js',
			[],
			SKAUTISINTEGRATION_VERSION,
			false
		);

		wp_enqueue_script(
			SKAUTISINTEGRATION_NAME . '_rules_membership',
			$this->adminDirUrl . 'js/skautis-rules-membership.js',
			[],
			SKAUTISINTEGRATION_VERSION,
			false
		);

		wp_enqueue_script(
			'interact',
			'https://cdnjs.cloudflare.com/ajax/libs/interact.js/1.2.8/interact.min.js',
			[],
			'1.2.8',
			false
		);

		wp_enqueue_script(
			'select2',
			'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js',
			[ 'jquery' ],
			'4.0.3',
			true
		);

		wp_enqueue_script(
			SKAUTISINTEGRATION_NAME . '_query-builder',
			'https://unpkg.com/jQuery-QueryBuilder@2.4.3/dist/js/query-builder.standalone.min.js',
			[ 'jquery' ],
			'2.4.3',
			false
		);

		wp_enqueue_script(
			SKAUTISINTEGRATION_NAME . '_query-builder_lang',
			$this->adminDirUrl . 'QueryBuilder/i18n/query-builder.cs.js',
			[ SKAUTISINTEGRATION_NAME . '_query-builder' ],
			SKAUTISINTEGRATION_VERSION,
			true
		);

		wp_enqueue_script(
			SKAUTISINTEGRATION_NAME . '_rules',
			$this->adminDirUrl . 'js/skautis-rules-admin.js',
			[ SKAUTISINTEGRATION_NAME . '_query-builder' ],
			SKAUTISINTEGRATION_VERSION,
			true
		);
	}

	public function initRulesBuilder() {
		if ( ! $this->skautisGateway->isInitialized() || ! $this->skautisGateway->getSkautisInstance()->getUser()->isLoggedIn( true ) ) {
			return;
		}
		?>
		<script>
            window.skautisQueryBuilderFilters = [];

            var data = {};
			<?php
			foreach ( (array) $this->rulesManager->getRules() as $rule ) {
			$data = json_encode( [
				'id'          => $rule->getId(),
				'label'       => $rule->getLabel(),
				'type'        => $rule->getType(),
				'input'       => $rule->getInput(),
				'multiple'    => $rule->getMultiple(),
				'values'      => $rule->getValues(),
				'operators'   => $rule->getOperators(),
				'placeholder' => $rule->getPlaceholder(),
				'description' => $rule->getDescription()
			] );
			?>
            data = <?php echo $data; ?>;

            if (typeof data.values !== "undefined") {
                data.values = Object.keys(data.values).map(function (key) {
                    return {[key]: data.values[key]};
                });
                data.values = data.values.sort(function (a, b) {
                    return a[Object.keys(a)[0]].localeCompare(b[Object.keys(b)[0]]);
                });
            }

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
            }

            window.skautisQueryBuilderFilters.push(data);
			<?php
			}
			?>
		</script>
		<?php
	}

}
