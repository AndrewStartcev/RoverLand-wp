<?php

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', 'roverland_base_import_register_page', 100 );
add_action( 'admin_post_roverland_base_import', 'roverland_base_import_handle' );

function roverland_base_import_register_page() {
	add_submenu_page(
		'roverland-settings',
		'Импорт базовых данных',
		'Импорт данных',
		'manage_options',
		'roverland-base-import',
		'roverland_base_import_render_page'
	);
}

function roverland_base_import_data() {
	$file = get_template_directory() . '/data/base-content.json';

	if ( ! is_readable( $file ) ) {
		return new WP_Error( 'roverland_import_missing_json', 'Не найден data/base-content.json.' );
	}

	$data = json_decode( file_get_contents( $file ), true );

	if ( ! is_array( $data ) ) {
		return new WP_Error( 'roverland_import_invalid_json', 'data/base-content.json содержит некорректный JSON.' );
	}

	return $data;
}

function roverland_base_import_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$report = get_transient( 'roverland_base_import_report_' . get_current_user_id() );

	if ( $report ) {
		delete_transient( 'roverland_base_import_report_' . get_current_user_id() );
	}

	$data      = roverland_base_import_data();
	$acf_ready = function_exists( 'update_field' );
	$can_run   = ! is_wp_error( $data ) && $acf_ready;
	$page_count = is_array( $data ) && ! empty( $data['pages'] ) && is_array( $data['pages'] ) ? count( $data['pages'] ) : 0;
	$batch_size = 20;
	?>
	<div class="wrap roverland-import">
		<h1>Импорт данных Rover Land</h1>
		<p class="description">
			Импорт разбит на независимые части. Уже заполненный контент не нужно прогонять повторно:
			запускайте только тот раздел, который изменился.
		</p>

		<?php if ( is_array( $report ) ) : ?>
			<div class="notice notice-<?php echo empty( $report['errors'] ) ? 'success' : 'warning'; ?> is-dismissible">
				<p><strong><?php echo empty( $report['errors'] ) ? 'Операция завершена.' : 'Операция завершена с замечаниями.'; ?></strong></p>
				<p>
					Создано: <?php echo (int) $report['created']; ?>,
					обновлено: <?php echo (int) $report['updated']; ?>,
					пропущено: <?php echo isset( $report['skipped'] ) ? (int) $report['skipped'] : 0; ?>,
					медиа: <?php echo (int) $report['media']; ?>.
				</p>
				<?php if ( ! empty( $report['label'] ) ) : ?>
					<p><strong>Режим:</strong> <?php echo esc_html( $report['label'] ); ?></p>
				<?php endif; ?>
				<?php if ( ! empty( $report['errors'] ) ) : ?>
					<ul>
						<?php foreach ( $report['errors'] as $error ) : ?>
							<li><?php echo esc_html( $error ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( is_wp_error( $data ) ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( $data->get_error_message() ); ?></p></div>
		<?php endif; ?>

		<div class="roverland-import__card">
			<h2>Безопасная частичная синхронизация</h2>
			<p>
				Режим <strong>«только отсутствующее»</strong> создаёт новые записи, но не перезаписывает ACF существующих страниц.
				Растровые изображения импортёр по-прежнему не трогает.
			</p>

			<div class="roverland-import__actions">
				<?php
				roverland_base_import_button( 'Глобальные настройки', 'options', 0, 0, false, $can_run );
				roverland_base_import_button( 'Модели и справочник услуг', 'references', 0, 0, false, $can_run );
				roverland_base_import_button( 'Вакансии', 'vacancies', 0, 0, false, $can_run );
				roverland_base_import_button( 'Акции', 'promotions', 0, 0, false, $can_run );
				roverland_base_import_button( 'Портфолио', 'portfolio', 0, 0, false, $can_run );
				?>
			</div>
		</div>

		<div class="roverland-import__card">
			<h2>Страницы</h2>
			<p>
				Страницы идут пачками по <?php echo (int) $batch_size; ?>. По умолчанию существующий ACF-контент сохраняется:
				обновляется только структура записи (URL, родитель, порядок, шаблон), а поля заполняются только у новых страниц.
			</p>

			<div class="roverland-import__batches">
				<?php for ( $offset = 0; $offset < $page_count; $offset += $batch_size ) : ?>
					<?php
					$from = $offset + 1;
					$to   = min( $offset + $batch_size, $page_count );
					roverland_base_import_button(
						sprintf( 'Страницы %d–%d', $from, $to ),
						'pages',
						$offset,
						$batch_size,
						true,
						$can_run
					);
					?>
				<?php endfor; ?>
			</div>
		</div>

		<div class="roverland-import__card roverland-import__card--warning">
			<h2>Принудительная синхронизация выбранной пачки</h2>
			<p>
				Используйте только когда нужно заново применить JSON к уже существующим ACF-полям.
				Это не требуется для обычного добавления новых страниц.
			</p>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" class="roverland-import__force-form">
				<input type="hidden" name="action" value="roverland_base_import">
				<input type="hidden" name="scope" value="pages">
				<input type="hidden" name="preserve_existing" value="0">
				<?php wp_nonce_field( 'roverland_base_import', 'roverland_base_import_nonce' ); ?>
				<label>
					<span>Начать с позиции</span>
					<input type="number" name="offset" min="0" max="<?php echo esc_attr( max( 0, $page_count - 1 ) ); ?>" value="0">
				</label>
				<label>
					<span>Количество</span>
					<input type="number" name="limit" min="1" max="30" value="10">
				</label>
				<?php submit_button( 'Принудительно обновить пачку', 'secondary', 'submit', false, $can_run ? array( 'onclick' => "return confirm('Перезаписать ACF выбранных существующих страниц данными из JSON?');" ) : array( 'disabled' => 'disabled' ) ); ?>
			</form>
		</div>
	</div>

	<style>
	.roverland-import{max-width:1100px}
	.roverland-import__card{margin-top:24px;padding:24px 28px;border:1px solid #dcdcde;border-radius:12px;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.04)}
	.roverland-import__card--warning{border-color:#dba617;background:#fffdf5}
	.roverland-import__actions,.roverland-import__batches{display:flex;flex-wrap:wrap;gap:10px;margin-top:18px}
	.roverland-import__actions form,.roverland-import__batches form{margin:0}
	.roverland-import__force-form{display:flex;align-items:end;flex-wrap:wrap;gap:12px;margin-top:16px}
	.roverland-import__force-form label span{display:block;margin-bottom:5px;font-weight:600}
	.roverland-import__force-form input{width:130px}
	</style>
	<?php
}

function roverland_base_import_button( $label, $scope, $offset = 0, $limit = 0, $preserve_existing = true, $enabled = true ) {
	?>
	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
		<input type="hidden" name="action" value="roverland_base_import">
		<input type="hidden" name="scope" value="<?php echo esc_attr( $scope ); ?>">
		<input type="hidden" name="offset" value="<?php echo (int) $offset; ?>">
		<input type="hidden" name="limit" value="<?php echo (int) $limit; ?>">
		<input type="hidden" name="preserve_existing" value="<?php echo $preserve_existing ? '1' : '0'; ?>">
		<?php wp_nonce_field( 'roverland_base_import', 'roverland_base_import_nonce' ); ?>
		<button class="button<?php echo 'pages' === $scope ? '' : ' button-primary'; ?>" type="submit"<?php disabled( ! $enabled ); ?>>
			<?php echo esc_html( $label ); ?>
		</button>
	</form>
	<?php
}

function roverland_base_import_handle() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Недостаточно прав.' );
	}

	check_admin_referer( 'roverland_base_import', 'roverland_base_import_nonce' );

	$scope             = isset( $_POST['scope'] ) ? sanitize_key( wp_unslash( $_POST['scope'] ) ) : 'pages';
	$offset            = isset( $_POST['offset'] ) ? max( 0, (int) $_POST['offset'] ) : 0;
	$limit             = isset( $_POST['limit'] ) ? max( 0, min( 30, (int) $_POST['limit'] ) ) : 0;
	$preserve_existing = ! isset( $_POST['preserve_existing'] ) || '0' !== (string) $_POST['preserve_existing'];

	$allowed_scopes = array( 'options', 'references', 'pages', 'vacancies', 'promotions', 'portfolio' );
	if ( ! in_array( $scope, $allowed_scopes, true ) ) {
		$scope = 'pages';
	}

	$report = array(
		'created' => 0,
		'updated' => 0,
		'skipped' => 0,
		'media'   => 0,
		'errors'  => array(),
		'label'   => $scope,
	);

	if ( ! function_exists( 'update_field' ) ) {
		$report['errors'][] = 'ACF Pro не активен.';
		roverland_base_import_finish( $report );
	}

	$data = roverland_base_import_data();

	if ( is_wp_error( $data ) ) {
		$report['errors'][] = $data->get_error_message();
		roverland_base_import_finish( $report );
	}

	@set_time_limit( 60 );
	wp_raise_memory_limit( 'admin' );

	if ( 'options' === $scope ) {
		$report['label'] = 'Глобальные настройки';

		foreach ( isset( $data['options'] ) && is_array( $data['options'] ) ? $data['options'] : array() as $field_name => $value ) {
			$current_value = get_field( $field_name, 'option' );

			update_field(
				$field_name,
				roverland_base_import_resolve_value( $value, $report, $current_value ),
				'option'
			);
		}

		roverland_base_import_finish( $report );
	}

	if ( 'references' === $scope ) {
		$report['label'] = 'Модели и справочник услуг';
		roverland_base_import_reference_group( isset( $data['models'] ) ? $data['models'] : array(), 'rover_model', 'модель', $report, $preserve_existing );
		roverland_base_import_reference_group( isset( $data['services'] ) ? $data['services'] : array(), 'rover_service', 'услугу', $report, $preserve_existing );
		flush_rewrite_rules();
		roverland_base_import_finish( $report );
	}

	if ( 'pages' === $scope ) {
		$pages = isset( $data['pages'] ) && is_array( $data['pages'] ) ? $data['pages'] : array();

		if ( $limit < 1 ) {
			$limit = 20;
		}

		$batch = array_slice( $pages, $offset, $limit );
		$report['label'] = sprintf( 'Страницы %d–%d', $offset + 1, min( $offset + $limit, count( $pages ) ) );

		foreach ( $batch as $page_data ) {
			$parent_id = ! empty( $page_data['parent_key'] ) ? roverland_base_import_find_post_by_key( 'page', $page_data['parent_key'] ) : 0;
			$existing  = roverland_base_import_find_page_id( $page_data );

			$page_id = roverland_base_import_upsert_page( $page_data, $report, $parent_id );

			if ( ! $page_id ) {
				continue;
			}

			$is_new = ! $existing;

			if ( 'home' === $page_data['key'] ) {
				update_option( 'show_on_front', 'page' );
				update_option( 'page_on_front', $page_id );
			}

			if ( $is_new || ! $preserve_existing ) {
				roverland_base_import_apply_fields( $page_id, $page_data, $report );

				if ( ! empty( $page_data['seo'] ) && is_array( $page_data['seo'] ) ) {
					roverland_base_import_apply_rank_math( $page_id, $page_data['seo'] );
				}
			} else {
				$report['skipped']++;
			}
		}

		flush_rewrite_rules();
		roverland_base_import_finish( $report );
	}

	if ( 'vacancies' === $scope ) {
		$report['label'] = 'Вакансии';

		foreach ( isset( $data['vacancies'] ) && is_array( $data['vacancies'] ) ? $data['vacancies'] : array() as $item_data ) {
			$existing = roverland_base_import_find_post_by_key( 'vacancy', $item_data['key'] );
			$item_id  = roverland_base_import_upsert_vacancy( $item_data, $report );

			if ( $item_id && ( ! $existing || ! $preserve_existing ) ) {
				roverland_base_import_apply_fields( $item_id, $item_data, $report );
				if ( ! empty( $item_data['seo'] ) ) {
					roverland_base_import_apply_rank_math( $item_id, $item_data['seo'] );
				}
			} elseif ( $item_id ) {
				$report['skipped']++;
			}
		}

		roverland_base_import_finish( $report );
	}

	$type_map = array(
		'promotions' => array( 'key' => 'promotions', 'post_type' => 'promotion', 'label' => 'Акции', 'single' => 'акцию' ),
		'portfolio'  => array( 'key' => 'portfolio_items', 'post_type' => 'portfolio_item', 'label' => 'Портфолио', 'single' => 'работу портфолио' ),
	);

	if ( isset( $type_map[ $scope ] ) ) {
		$config = $type_map[ $scope ];
		$report['label'] = $config['label'];

		foreach ( isset( $data[ $config['key'] ] ) && is_array( $data[ $config['key'] ] ) ? $data[ $config['key'] ] : array() as $item_data ) {
			$existing = roverland_base_import_find_post_by_key( $config['post_type'], $item_data['key'] );
			$item_id  = roverland_base_import_upsert_content_item( $item_data, $config['post_type'], $config['single'], $report );

			if ( $item_id && ( ! $existing || ! $preserve_existing ) ) {
				roverland_base_import_apply_fields( $item_id, $item_data, $report );
				if ( ! empty( $item_data['seo'] ) ) {
					roverland_base_import_apply_rank_math( $item_id, $item_data['seo'] );
				}
			} elseif ( $item_id ) {
				$report['skipped']++;
			}
		}

		roverland_base_import_finish( $report );
	}

	roverland_base_import_finish( $report );
}

function roverland_base_import_find_post_by_key( $post_type, $key ) {
	$ids = get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_roverland_base_import_key',
			'meta_value'     => sanitize_key( $key ),
			'no_found_rows'  => true,
		)
	);

	return $ids ? (int) $ids[0] : 0;
}

