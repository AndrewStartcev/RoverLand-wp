<?php

defined( 'ABSPATH' ) || exit;

add_action( 'wp_ajax_roverland_submit_form', 'roverland_handle_form_submit' );
add_action( 'wp_ajax_nopriv_roverland_submit_form', 'roverland_handle_form_submit' );

function roverland_form_recipient( $type ) {
	$key = 'career' === $type ? 'form_career_recipient' : 'form_service_recipient';
	$email = sanitize_email( (string) roverland_option( $key, '' ) );

	return $email ?: sanitize_email( (string) get_option( 'admin_email' ) );
}

function roverland_form_subject( $type ) {
	if ( 'career' === $type ) {
		$key = 'form_career_subject';
		$default = 'Новый отклик на вакансию — Rover Land';
	} elseif ( 'feedback' === $type ) {
		$key = 'form_feedback_subject';
		$default = 'Новая обратная связь — Rover Land';
	} elseif ( 'partner' === $type ) {
		$key = 'form_partner_subject';
		$default = 'Новая заявка на партнерство — Rover Land';
	} else {
		$key = 'form_service_subject';
		$default = 'Новая заявка на сервис — Rover Land';
	}

	return sanitize_text_field( (string) roverland_option( $key, $default ) );
}

function roverland_form_success_message( $type ) {
	if ( 'career' === $type ) {
		$key = 'form_career_success';
		$default = 'Спасибо! Отклик отправлен. Мы свяжемся с вами после рассмотрения.';
	} elseif ( 'feedback' === $type ) {
		$key = 'form_feedback_success';
		$default = 'Спасибо за обратную связь! Мы разберём обращение и свяжемся с вами.';
	} elseif ( 'partner' === $type ) {
		$key = 'form_partner_success';
		$default = 'Спасибо! Заявка на партнерство отправлена. Мы свяжемся с вами.';
	} else {
		$key = 'form_service_success';
		$default = 'Спасибо! Заявка отправлена. Мы свяжемся с вами в ближайшее время.';
	}

	return sanitize_text_field( (string) roverland_option( $key, $default ) );
}

function roverland_form_error_message() {
	return sanitize_text_field(
		(string) roverland_option(
			'form_error_message',
			'Не удалось отправить форму. Попробуйте ещё раз или позвоните нам.'
		)
	);
}

function roverland_form_clean_phone( $value ) {
	$digits = preg_replace( '/\D+/', '', (string) $value );

	if ( 11 === strlen( $digits ) && '8' === $digits[0] ) {
		$digits = '7' . substr( $digits, 1 );
	}

	return $digits;
}

function roverland_form_value( $key ) {
	return isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
}

function roverland_form_textarea( $key ) {
	return isset( $_POST[ $key ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) ) : '';
}

function roverland_form_upload_resume( &$errors ) {
	if ( empty( $_FILES['resume'] ) || empty( $_FILES['resume']['name'] ) ) {
		return '';
	}

	$file = $_FILES['resume'];

	if ( UPLOAD_ERR_OK !== (int) $file['error'] ) {
		$errors[] = 'Не удалось загрузить резюме.';
		return '';
	}

	if ( (int) $file['size'] > 8 * MB_IN_BYTES ) {
		$errors[] = 'Резюме должно быть не больше 8 МБ.';
		return '';
	}

	$allowed = array(
		'pdf'  => 'application/pdf',
		'doc'  => 'application/msword',
		'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
	);

	$ext = strtolower( pathinfo( sanitize_file_name( $file['name'] ), PATHINFO_EXTENSION ) );

	if ( ! isset( $allowed[ $ext ] ) ) {
		$errors[] = 'Разрешены только PDF, DOC и DOCX.';
		return '';
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';

	$uploaded = wp_handle_upload(
		$file,
		array(
			'test_form' => false,
			'mimes'     => $allowed,
		)
	);

	if ( ! empty( $uploaded['error'] ) || empty( $uploaded['file'] ) ) {
		$errors[] = 'Не удалось сохранить резюме.';
		return '';
	}

	return $uploaded['file'];
}

function roverland_seed_form_hidden_fields( $type ) {
	return '<input type="hidden" name="action" value="roverland_submit_form">'
		. '<input type="hidden" name="form_type" value="' . esc_attr( $type ) . '">'
		. '<input type="hidden" name="page_title" value="' . esc_attr( wp_get_document_title() ) . '">'
		. '<input type="hidden" name="page_url" value="' . esc_url( home_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ) ) ) . '">'
		. '<input type="hidden" name="_roverland_form_nonce" value="' . esc_attr( wp_create_nonce( 'roverland_form_submit' ) ) . '">'
		. '<input class="roverland-form-hp" type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true">';
}

