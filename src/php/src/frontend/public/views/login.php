<?php
/**
 * Renders the login/logout button.
 *
 * @package skautis-integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header(); ?>

<?php
if ( ! isUserLoggedInSkautis() ) {
	?>
	<div class="wp-core-ui" style="text-align: center;">
		<a class="button button-primary button-hero button-skautis" href="<?php echo esc_url( getSkautisRegisterUrl() ); ?>">
			<?php esc_html_e( 'Log in with SkautIS', 'skautis-integration' ); ?>
		</a>
	</div>
	<?php
} else {
	?>
	<div style="text-align: center;">
		<strong><?php esc_html_e( 'You are logged in to SkautIS', 'skautis-integration' ); ?></strong>
		<br/>
		<a class="button" href="<?php echo esc_url( getSkautisLogoutUrl() ); ?>">
			<?php esc_html_e( 'Log out of SkautIS', 'skautis-integration' ); ?>
		</a>
	</div>
	<?php
}
?>

<?php get_sidebar(); ?>
<?php get_footer(); ?>
