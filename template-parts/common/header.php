<?php

defined( 'ABSPATH' ) || exit;

$header_logo       = roverland_option( 'site_header_logo', array() );
$logo_url          = roverland_image_url( $header_logo, 'assets/images/content/logo-black.png' );
$logo_alt          = roverland_image_alt( $header_logo, get_bloginfo( 'name' ) );
$appointment_text  = roverland_option( 'site_header_appointment_label', 'Записаться на ТО' );
$appointment_url   = home_url( '/#appointment' );
$socials           = roverland_get_social_links();
?>
<header class="site-header" data-header>
	<div class="site-header__top">
		<div class="site-header__wide">
			<a class="site-header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> — на главную">
				<img src="<?php echo esc_url( $logo_url ); ?>" width="183" height="36" alt="<?php echo esc_attr( $logo_alt ); ?>">
			</a>

			<nav class="main-nav" aria-label="Основная навигация" data-desktop-nav>
				<?php
				roverland_primary_menu_fallback();
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
				roverland_models_menu_fallback();
				?>
			</nav>

			<?php if ( $socials ) : ?>
				<div class="social-links social-links--header" aria-label="Связаться с нами">
					<?php foreach ( $socials as $social ) : ?>
						<?php
						$url      = isset( $social['url'] ) ? $social['url'] : '';
						$name     = isset( $social['name'] ) ? $social['name'] : '';
						$icon     = isset( $social['icon'] ) ? $social['icon'] : array();
						$icon_url = roverland_image_url( $icon );

						if ( ! $url || ! $icon_url ) {
							continue;
						}
						?>
						<a class="social-link" href="<?php echo esc_url( $url ); ?>" aria-label="<?php echo esc_attr( $name ); ?>">
							<img src="<?php echo esc_url( $icon_url ); ?>" width="24" height="24" alt="" aria-hidden="true">
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<div class="mobile-menu" data-mobile-menu aria-hidden="true">
		<div class="mobile-menu__head">
			<img src="<?php echo esc_url( $logo_url ); ?>" width="183" height="36" alt="<?php echo esc_attr( $logo_alt ); ?>">
			<button class="icon-button" type="button" aria-label="Закрыть меню" data-menu-close>
				<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 5 19 19M19 5 5 19"></path></svg>
			</button>
		</div>

		<nav class="mobile-nav" aria-label="Мобильная навигация">
			<?php roverland_mobile_menu_fallback(); ?>
		</nav>

		<a class="button button--primary button--wide" href="<?php echo esc_url( $appointment_url ); ?>">
			<?php echo esc_html( $appointment_text ); ?>
		</a>

		<?php if ( $socials ) : ?>
			<div class="social-links social-links--mobile" aria-label="Социальные сети">
				<?php foreach ( array_slice( $socials, 0, 4 ) as $social ) : ?>
					<?php
					$url      = isset( $social['url'] ) ? $social['url'] : '';
					$name     = isset( $social['name'] ) ? $social['name'] : '';
					$icon     = isset( $social['icon'] ) ? $social['icon'] : array();
					$icon_url = roverland_image_url( $icon );

					if ( ! $url || ! $icon_url ) {
						continue;
					}
					?>
					<a class="social-link" href="<?php echo esc_url( $url ); ?>" aria-label="<?php echo esc_attr( $name ); ?>">
						<img src="<?php echo esc_url( $icon_url ); ?>" width="24" height="24" alt="" aria-hidden="true">
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="mobile-menu-backdrop" data-menu-backdrop></div>
</header>
