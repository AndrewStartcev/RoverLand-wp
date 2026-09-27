<?php

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', 'roverland_legacy_import_register_page', 110 );
add_action( 'admin_post_roverland_legacy_prepare', 'roverland_legacy_import_prepare' );
add_action( 'admin_post_roverland_legacy_import', 'roverland_legacy_import_handle' );
add_action( 'wp_ajax_roverland_legacy_import_batch', 'roverland_legacy_import_ajax_batch' );

function roverland_legacy_import_register_page() {
	add_submenu_page(
		'roverland-settings',
		'Импорт старого RoverLand',
		'Импорт старого сайта',
		'manage_options',
		'roverland-legacy-import',
		'roverland_legacy_import_render_page'
	);
}

function roverland_legacy_import_report_key() {
	return 'roverland_legacy_import_report_' . get_current_user_id();
}

function roverland_legacy_import_set_report( $report ) {
	set_transient( roverland_legacy_import_report_key(), $report, MINUTE_IN_SECONDS * 5 );
}

function roverland_legacy_import_get_report() {
	$key    = roverland_legacy_import_report_key();
	$report = get_transient( $key );

	if ( $report ) {
		delete_transient( $key );
	}

	return $report;
}

function roverland_legacy_import_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$report = roverland_legacy_import_get_report();
	$cache  = roverland_legacy_load_cache();
	$pages  = roverland_legacy_import_wp_pages();
	$found  = 0;
	$empty  = 0;

	foreach ( $pages as $page ) {
		if ( roverland_legacy_source_for_page( $page->ID ) ) {
			$found++;
		}

		$sections = roverland_field( 'service_sections', array(), $page->ID );
		if ( ! is_array( $sections ) || ! $sections ) {
			$empty++;
		}
	}
	?>
	<div class="wrap roverland-legacy-import">
		<h1>Импорт контента со старого RoverLand</h1>
		<p class="description">
			Источник — SQL-дамп старого Bitrix. Мигратор использует только публичный поисковый индекс страниц
			(<code>b_search_content</code>) и не импортирует заявки клиентов или другие служебные данные.
		</p>

		<?php if ( is_array( $report ) ) : ?>
			<div class="notice notice-<?php echo empty( $report['errors'] ) ? 'success' : 'warning'; ?> is-dismissible">
				<p><strong><?php echo esc_html( $report['message'] ?? 'Операция завершена.' ); ?></strong></p>
				<?php if ( isset( $report['updated'] ) ) : ?>
					<p>
						Обновлено страниц: <?php echo (int) $report['updated']; ?>,
						пропущено: <?php echo (int) ( $report['skipped'] ?? 0 ); ?>,
						изображений: <?php echo (int) ( $report['media'] ?? 0 ); ?>.
					</p>
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

		<div class="roverland-legacy-import__card">
			<h2>1. Подготовить дамп</h2>

			<?php if ( $cache ) : ?>
				<p>
					<strong>Дамп подготовлен.</strong>
					Публичных страниц в кеше: <?php echo count( $cache['pages'] ); ?>.
					Совпадений с текущими сервисными страницами: <?php echo (int) $found; ?>.
					Пустых сервисных страниц: <?php echo (int) $empty; ?>.
				</p>
			<?php else : ?>
				<p>Загрузите тот же <code>.zip</code> или распакованный <code>.sql</code>. Файл сохраняется в uploads и используется только для подготовки кеша публичного контента.</p>
			<?php endif; ?>

			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data" class="roverland-legacy-import__upload">
				<input type="hidden" name="action" value="roverland_legacy_prepare">
				<?php wp_nonce_field( 'roverland_legacy_prepare', 'roverland_legacy_nonce' ); ?>
				<input type="file" name="legacy_dump" accept=".zip,.sql" required>
				<?php submit_button( $cache ? 'Загрузить дамп заново' : 'Подготовить дамп', 'secondary', 'submit', false ); ?>
			</form>
		</div>

		<?php if ( $cache ) : ?>
			<div class="roverland-legacy-import__card">
				<h2>2. Безопасный массовый импорт</h2>
				<p>
					Обрабатывает максимум 5 страниц за запрос. По умолчанию заполняются только страницы,
					у которых <strong>service_sections ещё пустой</strong>. Существующий контент не перезаписывается.
				</p>

				<div class="roverland-legacy-import__batch">
					<label><input type="checkbox" id="roverland-legacy-download-images" value="1"> Скачать изображения со старых страниц</label>

					<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
						<input type="hidden" name="action" value="roverland_legacy_import">
						<input type="hidden" name="mode" value="batch">
						<?php wp_nonce_field( 'roverland_legacy_import', 'roverland_legacy_nonce' ); ?>
						<input type="hidden" name="download_images" value="0" class="roverland-legacy-images-hidden">
						<?php submit_button( 'Импортировать следующие 5 пустых страниц', 'secondary', 'submit', false ); ?>
					</form>

					<button
						type="button"
						class="button button-primary"
						id="roverland-legacy-import-all"
						data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
						data-nonce="<?php echo esc_attr( wp_create_nonce( 'roverland_legacy_ajax' ) ); ?>"
						data-force="0"
					>
						Импортировать все пустые страницы
					</button>

					<button
						type="button"
						class="button button-secondary"
						id="roverland-legacy-import-all-force"
						data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
						data-nonce="<?php echo esc_attr( wp_create_nonce( 'roverland_legacy_ajax' ) ); ?>"
						data-force="1"
					>
						Импортировать ВСЕ страницы с перезаписью
					</button>
				</div>

				<div id="roverland-legacy-import-progress" class="roverland-legacy-import__progress" hidden>
					<div class="roverland-legacy-import__progress-bar"><span></span></div>
					<p class="roverland-legacy-import__progress-text">Подготовка…</p>
				</div>
			</div>

			<div class="roverland-legacy-import__card">
				<h2>3. Страницы</h2>
				<p>Для принудительного импорта конкретной страницы используйте кнопку в таблице. Она заменит <code>service_sections</code> этой страницы данными из старого сайта.</p>

				<table class="widefat striped roverland-legacy-import__table">
					<thead>
						<tr>
							<th>Новая страница</th>
							<th>Источник</th>
							<th>Данные</th>
							<th>ACF сейчас</th>
							<th>Действие</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $pages as $page ) : ?>
							<?php
							$source   = roverland_legacy_source_for_page( $page->ID );
							$sections = roverland_field( 'service_sections', array(), $page->ID );
							$count    = is_array( $sections ) ? count( $sections ) : 0;
							$legacy   = trim( (string) roverland_field( 'legacy_source_url', '', $page->ID ) );
							?>
							<tr>
								<td>
									<strong><a href="<?php echo esc_url( get_edit_post_link( $page->ID ) ); ?>"><?php echo esc_html( get_the_title( $page ) ); ?></a></strong><br>
									<code><?php echo esc_html( wp_parse_url( get_permalink( $page ), PHP_URL_PATH ) ); ?></code>
								</td>
								<td>
									<?php if ( $legacy ) : ?>
										<a href="<?php echo esc_url( $legacy ); ?>" target="_blank" rel="noopener"><?php echo esc_html( roverland_legacy_normalize_path( $legacy ) ); ?></a>
									<?php else : ?>
										<span class="description">не задан</span>
									<?php endif; ?>
								</td>
								<td>
									<?php if ( $source ) : ?>
										<strong><?php echo esc_html( $source['title'] ); ?></strong><br>
										<span class="description"><?php echo number_format_i18n( mb_strlen( $source['body'] ?? '' ) ); ?> символов</span>
									<?php else : ?>
										<span class="roverland-legacy-import__missing">не найдено</span>
									<?php endif; ?>
								</td>
								<td>
									<?php echo $count ? '<strong>' . (int) $count . ' секц.</strong>' : '<span class="roverland-legacy-import__empty">пусто</span>'; ?>
									<?php if ( get_post_meta( $page->ID, '_roverland_legacy_imported_at', true ) ) : ?>
										<br><span class="description">импортировано</span>
									<?php endif; ?>
								</td>
								<td>
									<?php if ( $source ) : ?>
										<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
											<input type="hidden" name="action" value="roverland_legacy_import">
											<input type="hidden" name="mode" value="single">
											<input type="hidden" name="page_id" value="<?php echo (int) $page->ID; ?>">
											<input type="hidden" name="force" value="<?php echo $count ? '1' : '0'; ?>">
											<?php wp_nonce_field( 'roverland_legacy_import', 'roverland_legacy_nonce' ); ?>
											<label class="roverland-legacy-import__image-check"><input type="checkbox" name="download_images" value="1"> картинки</label>
											<button class="button<?php echo $count ? '' : ' button-primary'; ?>" type="submit"<?php echo $count ? ' onclick="return confirm(\'Заменить существующие секции этой страницы контентом со старого сайта?\');"' : ''; ?>>
												<?php echo $count ? 'Перезаписать текст' : 'Импортировать'; ?>
											</button>
										</form>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</div>

	<style>
	.roverland-legacy-import{max-width:1400px}
	.roverland-legacy-import__card{margin-top:22px;padding:22px 26px;border:1px solid #dcdcde;border-radius:10px;background:#fff}
	.roverland-legacy-import__upload,.roverland-legacy-import__batch{display:flex;align-items:center;flex-wrap:wrap;gap:14px;margin-top:16px}
	.roverland-legacy-import__table td{vertical-align:middle}
	.roverland-legacy-import__missing{color:#b32d2e;font-weight:600}
	.roverland-legacy-import__empty{color:#996800;font-weight:600}
	.roverland-legacy-import__image-check{display:block;margin-bottom:6px;font-size:12px}
	.roverland-legacy-import__progress{margin-top:18px;max-width:720px}
	.roverland-legacy-import__progress-bar{height:10px;overflow:hidden;border-radius:999px;background:#e2e4e7}
	.roverland-legacy-import__progress-bar span{display:block;width:0;height:100%;background:#3858e9;transition:width .2s ease}
	.roverland-legacy-import__progress-text{margin:8px 0 0}
	</style>

	<script>
	(function () {
		var imagesToggle = document.getElementById('roverland-legacy-download-images');
		var singleBatchForm = document.querySelector('.roverland-legacy-import__batch form');
		var importButtons = [
			document.getElementById('roverland-legacy-import-all'),
			document.getElementById('roverland-legacy-import-all-force')
		].filter(Boolean);
		var progress = document.getElementById('roverland-legacy-import-progress');

		if (singleBatchForm && imagesToggle) {
			singleBatchForm.addEventListener('submit', function () {
				var hidden = singleBatchForm.querySelector('.roverland-legacy-images-hidden');
				if (hidden) hidden.value = imagesToggle.checked ? '1' : '0';
			});
		}

		if (!importButtons.length || !progress) return;

		var bar = progress.querySelector('.roverland-legacy-import__progress-bar span');
		var text = progress.querySelector('.roverland-legacy-import__progress-text');

		importButtons.forEach(function (button) {
			button.addEventListener('click', async function () {
				if (button.disabled) return;

				var force = button.dataset.force === '1';

				if (force && !window.confirm('Перезаписать контент ВСЕХ найденных сервисных страниц данными из старого RoverLand?')) {
					return;
				}

				importButtons.forEach(function (item) { item.disabled = true; });
				progress.hidden = false;
				bar.style.width = '0%';
				text.textContent = force ? 'Подготовка полного импорта…' : 'Подготовка…';

				var skippedIds = [];
				var totalUpdated = 0;
				var totalMedia = 0;
				var totalErrors = [];
				var initialRemaining = null;

				async function runBatch() {
					var data = new FormData();
					data.append('action', 'roverland_legacy_import_batch');
					data.append('nonce', button.dataset.nonce || '');
					data.append('download_images', imagesToggle && imagesToggle.checked ? '1' : '0');
					data.append('force', force ? '1' : '0');
					data.append('skip_ids', skippedIds.join(','));

					var response = await fetch(button.dataset.ajaxUrl, {
						method: 'POST',
						credentials: 'same-origin',
						body: data
					});

					var payload = await response.json();

					if (!payload || !payload.success) {
						throw new Error(payload && payload.data && payload.data.message ? payload.data.message : 'Ошибка AJAX-импорта.');
					}

					var result = payload.data || {};
					var processedIds = Array.isArray(result.processed_ids) ? result.processed_ids : [];
					skippedIds = skippedIds.concat(processedIds);

					totalUpdated += Number(result.updated || 0);
					totalMedia += Number(result.media || 0);

					if (Array.isArray(result.errors) && result.errors.length) {
						totalErrors = totalErrors.concat(result.errors);
					}

					if (initialRemaining === null) {
						initialRemaining = Number(result.total || result.remaining || 0);
					}

					var remaining = Number(result.remaining || 0);
					var total = Math.max(initialRemaining || 0, totalUpdated + remaining);
					var done = Math.max(0, total - remaining);
					var percent = total > 0 ? Math.min(100, Math.round(done / total * 100)) : 100;

					bar.style.width = percent + '%';
					text.textContent = 'Импортировано страниц: ' + totalUpdated + '. Осталось: ' + remaining + '. Изображений: ' + totalMedia + '.';

					if (remaining > 0 && Number(result.processed || 0) > 0) {
						await runBatch();
						return;
					}

					bar.style.width = '100%';

					if (totalErrors.length) {
						text.textContent += ' Ошибок: ' + totalErrors.length + '. Обновите страницу — детали будут видны в таблице.';
					} else {
						text.textContent += ' Готово.';
					}

					setTimeout(function () {
						window.location.reload();
					}, 1200);
				}

				try {
					await runBatch();
				} catch (error) {
					text.textContent = 'Импорт остановлен: ' + error.message;
					importButtons.forEach(function (item) { item.disabled = false; });
				}
			});
		});
	})();
	</script>
	<?php
}

function roverland_legacy_import_wp_pages() {
	return get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'posts_per_page' => -1,
			'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
			'meta_query'     => array(
				array(
					'key'     => 'service_page_kind',
					'value'   => '',
					'compare' => '!=',
				),
			),
			'no_found_rows'  => true,
		)
	);
}

function roverland_legacy_import_prepare() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Недостаточно прав.' );
	}

	check_admin_referer( 'roverland_legacy_prepare', 'roverland_legacy_nonce' );

	$report = array(
		'message' => '',
		'errors'  => array(),
	);

	if ( empty( $_FILES['legacy_dump']['tmp_name'] ) || UPLOAD_ERR_OK !== (int) $_FILES['legacy_dump']['error'] ) {
		$report['errors'][] = 'Файл не был загружен.';
		roverland_legacy_import_finish( $report );
	}

	$name = sanitize_file_name( wp_unslash( $_FILES['legacy_dump']['name'] ) );
	$ext  = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );

	if ( ! in_array( $ext, array( 'zip', 'sql' ), true ) ) {
		$report['errors'][] = 'Поддерживаются только .zip и .sql.';
		roverland_legacy_import_finish( $report );
	}

	if ( (int) $_FILES['legacy_dump']['size'] > 100 * MB_IN_BYTES ) {
		$report['errors'][] = 'Файл больше 100 МБ.';
		roverland_legacy_import_finish( $report );
	}

	$dir = roverland_legacy_cache_dir();

	foreach ( array( 'source.zip', 'source.sql', 'legacy-pages.json' ) as $old ) {
		$path = trailingslashit( $dir ) . $old;
		if ( is_file( $path ) ) {
			@unlink( $path );
		}
	}

	$target = trailingslashit( $dir ) . ( 'zip' === $ext ? 'source.zip' : 'source.sql' );

	if ( ! move_uploaded_file( $_FILES['legacy_dump']['tmp_name'], $target ) ) {
		$report['errors'][] = 'Не удалось сохранить дамп в uploads.';
		roverland_legacy_import_finish( $report );
	}

	$sql = '';

	if ( 'zip' === $ext ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			$report['errors'][] = 'На сервере отсутствует PHP ZipArchive. Загрузите распакованный .sql.';
			roverland_legacy_import_finish( $report );
		}

		$zip = new ZipArchive();

		if ( true !== $zip->open( $target ) ) {
			$report['errors'][] = 'Не удалось открыть ZIP.';
			roverland_legacy_import_finish( $report );
		}

		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$entry = $zip->getNameIndex( $i );

			if ( $entry && 'sql' === strtolower( pathinfo( $entry, PATHINFO_EXTENSION ) ) ) {
				$sql = $zip->getFromIndex( $i );
				break;
			}
		}

		$zip->close();
	} else {
		$sql = file_get_contents( $target );
	}

	if ( ! $sql ) {
		$report['errors'][] = 'В архиве не найден SQL-дамп.';
		roverland_legacy_import_finish( $report );
	}

	@set_time_limit( 90 );
	wp_raise_memory_limit( 'admin' );

	$pages = roverland_legacy_sql_extract( $sql );

	if ( is_wp_error( $pages ) ) {
		$report['errors'][] = $pages->get_error_message();
		roverland_legacy_import_finish( $report );
	}

	$cache = array(
		'generated_at' => current_time( 'mysql' ),
		'pages'        => $pages,
	);

	$written = file_put_contents(
		roverland_legacy_cache_file(),
		wp_json_encode( $cache, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
	);

	if ( false === $written ) {
		$report['errors'][] = 'Не удалось записать кеш legacy-pages.json.';
		roverland_legacy_import_finish( $report );
	}

	$report['message'] = sprintf( 'Дамп подготовлен: найдено %d публичных страниц.', count( $pages ) );
	roverland_legacy_import_finish( $report );
}