function roverland_base_import_find_page_id( $page_data ) {
	if ( ! empty( $page_data['key'] ) ) {
		$id = roverland_base_import_find_post_by_key( 'page', $page_data['key'] );
		if ( $id ) {
			return $id;
		}
	}

	if ( ! empty( $page_data['slug'] ) ) {
		$page = get_page_by_path( $page_data['slug'] );
		if ( $page ) {
			return (int) $page->ID;
		}
	}

	return 0;
}

function roverland_base_import_reference_group( $items, $post_type, $label, &$report, $preserve_existing = true ) {
	if ( ! is_array( $items ) ) {
		return;
	}

	$ids = array();

	foreach ( $items as $item_data ) {
		$parent_id = 0;

		if ( ! empty( $item_data['parent_key'] ) ) {
			$parent_id = isset( $ids[ $item_data['parent_key'] ] )
				? (int) $ids[ $item_data['parent_key'] ]
				: roverland_base_import_find_post_by_key( $post_type, $item_data['parent_key'] );
		}

		$existing = roverland_base_import_find_post_by_key( $post_type, $item_data['key'] );
		$item_id  = roverland_base_import_upsert_content_item( $item_data, $post_type, $label, $report, $parent_id );

		if ( ! $item_id ) {
			continue;
		}

		$ids[ $item_data['key'] ] = $item_id;

		if ( ! $existing || ! $preserve_existing ) {
			roverland_base_import_apply_fields( $item_id, $item_data, $report );
		} else {
			$report['skipped']++;
		}
	}
}

