<?php
/*
Template Name: Контакты
*/

get_header();

while ( have_posts() ) :
	the_post();

	$title = roverland_field( 'contacts_title', get_the_title() );
	$lead  = roverland_field( 'contacts_lead', '' );
	?>
	<section class="services-page__intro contacts-page__intro">
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

			<h1 class="services-page__title"><?php echo nl2br( esc_html( $title ) ); ?></h1>

			<?php if ( $lead ) : ?>
				<p class="services-page__lead"><?php echo esc_html( $lead ); ?></p>
			<?php endif; ?>
		</div>
	</section>

	<?php
	if ( roverland_field( 'contacts_show_centers', true ) ) {
		get_template_part(
			'template-parts/blocks/branches',
			null,
			array(
				'class'     => 'contacts-centers',
				'list_only' => true,
			)
		);
	}

	if ( roverland_field( 'contacts_show_map', true ) ) {
		get_template_part( 'template-parts/blocks/map' );
	}

	if ( roverland_field( 'contacts_show_appointment', true ) ) {
		get_template_part( 'template-parts/blocks/appointment' );
	}
endwhile;

get_footer();