function roverland_legacy_import_handle() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Недостаточно прав.' );
	}

	check_admin_referer( 'roverland_legacy_import', 'roverland_legacy_nonce' );

	if ( ! function_exists( 'update_field' ) ) {
		roverland_legacy_import_finish(
			array(
				'message' => 'ACF Pro не активен.',
				'errors'  => array( 'Невозможно импортировать service_sections.' ),
			)
		);
	}

	$mode            = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : 'single';
	$page_id         = isset( $_POST['page_id'] ) ? (int) $_POST['page_id'] : 0;
	$force           = ! empty( $_POST['force'] );
	$download_images = ! empty( $_POST['download_images'] );

	$report = array(
		'message' => '',
		'updated' => 0,
		'skipped' => 0,
		'media'   => 0,
		'errors'  => array(),
	);

	@set_time_limit( $download_images ? 90 : 60 );
	wp_raise_memory_limit( 'admin' );

	if ( 'single' === $mode && $page_id ) {
		roverland_legacy_import_one_page( $page_id, $force, $download_images, $report );
		$report['message'] = 'Импорт страницы завершён.';
		roverland_legacy_import_finish( $report );
	}

	$pages = roverland_legacy_import_wp_pages();
	$done  = 0;

	foreach ( $pages as $page ) {
		if ( $done >= 5 ) {
			break;
		}

		$sections = roverland_field( 'service_sections', array(), $page->ID );

		if ( is_array( $sections ) && $sections ) {
			continue;
		}

		if ( ! roverland_legacy_source_for_page( $page->ID ) ) {
			continue;
		}

		roverland_legacy_import_one_page( $page->ID, $force, $download_images, $report );
		$done++;
	}

	$report['message'] = $done
		? sprintf( 'Обработано страниц: %d. Можно запускать следующую пачку.', $done )
		: 'Пустых страниц с найденным legacy-контентом больше нет.';

	roverland_legacy_import_finish( $report );
}