function roverland_base_import_upsert_page( $page_data, &$report, $parent_id = 0 ) {
	if ( empty( $page_data['key'] ) || empty( $page_data['title'] ) || empty( $page_data['slug'] ) ) {
		$report['errors'][] = 'В JSON найдена страница без key/title/slug.';
		return 0;
	}

	$existing = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_roverland_base_import_key',
			'meta_value'     => sanitize_key( $page_data['key'] ),
			'no_found_rows'  => true,
		)
	);

	$page_id = $existing ? (int) $existing[0] : 0;

	if ( ! $page_id && 'home' === $page_data['key'] ) {
		$page_id = (int) get_option( 'page_on_front' );
	}

	if ( ! $page_id ) {
		$page = get_page_by_path( $page_data['slug'] );

		if ( $page ) {
			$page_id = (int) $page->ID;
		}
	}

	$postarr = array(
		'post_type'    => 'page',
		'post_status'  => ! empty( $page_data['status'] ) && in_array( $page_data['status'], array( 'publish', 'draft', 'private' ), true ) ? $page_data['status'] : 'publish',
		'post_title'   => $page_data['title'],
		'post_name'    => $page_data['slug'],
		'post_parent'  => (int) $parent_id,
		'menu_order'   => isset( $page_data['menu_order'] ) ? (int) $page_data['menu_order'] : 0,
		'post_content' => '',
	);

	$is_new = ! $page_id;

	if ( $page_id ) {
		$postarr['ID'] = $page_id;
		$result        = wp_update_post( wp_slash( $postarr ), true );
	} else {
		$result = wp_insert_post( wp_slash( $postarr ), true );
	}

	if ( is_wp_error( $result ) ) {
		$report['errors'][] = 'Не удалось сохранить страницу «' . $page_data['title'] . '»: ' . $result->get_error_message();
		return 0;
	}

	$page_id = (int) $result;

	update_post_meta( $page_id, '_roverland_base_import_key', sanitize_key( $page_data['key'] ) );

	if ( ! empty( $page_data['template'] ) ) {
		update_post_meta( $page_id, '_wp_page_template', sanitize_file_name( $page_data['template'] ) );
	}

	if ( $is_new ) {
		$report['created']++;
	} else {
		$report['updated']++;
	}

	return $page_id;
}