function roverland_feedback_form_shortcode() {
	$branches = roverland_get_branches();
	ob_start();
	?>
	<div class="seeded-form-card">
		<h2>Расскажите о своем визите в наш центр</h2>
		<p>Ваш отзыв помогает нам улучшать качество обслуживания.</p>
		<form class="career-form seeded-form" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" method="post" data-roverland-form novalidate>
			<?php echo roverland_seed_form_hidden_fields( 'feedback' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<div class="career-form__row">
				<label><span>ФИО *</span><input type="text" name="name" autocomplete="name" required></label>
				<label><span>Телефон *</span><input type="tel" name="phone" autocomplete="tel" required></label>
			</div>
			<div class="career-form__row">
				<label><span>E-mail</span><input type="email" name="email" autocomplete="email"></label>
				<label><span>Дата визита</span><input type="date" name="visit_date"></label>
			</div>
			<label><span>Адрес филиала</span><select name="branch"><option value="">Выберите филиал</option><?php foreach ( $branches as $branch ) : $name = trim( (string) ( $branch['name'] ?? '' ) ); if ( ! $name ) continue; ?><option value="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $name ); ?></option><?php endforeach; ?></select></label>
			<label><span>Тип обращения</span><select name="feedback_type"><option value="Жалоба">Жалоба</option><option value="Предложение">Предложение</option><option value="Благодарность">Благодарность</option></select></label>
			<label><span>Текст обращения</span><textarea name="message" rows="6" required></textarea></label>
			<label class="career-form__consent"><input type="checkbox" name="consent" value="1" required><span>Я принимаю <a href="<?php echo esc_url( roverland_page_url( 'politika-konfidentsialnosti' ) ); ?>">условия обработки персональных данных</a>.</span></label>
			<button class="button button--primary" type="submit">Отправить</button>
			<p class="service-form__status" role="status" aria-live="polite" data-form-status></p>
		</form>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'roverland_feedback_form', 'roverland_feedback_form_shortcode' );

function roverland_partner_form_shortcode() {
	ob_start();
	?>
	<div class="seeded-form-card">
		<h2>Заявка на партнерство</h2>
		<form class="career-form seeded-form" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" method="post" data-roverland-form novalidate>
			<?php echo roverland_seed_form_hidden_fields( 'partner' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<div class="career-form__row">
				<label><span>ФИО представителя *</span><input type="text" name="name" required></label>
				<label><span>Телефон для связи *</span><input type="tel" name="phone" required></label>
			</div>
			<label><span>Название СТО</span><input type="text" name="station_name"></label>
			<label><span>Фактический адрес</span><input type="text" name="address"></label>
			<label><span>Реквизиты компании</span><textarea name="company_details" rows="3"></textarea></label>
			<div class="career-form__row">
				<label><span>Опыт работы СТО</span><input type="text" name="experience"></label>
				<label><span>Специализация СТО</span><input type="text" name="specialization"></label>
			</div>
			<label><span>Ожидания от работы с нами</span><textarea name="expectations" rows="5"></textarea></label>
			<label class="career-form__consent"><input type="checkbox" name="consent" value="1" required><span>Я принимаю <a href="<?php echo esc_url( roverland_page_url( 'politika-konfidentsialnosti' ) ); ?>">условия обработки персональных данных</a>.</span></label>
			<button class="button button--primary" type="submit">Отправить заявку</button>
			<p class="service-form__status" role="status" aria-live="polite" data-form-status></p>
		</form>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'roverland_partner_form', 'roverland_partner_form_shortcode' );

function roverland_handle_form_submit() {
	if ( ! check_ajax_referer( 'roverland_form_submit', '_roverland_form_nonce', false ) ) {
		wp_send_json_error( array( 'message' => 'Сессия формы устарела. Обновите страницу и попробуйте ещё раз.' ), 403 );
	}

	if ( ! empty( $_POST['website'] ) ) {
		wp_send_json_success( array( 'message' => roverland_form_success_message( 'service' ) ) );
	}

	$type = roverland_form_value( 'form_type' );

	if ( ! in_array( $type, array( 'service', 'career', 'feedback', 'partner' ), true ) ) {
		wp_send_json_error( array( 'message' => roverland_form_error_message() ), 400 );
	}

	$name    = roverland_form_value( 'name' );
	$phone   = roverland_form_value( 'phone' );
	$digits  = roverland_form_clean_phone( $phone );
	$consent = ! empty( $_POST['consent'] );
	$errors  = array();

	if ( mb_strlen( $name ) < 2 ) {
		$errors[] = 'Укажите имя.';
	}

	if ( strlen( $digits ) < 11 ) {
		$errors[] = 'Укажите корректный телефон.';
	}

	if ( ! $consent ) {
		$errors[] = 'Нужно согласие на обработку персональных данных.';
	}

	if ( $errors ) {
		wp_send_json_error( array( 'message' => implode( ' ', $errors ) ), 422 );
	}

	$page_title = roverland_form_value( 'page_title' );
	$page_url   = esc_url_raw( roverland_form_value( 'page_url' ) );
	$type_labels = array(
		'service'  => 'Запись на сервис',
		'career'   => 'Отклик на вакансию',
		'feedback' => 'Обратная связь',
		'partner'  => 'Заявка на партнерство',
	);
	$lines      = array(
		'Тип формы: ' . ( $type_labels[ $type ] ?? $type ),
		'Имя: ' . $name,
		'Телефон: ' . $phone,
	);

	if ( 'service' === $type ) {
		$branch = roverland_form_value( 'branch' );

		if ( $branch ) {
			$lines[] = 'Филиал: ' . $branch;
		}
	} elseif ( 'career' === $type ) {
		$position = roverland_form_value( 'position' );
		$message  = roverland_form_textarea( 'message' );

		if ( $position ) {
			$lines[] = 'Вакансия: ' . $position;
		}

		if ( $message ) {
			$lines[] = '';
			$lines[] = 'Сообщение:';
			$lines[] = $message;
		}
	} elseif ( 'feedback' === $type ) {
		$fields = array(
			'email'         => 'E-mail',
			'visit_date'    => 'Дата визита',
			'branch'        => 'Филиал',
			'feedback_type' => 'Тип обращения',
		);

		foreach ( $fields as $key => $label ) {
			$value = roverland_form_value( $key );
			if ( $value ) {
				$lines[] = $label . ': ' . $value;
			}
		}

		$message = roverland_form_textarea( 'message' );
		if ( $message ) {
			$lines[] = '';
			$lines[] = 'Текст обращения:';
			$lines[] = $message;
		}
	} elseif ( 'partner' === $type ) {
		$fields = array(
			'station_name'    => 'Название СТО',
			'address'         => 'Фактический адрес',
			'company_details' => 'Реквизиты компании',
			'experience'      => 'Опыт работы СТО',
			'specialization'  => 'Специализация СТО',
		);

		foreach ( $fields as $key => $label ) {
			$value = roverland_form_value( $key );
			if ( $value ) {
				$lines[] = $label . ': ' . $value;
			}
		}

		$expectations = roverland_form_textarea( 'expectations' );
		if ( $expectations ) {
			$lines[] = '';
			$lines[] = 'Ожидания от сотрудничества:';
			$lines[] = $expectations;
		}
	}

	if ( $page_title ) {
		$lines[] = '';
		$lines[] = 'Страница: ' . $page_title;
	}

	if ( $page_url ) {
		$lines[] = 'URL: ' . $page_url;
	}

	$attachments = array();
	$resume      = '';

	if ( 'career' === $type ) {
		$resume = roverland_form_upload_resume( $errors );

		if ( $errors ) {
			wp_send_json_error( array( 'message' => implode( ' ', $errors ) ), 422 );
		}

		if ( $resume ) {
			$attachments[] = $resume;
		}
	}

	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
	$sent    = wp_mail(
		roverland_form_recipient( $type ),
		roverland_form_subject( $type ),
		implode( "\n", $lines ),
		$headers,
		$attachments
	);

	if ( $resume && is_file( $resume ) ) {
		@unlink( $resume );
	}

	if ( ! $sent ) {
		wp_send_json_error( array( 'message' => roverland_form_error_message() ), 500 );
	}

	wp_send_json_success(
		array(
			'message' => roverland_form_success_message( $type ),
		)
	);
}