function roverland_legacy_import_ajax_batch() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Недостаточно прав.' ), 403 );
	}

	check_ajax_referer( 'roverland_legacy_ajax', 'nonce' );

	if ( ! function_exists( 'update_field' ) ) {
		wp_send_json_error( array( 'message' => 'ACF Pro не активен.' ), 500 );
	}

	$download_images = ! empty( $_POST['download_images'] );
	$force           = ! empty( $_POST['force'] );
	$skip_ids        = array();

	if ( ! empty( $_POST['skip_ids'] ) ) {
		$skip_ids = array_values(
			array_filter(
				array_map(
					'absint',
					explode( ',', sanitize_text_field( wp_unslash( $_POST['skip_ids'] ) ) )
				)
			)
		);
	}

	@set_time_limit( $download_images ? 90 : 60 );
	wp_raise_memory_limit( 'admin' );

	$report = array(
		'updated' => 0,
		'skipped' => 0,
		'media'   => 0,
		'errors'  => array(),
	);

	$pages      = roverland_legacy_import_wp_pages();
	$candidates = array();

	foreach ( $pages as $page ) {
		if ( in_array( (int) $page->ID, $skip_ids, true ) ) {
			continue;
		}

		$sections = roverland_field( 'service_sections', array(), $page->ID );

		if ( ! $force && is_array( $sections ) && $sections ) {
			continue;
		}

		if ( ! roverland_legacy_source_for_page( $page->ID ) ) {
			continue;
		}

		$candidates[] = $page;
	}

	$total         = count( $candidates );
	$batch         = array_slice( $candidates, 0, 5 );
	$processed_ids = array();

	foreach ( $batch as $page ) {
		$processed_ids[] = (int) $page->ID;
		roverland_legacy_import_one_page( $page->ID, false, $download_images, $report );
	}

	$next_skip_ids = array_merge( $skip_ids, $processed_ids );
	$remaining     = 0;

	foreach ( $pages as $page ) {
		if ( in_array( (int) $page->ID, $next_skip_ids, true ) ) {
			continue;
		}

		$sections = roverland_field( 'service_sections', array(), $page->ID );

		if ( ! $force && is_array( $sections ) && $sections ) {
			continue;
		}

		if ( roverland_legacy_source_for_page( $page->ID ) ) {
			$remaining++;
		}
	}

	wp_send_json_success(
		array(
			'processed'     => count( $processed_ids ),
			'processed_ids' => $processed_ids,
			'updated'       => (int) $report['updated'],
			'skipped'       => (int) $report['skipped'],
			'media'         => (int) $report['media'],
			'errors'        => $report['errors'],
			'remaining'     => $remaining,
			'total'         => $total,
		)
	);
}

