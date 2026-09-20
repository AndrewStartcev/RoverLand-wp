<?php

get_header();

while ( have_posts() ) :
	the_post();

	$image     = roverland_field( 'promotion_hero_image', array() );
	$image_url = roverland_image_url( $image );
	$content   = roverland_field( 'promotion_content', '' );
	$note      = roverland_field( 'promotion_note', '' );
	?>
	<section class="promo-detail">
		<div class="container">
			<?php
			get_template_part(
				'template-parts/elements/breadcrumbs',
				null,
				array(
					'items' => array(
						array( 'label' => 'Главная', 'url' => home_url( '/' ) ),
						array( 'label' => 'Акции', 'url' => roverland_page_url( 'aktsii' ) ),
						array( 'label' => get_the_title() ),
					),
				)
			);
			?>

			<div class="promo-detail__layout">
				<div class="promo-detail__content">
					<p class="promo-detail__eyebrow">Акция Rover Land</p>
					<h1><?php echo nl2br( esc_html( roverland_field( 'promotion_h1', get_the_title() ) ) ); ?></h1>
					<?php echo wp_kses_post( $content ); ?>

					<?php if ( $note ) : ?>
						<div class="promo-detail__note"><?php echo esc_html( $note ); ?></div>
					<?php endif; ?>

					<a class="button button--primary" href="#appointment">Записаться на сервис</a>
				</div>

				<?php if ( $image_url ) : ?>
					<div class="promo-detail__media">
						<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( roverland_image_alt( $image, get_the_title() ) ); ?>" loading="eager" decoding="async">
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<?php
	if ( roverland_field( 'promotion_show_appointment', true ) ) {
		get_template_part( 'template-parts/blocks/appointment' );
	}

	if ( roverland_field( 'promotion_show_branches', true ) ) {
		get_template_part(
			'template-parts/blocks/branches',
			null,
			array(
				'class' => 'branches',
				'title' => roverland_option( 'common_branches_title', '' ),
			)
		);
	}
endwhile;

get_footer();
