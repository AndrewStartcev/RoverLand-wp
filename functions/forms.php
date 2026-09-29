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
	$key = 'career' === $type ? 'form_career_subject' : 'form_service_subject';
	$default = 'career' === $type ? 'Новый отклик на вакансию — Rover Land' : 'Новая заявка на сервис — Rover Land';

	return sanitize_text_field( (string) roverland_option( $key, $default ) );
}

function roverland_form_success_message( $type ) {
	$key = 'career' === $type ? 'form_career_success' : 'form_service_success';
	$default = 'career' === $type
		? 'Спасибо! Отклик отправлен. Мы свяжемся с вами после рассмотрения.'
		: 'Спасибо! Заявка отправлена. Мы свяжемся с вами в ближайшее время.';

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

function roverland_handle_form_submit() {
	if ( ! check_ajax_referer( 'roverland_form_submit', '_roverland_form_nonce', false ) ) {
		wp_send_json_error( array( 'message' => 'Сессия формы устарела. Обновите страницу и попробуйте ещё раз.' ), 403 );
	}

	if ( ! empty( $_POST['website'] ) ) {
		wp_send_json_success( array( 'message' => roverland_form_success_message( 'service' ) ) );
	}

	$type = roverland_form_value( 'form_type' );

	if ( ! in_array( $type, array( 'service', 'career' ), true ) ) {
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
	$lines      = array(
		'Тип формы: ' . ( 'career' === $type ? 'Отклик на вакансию' : 'Запись на сервис' ),
		'Имя: ' . $name,
		'Телефон: ' . $phone,
	);

	if ( 'service' === $type ) {
		$branch = roverland_form_value( 'branch' );

		if ( $branch ) {
			$lines[] = 'Филиал: ' . $branch;
		}
	} else {
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
