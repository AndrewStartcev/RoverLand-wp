<?php

defined( 'ABSPATH' ) || exit;

$logo_url = roverland_asset( 'assets/images/content/logo-black.png' );
?>
<footer class="site-footer">
	<div class="container site-footer__top">
		<div class="site-footer__brand">
			<img src="<?php echo esc_url( $logo_url ); ?>" width="183" height="36" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
			<p>РоверЛэнд — сервис и ремонт Land Rover. Профессиональное обслуживание для истинных ценителей британского качества.</p>
		</div>

		<div class="site-footer__addresses">
			<h2 class="site-footer__heading">Адреса филиалов</h2>
			<div class="site-footer__address-grid">
				<address><strong>Запад</strong><span>Новорижское шоссе, 17-й километр, 1, Москва</span><a href="tel:+74951190199">+7 (495) 119-01-99</a></address>
				<address><strong>Северо-Запад</strong><span>Волоколамское шоссе, вл. 7А, Москва</span><a href="tel:+74952880424">+7 (495) 288-04-24</a></address>
				<address><strong>Юго-Запад</strong><span>Нагатинская улица, 23, корп. 4, стр. 1, Москва</span><a href="tel:+74956606776">+7 (495) 660-67-76</a></address>
				<address><strong>Юг</strong><span>Нагатинская улица, 16, корп. 1, стр. 5, Москва</span><a href="tel:+74956609858">+7 (495) 660-98-58</a></address>
			</div>
		</div>

		<nav class="site-footer__info" aria-label="Информация">
			<h2 class="site-footer__heading">Информация</h2>
			<?php if ( has_nav_menu( 'footer-info-menu' ) ) : ?>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'footer-info-menu',
						'container'      => false,
						'items_wrap'     => '%3$s',
						'depth'          => 1,
						'walker'         => new Roverland_Menu_Walker( 'flat' ),
					)
				);
				?>
			<?php else : ?>
				<a href="<?php echo esc_url( roverland_page_url( 'service' ) ); ?>">Сервисное обслуживание</a>
				<a href="<?php echo esc_url( roverland_page_url( 'services' ) ); ?>">Диагностика</a>
				<a href="<?php echo esc_url( roverland_page_url( 'services' ) ); ?>#bodyshop">Кузовной ремонт</a>
				<a href="<?php echo esc_url( roverland_page_url( 'engine-repair' ) ); ?>">Ремонт двигателя</a>
				<a href="<?php echo esc_url( get_privacy_policy_url() ?: roverland_page_url( 'privacy' ) ); ?>">Политика конфиденциальности</a>
			<?php endif; ?>
		</nav>
	</div>

	<div class="container site-footer__bottom">
		<span>© <?php echo esc_html( wp_date( 'Y' ) ); ?> РоверЛэнд. Все права защищены.</span>
	</div>
</footer>