function roverland_base_import_upsert_vacancy( $vacancy_data, &$report ) {
	if ( empty( $vacancy_data['key'] ) || empty( $vacancy_data['title'] ) || empty( $vacancy_data['slug'] ) ) {
		$report['errors'][] = 'В JSON найдена вакансия без key/title/slug.';
		return 0;
	}

	$existing = get_posts(
		array(
			'post_type' => 'vacancy',
			'post_status' => 'any',
			'posts_per_page' => 1,
			'fields' => 'ids',
			'meta_key' => '_roverland_base_import_key',
			'meta_value' => sanitize_key( $vacancy_data['key'] ),
			'no_found_rows' => true,
		)
	);

	$post_id = $existing ? (int) $existing[0] : 0;

	if ( ! $post_id ) {
		$post = get_page_by_path( $vacancy_data['slug'], OBJECT, 'vacancy' );
		if ( $post ) {
			$post_id = (int) $post->ID;
		}
	}

	$postarr = array(
		'post_type' => 'vacancy',
		'post_status' => 'publish',
		'post_title' => $vacancy_data['title'],
		'post_name' => $vacancy_data['slug'],
		'menu_order' => isset( $vacancy_data['menu_order'] ) ? (int) $vacancy_data['menu_order'] : 0,
	);

	$is_new = ! $post_id;

	if ( $post_id ) {
		$postarr['ID'] = $post_id;
		$result = wp_update_post( wp_slash( $postarr ), true );
	} else {
		$result = wp_insert_post( wp_slash( $postarr ), true );
	}

	if ( is_wp_error( $result ) ) {
		$report['errors'][] = 'Не удалось сохранить вакансию «' . $vacancy_data['title'] . '»: ' . $result->get_error_message();
		return 0;
	}

	$post_id = (int) $result;
	update_post_meta( $post_id, '_roverland_base_import_key', sanitize_key( $vacancy_data['key'] ) );

	if ( $is_new ) {
		$report['created']++;
	} else {
		$report['updated']++;
	}

	return $post_id;
}

