<?php
get_header();
?>
<section class="content-page">
	<div class="container">
		<h1>Страница не найдена</h1>
		<p>Похоже, страница была удалена или адрес изменился.</p>
		<a class="button button--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>">На главную</a>
	</div>
</section>
<?php
get_footer();
