<?php
/**
 * Contains stubs of WordPress functions for phan and PHPStan
 *
 * The skaut/wordpress-stubs package only contains the parts of WordPress that its plugins use. These stubs add the ones this plugin needs on top of it, with the signatures and types from WordPress 7.0.
 *
 * @package skautis-integration
 *
 * phpcs:disable Squiz.Commenting.FunctionComment.InvalidNoReturn
 * phpcs:disable Squiz.Commenting.FunctionComment.Missing
 * phpcs:disable Squiz.Commenting.FunctionComment.MissingParamComment
 * phpcs:disable Generic.Commenting.DocComment.MissingShort
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
 * phpcs:disable WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
 * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.Found
 * phpcs:disable Squiz.Commenting.VariableComment.Missing
 */

declare( strict_types=1 );

/**
 * @param string $post_meta_key
 */
function delete_post_meta_by_key( $post_meta_key ): bool {
}

/**
 * @param string $page
 * @param string $section
 */
function do_settings_fields( $page, $section ): void {
}

/**
 * @param string $email
 *
 * @return int|false
 */
function email_exists( $email ) {
}

/**
 * @param int|WP_Post $post
 * @param string      $context
 */
function get_edit_post_link( $post = 0, $context = 'display' ): ?string {
}

/**
 * @param array<string, mixed> $args
 * @param 'names'              $output
 * @param 'and'|'or'|'not'     $operator
 *
 * @return array<string, string>
 */
function get_post_stati( $args = array(), $output = 'names', $operator = 'and' ): array {
}

/**
 * @param string $option
 * @param mixed  $default_value
 *
 * @return mixed
 */
function get_site_option( $option, $default_value = false ) {
}

/**
 * @param array{fields: 'ids'} $args
 *
 * @return array<int>
 */
function get_sites( $args = array() ): array {
}

/**
 * @param int|string|WP_Post|null $post
 */
function has_blocks( $post = null ): bool {
}

function is_comment_feed(): bool {
}

/**
 * @param string $email
 * @param bool   $deprecated
 *
 * @return string|false
 */
function is_email( $email, $deprecated = false ) {
}

function is_login(): bool {
}

function is_multisite(): bool {
}

function is_ssl(): bool {
}

/**
 * @param string $content
 *
 * @return array<array{blockName: string|null, attrs: array<string, mixed>, innerBlocks: array<mixed>, innerHTML: string, innerContent: array<string|null>}>
 */
function parse_blocks( $content ): array {
}

/**
 * @param string               $post_type
 * @param string               $meta_key
 * @param array<string, mixed> $args
 */
function register_post_meta( $post_type, $meta_key, array $args ): bool {
}

/**
 * @param string $text
 * @param string $locale
 */
function remove_accents( $text, $locale = '' ): string {
}

function restore_current_blog(): bool {
}

/**
 * @param string $username
 * @param bool   $strict
 */
function sanitize_user( $username, $strict = false ): string {
}

/**
 * @param array<array<string, mixed>> $blocks
 */
function serialize_blocks( $blocks ): string {
}

/**
 * @param int  $new_blog_id
 * @param null $deprecated
 *
 * @return true
 */
function switch_to_blog( $new_blog_id, $deprecated = null ): bool {
}

/**
 * @param string $username
 *
 * @return int|false
 */
function username_exists( $username ) {
}

/**
 * @param string $username
 */
function validate_username( $username ): bool {
}

/**
 * @param string $username
 * @param string $password
 * @param string $email
 *
 * @return int|WP_Error
 */
function wp_create_user( $username, $password, $email = '' ) {
}

/**
 * @param int  $post_id
 * @param bool $force_delete
 *
 * @return WP_Post|false|null
 */
function wp_delete_post( $post_id = 0, $force_delete = false ) {
}

/**
 * @param int  $length
 * @param bool $special_chars
 * @param bool $extra_special_chars
 */
function wp_generate_password( $length = 12, $special_chars = true, $extra_special_chars = false ): string {
}

/**
 * @param string                                     $content
 * @param array<string, array<string, mixed>>|string $allowed_html
 * @param array<string>                              $allowed_protocols
 */
function wp_kses( $content, $allowed_html, $allowed_protocols = array() ): string {
}

/**
 * @param string $handle
 * @param string $domain
 * @param string $path
 */
function wp_set_script_translations( $handle, $domain = 'default', $path = '' ): bool {
}

/**
 * @param string           $text
 * @param WP_Post|int|null $post
 */
function wp_trim_excerpt( $text = '', $post = null ): string {
}
