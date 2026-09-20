<?php

defined( 'ABSPATH' ) || exit;

class Roverland_Menu_Walker extends Walker_Nav_Menu {
	private $context;

	public function __construct( $context = 'main' ) {
		$this->context = $context;
	}

	public function start_lvl( &$output, $depth = 0, $args = null ) {
		$class = 'models' === $this->context ? 'models-dropdown' : 'nav-dropdown';
		$output .= '<div class="' . esc_attr( $class ) . '">';
	}

	public function end_lvl( &$output, $depth = 0, $args = null ) {
		$output .= '</div>';
	}

	public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
		$item       = $data_object;
		$is_models  = 'models' === $this->context;
		$item_url   = ! empty( $item->url ) ? $item->url : '#';
		$item_title = esc_html( apply_filters( 'the_title', $item->title, $item->ID ) );

		/*
		 * В исходной верстке RoverLand dropdown — это <div> с прямыми <a>,
		 * без вложенного <ul>/<li>. Сохраняем эту структуру один в один.
		 */
		if ( $depth > 0 ) {
			$output .= '<a href="' . esc_url( $item_url ) . '">' . $item_title . '</a>';
			return;
		}

		$has_children = ! empty( $args->has_children );
		$item_class   = $is_models ? 'models-nav__item' : 'main-nav__item';
		$link_class   = $is_models ? 'models-nav__link' : 'main-nav__link';
		$arrow_class  = $is_models ? 'models-nav__arrow' : 'main-nav__arrow';

		if ( $has_children ) {
			$item_class .= $is_models ? ' models-nav__item--dropdown' : ' main-nav__item--dropdown';
		}

		$output .= '<li class="' . esc_attr( $item_class ) . '">';

		$attributes = ' class="' . esc_attr( $link_class ) . '" href="' . esc_url( $item_url ) . '"';

		if ( $has_children ) {
			$attributes .= ' aria-haspopup="true"';
		}

		$output .= '<a' . $attributes . '>';
		$output .= $item_title;

		if ( $has_children ) {
			$arrow = $is_models ? 'chevron-white.svg' : 'chevron-gray.svg';
			$output .= ' <img class="' . esc_attr( $arrow_class ) . '" src="' . esc_url( roverland_asset( 'assets/images/icons/ui/' . $arrow ) ) . '" width="10" height="6" alt="" aria-hidden="true">';
		}

		$output .= '</a>';
	}

	public function end_el( &$output, $data_object, $depth = 0, $args = null ) {
		if ( 0 === $depth ) {
			$output .= '</li>';
		}
	}
}

function roverland_primary_menu_fallback() {
	$items = array(
		array( 'Ремонт', roverland_page_url( 'services' ) . '#repair' ),
		array( 'Сервис', roverland_page_url( 'service' ) ),
		array( 'Запчасти', roverland_page_url( 'parts' ) ),
		array( 'О компании', roverland_page_url( 'about' ) ),
		array( 'Акции', roverland_page_url( 'promotions' ) ),
		array( 'Портфолио', roverland_page_url( 'portfolio' ) ),
		array( 'Контакты', roverland_page_url( 'contacts' ) ),
	);

	echo '<ul class="main-nav__list">';

	foreach ( $items as $item ) {
		printf(
			'<li class="main-nav__item"><a class="main-nav__link" href="%1$s">%2$s</a></li>',
			esc_url( $item[1] ),
			esc_html( $item[0] )
		);
	}

	echo '</ul>';
}

function roverland_models_menu_fallback() {
	$items = array(
		'Discovery',
		'Discovery Sport',
		'Range Rover',
		'Range Rover Sport',
		'Jaguar',
		'Defender',
		'Freelander',
		'Range Rover Evoque',
		'Range Rover Velar',
	);

	echo '<ul class="models-nav__list">';

	foreach ( $items as $item ) {
		printf(
			'<li class="models-nav__item"><a class="models-nav__link" href="#">%s</a></li>',
			esc_html( $item )
		);
	}

	echo '</ul>';
}
