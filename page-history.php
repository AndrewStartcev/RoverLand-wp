<?php
/*
Template Name: История
*/

get_header();

while ( have_posts() ) :
	the_post();

	$hero_title = roverland_field( 'history_hero_title', get_the_title() );
	$hero_lead      = roverland_field( 'history_hero_lead', '' );
	$hero_image     = roverland_field( 'history_hero_image', array() );
	$hero_image_url = roverland_image_url( $hero_image, 'assets/images/content/history-hero.png' );

	$expertise_title   = roverland_field( 'history_expertise_title', '' );
	$expertise_content = roverland_field( 'history_expertise_content', '' );
	$expertise_images  = roverland_field( 'history_expertise_images', array() );

	$timeline_eyebrow = roverland_field( 'history_timeline_eyebrow', '' );
	$timeline_title   = roverland_field( 'history_timeline_title', '' );
	$timeline         = roverland_field( 'history_timeline', array() );

	$values_eyebrow = roverland_field( 'history_values_eyebrow', '' );
	$values_title   = roverland_field( 'history_values_title', '' );
	$values         = roverland_field( 'history_values', array() );

	$branches_title = roverland_field( 'history_branches_title', roverland_option( 'common_branches_title', '' ) );
	?>
	<section class="history-hero"<?php if ( $hero_image_url ) : ?> style="--history-hero-image: url('<?php echo esc_url( $hero_image_url ); ?>');"<?php endif; ?>>
		<div class="container history-hero__container">
			<div class="history-hero__content">
				<?php
				get_template_part(
					'template-parts/elements/breadcrumbs',
					null,
					array(
						'items' => array(
							array( 'label' => 'Главная', 'url' => home_url( '/' ) ),
							array( 'label' => 'О компании', 'url' => roverland_page_url( 'kompaniya' ) ),
							array( 'label' => get_the_title() ),
						),
					)
				);
				?>

				<h1 class="history-hero__title"><?php echo nl2br( esc_html( $hero_title ) ); ?></h1>
				<?php if ( $hero_lead ) : ?><p class="history-hero__lead"><?php echo esc_html( $hero_lead ); ?></p><?php endif; ?>
			</div>
		</div>
	</section>

	<section class="history-expertise">
		<div class="container history-expertise__layout">
			<div class="history-expertise__content">
				<?php if ( $expertise_title ) : ?><h2><?php echo nl2br( esc_html( $expertise_title ) ); ?></h2><?php endif; ?>
				<?php echo wp_kses_post( $expertise_content ); ?>
			</div>

			<?php if ( is_array( $expertise_images ) && $expertise_images ) : ?>
				<div class="history-expertise__media">
					<?php foreach ( $expertise_images as $row ) : ?>
						<?php
						$image = isset( $row['image'] ) ? $row['image'] : array();
						$url   = roverland_image_url( $image );

						if ( ! $url ) {
							continue;
						}
						?>
						<img src="<?php echo esc_url( $url ); ?>" alt="<?php echo esc_attr( roverland_image_alt( $image, 'История Rover Land' ) ); ?>" loading="lazy" decoding="async">
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<section class="history-timeline-section">
		<div class="container">
			<div class="history-section-heading">
				<?php if ( $timeline_eyebrow ) : ?><p><?php echo esc_html( $timeline_eyebrow ); ?></p><?php endif; ?>
				<?php if ( $timeline_title ) : ?><h2><?php echo nl2br( esc_html( $timeline_title ) ); ?></h2><?php endif; ?>
			</div>

			<?php if ( is_array( $timeline ) && $timeline ) : ?>
				<div class="history-timeline">
					<?php foreach ( $timeline as $index => $item ) : ?>
						<?php
						$is_right = 1 === ( $index % 2 );
						$icon     = isset( $item['icon'] ) ? $item['icon'] : array();
						$icon_url = roverland_image_url( $icon );
						?>
						<article class="history-timeline__item history-timeline__item--<?php echo $is_right ? 'right' : 'left'; ?>">
							<?php if ( ! $is_right ) : ?>
								<div class="history-timeline__copy">
									<span><?php echo esc_html( isset( $item['year'] ) ? $item['year'] : '' ); ?></span>
									<h3><?php echo esc_html( isset( $item['title'] ) ? $item['title'] : '' ); ?></h3>
									<p><?php echo esc_html( isset( $item['text'] ) ? $item['text'] : '' ); ?></p>
								</div>
							<?php endif; ?>

							<?php if ( $is_right && $icon_url ) : ?>
								<div class="history-timeline__icon"><img src="<?php echo esc_url( $icon_url ); ?>" alt="" aria-hidden="true"></div>
							<?php endif; ?>

							<div class="history-timeline__dot" aria-hidden="true"></div>

							<?php if ( ! $is_right && $icon_url ) : ?>
								<div class="history-timeline__icon"><img src="<?php echo esc_url( $icon_url ); ?>" alt="" aria-hidden="true"></div>
							<?php endif; ?>

							<?php if ( $is_right ) : ?>
								<div class="history-timeline__copy">
									<span><?php echo esc_html( isset( $item['year'] ) ? $item['year'] : '' ); ?></span>
									<h3><?php echo esc_html( isset( $item['title'] ) ? $item['title'] : '' ); ?></h3>
									<p><?php echo esc_html( isset( $item['text'] ) ? $item['text'] : '' ); ?></p>
								</div>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<section class="history-values">
		<div class="container history-values__layout">
			<div class="history-values__intro">
				<?php if ( $values_eyebrow ) : ?><p><?php echo esc_html( $values_eyebrow ); ?></p><?php endif; ?>
				<?php if ( $values_title ) : ?><h2><?php echo nl2br( esc_html( $values_title ) ); ?></h2><?php endif; ?>
			</div>

			<?php if ( is_array( $values ) && $values ) : ?>
				<div class="history-values__cards">
					<?php foreach ( $values as $value ) : ?>
						<article class="history-value-card">
							<?php
							$icon = isset( $value['icon'] ) ? $value['icon'] : array();
							$url  = roverland_image_url( $icon );

							if ( $url ) :
								?>
								<img src="<?php echo esc_url( $url ); ?>" width="28" height="28" alt="" aria-hidden="true">
							<?php endif; ?>

							<h3><?php echo esc_html( isset( $value['title'] ) ? $value['title'] : '' ); ?></h3>
							<p><?php echo esc_html( isset( $value['text'] ) ? $value['text'] : '' ); ?></p>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<?php
	if ( roverland_field( 'history_show_branches', true ) ) {
		get_template_part(
			'template-parts/blocks/branches',
			null,
			array(
				'class' => 'branches history-branches',
				'title' => $branches_title,
			)
		);
	}

	if ( roverland_field( 'history_show_map', true ) ) {
		get_template_part( 'template-parts/blocks/map', null, array( 'class' => 'history-map' ) );
	}
endwhile;

get_footer();
