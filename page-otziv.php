<?php

get_header();

while ( have_posts() ) :
	the_post();

	$page_h1 = roverland_field( 'page_h1', get_the_title() );
	?>
	<main class="feedback-page">
		<section class="legacy-page-hero feedback-page__hero">
			<div class="container legacy-page-hero__layout">
				<div class="legacy-page-hero__content">
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

					<p class="legacy-page-hero__eyebrow">Обратная связь</p>
					<h1 class="legacy-page-hero__title"><?php echo esc_html( $page_h1 ); ?></h1>
					<p class="legacy-page-hero__lead">Расскажите, как прошёл ваш визит. Мы внимательно разбираем каждое обращение и используем обратную связь, чтобы становиться лучше.</p>

					<div class="legacy-page-hero__actions">
						<a class="button button--primary" href="#feedback-form">Оставить отзыв</a>
						<a class="button button--outline" href="<?php echo esc_url( roverland_page_url( 'contacts' ) ); ?>">Контакты</a>
					</div>
				</div>

				<aside class="feedback-page__hero-card">
					<span class="feedback-page__hero-card-label">Rover Land</span>
					<strong>Нам важно знать, как прошёл ваш визит</strong>
					<ul>
						<li><span>01</span>Опишите ситуацию</li>
						<li><span>02</span>Выберите филиал</li>
						<li><span>03</span>Мы свяжемся с вами</li>
					</ul>
				</aside>
			</div>
		</section>

		<section class="feedback-page__form-section" id="feedback-form">
			<div class="container feedback-page__form-layout">
				<div class="feedback-page__form-intro">
					<p class="legacy-section-label">Обращение в Rover Land</p>
					<h2>Мы читаем каждое сообщение</h2>
					<p>Можно оставить благодарность, предложение или описать проблему. Чем подробнее информация, тем быстрее мы сможем разобраться.</p>

					<div class="feedback-page__note">
						<strong>Что будет дальше</strong>
						<p>Обращение попадёт ответственному сотруднику. При необходимости мы уточним детали по указанному телефону.</p>
					</div>
				</div>

				<div class="feedback-page__form">
					<?php echo do_shortcode( '[roverland_feedback_form]' ); ?>
				</div>
			</div>
		</section>
	</main>
	<?php
endwhile;

get_footer();
