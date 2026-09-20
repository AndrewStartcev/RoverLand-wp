<?php

get_header();

while ( have_posts() ) :
	the_post();

	$hero_image     = roverland_field( 'home_hero_image', array() );
	$hero_image_url = roverland_image_url( $hero_image );
	$stats          = roverland_field( 'home_stats', array() );
	$services       = roverland_field( 'home_services', array() );
	$advantages     = roverland_field( 'home_advantages', array() );
	$offers         = roverland_field( 'home_offers', array() );
	?>
	<section class="hero"<?php if ( $hero_image_url ) : ?> style="--home-hero-image:url('<?php echo esc_url( $hero_image_url ); ?>');"<?php endif; ?>>
		<div class="container hero__container">
			<div class="hero__content">
				<?php if ( roverland_field( 'home_hero_eyebrow', '' ) ) : ?><p class="hero__eyebrow"><?php echo esc_html( roverland_field( 'home_hero_eyebrow', '' ) ); ?></p><?php endif; ?>
				<h1 class="hero__title"><span><?php echo esc_html( roverland_field( 'home_hero_title_first', '' ) ); ?></span><?php echo esc_html( roverland_field( 'home_hero_title_second', '' ) ); ?></h1>
				<?php if ( roverland_field( 'home_hero_text', '' ) ) : ?><p class="hero__text"><?php echo esc_html( roverland_field( 'home_hero_text', '' ) ); ?></p><?php endif; ?>
				<div class="hero__actions">
					<a class="button button--primary" href="#appointment"><?php echo esc_html( roverland_field( 'home_hero_primary_label', 'Запись на ТО' ) ); ?></a>
					<a class="button button--outline" href="<?php echo esc_url( roverland_page_url( 'aktsii' ) ); ?>"><?php echo esc_html( roverland_field( 'home_hero_secondary_label', 'Наши акции' ) ); ?></a>
				</div>
			</div>
		</div>
	</section>

	<?php if ( is_array( $stats ) && $stats ) : ?>
		<section class="stats" aria-label="Rover Land в цифрах"><div class="container stats__list">
			<?php foreach ( $stats as $stat ) : ?><div class="stat"><strong class="stat__value"><?php echo esc_html( $stat['value'] ?? '' ); ?></strong><span class="stat__label"><?php echo esc_html( $stat['label'] ?? '' ); ?></span></div><?php endforeach; ?>
		</div></section>
	<?php endif; ?>

	<section class="services" id="services">
		<div class="container">
			<div class="section-heading section-heading--row">
				<div><h2 class="section-heading__title"><?php echo esc_html( roverland_field( 'home_services_title', '' ) ); ?></h2><?php if ( roverland_field( 'home_services_text', '' ) ) : ?><p class="section-heading__text"><?php echo esc_html( roverland_field( 'home_services_text', '' ) ); ?></p><?php endif; ?></div>
				<a class="text-link" href="<?php echo esc_url( roverland_page_url( 'servis' ) ); ?>"><?php echo esc_html( roverland_field( 'home_services_link_text', 'Все услуги' ) ); ?> <span aria-hidden="true">→</span></a>
			</div>

			<?php if ( is_array( $services ) && $services ) : ?><div class="services__list">
				<?php foreach ( $services as $service ) : ?>
					<article class="service-card">
						<?php $image = $service['image'] ?? array(); $image_url = roverland_image_url( $image ); ?>
						<?php if ( $image_url ) : ?><img class="service-card__image" src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( roverland_image_alt( $image, $service['title'] ?? '' ) ); ?>" loading="lazy" decoding="async"><?php endif; ?>
						<div class="service-card__body"><h3><?php echo esc_html( $service['title'] ?? '' ); ?></h3><p><?php echo esc_html( $service['text'] ?? '' ); ?></p><?php if ( ! empty( $service['url'] ) ) : ?><a class="text-link" href="<?php echo esc_url( home_url( $service['url'] ) ); ?>">Подробнее</a><?php endif; ?></div>
					</article>
				<?php endforeach; ?>
			</div><?php endif; ?>
		</div>
	</section>

	<?php if ( is_array( $advantages ) && $advantages ) : ?>
		<section class="advantages" aria-label="Преимущества Rover Land"><div class="container advantages__list">
			<?php foreach ( $advantages as $advantage ) : ?>
				<article class="advantage-card">
					<?php $icon_url = roverland_image_url( $advantage['icon'] ?? array() ); ?>
					<?php if ( $icon_url ) : ?><div class="advantage-card__icon" aria-hidden="true"><img src="<?php echo esc_url( $icon_url ); ?>" alt=""></div><?php endif; ?>
					<h3><?php echo esc_html( $advantage['title'] ?? '' ); ?></h3><p><?php echo esc_html( $advantage['text'] ?? '' ); ?></p>
				</article>
			<?php endforeach; ?>
		</div></section>
	<?php endif; ?>

	<section class="offers" id="offers">
		<div class="container">
			<div class="section-heading section-heading--center"><h2 class="section-heading__title"><?php echo esc_html( roverland_field( 'home_offers_title', '' ) ); ?></h2><?php if ( roverland_field( 'home_offers_text', '' ) ) : ?><p class="section-heading__text"><?php echo esc_html( roverland_field( 'home_offers_text', '' ) ); ?></p><?php endif; ?></div>
			<?php if ( is_array( $offers ) && $offers ) : ?><div class="offers-slider swiper" data-offers-slider><div class="swiper-wrapper">
				<?php foreach ( $offers as $offer ) : ?>
					<article class="offer-card swiper-slide">
						<?php $image = $offer['image'] ?? array(); $image_url = roverland_image_url( $image ); ?>
						<?php if ( $image_url ) : ?><img class="offer-card__image" src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( roverland_image_alt( $image, $offer['title'] ?? '' ) ); ?>" loading="lazy" decoding="async"><?php endif; ?>
						<div class="offer-card__body"><h3><?php echo esc_html( $offer['title'] ?? '' ); ?></h3><p><?php echo esc_html( $offer['text'] ?? '' ); ?></p><?php if ( ! empty( $offer['url'] ) ) : ?><a class="text-link" href="<?php echo esc_url( home_url( $offer['url'] ) ); ?>">Узнать больше <span aria-hidden="true">→</span></a><?php endif; ?></div>
					</article>
				<?php endforeach; ?>
			</div><div class="offers-slider__pagination swiper-pagination"></div></div><?php endif; ?>
		</div>
	</section>

	<?php
	if ( roverland_field( 'home_show_appointment', true ) ) {
		get_template_part( 'template-parts/blocks/appointment' );
	}
	if ( roverland_field( 'home_show_branches', true ) ) {
		get_template_part( 'template-parts/blocks/branches', null, array( 'class' => 'branches', 'title' => roverland_option( 'common_branches_title', '' ) ) );
	}
	if ( roverland_field( 'home_show_map', true ) ) {
		get_template_part( 'template-parts/blocks/home-map' );
	}
endwhile;

get_footer();
