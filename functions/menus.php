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

function roverland_menu_page( $path ) {
	return get_page_by_path( trim( (string) $path, '/' ) );
}

function roverland_menu_page_children( $parent_id ) {
	return get_pages(
		array(
			'parent'      => (int) $parent_id,
			'post_status' => 'publish',
			'sort_column' => 'menu_order,post_title',
			'sort_order'  => 'ASC',
		)
	);
}

function roverland_menu_page_label( $page_id ) {
	$service_id = (int) roverland_field( 'service_related_service', 0, $page_id );

	if ( $service_id ) {
		$label = trim( (string) roverland_field( 'service_menu_name', '', $service_id ) );
		if ( $label ) {
			return $label;
		}
	}

	return get_the_title( $page_id );
}

function roverland_render_desktop_page_children( $parent_id, $depth = 1 ) {
	$children = roverland_menu_page_children( $parent_id );

	foreach ( $children as $child ) {
		$grandchildren = $depth < 2 ? roverland_menu_page_children( $child->ID ) : array();
		$label         = roverland_menu_page_label( $child->ID );

		if ( $grandchildren ) {
			echo '<div class="nav-dropdown__item nav-dropdown__item--has-children">';
			echo '<a href="' . esc_url( get_permalink( $child ) ) . '">' . esc_html( $label ) . '<span class="nav-dropdown__chevron" aria-hidden="true">›</span></a>';
			echo '<div class="nav-subdropdown">';
			roverland_render_desktop_page_children( $child->ID, $depth + 1 );
			echo '</div></div>';
		} else {
			echo '<a href="' . esc_url( get_permalink( $child ) ) . '">' . esc_html( $label ) . '</a>';
		}
	}
}

function roverland_render_main_service_menu_item( $path, $label ) {
	$page = roverland_menu_page( $path );

	if ( ! $page ) {
		return;
	}

	$children = roverland_menu_page_children( $page->ID );
	$class    = 'main-nav__item' . ( $children ? ' main-nav__item--dropdown' : '' );

	echo '<li class="' . esc_attr( $class ) . '">';
	echo '<a class="main-nav__link" href="' . esc_url( get_permalink( $page ) ) . '"' . ( $children ? ' aria-haspopup="true"' : '' ) . '>';
	echo esc_html( $label );

	if ( $children ) {
		echo ' <img class="main-nav__arrow" src="' . esc_url( roverland_asset( 'assets/images/icons/ui/chevron-gray.svg' ) ) . '" width="10" height="6" alt="" aria-hidden="true">';
	}

	echo '</a>';

	if ( $children ) {
		echo '<div class="nav-dropdown">';
		roverland_render_desktop_page_children( $page->ID );
		echo '</div>';
	}

	echo '</li>';
}

function roverland_primary_menu_fallback() {
	echo '<ul class="main-nav__list">';

	roverland_render_main_service_menu_item( 'remont', 'Ремонт' );
	roverland_render_main_service_menu_item( 'servis', 'Сервис' );
	roverland_render_main_service_menu_item( 'zapchasti', 'Запчасти' );

	$company = roverland_menu_page( 'kompaniya' );

	if ( $company ) {
		echo '<li class="main-nav__item main-nav__item--dropdown">';
		echo '<a class="main-nav__link" href="' . esc_url( get_permalink( $company ) ) . '" aria-haspopup="true">О компании <img class="main-nav__arrow" src="' . esc_url( roverland_asset( 'assets/images/icons/ui/chevron-gray.svg' ) ) . '" width="10" height="6" alt="" aria-hidden="true"></a>';
		echo '<div class="nav-dropdown">';

		$history = roverland_menu_page( 'kompaniya/istoriya' );
		if ( $history ) {
			echo '<a href="' . esc_url( get_permalink( $history ) ) . '">История</a>';
		}

		$vacancies_page = roverland_menu_page( 'kompaniya/vakansii' );
		if ( $vacancies_page ) {
			echo '<div class="nav-dropdown__item nav-dropdown__item--has-children">';
			echo '<a href="' . esc_url( get_permalink( $vacancies_page ) ) . '">Вакансии <span class="nav-dropdown__chevron" aria-hidden="true">›</span></a>';
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
				echo '<a href="' . esc_url( get_permalink( $vacancy ) ) . '">' . esc_html( get_the_title( $vacancy ) ) . '</a>';
			}

			echo '</div></div>';
		}

		echo '</div></li>';
	}

	foreach (
		array(
			'aktsii'    => 'Акции',
			'portfolio' => 'Портфолио',
			'contacts'  => 'Контакты',
		) as $path => $label
	) {
		$page = roverland_menu_page( $path );
		if ( $page ) {
			echo '<li class="main-nav__item"><a class="main-nav__link" href="' . esc_url( get_permalink( $page ) ) . '">' . esc_html( $label ) . '</a></li>';
		}
	}

	echo '</ul>';
}

