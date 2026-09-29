<?php

get_header();

$services = roverland_menu_page( 'servis' );
$contact  = roverland_menu_page( 'kontakty' );
?>
<section class="error-page">
	<div class="container error-page__layout">
		<div class="error-page__content">
			<p class="error-page__eyebrow">Ошибка 404</p>
			<p class="error-page__code" aria-hidden="true">404</p>
			<h1>Такой страницы больше нет</h1>
			<p class="error-page__lead">
				Возможно, адрес изменился после обновления сайта. Перейдите на главную, выберите нужную услугу или свяжитесь с Rover Land.
			</p>

			<div class="error-page__actions">
				<a class="button button--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>">На главную</a>
				<?php if ( $services ) : ?>
					<a class="button button--outline" href="<?php echo esc_url( get_permalink( $services ) ); ?>">Услуги сервиса</a>
				<?php endif; ?>
			</div>

			<?php if ( $contact ) : ?>
				<a class="error-page__contact" href="<?php echo esc_url( get_permalink( $contact ) ); ?>">Контакты и адреса филиалов <span aria-hidden="true">→</span></a>
			<?php endif; ?>
		</div>

		<div class="error-page__visual" aria-hidden="true">
			<div class="error-page__badge">Rover Land</div>
			<div class="error-page__road">
				<span></span><span></span><span></span>
			</div>
		</div>
	</div>
</section>
<?php
get_footer();