function roverland_base_import_apply_fields( $post_id, $item_data, &$report ) {
	if ( empty( $item_data['fields'] ) || ! is_array( $item_data['fields'] ) ) {
		return;
	}

	foreach ( $item_data['fields'] as $field_name => $value ) {
		$current_value = get_field( $field_name, $post_id );

		update_field(
			$field_name,
			roverland_base_import_resolve_value( $value, $report, $current_value ),
			$post_id
		);
	}
}

function roverland_base_import_upsert_content_item( $item_data, $post_type, $label, &$report, $parent_id = 0 ) {
	if ( empty( $item_data['key'] ) || empty( $item_data['title'] ) || ! isset( $item_data['slug'] ) ) {
		$report['errors'][] = 'В JSON найдена запись без key/title/slug: ' . $label . '.';
		return 0;
	}

	$existing = get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_roverland_base_import_key',
			'meta_value'     => sanitize_key( $item_data['key'] ),
			'no_found_rows'  => true,
		)
	);

	$post_id = $existing ? (int) $existing[0] : 0;

	if ( ! $post_id ) {
		$post = get_page_by_path( (string) $item_data['slug'], OBJECT, $post_type );

		if ( $post ) {
			$post_id = (int) $post->ID;
		}
	}

	$postarr = array(
		'post_type'   => $post_type,
		'post_status' => 'publish',
		'post_title'  => $item_data['title'],
		'post_name'   => (string) $item_data['slug'],
		'post_parent' => (int) $parent_id,
		'menu_order'  => isset( $item_data['menu_order'] ) ? (int) $item_data['menu_order'] : 0,
	);

	$is_new = ! $post_id;

	if ( $post_id ) {
		$postarr['ID'] = $post_id;
		$result        = wp_update_post( wp_slash( $postarr ), true );
	} else {
		$result = wp_insert_post( wp_slash( $postarr ), true );
	}

	if ( is_wp_error( $result ) ) {
		$report['errors'][] = 'Не удалось сохранить ' . $label . ' «' . $item_data['title'] . '»: ' . $result->get_error_message();
		return 0;
	}

	$post_id = (int) $result;
	update_post_meta( $post_id, '_roverland_base_import_key', sanitize_key( $item_data['key'] ) );

	if ( $is_new ) {
		$report['created']++;
	} else {
		$report['updated']++;
	}

	return $post_id;
}

