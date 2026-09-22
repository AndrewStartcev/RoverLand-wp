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

		register_post_type(
			'promotion',
			array(
				'labels' => array(
					'name' => 'Акции',
					'singular_name' => 'Акция',
					'add_new_item' => 'Добавить акцию',
					'edit_item' => 'Редактировать акцию',
					'menu_name' => 'Акции',
				),
				'public' => true,
				'has_archive' => false,
				'show_in_rest' => false,
				'menu_icon' => 'dashicons-tickets-alt',
				'menu_position' => 22,
				'supports' => array( 'title', 'page-attributes' ),
				'rewrite' => array(
					'slug' => 'aktsii',
					'with_front' => false,
				),
			)
		);

		register_post_type(
			'portfolio_item',
			array(
				'labels' => array(
					'name' => 'Портфолио',
					'singular_name' => 'Работа',
					'add_new_item' => 'Добавить работу',
					'edit_item' => 'Редактировать работу',
					'menu_name' => 'Портфолио',
				),
				'public' => true,
				'has_archive' => false,
				'show_in_rest' => false,
				'menu_icon' => 'dashicons-format-gallery',
				'menu_position' => 23,
				'supports' => array( 'title', 'page-attributes' ),
				'rewrite' => array(
					'slug' => 'portfolio',
					'with_front' => false,
				),
			)
		);

		register_post_type(
			'rover_model',
			array(
				'labels' => array(
					'name'          => 'Модели автомобилей',
					'singular_name' => 'Модель',
					'add_new_item'  => 'Добавить модель',
					'edit_item'     => 'Редактировать модель',
					'menu_name'     => 'Модели',
				),
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_rest'       => false,
				'hierarchical'       => true,
				'menu_icon'          => 'dashicons-car',
				'menu_position'      => 24,
				'supports'           => array( 'title', 'page-attributes' ),
			)
		);

		register_post_type(
			'rover_service',
			array(
				'labels' => array(
					'name'          => 'Справочник услуг',
					'singular_name' => 'Услуга',
					'add_new_item'  => 'Добавить услугу',
					'edit_item'     => 'Редактировать услугу',
					'menu_name'     => 'Услуги',
				),
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_rest'       => false,
				'hierarchical'       => true,
				'menu_icon'          => 'dashicons-admin-tools',
				'menu_position'      => 25,
				'supports'           => array( 'title', 'page-attributes' ),
			)
		);
	}
);
