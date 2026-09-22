<?php

get_header();

while ( have_posts() ) :
	the_post();

	$service_kind = roverland_service_page_kind();

	if ( $service_kind ) {
		get_template_part( 'template-parts/pages/service-dynamic' );
		continue;
	}

	$page_h1 = roverland_field( 'page_h1', get_the_title() );
	?>
	<section class="content-page">
		<div class="container">
			<h1><?php echo esc_html( $page_h1 ); ?></h1>

			<div class="content-page__body">
				<?php the_content(); ?>
			</div>
		</div>
	</section>
	<?php
endwhile;

get_footer();
