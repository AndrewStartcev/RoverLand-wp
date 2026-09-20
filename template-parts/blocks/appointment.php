<?php

defined( 'ABSPATH' ) || exit;

$title       = roverland_option( 'common_appointment_title', 'Запишитесь на сервис' );
$accent      = roverland_option( 'common_appointment_accent', 'прямо сейчас' );
$description = roverland_option( 'common_appointment_description', '' );
$phone       = roverland_option( 'common_appointment_phone', roverland_option( 'site_main_phone', '' ) );
$button      = roverland_option( 'common_appointment_button', 'Отправить заявку' );
$branches    = roverland_get_branches();
?>
<section class="appointment" id="appointment">
	<div class="container appointment__layout">
		<div class="appointment__content">
			<h2>
				<?php echo esc_html( $title ); ?>
				<?php if ( $accent ) : ?><span><?php echo esc_html( $accent ); ?></span><?php endif; ?>
			</h2>

			<?php if ( $description ) : ?>
				<p><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>

			<?php if ( $phone ) : ?>
				<a class="appointment__phone" href="<?php echo esc_url( 'tel:' . roverland_phone_href( $phone ) ); ?>">
					<img src="<?php echo esc_url( roverland_asset( 'assets/images/icons/social/phone.svg' ) ); ?>" width="24" height="24" alt="" aria-hidden="true">
					<?php echo esc_html( $phone ); ?>
				</a>
			<?php endif; ?>
		</div>

		<form class="service-form" action="#" method="post" data-service-form novalidate>
			<div class="service-form__row">
				<label class="field">
					<span class="field__label">Ваше имя</span>
					<input class="field__control" type="text" name="your-name" placeholder="Иван" autocomplete="name" required>
					<span class="field__error" data-error>Введите имя</span>
				</label>

				<label class="field">
					<span class="field__label">Телефон</span>
					<input class="field__control" type="tel" name="your-phone" placeholder="+7 (___) ___-__-__" autocomplete="tel" required>
					<span class="field__error" data-error>Введите телефон</span>
				</label>
			</div>

			<label class="field field--full">
				<span class="field__label">Выберите филиал</span>
				<select class="field__control" name="branch" required>
					<option value="">Выберите филиал</option>
					<?php foreach ( $branches as $branch ) : ?>
						<?php
						$name       = isset( $branch['name'] ) ? $branch['name'] : '';
						$department = isset( $branch['department'] ) ? $branch['department'] : '';

						if ( ! $name ) {
							continue;
						}

						$label = $department ? $name . ' — ' . $department : $name;
						?>
						<option value="<?php echo esc_attr( $label ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<span class="field__error" data-error>Выберите филиал</span>
			</label>

			<label class="service-form__consent">
				<input type="checkbox" name="consent" required>
				<span>
					Нажимая на кнопку, я принимаю согласие на обработку
					<a href="<?php echo esc_url( roverland_page_url( 'politika-konfidentsialnosti' ) ); ?>">персональных данных</a>
				</span>
			</label>

			<button class="button button--primary button--wide" type="submit"><?php echo esc_html( $button ); ?></button>
			<p class="service-form__status" role="status" aria-live="polite" data-form-status></p>
		</form>
	</div>
</section>
