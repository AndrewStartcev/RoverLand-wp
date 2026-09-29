<?php

get_header();

while ( have_posts() ) :
	the_post();

	$page_h1      = roverland_field( 'page_h1', get_the_title() );
	$raw_content  = (string) get_post_field( 'post_content', get_the_ID() );
	$raw_content  = preg_replace( '/\\[roverland_partner_form(?:[^\\]]*)\\]/u', '', $raw_content );
	$raw_content  = preg_replace( '#<h2[^>]*>\\s*Заявка на партнерство\\s*</h2>#iu', '', $raw_content );
	$content_html = apply_filters( 'the_content', $raw_content );
	?>
	<main class="partner-page">
		<section class="legacy-page-hero partner-page__hero">
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

					<p class="legacy-page-hero__eyebrow">Партнерство Rover Land</p>
					<h1 class="legacy-page-hero__title"><?php echo esc_html( $page_h1 ); ?></h1>
					<p class="legacy-page-hero__lead">Развивайте существующий автосервис или запускайте новую СТО вместе с командой Rover Land.</p>

					<div class="legacy-page-hero__actions">
						<a class="button button--primary" href="#partner-form">Стать партнером</a>
					</div>
				</div>

				<div class="partner-page__stats" aria-label="Ключевые условия партнерства">
					<div class="partner-page__stat">
						<strong>20+</strong>
						<span>лет опыта и экспертизы</span>
					</div>
					<div class="partner-page__stat">
						<strong>7+</strong>
						<span>постов для формата СТО</span>
					</div>
					<div class="partner-page__stat">
						<strong>300 м²</strong>
						<span>рекомендуемая площадь</span>
					</div>
				</div>
			</div>
		</section>

		<section class="partner-page__content-section">
			<div class="container partner-page__content-layout">
				<article class="partner-page__article">
					<?php echo $content_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</article>

				<aside class="partner-page__aside">
					<p class="legacy-section-label">Партнерская программа</p>
					<h2>Обсудим ваш проект</h2>
					<p>Условия сотрудничества рассматриваются индивидуально — с учетом локации, формата СТО и ваших текущих возможностей.</p>
					<a class="button button--primary button--wide" href="#partner-form">Оставить заявку</a>
					<span>Москва и Московская область — приоритетный регион.</span>
				</aside>
			</div>
		</section>

		<section class="partner-page__form-section" id="partner-form">
			<div class="container partner-page__form-layout">
				<div class="partner-page__form-intro">
					<p class="legacy-section-label">Следующий шаг</p>
					<h2>Расскажите о своей СТО</h2>
					<p>Заполните короткую анкету. Мы изучим исходные данные и свяжемся, чтобы обсудить возможный формат сотрудничества.</p>
				</div>

				<div class="partner-page__form">
					<?php echo do_shortcode( '[roverland_partner_form]' ); ?>
				</div>
			</div>
		</section>
	</main>
	<?php
endwhile;

get_footer();
