<?php get_header(); ?>

<?php
if ( ! isUserLoggedInSkautis() ) {
	?>
	<div class="wp-core-ui" style="text-align: center;">
		<a class="button button-primary button-hero pic-lilie" href="<?php echo getSkautisRegisterUrl(); ?>">
			<?php _e( 'Log in with skautIS', 'skautis-integration' ); ?>
		</a>
	</div>
	<?php
} else {
	?>
	<div style="text-align: center;">
		<strong>Jste přihlášeni ve skautISu</strong>
		<br/>
		<a class="button" href="<?php echo getSkautisLogoutUrl(); ?>">
			<?php _e( 'Log out of skautIS', 'skautis-integration' ); ?>
		</a>
	</div>
	<?php
}
?>

<?php get_sidebar(); ?>
<?php get_footer(); ?>
