<?php

defined( 'ABSPATH' ) || exit;

$logo_url         = roverland_asset( 'assets/images/content/logo-black.png' );
$appointment_text = 'Записаться на ТО';
$appointment_url  = home_url( '/#appointment' );
?>
<header class="site-header" data-header>
	<div class="site-header__top">
		<div class="site-header__wide">
			<a class="site-header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> — на главную">
				<img src="<?php echo esc_url( $logo_url ); ?>" width="183" height="36" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
			</a>

			<nav class="main-nav" aria-label="Основная навигация" data-desktop-nav>
				<?php
				if ( has_nav_menu( 'primary-menu' ) ) {
					wp_nav_menu(
						array(
							'theme_location' => 'primary-menu',
							'container'      => false,
							'menu_class'     => 'main-nav__list',
							'depth'          => 2,
							'walker'         => new Roverland_Menu_Walker( 'main' ),
						)
					);
				} else {
					roverland_primary_menu_fallback();
				}
				?>
			</nav>

			<div class="site-header__actions">
				<a class="button button--primary site-header__appointment" href="<?php echo esc_url( $appointment_url ); ?>">
					<?php echo esc_html( $appointment_text ); ?>
				</a>

				<button class="icon-button site-header__search" type="button" aria-label="Открыть поиск" data-search-open>
					<img src="<?php echo esc_url( roverland_asset( 'assets/images/icons/ui/search.svg' ) ); ?>" width="26" height="28" alt="" aria-hidden="true">
				</button>

				<button class="menu-toggle" type="button" aria-label="Открыть меню" aria-expanded="false" data-menu-toggle>
					<span></span><span></span><span></span>
				</button>
			</div>
		</div>
	</div>

	<div class="site-header__models">
		<div class="site-header__wide site-header__wide--models">
			<nav class="models-nav" aria-label="Модели автомобилей">
				<?php
				if ( has_nav_menu( 'models-menu' ) ) {
					wp_nav_menu(
						array(
							'theme_location' => 'models-menu',
							'container'      => false,
							'menu_class'     => 'models-nav__list',
							'depth'          => 2,
							'walker'         => new Roverland_Menu_Walker( 'models' ),
						)
					);
				} else {
					roverland_models_menu_fallback();
				}
				?>
			</nav>
		</div>
	</div>

	<div class="mobile-menu" data-mobile-menu aria-hidden="true">
		<div class="mobile-menu__head">
			<img src="<?php echo esc_url( $logo_url ); ?>" width="183" height="36" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
			<button class="icon-button" type="button" aria-label="Закрыть меню" data-menu-close>
				<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 5 19 19M19 5 5 19"></path></svg>
			</button>
		</div>

		<nav class="mobile-nav" aria-label="Мобильная навигация">
			<a href="<?php echo esc_url( roverland_page_url( 'services' ) ); ?>">Ремонт</a>
			<a href="<?php echo esc_url( roverland_page_url( 'service' ) ); ?>">Сервис</a>
			<a href="<?php echo esc_url( roverland_page_url( 'parts' ) ); ?>">Запчасти</a>
			<a href="<?php echo esc_url( roverland_page_url( 'about' ) ); ?>">О компании</a>
			<a href="<?php echo esc_url( roverland_page_url( 'vacancies' ) ); ?>">Вакансии</a>
			<a href="<?php echo esc_url( roverland_page_url( 'promotions' ) ); ?>">Акции</a>
			<a href="<?php echo esc_url( roverland_page_url( 'portfolio' ) ); ?>">Портфолио</a>
			<a href="<?php echo esc_url( roverland_page_url( 'contacts' ) ); ?>">Контакты</a>
		</nav>

		<a class="button button--primary button--wide" href="<?php echo esc_url( $appointment_url ); ?>">
			<?php echo esc_html( $appointment_text ); ?>
		</a>
	</div>

	<div class="mobile-menu-backdrop" data-menu-backdrop></div>
</header>
