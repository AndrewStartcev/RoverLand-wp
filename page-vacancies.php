<?php
/*
Template Name: Вакансии
*/

get_header();

while ( have_posts() ) :
	the_post();

	$hero_image = roverland_field( 'vacancies_hero_image', array() );
	$hero_url   = roverland_image_url( $hero_image );
	$advantages = roverland_field( 'vacancies_advantages', array() );
	$contact_name  = roverland_option( 'careers_contact_name', 'Заверняев Геннадий' );
	$contact_phone = roverland_option( 'careers_contact_phone', '+7 (495) 668-06-58' );

	$vacancies = new WP_Query(
		array(
			'post_type'      => 'vacancy',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		)
	);
	?>
	<section class="vacancies-hero"<?php if ( $hero_url ) : ?> style="--vacancies-hero-image:url('<?php echo esc_url( $hero_url ); ?>');"<?php endif; ?>>
		<div class="container vacancies-hero__container"><div class="vacancies-hero__content">
			<?php get_template_part( 'template-parts/elements/breadcrumbs', null, array( 'items' => array(
				array( 'label' => 'Главная', 'url' => home_url( '/' ) ),
				array( 'label' => 'О компании', 'url' => roverland_page_url( 'kompaniya' ) ),
				array( 'label' => get_the_title() ),
			) ) ); ?>
			<h1 class="vacancies-hero__title"><?php echo nl2br( esc_html( roverland_field( 'vacancies_hero_title', get_the_title() ) ) ); ?></h1>
			<?php if ( roverland_field( 'vacancies_hero_lead', '' ) ) : ?><p class="vacancies-hero__lead"><?php echo esc_html( roverland_field( 'vacancies_hero_lead', '' ) ); ?></p><?php endif; ?>
		</div></div>
	</section>

	<?php if ( is_array( $advantages ) && $advantages ) : ?><section class="vacancies-advantages"><div class="container vacancies-advantages__list">
		<?php foreach ( $advantages as $item ) : ?><article class="vacancies-advantage-card">
			<?php $icon_url = roverland_image_url( $item['icon'] ?? array() ); if ( $icon_url ) : ?><img src="<?php echo esc_url( $icon_url ); ?>" width="30" height="30" alt="" aria-hidden="true"><?php endif; ?>
			<h2><?php echo esc_html( $item['title'] ?? '' ); ?></h2><p><?php echo esc_html( $item['text'] ?? '' ); ?></p>
		</article><?php endforeach; ?>
	</div></section><?php endif; ?>

	<section class="vacancies-open" id="vacancies"><div class="container">
		<div class="vacancies-open__heading"><h2><?php echo esc_html( roverland_field( 'vacancies_list_title', 'Открытые позиции' ) ); ?></h2></div>
		<div class="vacancies-open__list">
			<?php while ( $vacancies->have_posts() ) : $vacancies->the_post(); ?>
				<article class="vacancy-card">
					<div class="vacancy-card__top"><span><?php echo esc_html( roverland_field( 'vacancy_card_label', '', get_the_ID() ) ); ?></span>
					<?php $icon_url = roverland_image_url( roverland_field( 'vacancy_card_icon', array(), get_the_ID() ) ); if ( $icon_url ) : ?><img src="<?php echo esc_url( $icon_url ); ?>" width="22" height="22" alt="" aria-hidden="true"><?php endif; ?></div>
					<h3><?php echo esc_html( get_the_title() ); ?></h3>
					<p><?php echo esc_html( roverland_field( 'vacancy_summary', '', get_the_ID() ) ); ?></p>
					<a class="button button--outline button--wide" href="<?php the_permalink(); ?>">Подробнее</a>
				</article>
			<?php endwhile; wp_reset_postdata(); ?>
		</div>
	</div></section>

	<section class="vacancies-contact"><div class="container vacancies-contact__inner">
		<h2><?php echo esc_html( roverland_field( 'vacancies_contact_title', '' ) ); ?></h2>
		<p><?php echo nl2br( esc_html( roverland_field( 'vacancies_contact_text', '' ) ) ); ?></p>
		<div class="vacancies-contact__details"><div><span>КОНТАКТНОЕ ЛИЦО</span><strong><?php echo esc_html( $contact_name ); ?></strong></div><div><span>ПРЯМОЙ НОМЕР</span><a href="<?php echo esc_url( 'tel:' . roverland_phone_href( $contact_phone ) ); ?>"><?php echo esc_html( $contact_phone ); ?></a></div></div>
	</div></section>

	<section class="career-apply" id="career-form"><div class="container career-apply__layout">
		<div class="career-apply__intro">
			<h2><?php echo esc_html( roverland_field( 'vacancies_form_title', '' ) ); ?></h2>
			<p><?php echo esc_html( roverland_field( 'vacancies_form_text', '' ) ); ?></p>
			<ul class="career-apply__notes">
				<li><img src="<?php echo esc_url( roverland_asset( 'assets/images/icons/ui/vacancies-response.svg' ) ); ?>" width="20" height="20" alt="" aria-hidden="true"><span>Быстро рассмотрим отклик</span></li>
				<li><img src="<?php echo esc_url( roverland_asset( 'assets/images/icons/ui/vacancies-privacy.svg' ) ); ?>" width="20" height="20" alt="" aria-hidden="true"><span>Конфиденциальность данных</span></li>
			</ul>
		</div>
		<form class="career-form" action="#" method="post" enctype="multipart/form-data" novalidate>
			<div class="career-form__row"><label><span>ВАШЕ ИМЯ</span><input type="text" name="name" placeholder="Имя Фамилия" required></label><label><span>ТЕЛЕФОН</span><input type="tel" name="phone" placeholder="+7 (___) ___-__-__" required></label></div>
			<label><span>ЖЕЛАЕМАЯ ДОЛЖНОСТЬ</span><select name="position" required><option value="">Выберите должность</option>
				<?php foreach ( get_posts( array( 'post_type' => 'vacancy', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC' ) ) as $position ) : ?><option><?php echo esc_html( $position->post_title ); ?></option><?php endforeach; ?>
				<option>Другая должность</option>
			</select></label>
			<label class="career-form__file"><span>ПРИКРЕПИТЬ РЕЗЮМЕ</span><input type="file" name="resume" accept=".pdf,.doc,.docx"><span class="career-form__file-control">Добавить файл</span></label>
			<label><span>СООБЩЕНИЕ</span><textarea name="message" rows="5" placeholder="Расскажите немного о себе и вашем опыте..."></textarea></label>
			<label class="career-form__consent"><input type="checkbox" name="consent" required><span>Нажимая на кнопку, вы соглашаетесь с <a href="<?php echo esc_url( roverland_page_url( 'politika-konfidentsialnosti' ) ); ?>">политикой конфиденциальности</a>.</span></label>
			<button class="button button--primary button--wide" type="submit">Отправить отклик</button>
		</form>
	</div></section>

	<?php
	if ( roverland_field( 'vacancies_show_branches', true ) ) {
		get_template_part( 'template-parts/blocks/branches', null, array( 'class' => 'branches vacancies-branches', 'title' => roverland_option( 'common_branches_title', '' ) ) );
	}
	if ( roverland_field( 'vacancies_show_map', true ) ) {
		get_template_part( 'template-parts/blocks/map', null, array( 'class' => 'vacancies-map' ) );
	}
endwhile;

get_footer();
