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
	?>
	<main class="vehicle-import-page">
		<section class="vehicle-import-hero">
			<div class="container vehicle-import-hero__layout">
				<div class="vehicle-import-hero__content">
					<?php
					get_template_part(
						'template-parts/elements/breadcrumbs',
						null,
						array(
							'items' => array(
								array( 'label' => 'Главная', 'url' => home_url( '/' ) ),
								array( 'label' => get_the_title() ),
							),
						)
					);
					?>

					<p class="vehicle-import-eyebrow">Автомобили из Европы и Кореи</p>
					<h1><?php echo esc_html( $page_h1 ); ?></h1>
					<p class="vehicle-import-hero__lead">
						Подберём автомобиль под ваши требования и бюджет, проверим его до покупки,
						сопроводим сделку и организуем доставку.
					</p>

					<div class="vehicle-import-hero__actions">
						<a class="button button--primary" href="#appointment">Получить консультацию</a>
						<a class="button button--outline" href="#vehicle-import-benefits">Как это работает</a>
					</div>
				</div>

				<div class="vehicle-import-hero__panel">
					<div class="vehicle-import-hero__panel-head">
						<span>Rover Land</span>
						<strong>Покупка автомобиля без лишних рисков</strong>
					</div>

					<ul class="vehicle-import-hero__facts">
						<li>
							<span>01</span>
							<div>
								<strong>Официальный договор</strong>
								<p>Условия сделки фиксируются документально.</p>
							</div>
						</li>
						<li>
							<span>02</span>
							<div>
								<strong>Полная проверка</strong>
								<p>Осмотр автомобиля с фото- и видеофиксацией.</p>
							</div>
						</li>
						<li>
							<span>03</span>
							<div>
								<strong>Прозрачная оплата</strong>
								<p>Безналичный расчёт напрямую продавцу.</p>
							</div>
						</li>
						<li>
							<span>04</span>
							<div>
								<strong>Большой выбор</strong>
								<p>Подбор практически любой зарубежной марки.</p>
							</div>
						</li>
					</ul>
				</div>
			</div>
		</section>

		<section class="vehicle-import-benefits" id="vehicle-import-benefits">
			<div class="container">
				<div class="vehicle-import-heading">
					<p class="vehicle-import-eyebrow">Почему это удобно</p>
					<h2>Подбираем автомобиль под задачу, а не под остатки на площадке</h2>
					<p>
						Автомобили из Европы и Кореи дают больше вариантов по комплектациям,
						состоянию и стоимости. Мы берём на себя основные этапы сделки.
					</p>
				</div>

				<div class="vehicle-import-benefits__grid">
					<article class="vehicle-import-benefit">
						<span class="vehicle-import-benefit__number">01</span>
						<h3>Выгодная стоимость</h3>
						<p>Подбираем варианты на зарубежных рынках и сравниваем их с доступными предложениями в России.</p>
					</article>

					<article class="vehicle-import-benefit">
						<span class="vehicle-import-benefit__number">02</span>
						<h3>Юридическая прозрачность</h3>
						<p>Сделка проходит официально, с договором и понятными условиями на каждом этапе.</p>
					</article>

					<article class="vehicle-import-benefit">
						<span class="vehicle-import-benefit__number">03</span>
						<h3>Проверка до покупки</h3>
						<p>Перед бронированием проводим осмотр и показываем состояние автомобиля на фото и видео.</p>
					</article>

					<article class="vehicle-import-benefit">
						<span class="vehicle-import-benefit__number">04</span>
						<h3>Не ограничиваем выбор</h3>
						<p>Работаем не только с Land Rover и Volvo — можно подобрать практически любую зарубежную марку.</p>
					</article>
				</div>
			</div>
		</section>

		<section class="vehicle-import-process">
			<div class="container vehicle-import-process__layout">
				<div class="vehicle-import-process__intro">
					<p class="vehicle-import-eyebrow">Процесс покупки</p>
					<h2>От запроса до автомобиля в России</h2>
					<p>
						Сначала определяем бюджет и требования, затем подбираем конкретные автомобили
						и только после согласования переходим к покупке.
					</p>
				</div>

				<ol class="vehicle-import-process__steps">
					<li>
						<span>01</span>
						<div>
							<h3>Фиксируем требования</h3>
							<p>Марка, модель, год, комплектация, пробег, бюджет и важные для вас опции.</p>
						</div>
					</li>
					<li>
						<span>02</span>
						<div>
							<h3>Подбираем варианты</h3>
							<p>Ищем подходящие автомобили и предоставляем информацию для сравнения.</p>
						</div>
					</li>
					<li>
						<span>03</span>
						<div>
							<h3>Проверяем автомобиль</h3>
							<p>Проводим осмотр до бронирования, фиксируем состояние на фото и видео.</p>
						</div>
					</li>
					<li>
						<span>04</span>
						<div>
							<h3>Оформляем сделку</h3>
							<p>Заключаем договор и сопровождаем оплату и необходимые документы.</p>
						</div>
					</li>
					<li>
						<span>05</span>
						<div>
							<h3>Организуем доставку</h3>
							<p>Сопровождаем автомобиль до завершения процесса передачи клиенту.</p>
						</div>
					</li>
				</ol>
			</div>
		</section>

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
	</main>
	<?php
endwhile;

get_footer();
