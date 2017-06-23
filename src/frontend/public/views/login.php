<?php get_header(); ?>

<?php
if ( ! isUserLoggedInSkautis() ) {
	?>
	<div class="wp-core-ui" style="text-align: center;">
		<a class="button button-primary button-hero pic-lilie" href="<?php echo getSkautisRegisterUrl(); ?>">
			Přihlásit se přes SkautIS
		</a>
	</div>
	<?php
} else {
	?>
	<div style="text-align: center;">
		<strong>Jste přihlášeni ve SkautISu</strong><br/>
		<a class="button" href="<?php echo getSkautisLogoutUrl(); ?>">
			Odhlásit se ze SkautISu
		</a>
	</div>
	<?php
}
?>

<?php get_sidebar(); ?>
<?php get_footer(); ?>