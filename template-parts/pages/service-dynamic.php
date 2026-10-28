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
if ((trim(wp_parse_url(get_permalink(), PHP_URL_PATH), '/') === 'servis/to')) { $sections = array_filter($sections, function($section) { return ($section['acf_fc_layout'] ?? '') !== 'cards' && !preg_match('/^(Land Rover |Range Rover|Jaguar)/u', $section['title'] ?? ''); }); }
$related      = roverland_field( 'service_auto_related', true, $post_id ) ? roverland_get_related_service_pages( $post_id, -1 ) : array();
if ((trim(wp_parse_url(get_permalink(), PHP_URL_PATH), '/') === 'servis/to')) { $related = array(); }

$hero_class = 'service-universal-hero';
if ( 'model' === $kind ) {
	$hero_class .= ' service-universal-hero--model';
} elseif ( 'maintenance' === $kind ) {
	$hero_class .= ' service-universal-hero--maintenance';
} elseif ( 'hub' === $kind ) {
	$hero_class .= ' service-universal-hero--hub';
}
?>
<section class="<?php echo esc_attr( $hero_class ); ?>">
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

			<h1 class="service-universal-hero__title"><?php $audit_h1 = roverland_audit_h1(''); if ($audit_h1) { echo esc_html($audit_h1); } else { ?><?php echo nl2br( esc_html( $title ) ); ?><?php } ?></h1>

			<?php if ( $lead ) : ?>
				<p class="service-universal-hero__lead"><?php echo esc_html( $lead ); ?></p>
			<?php endif; ?>

			<div class="service-universal-hero__actions">
				<a class="button button--primary" href="#appointment"><?php echo esc_html( roverland_field( 'service_primary_button_label', 'Записаться на сервис', $post_id ) ); ?></a>
				<?php if ( roverland_field( 'service_secondary_button_label', '', $post_id ) ) : ?>
					<a class="button button--outline" href="<?php echo is_page('servis') ? '#service-catalog' : '#service-content'; ?>"><?php echo esc_html( roverland_field( 'service_secondary_button_label', '', $post_id ) ); ?></a>
				<?php endif; ?>
			</div>
		</div>

		<?php if (is_page('prodat-avtomobil')) : ?><div class="sell-car-hero-form"><?php get_template_part('template-parts/blocks/sell-car-form'); ?></div><?php elseif ( $hero_url ) : ?>
			<figure class="service-universal-hero__media">
				<img
					src="<?php echo esc_url( $hero_url ); ?>"
					alt="<?php echo esc_attr( roverland_image_alt( $hero_image, $title ) ); ?>"
					loading="eager"
					decoding="async"
					fetchpriority="high"
				>
			</figure>
		<?php endif; ?>
	</div>
</section>

<div id="service-content">
	<?php
	$revision_content = roverland_field('service_revision_content', '', $post_id);
	if ($revision_content) {
		echo '<section class="service-content-block service-content-block--text"><div class="container service-revision-copy">' . roverland_usability_price_tables(wp_kses_post($revision_content)) . '</div></section>';
	} elseif ( is_array( $sections ) && $sections ) {
		get_template_part(
			'template-parts/blocks/service-sections',
			null,
			array(
				'sections' => $sections,
			)
		);
	}
	?>
<?php if ( !is_page('prodat-avtomobil') && !is_page('servis/prodat-avtomobil') ) {
 get_template_part('template-parts/blocks/appointment', null, array('id'=>'appointment-content'));
} ?>
	<?php get_template_part('template-parts/blocks/service-catalog'); ?>

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
							<h3><a href="<?php echo esc_url(get_permalink( $related_page )); ?>"><?php echo esc_html( get_the_title( $related_page ) ); ?></a></h3>
							<?php
							$related_lead = roverland_field( 'service_hero_lead', '', $related_page->ID );
							if ( $related_lead ) :
								?>
								<p><?php echo esc_html( $related_lead ); ?></p>
							<?php endif; ?>
							<a class="text-link card-link" href="<?php echo esc_url( get_permalink( $related_page ) ); ?>">Подробнее <span class="rl-chevron rl-chevron--right" aria-hidden="true"></span></a>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>
</div>

<?php
if ( in_array($kind, array('service', 'hub', 'model', 'model_service', 'maintenance'), true) || $revision_content || (trim(wp_parse_url(get_permalink(), PHP_URL_PATH), '/') === 'servis/to') ) {
	get_template_part('template-parts/blocks/selected-cases');
	get_template_part('template-parts/blocks/service-offers');
	get_template_part('template-parts/blocks/company-videos');
}
if ( !is_page('prodat-avtomobil') && roverland_field( 'service_show_appointment', true, $post_id ) ) {
	get_template_part( is_page('prodat-avtomobil') ? 'template-parts/blocks/sell-car-form' : 'template-parts/blocks/appointment' );
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

