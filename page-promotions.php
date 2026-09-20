<?php
/*
Template Name: Акции
*/

get_header();

while ( have_posts() ) :
	the_post();

	$hero_image = roverland_field( 'promotions_hero_image', array() );
	$hero_url   = roverland_image_url( $hero_image );
	$promotions = new WP_Query(
		array(
			'post_type'      => 'promotion',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		)
	);
	?>
	<section class="promotions-hero"<?php if ( $hero_url ) : ?> style="--promotions-hero-image:url('<?php echo esc_url( $hero_url ); ?>');"<?php endif; ?>>
		<div class="container promotions-hero__container">
			<div class="promotions-hero__content">
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
				<h1 class="promotions-hero__title"><?php echo nl2br( esc_html( roverland_field( 'promotions_hero_title', get_the_title() ) ) ); ?></h1>
				<?php if ( roverland_field( 'promotions_hero_lead', '' ) ) : ?>
					<p class="promotions-hero__lead"><?php echo esc_html( roverland_field( 'promotions_hero_lead', '' ) ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<section class="promotions-catalog" aria-label="Акции Rover Land">
		<div class="container promotions-catalog__list">
			<?php while ( $promotions->have_posts() ) : $promotions->the_post(); ?>
				<article class="promo-card">
					<?php
					$image     = roverland_field( 'promotion_card_image', array(), get_the_ID() );
					$image_url = roverland_image_url( $image );
					?>
					<?php if ( $image_url ) : ?>
						<img class="promo-card__image" src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( roverland_image_alt( $image, get_the_title() ) ); ?>" loading="lazy" decoding="async">
					<?php endif; ?>
					<div class="promo-card__body">
						<h2><?php echo esc_html( get_the_title() ); ?></h2>
						<p><?php echo esc_html( roverland_field( 'promotion_summary', '', get_the_ID() ) ); ?></p>
						<a class="text-link" href="<?php the_permalink(); ?>">Узнать больше <span aria-hidden="true">→</span></a>
					</div>
				</article>
			<?php endwhile; wp_reset_postdata(); ?>

			<article class="promo-card promo-card--contact">
				<div class="promo-card__contact-content">
					<h2>Индивидуальные условия</h2>
					<p>У вас особый случай или требуется комплексное решение? Свяжитесь с нами — подберём подходящий вариант обслуживания.</p>
				</div>
				<?php if ( file_exists( get_theme_file_path( 'assets/images/icons/ui/promo-ticket.svg' ) ) ) : ?>
					<img class="promo-card__ticket" src="<?php echo esc_url( roverland_asset( 'assets/images/icons/ui/promo-ticket.svg' ) ); ?>" width="150" height="150" alt="" aria-hidden="true">
				<?php endif; ?>
				<a class="button button--contact" href="<?php echo esc_url( roverland_page_url( 'contacts' ) ); ?>">Связаться с нами</a>
			</article>
		</div>
	</section>

	<?php
	if ( roverland_field( 'promotions_show_appointment', true ) ) {
		get_template_part( 'template-parts/blocks/appointment' );
	}
endwhile;

get_footer();
