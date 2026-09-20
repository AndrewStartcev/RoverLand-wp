<?php

defined( 'ABSPATH' ) || exit;

add_action(
	'template_redirect',
	static function () {
		if ( is_admin() ) {
			return;
		}

		$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';

		$redirects = array(
			'/politika-konfidentsialnosti.php' => roverland_page_url( 'politika-konfidentsialnosti' ),
			'/politika-ispolzovaniya-cookie.php' => roverland_page_url( 'politika-ispolzovaniya-cookie' ),
		);

		if ( isset( $redirects[ $path ] ) ) {
			wp_safe_redirect( $redirects[ $path ], 301 );
			exit;
		}
	},
	1
);
