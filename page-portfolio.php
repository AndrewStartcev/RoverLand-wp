<?php
/*
Template Name: Портфолио
*/

get_header();

while ( have_posts() ) :
	the_post();

	$works = new WP_Query(
		array(
			'post_type'      => 'portfolio_item',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		)
	);
	?>
	<section class="portfolio-intro">
		<div class="container">
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

			<h1 class="portfolio-intro__title"><?php echo nl2br( esc_html( roverland_field( 'portfolio_title', get_the_title() ) ) ); ?></h1>
			<?php if ( roverland_field( 'portfolio_lead', '' ) ) : ?>
				<p class="portfolio-intro__lead"><?php echo esc_html( roverland_field( 'portfolio_lead', '' ) ); ?></p>
			<?php endif; ?>

			<div class="portfolio-tabs" aria-label="Тип портфолио">
				<span class="portfolio-tabs__button is-active">Галерея</span>
			</div>
		</div>
	</section>

	<section class="portfolio-catalog" aria-label="Выполненные работы Rover Land">
		<div class="container portfolio-catalog__list">
			<?php while ( $works->have_posts() ) : $works->the_post(); ?>
				<article class="portfolio-card">
					<?php
					$image     = roverland_field( 'portfolio_card_image', array(), get_the_ID() );
					$image_url = roverland_image_url( $image );
					?>
					<?php if ( $image_url ) : ?>
						<img class="portfolio-card__image" src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( roverland_image_alt( $image, get_the_title() ) ); ?>" loading="lazy" decoding="async">
					<?php endif; ?>
					<div class="portfolio-card__body">
						<h2><?php echo esc_html( get_the_title() ); ?></h2>
						<?php if ( roverland_field( 'portfolio_summary', '', get_the_ID() ) ) : ?>
							<p><?php echo esc_html( roverland_field( 'portfolio_summary', '', get_the_ID() ) ); ?></p>
						<?php endif; ?>
						<a class="text-link" href="<?php the_permalink(); ?>">Подробнее <span aria-hidden="true">→</span></a>
					</div>
				</article>
			<?php endwhile; wp_reset_postdata(); ?>
		</div>
	</section>

	<section class="portfolio-cta">
		<div class="container">
			<div class="portfolio-cta__box">
				<h2><?php echo esc_html( roverland_field( 'portfolio_cta_title', 'Ваш автомобиль достоин лучшего' ) ); ?></h2>
				<p><?php echo nl2br( esc_html( roverland_field( 'portfolio_cta_text', '' ) ) ); ?></p>
				<a class="button button--primary" href="<?php echo esc_url( home_url( '/#appointment' ) ); ?>"><?php echo esc_html( roverland_field( 'portfolio_cta_button', 'Записаться в сервис' ) ); ?></a>
			</div>
		</div>
	</section>
	<?php
endwhile;

get_footer();