function roverland_base_import_apply_rank_math( $page_id, $seo ) {
	$page_id = (int) $page_id;

	if ( ! $page_id || ! is_array( $seo ) ) {
		return;
	}

	if ( ! empty( $seo['title'] ) ) {
		update_post_meta( $page_id, 'rank_math_title', sanitize_text_field( $seo['title'] ) );
	}

	if ( ! empty( $seo['description'] ) ) {
		update_post_meta( $page_id, 'rank_math_description', sanitize_textarea_field( $seo['description'] ) );
	}

	if ( ! empty( $seo['focus_keyword'] ) ) {
		update_post_meta( $page_id, 'rank_math_focus_keyword', sanitize_text_field( $seo['focus_keyword'] ) );
	}

	update_post_meta( $page_id, 'rank_math_robots', array( 'index', 'follow' ) );
	update_post_meta( $page_id, 'rank_math_canonical_url', get_permalink( $page_id ) );
}

function roverland_base_import_find_menu_item( $items, $object, $object_id ) {
	foreach ( $items as $item ) {
		if ( $item->object === $object && (int) $item->object_id === (int) $object_id ) {
			return $item;
		}
	}
	return null;
}

function roverland_base_import_ensure_menu_item( $menu_id, $object, $object_id, $parent_id = 0 ) {
	$items = wp_get_nav_menu_items( $menu_id );
	$items = is_array( $items ) ? $items : array();
	$item  = roverland_base_import_find_menu_item( $items, $object, $object_id );

	return wp_update_nav_menu_item(
		$menu_id,
		$item ? (int) $item->ID : 0,
		array(
			'menu-item-object-id' => (int) $object_id,
			'menu-item-object' => $object,
			'menu-item-type' => 'post_type',
			'menu-item-status' => 'publish',
			'menu-item-parent-id' => (int) $parent_id,
		)
	);
}

function roverland_base_import_fix_primary_menu_hierarchy( $page_ids, $vacancy_ids = array() ) {
	if ( empty( $page_ids['about'] ) || empty( $page_ids['vacancies'] ) ) {
		return;
	}

	$locations = get_theme_mod( 'nav_menu_locations', array() );
	if ( empty( $locations['primary-menu'] ) ) {
		return;
	}

	$menu_id = (int) $locations['primary-menu'];
	$items   = wp_get_nav_menu_items( $menu_id );
	$items   = is_array( $items ) ? $items : array();
	$about_item = roverland_base_import_find_menu_item( $items, 'page', $page_ids['about'] );

	if ( ! $about_item ) {
		return;
	}

	if ( ! empty( $page_ids['history'] ) ) {
		roverland_base_import_ensure_menu_item( $menu_id, 'page', $page_ids['history'], $about_item->ID );
	}

	$vacancies_item_id = roverland_base_import_ensure_menu_item( $menu_id, 'page', $page_ids['vacancies'], $about_item->ID );
	if ( is_wp_error( $vacancies_item_id ) || ! $vacancies_item_id ) {
		return;
	}

	foreach ( $vacancy_ids as $vacancy_id ) {
		roverland_base_import_ensure_menu_item( $menu_id, 'vacancy', $vacancy_id, $vacancies_item_id );
	}
}

function roverland_base_import_preserve_media_value( $current ) {
	if ( is_array( $current ) && ! empty( $current['ID'] ) ) {
		return (int) $current['ID'];
	}
	if ( is_numeric( $current ) ) {
		return (int) $current;
	}
	return 0;
}

