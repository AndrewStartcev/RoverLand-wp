<?php

defined( 'ABSPATH' ) || exit;

add_filter(
	'acf/settings/save_json',
	static function () {
		return get_template_directory() . '/acf-json';
	}
);

add_filter(
	'acf/settings/load_json',
	static function ( $paths ) {
		$path = get_template_directory() . '/acf-json';

		if ( ! in_array( $path, $paths, true ) ) {
			$paths[] = $path;
		}

		return $paths;
	}
);

add_action(
	'acf/init',
	static function () {
		if ( ! function_exists( 'acf_add_options_page' ) ) {
			return;
		}

		acf_add_options_page(
			array(
				'page_title' => 'Настройки Rover Land',
				'menu_title' => 'Rover Land',
				'menu_slug'  => 'roverland-settings',
				'capability' => 'manage_options',
				'redirect'   => false,
				'position'   => 3,
				'icon_url'   => 'dashicons-car',
			)
		);
	}
);
