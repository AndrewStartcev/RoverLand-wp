<?php

defined( 'ABSPATH' ) || exit;

function roverland_asset_version( $relative_path ) {
	$file_path = get_theme_file_path( ltrim( $relative_path, '/' ) );

	if ( file_exists( $file_path ) ) {
		return (string) filemtime( $file_path );
	}

	return wp_get_theme()->get( 'Version' );
}

function roverland_enqueue_assets() {
	wp_enqueue_style(
		'roverland-swiper',
		'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
		array(),
		'11'
	);

	wp_enqueue_style(
		'roverland-style',
		get_theme_file_uri( 'assets/css/style.css' ),
		array( 'roverland-swiper' ),
		roverland_asset_version( 'assets/css/style.css' )
	);

	wp_enqueue_script(
		'roverland-swiper',
		'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
		array(),
		'11',
		true
	);

	wp_enqueue_script(
		'roverland-main',
		get_theme_file_uri( 'assets/js/main.js' ),
		array( 'roverland-swiper' ),
		roverland_asset_version( 'assets/js/main.js' ),
		true
	);

	wp_localize_script(
		'roverland-main',
		'roverlandTheme',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'roverland_ajax' ),
			'homeUrl' => home_url( '/' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'roverland_enqueue_assets' );
