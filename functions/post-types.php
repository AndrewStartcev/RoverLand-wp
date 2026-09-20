<?php

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	static function () {
		register_post_type(
			'vacancy',
			array(
				'labels' => array(
					'name' => 'Вакансии',
					'singular_name' => 'Вакансия',
					'add_new_item' => 'Добавить вакансию',
					'edit_item' => 'Редактировать вакансию',
					'menu_name' => 'Вакансии',
				),
				'public' => true,
				'has_archive' => false,
				'show_in_rest' => false,
				'menu_icon' => 'dashicons-businessperson',
				'menu_position' => 21,
				'supports' => array( 'title', 'page-attributes' ),
				'rewrite' => array(
					'slug' => 'kompaniya/vakansii',
					'with_front' => false,
				),
			)
		);
	}
);
