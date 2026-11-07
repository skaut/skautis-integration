<?php
/**
 * Contains the Columns class.
 *
 * @package skautis-integration
 */

declare( strict_types=1 );

namespace Skautis_Integration\Rules;

use Skautis_Integration\Admin\Settings;
use Skautis_Integration\Modules\Visibility\Frontend\Block_Visibility;

/**
 * Adds the "Used in" and "Last modified" columns to the rule table view.
 *
 * @phan-constructor-used-for-side-effects
 */
class Columns {

	/**
	 * The maximum number of linked posts shown for a rule.
	 */
	const MAX_LISTED_POSTS = 5;

	/**
	 * The usage of all rules, computed once per request.
	 *
	 * @var array<int, array{posts: array<int, true>, blocks: int, registration: bool}>|null
	 */
	private static $usage = null;

	/**
	 * Intializes all hooks used by the object.
	 */
	public function __construct() {
		self::init_hooks();
	}

	/**
	 * Intializes all hooks used by the object.
	 *
	 * @return void
	 */
	protected static function init_hooks() {
		add_filter( 'manage_edit-' . Rules_Init::RULES_TYPE_SLUG . '_columns', array( self::class, 'last_modified_admin_column' ) );
		add_filter(
			'manage_edit-' . Rules_Init::RULES_TYPE_SLUG . '_sortable_columns',
			array(
				self::class,
				'sortable_last_modified_column',
			)
		);
		add_action(
			'manage_' . Rules_Init::RULES_TYPE_SLUG . '_posts_custom_column',
			array(
				self::class,
				'last_modified_admin_column_content',
			),
			10,
			2
		);
	}

	/**
	 * Adds the header for the "Last modified" column in the rules overview.
	 *
	 * @param array<string, string> $columns A list of already present column headers, keyed by their ID.
	 *
	 * @return array<string, string> The updated list.
	 */
	public static function last_modified_admin_column( array $columns = array() ): array {
		$columns['skautis_usage'] = __( 'Used in', 'skautis-integration' );
		$columns['modified_last'] = __( 'Last modified', 'skautis-integration' );

		return $columns;
	}

	/**
	 * Adds the "Last modified" column to the rules overview.
	 *
	 * TODO: What are the parameter keys and values?
	 *
	 * @param array<string, string> $columns A list of already present columns.
	 *
	 * @return array<string, string> The updated list.
	 */
	public static function sortable_last_modified_column( array $columns = array() ): array {
		$columns['modified_last'] = 'modified';

		return $columns;
	}

	/**
	 * Prints the content for the "Last modified" column in the rules overview.
	 *
	 * This function gets called for each cell of the table, so it needs to check before printing the value.
	 *
	 * @param string $column_name The current column name.
	 * @param int    $post_id The current post ID (the current row).
	 *
	 * @return void
	 */
	public static function last_modified_admin_column_content( string $column_name, int $post_id ) {
		if ( 'skautis_usage' === $column_name ) {
			self::print_usage( $post_id );
			return;
		}
		if ( 'modified_last' !== $column_name ) {
			return;
		}

		$post = get_post( $post_id );
		if ( ! ( $post instanceof \WP_Post ) ) {
			return;
		}
		$time = strtotime( $post->post_modified );
		if ( false === $time ) {
			return;
		}
		/* translators: human-readable time difference */
		$modified_date   = sprintf( _x( '%s ago', '%s = human-readable time difference', 'skautis-integration' ), human_time_diff( $time, time() ) );
		$modified_author = get_the_modified_author();
		if ( null === $modified_author ) {
			return;
		}

		echo esc_html( $modified_date );
		echo '<br>';
		echo '<strong>' . esc_html( $modified_author ) . '</strong>';
	}

	/**
	 * Adds a rule usage to the usage list.
	 *
	 * @param array<int, array{posts: array<int, true>, blocks: int, registration: bool}> $usage The usage list.
	 * @param mixed                                                                       $rule_id The rule ID.
	 *
	 * @return array<int, array{posts: array<int, true>, blocks: int, registration: bool}> The updated list.
	 */
	private static function ensure_rule( array $usage, $rule_id ): array {
		$rule_id = intval( $rule_id );
		if ( ! isset( $usage[ $rule_id ] ) ) {
			$usage[ $rule_id ] = array(
				'posts'        => array(),
				'blocks'       => 0,
				'registration' => false,
			);
		}

		return $usage;
	}

	/**
	 * Counts the blocks with rules in a list of blocks, including the inner blocks.
	 *
	 * @param array<array<string, mixed>>                                                 $blocks The parsed blocks.
	 * @param int                                                                         $post_id The post with the blocks.
	 * @param array<int, array{posts: array<int, true>, blocks: int, registration: bool}> $usage The usage list.
	 *
	 * @return array<int, array{posts: array<int, true>, blocks: int, registration: bool}> The updated list.
	 */
	private static function add_block_usage( array $blocks, int $post_id, array $usage ): array {
		foreach ( $blocks as $block ) {
			$attributes = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
			if ( isset( $attributes[ Block_Visibility::RULES_ATTRIBUTE ] ) && is_array( $attributes[ Block_Visibility::RULES_ATTRIBUTE ] ) ) {
				foreach ( array_unique( array_map( 'intval', $attributes[ Block_Visibility::RULES_ATTRIBUTE ] ) ) as $rule_id ) {
					$usage                                  = self::ensure_rule( $usage, $rule_id );
					$usage[ $rule_id ]['posts'][ $post_id ] = true;
					++$usage[ $rule_id ]['blocks'];
				}
			}
			if ( isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$usage = self::add_block_usage( $block['innerBlocks'], $post_id, $usage );
			}
		}

		return $usage;
	}