function roverland_render_mobile_page_children( $parent_id ) {
	$children = roverland_menu_page_children( $parent_id );

	foreach ( $children as $child ) {
		echo '<a class="mobile-nav__child" href="' . esc_url( get_permalink( $child ) ) . '">' . esc_html( roverland_menu_page_label( $child->ID ) ) . '</a>';

		$grandchildren = roverland_menu_page_children( $child->ID );
		foreach ( $grandchildren as $grandchild ) {
			echo '<a class="mobile-nav__grandchild" href="' . esc_url( get_permalink( $grandchild ) ) . '">' . esc_html( roverland_menu_page_label( $grandchild->ID ) ) . '</a>';
		}
	}
}

function roverland_mobile_menu_fallback() {
	foreach (
		array(
			'remont'    => 'Ремонт',
			'servis'    => 'Сервис',
			'zapchasti' => 'Запчасти',
		) as $path => $label
	) {
		$page = roverland_menu_page( $path );

		if ( ! $page ) {
			continue;
		}

		$children = roverland_menu_page_children( $page->ID );

		if ( $children ) {
			echo '<details class="mobile-nav__group">';
			echo '<summary>' . esc_html( $label ) . '</summary>';
			echo '<a class="mobile-nav__root" href="' . esc_url( get_permalink( $page ) ) . '">Все: ' . esc_html( $label ) . '</a>';
			roverland_render_mobile_page_children( $page->ID );
			echo '</details>';
		} else {
			echo '<a href="' . esc_url( get_permalink( $page ) ) . '">' . esc_html( $label ) . '</a>';
		}
	}

	$company = roverland_menu_page( 'kompaniya' );
	if ( $company ) {
		echo '<details class="mobile-nav__group">';
		echo '<summary>О компании</summary>';
		echo '<a class="mobile-nav__root" href="' . esc_url( get_permalink( $company ) ) . '">О компании</a>';

		foreach ( roverland_menu_page_children( $company->ID ) as $child ) {
			echo '<a class="mobile-nav__child" href="' . esc_url( get_permalink( $child ) ) . '">' . esc_html( get_the_title( $child ) ) . '</a>';
		}

		echo '</details>';
	}

	foreach (
		array(
			'aktsii'    => 'Акции',
			'portfolio' => 'Портфолио',
			'contacts'  => 'Контакты',
		) as $path => $label
	) {
		$page = roverland_menu_page( $path );
		if ( $page ) {
			echo '<a href="' . esc_url( get_permalink( $page ) ) . '">' . esc_html( $label ) . '</a>';
		}
	}
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
		$model_page = roverland_get_service_page_for_model( $model->ID );

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

		$children = array_values(
			array_filter(
				$children,
				static function ( $child ) {
					return (bool) roverland_get_service_page_for_model( $child->ID );
				}
			)
		);

		if ( ! $model_page && ! $children ) {
			continue;
		}

		$item_class = 'models-nav__item' . ( $children ? ' models-nav__item--dropdown' : '' );
		$model_url  = $model_page ? get_permalink( $model_page ) : '#';

		echo '<li class="' . esc_attr( $item_class ) . '">';
		echo '<a class="models-nav__link" href="' . esc_url( $model_url ) . '">';
		echo esc_html( roverland_field( 'model_menu_name', get_the_title( $model ), $model->ID ) );

		if ( $children ) {
			echo ' <img class="models-nav__arrow" src="' . esc_url( roverland_asset( 'assets/images/icons/ui/chevron-white.svg' ) ) . '" width="10" height="6" alt="" aria-hidden="true">';
		}

		echo '</a>';

		if ( $children ) {
			echo '<div class="models-dropdown">';
			foreach ( $children as $child ) {
				$child_page = roverland_get_service_page_for_model( $child->ID );
				if ( ! $child_page ) {
					continue;
				}

				echo '<a href="' . esc_url( get_permalink( $child_page ) ) . '">' . esc_html( roverland_field( 'model_menu_name', get_the_title( $child ), $child->ID ) ) . '</a>';
			}
			echo '</div>';
		}

		echo '</li>';
	}

	echo '</ul>';
}

