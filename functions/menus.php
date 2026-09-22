<?php

defined( 'ABSPATH' ) || exit;

class Roverland_Menu_Walker extends Walker_Nav_Menu {
	private $context;

	public function __construct( $context = 'main' ) {
		$this->context = $context;
	}

	public function start_lvl( &$output, $depth = 0, $args = null ) {
		if ( 'flat' === $this->context ) {
			return;
		}

		if ( 'main' === $this->context && $depth >= 1 ) {
			$output .= '<div class="nav-subdropdown">';
			return;
		}

		$class = 'models' === $this->context ? 'models-dropdown' : 'nav-dropdown';
		$output .= '<div class="' . esc_attr( $class ) . '">';
	}

	public function end_lvl( &$output, $depth = 0, $args = null ) {
		if ( 'flat' === $this->context ) {
			return;
		}

		$output .= '</div>';
	}

	public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
		$item        = $data_object;
		$item_url    = ! empty( $item->url ) ? $item->url : '#';
		$item_title  = esc_html( apply_filters( 'the_title', $item->title, $item->ID ) );
		$is_models   = 'models' === $this->context;
		$has_children = ! empty( $args->has_children );

		if ( 'flat' === $this->context ) {
			$output .= '<a href="' . esc_url( $item_url ) . '">' . $item_title . '</a>';
			return;
		}

		if ( $depth > 0 ) {
			if ( 'main' === $this->context && 1 === $depth && $has_children ) {
				$output .= '<div class="nav-dropdown__item nav-dropdown__item--has-children">';
				$output .= '<a href="' . esc_url( $item_url ) . '">' . $item_title . '<span class="nav-dropdown__chevron" aria-hidden="true">›</span></a>';
				return;
			}

			$output .= '<a href="' . esc_url( $item_url ) . '">' . $item_title . '</a>';
			return;
		}

		$item_class  = $is_models ? 'models-nav__item' : 'main-nav__item';
		$link_class  = $is_models ? 'models-nav__link' : 'main-nav__link';
		$arrow_class = $is_models ? 'models-nav__arrow' : 'main-nav__arrow';

		if ( $has_children ) {
			$item_class .= $is_models ? ' models-nav__item--dropdown' : ' main-nav__item--dropdown';
		}

		$output .= '<li class="' . esc_attr( $item_class ) . '">';

		$attributes = ' class="' . esc_attr( $link_class ) . '" href="' . esc_url( $item_url ) . '"';
		if ( $has_children ) {
			$attributes .= ' aria-haspopup="true"';
		}

		$output .= '<a' . $attributes . '>' . $item_title;

		if ( $has_children ) {
			$arrow = $is_models ? 'chevron-white.svg' : 'chevron-gray.svg';
			$output .= ' <img class="' . esc_attr( $arrow_class ) . '" src="' . esc_url( roverland_asset( 'assets/images/icons/ui/' . $arrow ) ) . '" width="10" height="6" alt="" aria-hidden="true">';
		}

		$output .= '</a>';
	}

	public function end_el( &$output, $data_object, $depth = 0, $args = null ) {
		if ( 'flat' === $this->context ) {
			return;
		}

		if ( 0 === $depth ) {
			$output .= '</li>';
			return;
		}

		if ( 'main' === $this->context && 1 === $depth && ! empty( $args->has_children ) ) {
			$output .= '</div>';
		}
	}
}

function roverland_primary_menu_fallback() {
	echo '<ul class="main-nav__list">';
	printf( '<li class="main-nav__item"><a class="main-nav__link" href="%s#repair">Ремонт</a></li>', esc_url( roverland_page_url( 'remont' ) ) );
	printf( '<li class="main-nav__item"><a class="main-nav__link" href="%s">Сервис</a></li>', esc_url( roverland_page_url( 'servis' ) ) );
	printf( '<li class="main-nav__item"><a class="main-nav__link" href="%s">Запчасти</a></li>', esc_url( roverland_page_url( 'zapchasti' ) ) );

	echo '<li class="main-nav__item main-nav__item--dropdown">';
	printf( '<a class="main-nav__link" href="%s" aria-haspopup="true">О компании <img class="main-nav__arrow" src="%s" width="10" height="6" alt="" aria-hidden="true"></a>', esc_url( roverland_page_url( 'kompaniya' ) ), esc_url( roverland_asset( 'assets/images/icons/ui/chevron-gray.svg' ) ) );
	echo '<div class="nav-dropdown">';
	printf( '<a href="%s">История</a>', esc_url( roverland_page_url( 'kompaniya/istoriya' ) ) );

	echo '<div class="nav-dropdown__item nav-dropdown__item--has-children">';
	printf( '<a href="%s">Вакансии <span class="nav-dropdown__chevron" aria-hidden="true">›</span></a>', esc_url( roverland_page_url( 'kompaniya/vakansii' ) ) );
	echo '<div class="nav-subdropdown">';

	$vacancies = get_posts(
		array(
			'post_type'      => 'vacancy',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		)
	);

	foreach ( $vacancies as $vacancy ) {
		printf( '<a href="%s">%s</a>', esc_url( get_permalink( $vacancy ) ), esc_html( get_the_title( $vacancy ) ) );
	}

	echo '</div></div></div></li>';

	printf( '<li class="main-nav__item"><a class="main-nav__link" href="%s">Акции</a></li>', esc_url( roverland_page_url( 'aktsii' ) ) );
	printf( '<li class="main-nav__item"><a class="main-nav__link" href="%s">Портфолио</a></li>', esc_url( roverland_page_url( 'portfolio' ) ) );
	printf( '<li class="main-nav__item"><a class="main-nav__link" href="%s">Контакты</a></li>', esc_url( roverland_page_url( 'contacts' ) ) );
	echo '</ul>';
}

function roverland_models_menu_fallback() {
	$models = get_posts(
		array(
			'post_type'      => 'rover_model',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'post_parent'    => 0,
			'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
			'no_found_rows'  => true,
		)
	);

	echo '<ul class="models-nav__list">';

	foreach ( $models as $model ) {
		$children = get_posts(
			array(
				'post_type'      => 'rover_model',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'post_parent'    => $model->ID,
				'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
				'no_found_rows'  => true,
			)
		);

		$item_class = 'models-nav__item' . ( $children ? ' models-nav__item--dropdown' : '' );

		echo '<li class="' . esc_attr( $item_class ) . '">';
		echo '<a class="models-nav__link" href="' . esc_url( roverland_model_public_url( $model->ID ) ) . '">';
		echo esc_html( roverland_field( 'model_menu_name', get_the_title( $model ), $model->ID ) );

		if ( $children ) {
			echo ' <img class="models-nav__arrow" src="' . esc_url( roverland_asset( 'assets/images/icons/ui/chevron-white.svg' ) ) . '" width="10" height="6" alt="" aria-hidden="true">';
		}

		echo '</a>';

		if ( $children ) {
			echo '<div class="models-dropdown">';
			foreach ( $children as $child ) {
				echo '<a href="' . esc_url( roverland_model_public_url( $child->ID ) ) . '">' . esc_html( roverland_field( 'model_menu_name', get_the_title( $child ), $child->ID ) ) . '</a>';
			}
			echo '</div>';
		}

		echo '</li>';
	}

	echo '</ul>';
}

