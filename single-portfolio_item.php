<?php

get_header();

while ( have_posts() ) :
	the_post();

	$gallery = roverland_field( 'portfolio_gallery', array() );
	$content = roverland_field( 'portfolio_content', '' );
	?>
	<section class="portfolio-detail-hero">
		<div class="container">
			<?php
			get_template_part(
				'template-parts/elements/breadcrumbs',
				null,
				array(
					'items' => array(
						array( 'label' => 'Главная', 'url' => home_url( '/' ) ),
						array( 'label' => 'Портфолио', 'url' => roverland_page_url( 'portfolio' ) ),
						array( 'label' => get_the_title() ),
					),
				)
			);
			?>

			<div class="portfolio-detail-hero__copy">
				<p class="portfolio-detail-hero__eyebrow">Работа Rover Land</p>
				<h1><?php echo nl2br( esc_html( roverland_field( 'portfolio_h1', get_the_title() ) ) ); ?></h1>
				<?php if ( roverland_field( 'portfolio_lead', '', get_the_ID() ) ) : ?>
					<p><?php echo esc_html( roverland_field( 'portfolio_lead', '', get_the_ID() ) ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<section class="portfolio-detail-content">
		<div class="container portfolio-detail-content__layout">
			<div class="portfolio-detail-content__copy">
				<h2>О выполненной работе</h2>
				<?php echo wp_kses_post( $content ); ?>
			</div>

			<div class="portfolio-detail-content__aside">
				<span>Rover Land</span>
				<strong>Профессиональный ремонт и кузовные работы</strong>
				<a class="button button--primary button--wide" href="#appointment">Записаться в сервис</a>
			</div>
		</div>
	</section>

	<?php if ( is_array( $gallery ) && $gallery ) : ?>
		<section class="portfolio-detail-gallery" aria-label="Фотографии выполненной работы">
			<div class="container">
				<h2>Фотографии работы</h2>
				<div class="portfolio-detail-gallery__grid">
					<?php foreach ( $gallery as $row ) : ?>
						<?php
						$image     = $row['image'] ?? array();
						$image_url = roverland_image_url( $image );
						if ( ! $image_url ) {
							continue;
						}
						?>
						<figure>
							<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( roverland_image_alt( $image, get_the_title() ) ); ?>" loading="lazy" decoding="async">
						</figure>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<section class="portfolio-detail-service">
		<div class="container portfolio-detail-service__box">
			<h2><?php echo esc_html( roverland_field( 'portfolio_service_title', 'Нужна такая же работа?' ) ); ?></h2>
			<p>Запишитесь на осмотр автомобиля. Специалист Rover Land оценит состояние и предложит подходящий вариант ремонта.</p>
		</div>
	</section>

	<?php
	get_template_part( 'template-parts/blocks/appointment' );
	get_template_part(
		'template-parts/blocks/branches',
		null,
		array(
			'class' => 'branches',
			'title' => roverland_option( 'common_branches_title', '' ),
		)
	);
endwhile;

get_footer();
