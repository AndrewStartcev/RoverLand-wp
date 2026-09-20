<?php

defined( 'ABSPATH' ) || exit;

/**
 * SVG is used for interface icons in the theme.
 * Keep upload permission restricted to administrators.
 */
function roverland_allow_svg_uploads( $mimes ) {
	if ( current_user_can( 'manage_options' ) ) {
		$mimes['svg'] = 'image/svg+xml';
	}

	return $mimes;
}
add_filter( 'upload_mimes', 'roverland_allow_svg_uploads' );

function roverland_fix_svg_filetype( $data, $file, $filename, $mimes, $real_mime = '' ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return $data;
	}

	if ( 'svg' !== strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) ) ) {
		return $data;
	}

	$data['ext']             = 'svg';
	$data['type']            = 'image/svg+xml';
	$data['proper_filename'] = $filename;

	return $data;
}
add_filter( 'wp_check_filetype_and_ext', 'roverland_fix_svg_filetype', 10, 5 );