function roverland_base_import_entity_id( $reference ) {
	$reference = trim( (string) $reference );

	if ( false === strpos( $reference, ':' ) ) {
		return 0;
	}

	list( $type, $slug ) = array_map( 'trim', explode( ':', $reference, 2 ) );

	$post_type  = '';
	$key_prefix = '';
	$slug_field = '';

	if ( 'model' === $type ) {
		$post_type  = 'rover_model';
		$key_prefix = 'model-';
		$slug_field = 'model_slug';
	} elseif ( 'service' === $type ) {
		$post_type  = 'rover_service';
		$key_prefix = 'service-';
		$slug_field = 'service_slug';
	}

	if ( ! $post_type || ! $slug ) {
		return 0;
	}

	$ids = get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_roverland_base_import_key',
			'meta_value'     => $key_prefix . sanitize_key( $slug ),
			'no_found_rows'  => true,
		)
	);

	if ( $ids ) {
		return (int) $ids[0];
	}

	$ids = get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => $slug_field,
			'meta_value'     => sanitize_title( $slug ),
			'no_found_rows'  => true,
		)
	);

	return $ids ? (int) $ids[0] : 0;
}

function roverland_base_import_resolve_value( $value, &$report, $current = null ) {
	if ( ! is_array( $value ) ) {
		return $value;
	}

	if ( ! empty( $value['entity_ref'] ) ) {
		return roverland_base_import_entity_id( $value['entity_ref'] );
	}

	if ( ! empty( $value['manual_image'] ) ) {
		return roverland_base_import_preserve_media_value( $current );
	}

	if ( ! empty( $value['theme_image'] ) ) {
		if ( 'svg' !== strtolower( pathinfo( $value['theme_image'], PATHINFO_EXTENSION ) ) ) {
			return roverland_base_import_preserve_media_value( $current );
		}

		return roverland_base_import_media(
			$value['theme_image'],
			isset( $value['alt'] ) ? $value['alt'] : '',
			$report
		);
	}

	$result = array();
	foreach ( $value as $key => $item ) {
		$current_item = is_array( $current ) && array_key_exists( $key, $current ) ? $current[ $key ] : null;
		$result[ $key ] = roverland_base_import_resolve_value( $item, $report, $current_item );
	}
	return $result;
}

function roverland_base_import_media( $relative_path, $alt, &$report ) {
	$relative_path = ltrim( str_replace( '\\', '/', (string) $relative_path ), '/' );

	if ( 'svg' !== strtolower( pathinfo( $relative_path, PATHINFO_EXTENSION ) ) ) {
		return 0;
	}

	$source = get_template_directory() . '/' . $relative_path;

	if ( ! is_readable( $source ) ) {
		$report['errors'][] = 'Не найден медиафайл темы: ' . $relative_path;
		return 0;
	}

	$existing = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_roverland_import_source',
			'meta_value'     => $relative_path,
			'no_found_rows'  => true,
		)
	);

	if ( $existing ) {
		$attachment_id = (int) $existing[0];

		if ( '' !== (string) $alt ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', (string) $alt );
		}

		return $attachment_id;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	$filename = wp_basename( $source );
	$bits     = wp_upload_bits( $filename, null, file_get_contents( $source ) );

	if ( ! empty( $bits['error'] ) ) {
		$report['errors'][] = 'Не удалось загрузить ' . $relative_path . ': ' . $bits['error'];
		return 0;
	}

	$filetype = wp_check_filetype( $filename, null );

	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => $filetype['type'] ? $filetype['type'] : 'application/octet-stream',
			'post_title'     => sanitize_text_field( pathinfo( $filename, PATHINFO_FILENAME ) ),
			'post_status'    => 'inherit',
		),
		$bits['file']
	);

	if ( is_wp_error( $attachment_id ) ) {
		$report['errors'][] = 'Не удалось создать attachment для ' . $relative_path . '.';
		return 0;
	}

	if ( $filetype['type'] && 'image/svg+xml' !== $filetype['type'] ) {
		$metadata = wp_generate_attachment_metadata( $attachment_id, $bits['file'] );

		if ( is_array( $metadata ) ) {
			wp_update_attachment_metadata( $attachment_id, $metadata );
		}
	}

	update_post_meta( $attachment_id, '_roverland_import_source', $relative_path );

	if ( '' !== (string) $alt ) {
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', (string) $alt );
	}

	$report['media']++;

	return (int) $attachment_id;
}

function roverland_base_import_finish( $report ) {
	set_transient(
		'roverland_base_import_report_' . get_current_user_id(),
		$report,
		5 * MINUTE_IN_SECONDS
	);

	wp_safe_redirect( admin_url( 'admin.php?page=roverland-base-import' ) );
	exit;
}
