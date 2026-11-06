<?php
/**
 * Plugin uninstallation file.
 *
 * Deletes all the plugin data (options, rules, post and user meta and sessions) so that the database is clean after uninstall.
 *
 * @package skautis-integration
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die( 'Die, die, die!' );
}

/**
 * Deletes the plugin data of the current site.
 *
 * @return void
 */
function skautis_integration_uninstall_site() {
	global $wpdb;

	// defined in skautis-integration.php.
	delete_option( 'skautis_rewrite_rules_need_to_flush' );

	// defined in src/admin/class-settings.php and in the plugin activation.
	delete_option( 'skautis_integration_appid_prod' );
	delete_option( 'skautis_integration_appid_test' );
	delete_option( 'skautis_integration_appid_type' );
	delete_option( 'skautis_integration_activated_modules' );
	delete_option( 'skautis_integration_login_page_url' );
	delete_option( 'skautis-integration_login_page_url' );
	delete_option( 'skautis-integration_allowUsersDisconnectFromSkautis' );
	delete_option( 'skautis-integration_checkUserPrivilegesIfLoginBySkautis' );

	// defined in src/modules/Register/admin/class-settings.php.
	delete_option( 'skautis-integration_modules_register_defaultwpRole' );
	delete_option( 'skautis-integration_modules_register_notifications' );
	delete_option( 'skautis-integration_modules_register_rules' );

	// defined by the removed Shortcodes module.
	delete_option( 'skautis-integration_modules_shortcodes_visibilityMode' );

	// defined in src/modules/Visibility/admin/class-settings.php.
	delete_option( 'skautis-integration_modules_visibility_postTypes' );
	delete_option( 'skautis-integration_modules_visibility_visibilityMode' );
	delete_option( 'skautis-integration_modules_visibility_includeChildren' );

	// defined in src/migrations/class-autosave-meta-cleanup.php.
	delete_option( 'skautis_integration_autosave_meta_cleaned' );

	// The rules (src/rules/class-rules-init.php), including their meta and revisions. The post type is not registered during uninstall, so the IDs are loaded directly.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$rule_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s", 'skautis_rules' ) );
	foreach ( $rule_ids as $rule_id ) {
		wp_delete_post( intval( $rule_id ), true );
	}

	// The visibility rules of posts (src/modules/Visibility/admin/class-metabox.php and class-block-editor.php) and the rule data (src/rules/admin/class-admin.php).
	delete_post_meta_by_key( 'skautis-integration_rules' );
	delete_post_meta_by_key( 'skautis-integration_rules_includeChildren' );
	delete_post_meta_by_key( 'skautis-integration_rules_visibilityMode' );
	delete_post_meta_by_key( 'skautis-integration_rules_data' );

	// The skautIS sessions (src/auth/class-transient-session-adapter.php).
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_skautis-integration_session_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_skautis-integration_session_' ) . '%'
		)
	);

	flush_rewrite_rules();
}

if ( is_multisite() ) {
	foreach ( get_sites( array( 'fields' => 'ids' ) ) as $skautis_integration_site_id ) {
		switch_to_blog( intval( $skautis_integration_site_id ) );
		skautis_integration_uninstall_site();
		restore_current_blog();
	}
} else {
	skautis_integration_uninstall_site();
}

// The connections of WordPress users to skautIS users (src/auth/class-connect-and-disconnect-wp-account.php). Users are shared by all sites of a network.
delete_metadata( 'user', 0, 'skautisUserId_prod', '', true );
delete_metadata( 'user', 0, 'skautisUserId_test', '', true );
