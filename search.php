<?php
get_header();

$query = get_search_query();
?>
<section class="content-page search-results-page">
	<div class="container">
		<h1><?php echo esc_html( $query ? 'Результаты поиска: ' . $query : 'Поиск по сайту' ); ?></h1>

        <form class="search-repeat" action="<?php echo esc_url(home_url('/')); ?>" method="get" role="search"><label for="search-repeat-input">Поиск по сайту</label><div><input class="field__control" id="search-repeat-input" type="search" name="s" value="<?php echo esc_attr($query); ?>" placeholder="Услуга или модель автомобиля"><button class="button button--primary" type="submit">Найти</button></div></form>
        <?php if ( have_posts() ) : ?>
			<?php while ( have_posts() ) : the_post(); ?>
				<article <?php post_class(); ?>>
					<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<?php the_excerpt(); ?>
				</article>
			<?php endwhile; ?>

			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<p>По вашему запросу ничего не найдено. Попробуйте изменить запрос или выберите раздел ниже.</p><nav class="search-suggestions" aria-label="Возможно, вам подойдёт"><h2>Возможно, вам подойдёт</h2><div><?php foreach (array('/'=>'Главная страница', '/remont/'=>'Ремонт автомобиля', '/servis/to/'=>'Техническое обслуживание', '/servis/diagnostika/'=>'Диагностика') as $path=>$label) : ?><a class="service-catalog-card" href="<?php echo esc_url(home_url($path)); ?>"><?php echo esc_html($label); ?> <span aria-hidden="true">→</span></a><?php endforeach; ?></div></nav>
		<?php endif; ?>
	</div>
</section>
<?php
if (!have_posts()) get_template_part('template-parts/blocks/appointment', null, array('title'=>'Оставьте свой номер телефона', 'accent'=>'и мы свяжемся с вами', 'description'=>'Оставьте свои контактные данные, и наш мастер-консультант перезвонит в течение 15 минут для уточнения деталей и записи.', 'button'=>'Отправить заявку'));
get_footer();
