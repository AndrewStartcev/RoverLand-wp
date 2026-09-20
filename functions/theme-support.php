<?php

defined( 'ABSPATH' ) || exit;

function roverland_theme_setup() {
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo' );
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	register_nav_menus(
		array(
			'primary-menu'     => 'Основное меню',
			'models-menu'      => 'Меню моделей',
			'footer-info-menu' => 'Информация в подвале',
		)
	);
}
add_action( 'after_setup_theme', 'roverland_theme_setup' );

function roverland_admin_footer_text() {
	return sprintf(
		'Разработка сайта <a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
		esc_url( 'https://starcev.agency/' ),
		esc_html( 'Андрей Старцев' )
	);
}
add_filter( 'admin_footer_text', 'roverland_admin_footer_text' );