	/**
	 * Returns where all the rules are used: in the rules of posts, in blocks and in the registration rules.
	 *
	 * @return array<int, array{posts: array<int, true>, blocks: int, registration: bool}> The usage, keyed by the rule ID.
	 */
	public static function get_usage(): array {
		if ( ! is_null( self::$usage ) ) {
			return self::$usage;
		}
		$usage      = array();
		$post_types = array_values( array_diff( get_post_types(), array( 'revision', Rules_Init::RULES_TYPE_SLUG ) ) );
		$statuses   = array_values( array_diff( get_post_stati(), array( 'trash', 'auto-draft', 'inherit' ) ) );

		$posts_with_rules = new \WP_Query(
			array(
				'post_type'              => $post_types,
				'post_status'            => $statuses,
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				'meta_key'               => SKAUTIS_INTEGRATION_NAME . '_rules', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			)
		);
		foreach ( $posts_with_rules->posts as $post_id ) {
			$post_id = intval( $post_id );
			foreach ( (array) get_post_meta( $post_id, SKAUTIS_INTEGRATION_NAME . '_rules', true ) as $rule ) {
				if ( is_array( $rule ) && isset( $rule[ SKAUTIS_INTEGRATION_NAME . '_rules' ] ) ) {
					$rule_id                                = intval( $rule[ SKAUTIS_INTEGRATION_NAME . '_rules' ] );
					$usage                                  = self::ensure_rule( $usage, $rule_id );
					$usage[ $rule_id ]['posts'][ $post_id ] = true;
				}
			}
		}

		$posts_with_blocks = new \WP_Query(
			array(
				'post_type'              => $post_types,
				'post_status'            => $statuses,
				'posts_per_page'         => -1,
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
				's'                      => Block_Visibility::RULES_ATTRIBUTE,
				'search_columns'         => array( 'post_content' ),
			)
		);
		foreach ( $posts_with_blocks->posts as $post ) {
			if ( $post instanceof \WP_Post ) {
				$usage = self::add_block_usage( parse_blocks( $post->post_content ), $post->ID, $usage );
			}
		}

		foreach ( (array) get_option( SKAUTIS_INTEGRATION_NAME . '_modules_register_rules', array() ) as $rule ) {
			if ( is_array( $rule ) && isset( $rule['rule'] ) ) {
				$rule_id                           = intval( $rule['rule'] );
				$usage                             = self::ensure_rule( $usage, $rule_id );
				$usage[ $rule_id ]['registration'] = true;
			}
		}

		self::$usage = $usage;

		return $usage;
	}

	/**
	 * Prints where a rule is used.
	 *
	 * @param int $rule_id The rule ID.
	 *
	 * @return void
	 */
	public static function print_usage( int $rule_id ) {
		$usage = self::get_usage()[ $rule_id ] ?? null;
		if ( is_null( $usage ) || ( 0 === count( $usage['posts'] ) && ! $usage['registration'] ) ) {
			echo '<span class="skautis-rule-unused">' . esc_html__( 'Not used', 'skautis-integration' ) . '</span>';
			return;
		}

		echo '<ul class="skautis-rule-usage">';
		$post_ids = array_keys( $usage['posts'] );
		foreach ( array_slice( $post_ids, 0, self::MAX_LISTED_POSTS ) as $post_id ) {
			$title = get_the_title( $post_id );
			$link  = get_edit_post_link( $post_id );
			$title = '' !== $title ? $title : __( '(no title)', 'skautis-integration' );
			echo '<li>' . ( is_null( $link ) ? esc_html( $title ) : '<a href="' . esc_url( $link ) . '">' . esc_html( $title ) . '</a>' ) . '</li>';
		}
		if ( count( $post_ids ) > self::MAX_LISTED_POSTS ) {
			$more = count( $post_ids ) - self::MAX_LISTED_POSTS;
			/* translators: %d: The number of other posts */
			echo '<li>' . esc_html( sprintf( _n( 'and %d more', 'and %d more', $more, 'skautis-integration' ), $more ) ) . '</li>';
		}
		if ( $usage['blocks'] > 0 ) {
			/* translators: %d: The number of blocks */
			echo '<li class="skautis-rule-usage-note">' . esc_html( sprintf( _n( '%d block with this rule', '%d blocks with this rule', $usage['blocks'], 'skautis-integration' ), $usage['blocks'] ) ) . '</li>';
		}
		if ( $usage['registration'] ) {
			echo '<li><a href="' . esc_url( Settings::get_tab_url( 'registration' ) ) . '">' . esc_html__( 'Registration rules', 'skautis-integration' ) . '</a></li>';
		}
		echo '</ul>';
	}
}
