<?php

defined( 'ABSPATH' ) || exit;

$post_id      = get_the_ID();
$kind         = roverland_service_page_kind( $post_id );
$eyebrow      = roverland_field( 'service_hero_eyebrow', '', $post_id );
$title        = roverland_field( 'service_page_h1', get_the_title( $post_id ), $post_id );
$lead         = roverland_field( 'service_hero_lead', '', $post_id );
$hero_image   = roverland_field( 'service_hero_image', array(), $post_id );
$hero_url     = roverland_image_url( $hero_image );
$sections     = roverland_field( 'service_sections', array(), $post_id );
$related      = roverland_field( 'service_auto_related', true, $post_id ) ? roverland_get_related_service_pages( $post_id, 12 ) : array();

$hero_class = 'service-universal-hero';
if ( 'model' === $kind ) {
	$hero_class .= ' service-universal-hero--model';
} elseif ( 'maintenance' === $kind ) {
	$hero_class .= ' service-universal-hero--maintenance';
} elseif ( 'hub' === $kind ) {
	$hero_class .= ' service-universal-hero--hub';
}
?>
<section class="<?php echo esc_attr( $hero_class ); ?>"<?php if ( $hero_url ) : ?> style="--service-page-hero:url('<?php echo esc_url( $hero_url ); ?>');"<?php endif; ?>>
	<div class="container service-universal-hero__container">
		<div class="service-universal-hero__content">
			<?php
			get_template_part(
				'template-parts/elements/breadcrumbs',
				null,
				array(
					'items' => roverland_service_breadcrumb_items( $post_id ),
				)
			);
			?>

			<?php if ( $eyebrow ) : ?>
				<p class="service-universal-hero__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
			<?php endif; ?>

			<h1 class="service-universal-hero__title"><?php echo nl2br( esc_html( $title ) ); ?></h1>

			<?php if ( $lead ) : ?>
				<p class="service-universal-hero__lead"><?php echo esc_html( $lead ); ?></p>
			<?php endif; ?>

			<div class="service-universal-hero__actions">
				<a class="button button--primary" href="#appointment"><?php echo esc_html( roverland_field( 'service_primary_button_label', 'Записаться на сервис', $post_id ) ); ?></a>
				<?php if ( roverland_field( 'service_secondary_button_label', '', $post_id ) ) : ?>
					<a class="button button--outline" href="#service-content"><?php echo esc_html( roverland_field( 'service_secondary_button_label', '', $post_id ) ); ?></a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>

<div id="service-content">
	<?php
	if ( is_array( $sections ) && $sections ) {
		get_template_part(
			'template-parts/blocks/service-sections',
			null,
			array(
				'sections' => $sections,
			)
		);
	}
	?>

	<?php if ( $related ) : ?>
		<section class="service-related">
			<div class="container">
				<h2 class="service-related__title">
					<?php
					if ( 'model' === $kind ) {
						echo esc_html( 'Услуги для ' . get_the_title( (int) roverland_field( 'service_related_model', 0, $post_id ) ) );
					} elseif ( 'service' === $kind ) {
						echo esc_html( 'Выберите модель' );
					} else {
						echo esc_html( 'Другие услуги для этой модели' );
					}
					?>
				</h2>

				<div class="service-related__grid">
					<?php foreach ( $related as $related_page ) : ?>
						<article class="service-related-card">
							<h3><?php echo esc_html( get_the_title( $related_page ) ); ?></h3>
							<?php
							$related_lead = roverland_field( 'service_hero_lead', '', $related_page->ID );
							if ( $related_lead ) :
								?>
								<p><?php echo esc_html( $related_lead ); ?></p>
							<?php endif; ?>
							<a class="text-link" href="<?php echo esc_url( get_permalink( $related_page ) ); ?>">Подробнее <span aria-hidden="true">→</span></a>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>
</div>

<?php
if ( roverland_field( 'service_show_appointment', true, $post_id ) ) {
	get_template_part( 'template-parts/blocks/appointment' );
}

if ( roverland_field( 'service_show_branches', true, $post_id ) ) {
	get_template_part(
		'template-parts/blocks/branches',
		null,
		array(
			'class' => 'branches service-universal-branches',
			'title' => roverland_option( 'common_branches_title', '' ),
		)
	);
}

if ( roverland_field( 'service_show_map', true, $post_id ) ) {
	get_template_part( 'template-parts/blocks/map', null, array( 'class' => 'service-universal-map' ) );
}
