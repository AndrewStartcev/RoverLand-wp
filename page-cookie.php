<?php
/*
Template Name: Политика использования cookie
*/

get_header();

while ( have_posts() ) :
	the_post();
	get_template_part( 'template-parts/pages/legal' );
endwhile;

get_footer();
