<?php

get_header();

while ( have_posts() ) :
	the_post();

	$hero_image = roverland_field( 'vacancy_hero_image', array() );
	$hero_url   = roverland_image_url( $hero_image );
	$duties     = roverland_field( 'vacancy_responsibilities', array() );
	$requirements = roverland_field( 'vacancy_requirements', array() );
	$offer      = roverland_field( 'vacancy_offer', array() );
	$contact_name  = roverland_option( 'careers_contact_name', 'Заверняев Геннадий' );
	$contact_phone = roverland_option( 'careers_contact_phone', '+7 (495) 668-06-58' );
	?>
	<section class="vacancy-hero"<?php if ( $hero_url ) : ?> style="--vacancy-hero-image:url('<?php echo esc_url( $hero_url ); ?>');"<?php endif; ?>>
		<div class="container vacancy-hero__container"><div class="vacancy-hero__content">
			<?php get_template_part( 'template-parts/elements/breadcrumbs', null, array( 'items' => array(
				array( 'label' => 'Главная', 'url' => home_url( '/' ) ),
				array( 'label' => 'О компании', 'url' => roverland_page_url( 'kompaniya' ) ),
				array( 'label' => 'Вакансии', 'url' => roverland_page_url( 'kompaniya/vakansii' ) ),
				array( 'label' => get_the_title() ),
			) ) ); ?>
			<h1 class="vacancy-hero__title"><?php echo nl2br( esc_html( roverland_field( 'vacancy_hero_title', get_the_title() ) ) ); ?></h1>
			<a class="button button--primary vacancy-hero__button" href="#vacancy-apply">Оставить заявку</a>
		</div></div>
	</section>

	<section class="vacancy-main"><div class="container vacancy-main__layout">
		<div class="vacancy-main__content">
			<section class="vacancy-section">
				<h2 class="vacancy-section__title"><?php echo esc_html( roverland_field( 'vacancy_responsibilities_title', 'Обязанности' ) ); ?></h2>
				<?php if ( is_array( $duties ) && $duties ) : ?><div class="vacancy-duty-list"><?php foreach ( $duties as $duty ) : ?><div class="vacancy-duty">
					<?php $icon_url = roverland_image_url( $duty['icon'] ?? array() ); if ( $icon_url ) : ?><img src="<?php echo esc_url( $icon_url ); ?>" width="24" height="24" alt=""><?php endif; ?><span><?php echo esc_html( $duty['text'] ?? '' ); ?></span>
				</div><?php endforeach; ?></div><?php endif; ?>
			</section>

			<section class="vacancy-requirements"><h2><?php echo esc_html( roverland_field( 'vacancy_requirements_title', 'Требования' ) ); ?></h2>
				<?php if ( is_array( $requirements ) && $requirements ) : ?><ul><?php foreach ( $requirements as $requirement ) : ?><li>
					<?php $icon_url = roverland_image_url( $requirement['icon'] ?? array() ); if ( $icon_url ) : ?><img src="<?php echo esc_url( $icon_url ); ?>" width="22" height="22" alt=""><?php endif; ?><span><?php echo esc_html( $requirement['text'] ?? '' ); ?></span>
				</li><?php endforeach; ?></ul><?php endif; ?>
			</section>

			<section class="vacancy-offer"><h2><?php echo esc_html( roverland_field( 'vacancy_offer_title', 'Компания предлагает' ) ); ?></h2>
				<?php if ( is_array( $offer ) && $offer ) : ?><div class="vacancy-offer__list"><?php foreach ( $offer as $item ) : ?><div><span><?php echo esc_html( $item['label'] ?? '' ); ?></span><strong><?php echo esc_html( $item['text'] ?? '' ); ?></strong></div><?php endforeach; ?></div><?php endif; ?>
			</section>
		</div>

		<aside class="vacancy-sidebar">
			<div class="vacancy-contact-card"><span>КОНТАКТНОЕ ЛИЦО</span><strong><?php echo esc_html( $contact_name ); ?></strong><a href="<?php echo esc_url( 'tel:' . roverland_phone_href( $contact_phone ) ); ?>"><?php echo esc_html( $contact_phone ); ?></a></div>
			<form class="vacancy-apply-card" id="vacancy-apply" action="#" method="post" novalidate>
				<h2>Оставить заявку</h2>
				<label><span>ВАШЕ ИМЯ</span><input type="text" name="name" placeholder="Имя Фамилия" required></label>
				<label><span>НОМЕР ТЕЛЕФОНА</span><input type="tel" name="phone" placeholder="+7 (___) ___-__-__" required></label>
				<label><span>ВАКАНСИЯ</span><input type="text" name="position" value="<?php echo esc_attr( get_the_title() ); ?>" readonly></label>
				<label><span>СООБЩЕНИЕ</span><textarea name="message" rows="4" placeholder="Опишите"></textarea></label>
				<button class="button button--wide vacancy-apply-card__submit" type="submit">Отправить заявку</button>
			</form>
		</aside>
	</div></section>

	<section class="vacancies-contact"><div class="container vacancies-contact__inner">
		<h2>Не нашли подходящую вакансию?</h2><p>Мы всегда рады талантливым специалистам. Свяжитесь с нами напрямую или оставьте заявку — мы обязательно рассмотрим ваше резюме.</p>
		<div class="vacancies-contact__details"><div><span>КОНТАКТНОЕ ЛИЦО</span><strong><?php echo esc_html( $contact_name ); ?></strong></div><div><span>ПРЯМОЙ НОМЕР</span><a href="<?php echo esc_url( 'tel:' . roverland_phone_href( $contact_phone ) ); ?>"><?php echo esc_html( $contact_phone ); ?></a></div></div>
	</div></section>

	<?php
	get_template_part( 'template-parts/blocks/branches', null, array( 'class' => 'branches vacancies-branches', 'title' => roverland_option( 'common_branches_title', '' ) ) );
	get_template_part( 'template-parts/blocks/map', null, array( 'class' => 'vacancies-map' ) );
endwhile;

get_footer();