function roverland_legacy_import_one_page( $page_id, $force, $download_images, &$report ) {
	$page_id = (int) $page_id;
	$page    = get_post( $page_id );

	if ( ! $page || 'page' !== $page->post_type ) {
		$report['errors'][] = 'Страница не найдена: #' . $page_id;
		return false;
	}

	$source = roverland_legacy_source_for_page( $page_id );

	if ( ! $source || empty( $source['body'] ) ) {
		$report['errors'][] = 'Для «' . get_the_title( $page_id ) . '» не найден текст в старом дампе.';
		return false;
	}

	$existing = roverland_field( 'service_sections', array(), $page_id );

	if ( ! $force && is_array( $existing ) && $existing ) {
		$report['skipped']++;
		return false;
	}

	$body     = roverland_legacy_clean_text( $source['body'] );
	$lines    = preg_split( '/\n+/u', $body );
	$lines    = array_values( array_filter( array_map( 'trim', $lines ), 'strlen' ) );
	$h1       = $lines ? $lines[0] : get_the_title( $page_id );
	$sections = roverland_legacy_content_to_sections( $body, $h1 );

	if ( ! $sections ) {
		$report['errors'][] = 'Не удалось разбить на секции: ' . get_the_title( $page_id );
		return false;
	}

	$legacy_url = trim( (string) roverland_field( 'legacy_source_url', '', $page_id ) );

	if ( ! $legacy_url ) {
		$legacy_url = 'https://roverland.ru' . roverland_legacy_normalize_path( $source['url'] );
		update_field( 'legacy_source_url', $legacy_url, $page_id );
	}

	if ( $download_images ) {
		$sections = roverland_legacy_attach_images_to_sections( $sections, $legacy_url, $page_id, $report );
	}

	update_field( 'service_sections', $sections, $page_id );

	if ( $force || ! roverland_field( 'service_page_h1', '', $page_id ) ) {
		update_field( 'service_page_h1', $h1, $page_id );
	}

	if ( $force || ! roverland_field( 'service_hero_lead', '', $page_id ) ) {
		$lead = roverland_legacy_first_paragraph( $lines, $h1 );
		if ( $lead ) {
			update_field( 'service_hero_lead', $lead, $page_id );
		}
	}

	$title = trim( (string) ( $source['title'] ?? '' ) );

	if ( $title ) {
		update_post_meta( $page_id, 'rank_math_title', $title );
	}

	$description = roverland_legacy_excerpt( $body, 180 );

	if ( $description ) {
		update_post_meta( $page_id, 'rank_math_description', $description );
	}

	update_post_meta( $page_id, '_roverland_legacy_imported_at', current_time( 'mysql' ) );
	update_post_meta( $page_id, '_roverland_legacy_source_id', (int) ( $source['source_id'] ?? 0 ) );

	$report['updated']++;

	return true;
}

function roverland_legacy_first_paragraph( $lines, $h1 ) {
	foreach ( $lines as $line ) {
		if ( roverland_legacy_similar_text( $line, $h1 ) ) {
			continue;
		}

		if ( preg_match( '/^(поделиться страницей|записаться)/iu', $line ) ) {
			continue;
		}

		if ( mb_strlen( $line ) >= 80 ) {
			return roverland_legacy_excerpt( $line, 420 );
		}
	}

	return '';
}

function roverland_legacy_import_finish( $report ) {
	roverland_legacy_import_set_report( $report );

	wp_safe_redirect(
		add_query_arg(
			array(
				'page' => 'roverland-legacy-import',
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}
