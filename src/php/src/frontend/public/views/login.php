<?php
/**
 * Renders the login/logout button.
 *
 * @package skautis-integration
 */

get_header(); ?>

<?php
if ( ! isUserLoggedInSkautis() ) {
	?>
	<div class="wp-core-ui" style="text-align: center;">
		<a class="button button-primary button-hero button-skautis" href="<?php echo esc_url( getSkautisRegisterUrl() ); ?>">
			<?php esc_html_e( 'Log in with skautIS', 'skautis-integration' ); ?>
		</a>
	</div>
	<?php
} else {
	?>
	<div style="text-align: center;">
		<strong>Jste přihlášeni ve skautISu</strong>
		<br/>
		<a class="button" href="<?php echo esc_url( getSkautisLogoutUrl() ); ?>">
			<?php esc_html_e( 'Log out of skautIS', 'skautis-integration' ); ?>
		</a>
	</div>
	<?php
}
?>

<?php get_sidebar(); ?>
<?php get_footer(); ?>
