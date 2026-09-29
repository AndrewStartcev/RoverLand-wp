<?php

defined( 'ABSPATH' ) || exit;

$branches = roverland_get_branches();
$eyebrow  = roverland_option( 'form_service_modal_eyebrow', 'Запись в Rover Land' );
$title    = roverland_option( 'form_service_modal_title', 'Запишитесь на сервис' );
$lead     = roverland_option( 'form_service_modal_lead', 'Оставьте контактные данные — мастер-консультант свяжется с вами для уточнения деталей.' );
$button   = roverland_option( 'form_service_button', 'Отправить заявку' );
?>
<div class="service-modal" data-service-modal aria-hidden="true">
	<div class="service-modal__backdrop" data-service-modal-close></div>
	<div class="service-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="service-modal-title">
		<button class="icon-button service-modal__close" type="button" aria-label="Закрыть форму" data-service-modal-close>
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 5 19 19M19 5 5 19"></path></svg>
		</button>

		<?php if ( $eyebrow ) : ?><p class="service-modal__eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?>
		<h2 id="service-modal-title"><?php echo esc_html( $title ); ?></h2>
		<?php if ( $lead ) : ?><p class="service-modal__lead"><?php echo esc_html( $lead ); ?></p><?php endif; ?>

		<form class="service-form service-modal__form" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" method="post" data-roverland-form novalidate>
			<input type="hidden" name="action" value="roverland_submit_form">
			<input type="hidden" name="form_type" value="service">
			<input type="hidden" name="page_title" value="<?php echo esc_attr( wp_get_document_title() ); ?>">
			<input type="hidden" name="page_url" value="<?php echo esc_url( home_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ) ) ); ?>">
			<input type="hidden" name="_roverland_form_nonce" value="<?php echo esc_attr( wp_create_nonce( 'roverland_form_submit' ) ); ?>">
			<input class="roverland-form-hp" type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true">

			<div class="service-form__row">
				<label class="field">
					<span class="field__label">Ваше имя</span>
					<input class="field__control" type="text" name="name" placeholder="Иван" autocomplete="name" required>
					<span class="field__error" data-error>Введите имя</span>
				</label>
				<label class="field">
					<span class="field__label">Телефон</span>
					<input class="field__control" type="tel" name="phone" placeholder="+7 (___) ___-__-__" autocomplete="tel" required>
					<span class="field__error" data-error>Введите телефон</span>
				</label>
			</div>

			<label class="field field--full">
				<span class="field__label">Выберите филиал</span>
				<select class="field__control" name="branch" required>
					<option value="">Выберите филиал</option>
					<?php foreach ( $branches as $branch ) : ?>
						<?php
						$name = trim( (string) ( $branch['name'] ?? '' ) );
						$department = trim( (string) ( $branch['department'] ?? '' ) );
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
				<input type="checkbox" name="consent" value="1" required>
				<span>Нажимая на кнопку, я принимаю согласие на обработку <a href="<?php echo esc_url( roverland_page_url( 'politika-konfidentsialnosti' ) ); ?>">персональных данных</a></span>
			</label>

			<button class="button button--primary button--wide" type="submit"><?php echo esc_html( $button ); ?></button>
			<p class="service-form__status" role="status" aria-live="polite" data-form-status></p>
		</form>
	</div>
</div>
