<?php
/*
Template Name: О компании
*/

get_header();

while ( have_posts() ) :
	the_post();

	$hero_eyebrow = roverland_field( 'about_hero_eyebrow', '' );
	$hero_title   = roverland_field( 'about_hero_title', get_the_title() );
	$hero_lead      = roverland_field( 'about_hero_lead', '' );
	$hero_image     = roverland_field( 'about_hero_image', array() );
	$hero_image_url = roverland_image_url( $hero_image, 'assets/images/content/about-hero.png' );

	$service_title   = roverland_field( 'about_service_title', '' );
	$service_content = roverland_field( 'about_service_content', '' );
	$award            = roverland_field( 'about_service_award', array() );

	$parts_title   = roverland_field( 'about_parts_title', '' );
	$parts_content = roverland_field( 'about_parts_content', '' );
	$parts_gallery = roverland_field( 'about_parts_gallery', array() );

	$stats = roverland_field( 'about_stats', array() );

	$benefits_title = roverland_field( 'about_benefits_title', '' );
	$benefits_lead  = roverland_field( 'about_benefits_lead', '' );
	$benefits       = roverland_field( 'about_benefits', array() );
	?>
	<section class="about-hero"<?php if ( $hero_image_url ) : ?> style="--about-hero-image: url('<?php echo esc_url( $hero_image_url ); ?>');"<?php endif; ?>>
		<div class="container about-hero__container">
			<div class="about-hero__content">
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

				<?php if ( $hero_eyebrow ) : ?><p class="about-hero__eyebrow"><?php echo esc_html( $hero_eyebrow ); ?></p><?php endif; ?>
				<h1 class="about-hero__title"><?php echo nl2br( esc_html( $hero_title ) ); ?></h1>
				<?php if ( $hero_lead ) : ?><p class="about-hero__lead"><?php echo esc_html( $hero_lead ); ?></p><?php endif; ?>
			</div>
		</div>
	</section>

	<section class="about-service-info">
		<div class="container about-service-info__layout">
			<div class="about-service-info__content">
				<?php if ( $service_title ) : ?><h2><?php echo nl2br( esc_html( $service_title ) ); ?></h2><?php endif; ?>
				<?php echo wp_kses_post( $service_content ); ?>
			</div>

			<?php
			$award_url = roverland_image_url( $award, 'assets/images/content/about-yandex-award.png' );
			if ( $award_url ) :
				?>
				<div class="about-service-info__award">
					<img src="<?php echo esc_url( $award_url ); ?>" alt="<?php echo esc_attr( roverland_image_alt( $award, 'Награда Яндекс — Хорошее место' ) ); ?>" loading="lazy" decoding="async">
				</div>
			<?php endif; ?>
		</div>
	</section>

	<section class="about-parts">
		<div class="container about-parts__layout">
			<div class="about-parts__content">
				<?php if ( $parts_title ) : ?><h2><?php echo nl2br( esc_html( $parts_title ) ); ?></h2><?php endif; ?>
				<?php echo wp_kses_post( $parts_content ); ?>

				<div class="about-parts__actions">
					<a class="button button--primary" href="<?php echo esc_url( roverland_page_url( 'vacancies' ) ); ?>">Стать частью команды</a>
					<a class="button button--primary" href="<?php echo esc_url( roverland_page_url( 'kompaniya/istoriya' ) ); ?>">Наша история</a>
				</div>
			</div>

			<?php if ( is_array( $parts_gallery ) && $parts_gallery ) : ?>
				<div class="about-gallery swiper" data-about-gallery>
					<div class="swiper-wrapper">
						<?php foreach ( $parts_gallery as $row ) : ?>
							<?php
							$image = isset( $row['image'] ) ? $row['image'] : array();
							$url   = roverland_image_url( $image );

							if ( ! $url ) {
								continue;
							}
							?>
							<div class="swiper-slide">
								<img src="<?php echo esc_url( $url ); ?>" alt="<?php echo esc_attr( roverland_image_alt( $image, 'Rover Land' ) ); ?>" loading="lazy" decoding="async">
							</div>
						<?php endforeach; ?>
					</div>
					<div class="about-gallery__pagination swiper-pagination"></div>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<?php if ( is_array( $stats ) && $stats ) : ?>
		<section class="about-stats" aria-label="Rover Land в цифрах">
			<div class="container about-stats__list">
				<?php foreach ( $stats as $stat ) : ?>
					<div class="about-stat">
						<strong><?php echo esc_html( isset( $stat['value'] ) ? $stat['value'] : '' ); ?></strong>
						<span><?php echo esc_html( isset( $stat['label'] ) ? $stat['label'] : '' ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<section class="about-benefits" id="team">
		<div class="container about-benefits__layout">
			<div class="about-benefits__intro">
				<?php if ( $benefits_title ) : ?><h2><?php echo nl2br( esc_html( $benefits_title ) ); ?></h2><?php endif; ?>
				<?php if ( $benefits_lead ) : ?><p><?php echo esc_html( $benefits_lead ); ?></p><?php endif; ?>
			</div>

			<?php if ( is_array( $benefits ) && $benefits ) : ?>
				<div class="about-benefits__list">
					<?php foreach ( $benefits as $benefit ) : ?>
						<article class="about-benefit-card">
							<?php
							$icon = isset( $benefit['icon'] ) ? $benefit['icon'] : array();
							$url  = roverland_image_url( $icon );

							if ( $url ) :
								?>
								<img src="<?php echo esc_url( $url ); ?>" width="24" height="24" alt="" aria-hidden="true">
							<?php endif; ?>

							<h3><?php echo esc_html( isset( $benefit['title'] ) ? $benefit['title'] : '' ); ?></h3>
							<p><?php echo esc_html( isset( $benefit['text'] ) ? $benefit['text'] : '' ); ?></p>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>
	<?php
endwhile;

get_footer();
