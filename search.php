<?php
get_header();

$query = get_search_query();
?>
<section class="content-page">
	<div class="container">
		<h1><?php echo esc_html( $query ? 'Результаты поиска: ' . $query : 'Поиск по сайту' ); ?></h1>

		<?php if ( have_posts() ) : ?>
			<?php while ( have_posts() ) : the_post(); ?>
				<article <?php post_class(); ?>>
					<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<?php the_excerpt(); ?>
				</article>
			<?php endwhile; ?>

			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<p>По вашему запросу ничего не найдено.</p>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
