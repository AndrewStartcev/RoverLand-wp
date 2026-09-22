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

	$service_page_has_map = is_page() && roverland_service_page_kind( get_queried_object_id() ) && roverland_field( 'service_show_map', true, get_queried_object_id() );

	if ( is_front_page() || is_singular( 'vacancy' ) || $service_page_has_map || is_page_template( array( 'page-contacts.php', 'page-history.php', 'page-vacancies.php' ) ) ) {
		$api_key = trim( (string) roverland_option( 'site_yandex_maps_api_key', '6b4eac7a-0149-488d-b8c5-391ae43a22e4' ) );

		if ( $api_key ) {
			wp_enqueue_script(
				'roverland-yandex-maps',
				'https://api-maps.yandex.ru/2.1/?apikey=' . rawurlencode( $api_key ) . '&lang=ru_RU',
				array(),
				null,
				true
			);
		}
	}

	$main_deps = array( 'roverland-swiper' );

	if ( wp_script_is( 'roverland-yandex-maps', 'enqueued' ) ) {
		$main_deps[] = 'roverland-yandex-maps';
	}

	wp_enqueue_script(
		'roverland-main',
		get_theme_file_uri( 'assets/js/main.js' ),
		$main_deps,
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
