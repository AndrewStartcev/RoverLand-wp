<?php

get_header();

while ( have_posts() ) :
	the_post();

	$page_h1 = roverland_field(
		'page_h1',
		'Оригинальные автомобили Rover из Европы и Кореи'
	);

	$cases = new WP_Query(
		array(
			'post_type'      => 'portfolio_item',
			'post_status'    => 'publish',
			'posts_per_page' => 3,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
			'no_found_rows'  => true,
		)
	);
	$copy = json_decode(file_get_contents(get_template_directory() . '/data/vehicle-import-revision.json'), true);
	$get = function($key) use ($copy) { return roverland_field('import_' . $key, $copy[$key] ?? ''); };
	?>
<div class="vehicle-import-page">
<section class="vehicle-import-hero"><div class="container vehicle-import-hero__layout"><div class="vehicle-import-hero__content">
<?php get_template_part('template-parts/elements/breadcrumbs', null, array('items'=>array(array('label'=>'Главная','url'=>home_url('/')),array('label'=>get_the_title())))); ?>
<h1><?php echo esc_html($get('title')); ?></h1><p class="vehicle-import-hero__lead"><?php echo esc_html($get('lead')); ?></p>
<div class="vehicle-import-hero__actions"><a class="button button--primary" href="#appointment">Получить консультацию</a><a class="button button--outline" href="#vehicle-import-benefits">Как это работает</a></div></div>
<div class="vehicle-import-hero__panel"><div class="vehicle-import-hero__panel-head"><span>Rover Land</span><h2><?php echo esc_html($get('facts_title')); ?></h2></div><ul class="vehicle-import-hero__facts">
<?php foreach($get('facts') as $i=>$item): ?><li><span><?php echo esc_html(sprintf('%02d',$i+1)); ?></span><div><strong><?php echo esc_html($item['title']); ?></strong><p><?php echo esc_html($item['text']); ?></p></div></li><?php endforeach; ?></ul></div></div></section>
<section class="vehicle-import-benefits" id="vehicle-import-benefits"><div class="container"><div class="vehicle-import-heading"><h2><?php echo esc_html($get('benefits_title')); ?></h2><p><?php echo esc_html($get('benefits_lead')); ?></p></div><div class="vehicle-import-benefits__grid">
<?php foreach($get('benefits') as $i=>$item): ?><article class="vehicle-import-benefit"><span class="vehicle-import-benefit__number"><?php echo esc_html(sprintf('%02d',$i+1)); ?></span><h3><?php echo esc_html($item['title']); ?></h3><p><?php echo esc_html($item['text']); ?></p></article><?php endforeach; ?></div><p class="vehicle-import-price-note"><?php echo esc_html($get('benefits_note')); ?></p></div></section>
<section class="vehicle-import-process"><div class="container vehicle-import-process__layout"><div class="vehicle-import-process__intro"><h2><?php echo esc_html($get('process_title')); ?></h2></div><ol class="vehicle-import-process__steps">
<?php foreach($get('process') as $i=>$item): ?><li><span><?php echo esc_html(sprintf('%02d',$i+1)); ?></span><div><h3><?php echo esc_html($item['title']); ?></h3><p><?php echo esc_html($item['text']); ?></p></div></li><?php endforeach; ?></ol></div></section>
<section class="vehicle-import-benefits"><div class="container vehicle-import-heading"><h2><?php echo esc_html($get('price_title')); ?></h2><?php echo wp_kses_post(wpautop($get('price_text'))); ?><img class="vehicle-import-prices" src="<?php echo esc_url(roverland_image_url(roverland_field('import_price_image',array()),'assets/images/vehicle-import-prices.png')); ?>" alt="Стартовые цены автомобилей Land Rover и Volvo" loading="lazy"></div></section>
<section class="vehicle-import-process"><div class="container vehicle-import-heading"><h2><?php echo esc_html($get('service_title')); ?></h2><?php echo wp_kses_post(wpautop($get('service_text'))); ?></div></section>
		<?php if ( $cases->have_posts() ) : ?>
			<section class="vehicle-import-cases" aria-label="Наши кейсы">
				<div class="container">
					<div class="vehicle-import-cases__head">
						<div>
							<p class="vehicle-import-eyebrow">Опыт Rover Land</p>
							<h2>Наши кейсы</h2>
						</div>
						<a class="text-link" href="<?php echo esc_url( roverland_page_url( 'portfolio' ) ); ?>">
							Все работы <span aria-hidden="true">→</span>
						</a>
					</div>

					<div class="vehicle-import-cases__grid">
						<?php while ( $cases->have_posts() ) : $cases->the_post(); ?>
							<article class="vehicle-import-case">
								<a class="vehicle-import-case__media" href="<?php the_permalink(); ?>">
									<?php
									$image     = roverland_field( 'portfolio_card_image', array(), get_the_ID() );
									$image_url = roverland_image_url( $image );
									?>
									<?php if ( $image_url ) : ?>
										<img
											src="<?php echo esc_url( $image_url ); ?>"
											alt="<?php echo esc_attr( roverland_image_alt( $image, get_the_title() ) ); ?>"
											loading="lazy"
											decoding="async"
										>
									<?php else : ?>
										<span class="vehicle-import-case__placeholder">Rover Land</span>
									<?php endif; ?>
								</a>

								<div class="vehicle-import-case__body">
									<h3><a href="<?php the_permalink(); ?>"><?php echo esc_html( get_the_title() ); ?></a></h3>

									<?php $summary = roverland_field( 'portfolio_summary', '', get_the_ID() ); ?>
									<?php if ( $summary ) : ?>
										<p><?php echo esc_html( $summary ); ?></p>
									<?php endif; ?>

									<a class="text-link" href="<?php the_permalink(); ?>">
										Подробнее <span aria-hidden="true">→</span>
									</a>
								</div>
							</article>
						<?php endwhile; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<?php
		wp_reset_postdata();
		get_template_part( 'template-parts/blocks/appointment' );
		?>
	</div>
	<?php
endwhile;

get_footer();
