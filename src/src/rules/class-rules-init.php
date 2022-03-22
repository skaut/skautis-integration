<?php

declare( strict_types=1 );

namespace SkautisIntegration\Rules;

use SkautisIntegration\Utils\Helpers;

final class Rules_Init {

	const RULES_TYPE_SINGULAR = 'skautis_rule';
	const RULES_TYPE_SLUG     = 'skautis_rules';

	private $revisions;

	public function __construct( Revisions $revisions ) {
		$this->revisions = $revisions;
		$this->init_hooks();
	}

	private function init_hooks() {
		add_action( 'init', array( $this, 'register_post_type' ) );

		if ( is_admin() ) {
			add_filter( 'default_content', array( $this, 'default_content' ) );
			add_filter( 'enter_title_here', array( $this, 'title_placeholder' ) );
			add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );
		}
	}

	public function register_post_type() {
		$labels       = array(
			'name'                  => _x( 'Správa pravidel', 'Post Type General Name', 'skautis-integration' ),
			'singular_name'         => _x( 'Pravidlo', 'Post Type Singular Name', 'skautis-integration' ),
			'menu_name'             => __( 'Správa pravidel', 'skautis-integration' ),
			'name_admin_bar'        => __( 'Správa pravidel', 'skautis-integration' ),
			'archives'              => __( 'Archiv pravidel', 'skautis-integration' ),
			'attributes'            => __( 'Atributy', 'skautis-integration' ),
			'parent_item_colon'     => __( 'Nadřazené pravidlo', 'skautis-integration' ),
			'all_items'             => __( 'Správa pravidel', 'skautis-integration' ),
			'add_new_item'          => __( 'Přidat nové pravidlo', 'skautis-integration' ),
			'add_new'               => __( 'Přidat pravidlo', 'skautis-integration' ),
			'new_item'              => __( 'Nové pravidlo', 'skautis-integration' ),
			'edit_item'             => __( 'Upravit pravidlo', 'skautis-integration' ),
			'update_item'           => __( 'Aktualizovat pravidlo', 'skautis-integration' ),
			'view_item'             => __( 'Zobrazit pravidlo', 'skautis-integration' ),
			'view_items'            => __( 'Zobrazit pravidla', 'skautis-integration' ),
			'search_items'          => __( 'Hledat v pravidlech', 'skautis-integration' ),
			'not_found'             => __( 'Žádná pravidla', 'skautis-integration' ),
			'not_found_in_trash'    => __( 'Koš je prázdný', 'skautis-integration' ),
			'featured_image'        => __( 'Náhledový obrázek', 'skautis-integration' ),
			'set_featured_image'    => __( 'Zadat náhledový obrázek', 'skautis-integration' ),
			'remove_featured_image' => __( 'Odstranit náhledový obrázek', 'skautis-integration' ),
			'use_featured_image'    => __( 'Použít jako náhledový obrázek', 'skautis-integration' ),
			'insert_into_item'      => __( 'Vložit do pravidla', 'skautis-integration' ),
			'uploaded_to_this_item' => __( 'Přiřazeno k tomuto pravidlu', 'skautis-integration' ),
			'items_list'            => __( 'Seznam pravidel', 'skautis-integration' ),
			'items_list_navigation' => __( 'Navigace v seznamu pravidel', 'skautis-integration' ),
			'filter_items_list'     => __( 'Filtrovat pravidla', 'skautis-integration' ),
		);
		$capabilities = array(
			'edit_post'              => Helpers::getSkautisManagerCapability(),
			'read_post'              => Helpers::getSkautisManagerCapability(),
			'delete_post'            => Helpers::getSkautisManagerCapability(),
			'edit_posts'             => Helpers::getSkautisManagerCapability(),
			'edit_others_posts'      => Helpers::getSkautisManagerCapability(),
			'publish_posts'          => Helpers::getSkautisManagerCapability(),
			'read_private_posts'     => Helpers::getSkautisManagerCapability(),
			'delete_posts'           => Helpers::getSkautisManagerCapability(),
			'delete_private_posts'   => Helpers::getSkautisManagerCapability(),
			'delete_published_posts' => Helpers::getSkautisManagerCapability(),
			'delete_others_posts'    => Helpers::getSkautisManagerCapability(),
			'edit_private_posts'     => Helpers::getSkautisManagerCapability(),
			'edit_published_posts'   => Helpers::getSkautisManagerCapability(),
			'create_posts'           => Helpers::getSkautisManagerCapability(),
		);
		$args         = array(
			'label'               => __( 'Pravidla', 'skautis-integration' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'author', 'revisions' ),
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => SKAUTISINTEGRATION_NAME,
			'menu_position'       => 3,
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'rewrite'             => false,
			'capabilities'        => $capabilities,
			'show_in_rest'        => false,
		);
		register_post_type( self::RULES_TYPE_SLUG, $args );
	}

	public function default_content( string $content ): string {
		global $post_type;
		if ( self::RULES_TYPE_SLUG === $post_type ) {
			$content = '';
		}

		return $content;
	}

	public function title_placeholder( string $title ): string {
		global $post_type;
		if ( self::RULES_TYPE_SLUG === $post_type ) {
			$title = __( 'Zadejte název pravidla', 'skautis-integration' );
		}

		return $title;
	}

	public function updated_messages( array $messages = array() ): array {
		$post                              = get_post();
		$messages[ self::RULES_TYPE_SLUG ] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Hotovo', 'skautis-integration' ), // My Post Type updated.
			2  => __( 'Hotovo', 'skautis-integration' ), // Custom field updated.
			3  => __( 'Hotovo', 'skautis-integration' ), // Custom field deleted.
			4  => __( 'Hotovo', 'skautis-integration' ), // My Post Type updated.
			/* translators: The time of the previous version */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Pravidlo bylo obnoveno na starší verzi z %s', 'skautis-integration' ), wp_post_revision_title( absint( $_GET['revision'] ), false ) ) : false, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			6  => __( 'Hotovo', 'skautis-integration' ), // My Post Type published.
			7  => __( 'Pravidlo bylo uloženo', 'skautis-integration' ), // My Post Type saved.
			8  => __( 'Hotovo', 'skautis-integration' ), // My Post Type submitted.
			9  => sprintf(
				/* translators: 1: The time of the rule run */
				__( 'Pravidlo naplánováno na: <strong>%1$s</strong>.', 'skautis-integration' ),
				/* translators: Publish box date format, see http://php.net/date */
				date_i18n( __( 'M j, Y @ G:i', 'skautis-integration' ), strtotime( $post->post_date ) )
			),
			10 => __( 'Koncept pravidla aktualizován', 'skautis-integration' ), // My Post Type draft updated.
		);

		return $messages;
	}

	public function getAllRules(): array {
		$rulesWpQuery = new \WP_Query(
			array(
				'post_type'     => self::RULES_TYPE_SLUG,
				'nopaging'      => true,
				'no_found_rows' => true,
			)
		);

		if ( $rulesWpQuery->have_posts() ) {
			return $rulesWpQuery->posts;
		}

		return array();
	}

}
