<?php

defined( 'ABSPATH' ) || exit;

$footer_logo = roverland_option( 'site_footer_logo', array() );
$logo_url    = roverland_image_url( $footer_logo, 'assets/images/content/logo-black.png' );
$logo_alt    = roverland_image_alt( $footer_logo, get_bloginfo( 'name' ) );
$description = roverland_option( 'site_footer_description', 'РоверЛэнд — сервис и ремонт Land Rover.' );
$copyright   = roverland_option( 'site_copyright', 'РоверЛэнд. Все права защищены.' );
$branches    = roverland_get_branches();
$socials     = roverland_get_social_links();
?>
<footer class="site-footer">
	<div class="container site-footer__top">
		<div class="site-footer__brand">
			<img src="<?php echo esc_url( $logo_url ); ?>" width="183" height="36" alt="<?php echo esc_attr( $logo_alt ); ?>">

			<?php if ( $description ) : ?>
				<p><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>

			<?php if ( $socials ) : ?>
				<div class="social-links social-links--footer" aria-label="Социальные сети">
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

		<div class="site-footer__addresses">
			<h2 class="site-footer__heading">Адреса филиалов</h2>

			<div class="site-footer__address-grid">
				<?php foreach ( $branches as $branch ) : ?>
					<?php
					if ( array_key_exists( 'show_in_footer', $branch ) && ! $branch['show_in_footer'] ) {
						continue;
					}

					$name    = isset( $branch['name'] ) ? $branch['name'] : '';
					$phone   = isset( $branch['phone'] ) ? $branch['phone'] : '';
					$address = isset( $branch['address'] ) ? $branch['address'] : '';

					if ( ! $name ) {
						continue;
					}
					?>
					<address>
						<strong><?php echo esc_html( $name ); ?></strong>
						<?php if ( $address ) : ?><span><?php echo esc_html( $address ); ?>, Москва</span><?php endif; ?>
						<?php if ( $phone ) : ?><a href="<?php echo esc_url( 'tel:' . roverland_phone_href( $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a><?php endif; ?>
					</address>
				<?php endforeach; ?>
			</div>
		</div>

		<nav class="site-footer__info" aria-label="Информация">
			<h2 class="site-footer__heading">Информация</h2>
			<?php
			$footer_info_pages = array(
				array( 'path' => 'kompaniya', 'label' => 'О компании' ),
				array( 'path' => 'kompaniya/istoriya', 'label' => 'История' ),
				array( 'path' => 'kompaniya/vakansii', 'label' => 'Вакансии' ),
				array( 'path' => 'aktsii', 'label' => 'Акции' ),
				array( 'path' => 'portfolio', 'label' => 'Портфолио' ),
				array( 'path' => 'kontakty', 'label' => 'Контакты' ),
				array( 'path' => 'politika-konfidentsialnosti', 'label' => 'Политика конфиденциальности' ),
				array( 'path' => 'politika-ispolzovaniya-cookie', 'label' => 'Политика использования cookie' ),
			);

			foreach ( $footer_info_pages as $footer_item ) {
				$page = roverland_menu_page( $footer_item['path'] );

				if ( ! $page ) {
					continue;
				}

				echo '<a href="' . esc_url( get_permalink( $page ) ) . '">' . esc_html( $footer_item['label'] ) . '</a>';
			}
			?>
		</nav>
	</div>

	<div class="container site-footer__bottom">
		<span>© <?php echo esc_html( wp_date( 'Y' ) . ' ' . $copyright ); ?></span>
	</div>
</footer>
