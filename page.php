<?php
get_header();

while ( have_posts() ) :
	the_post();

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
