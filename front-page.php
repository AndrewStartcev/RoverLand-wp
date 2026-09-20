<?php
get_header();

while ( have_posts() ) :
	the_post();
	?>
	<?php if ( trim( (string) get_the_content() ) ) : ?>
		<div class="container">
			<?php the_content(); ?>
		</div>
	<?php endif; ?>
	<?php
endwhile;

get_footer();
