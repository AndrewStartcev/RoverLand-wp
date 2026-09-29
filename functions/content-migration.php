<?php

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', 'roverland_content_migration_register_page', 115 );
add_action( 'admin_post_roverland_content_prepare_dump', 'roverland_content_migration_prepare_dump' );
add_action( 'admin_post_roverland_content_import_page', 'roverland_content_migration_import_page' );
add_action( 'admin_post_roverland_content_import_pages_batch', 'roverland_content_migration_import_pages_batch' );
add_action( 'admin_post_roverland_content_import_portfolio_batch', 'roverland_content_migration_import_portfolio_batch' );
add_action( 'wp_ajax_roverland_content_import_portfolio_all', 'roverland_content_migration_import_portfolio_all_ajax' );

function roverland_content_migration_register_page() {
	add_submenu_page(
		'roverland-settings',
		'Перенос контента',
		'Перенос контента',
		'manage_options',
		'roverland-content-migration',
		'roverland_content_migration_render'
	);
}

function roverland_content_migration_allowed_kinds() {
	return array( 'hub', 'service', 'model', 'model_service', 'maintenance' );
}

function roverland_content_migration_pages() {
	return get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'posts_per_page' => -1,
			'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
			'meta_query'     => array(
				array(
					'key'     => 'service_page_kind',
					'value'   => roverland_content_migration_allowed_kinds(),
					'compare' => 'IN',
				),
			),
			'no_found_rows'  => true,
		)
	);
}

function roverland_content_migration_report_key() {
	return 'roverland_content_migration_report_' . get_current_user_id();
}

function roverland_content_migration_set_report( $report ) {
	set_transient( roverland_content_migration_report_key(), $report, 5 * MINUTE_IN_SECONDS );
}

function roverland_content_migration_get_report() {
	$key    = roverland_content_migration_report_key();
	$report = get_transient( $key );

	if ( $report ) {
		delete_transient( $key );
	}

	return $report;
}

function roverland_content_migration_render() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$report    = roverland_content_migration_get_report();
	$pages     = roverland_content_migration_pages();
	$portfolio = roverland_bitrix_load_portfolio_cache();
	?>
	<div class="wrap roverland-content-migration">
		<h1>Перенос контента Rover Land</h1>
		<p class="description">
			Только услуги, модели, услуги моделей/ТО и портфолио.
			Главная, компания, история, контакты, вакансии, политики и остальные обычные страницы этим модулем не изменяются.
		</p>

		<?php if ( is_array( $report ) ) : ?>
			<div class="notice notice-<?php echo empty( $report['errors'] ) ? 'success' : 'warning'; ?> is-dismissible">
				<p><strong><?php echo esc_html( $report['message'] ?? 'Операция завершена.' ); ?></strong></p>
				<?php if ( ! empty( $report['items'] ) && is_array( $report['items'] ) ) : ?>
					<ul>
						<?php foreach ( $report['items'] as $item ) : ?>
							<li>
								<strong><?php echo esc_html( $item['title'] ?? '' ); ?></strong>
								<?php if ( ! empty( $item['url'] ) ) : ?>
									— <a href="<?php echo esc_url( $item['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $item['url'] ); ?></a>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
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

		<div class="roverland-content-migration__grid">
			<section class="roverland-content-migration__card">
				<h2>Услуги и модели</h2>
				<p>
					Источник — текущая старая страница <code>roverland.ru</code>.
					Так сохраняются настоящие H2, таблицы и изображения, которых нет в структурированном виде в Bitrix SQL.
				</p>
				<p>
					Найдено сервисных страниц в WordPress: <strong><?php echo count( $pages ); ?></strong>.
				</p>

				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
					<input type="hidden" name="action" value="roverland_content_import_pages_batch">
					<?php wp_nonce_field( 'roverland_content_import_pages_batch', 'roverland_content_nonce' ); ?>
					<label><input type="checkbox" name="download_images" value="1" checked> Скачивать картинки со старого сайта</label>
					<?php submit_button( 'Импортировать следующие 2 страницы', 'primary', 'submit', false ); ?>
				</form>
			</section>

			<section class="roverland-content-migration__card">
				<h2>Портфолио из базы Bitrix</h2>

				<?php if ( $portfolio ) : ?>
					<p>
						В кеше: <strong><?php echo count( $portfolio['items'] ); ?></strong> работ,
						<strong><?php echo (int) array_sum( array_map( static function ( $item ) { return count( $item['gallery'] ?? array() ); }, $portfolio['items'] ) ); ?></strong> фотографий.
					</p>

					<div class="roverland-content-migration__portfolio-actions">
						<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
							<input type="hidden" name="action" value="roverland_content_import_portfolio_batch">
							<?php wp_nonce_field( 'roverland_content_import_portfolio_batch', 'roverland_content_nonce' ); ?>
							<?php submit_button( 'Импортировать следующие 2 работы', 'secondary', 'submit', false ); ?>
						</form>

						<button
							type="button"
							class="button button-primary"
							id="roverland-import-all-portfolio"
							data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
							data-nonce="<?php echo esc_attr( wp_create_nonce( 'roverland_content_import_portfolio_all' ) ); ?>"
						>
							Импортировать всё портфолио
						</button>
					</div>

					<div id="roverland-portfolio-progress" class="roverland-content-migration__progress" hidden>
						<div class="roverland-content-migration__progress-bar"><span></span></div>
						<p class="roverland-content-migration__progress-text">Подготовка…</p>
					</div>
				<?php else : ?>
					<p>Сначала загрузите SQL-дамп. Из него берутся точные связи кейс → фотографии через <code>b_iblock_section</code>, <code>b_iblock_element</code> и <code>b_file</code>.</p>

					<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data">
						<input type="hidden" name="action" value="roverland_content_prepare_dump">
						<?php wp_nonce_field( 'roverland_content_prepare_dump', 'roverland_content_nonce' ); ?>
						<input type="file" name="bitrix_dump" accept=".zip,.sql" required>
						<?php submit_button( 'Разобрать портфолио из дампа', 'secondary', 'submit', false ); ?>
					</form>
				<?php endif; ?>
			</section>
		</div>

		<section class="roverland-content-migration__card">
			<h2>Страницы услуг и моделей</h2>

			<table class="widefat striped">
				<thead>
					<tr>
						<th>Страница</th>
						<th>Тип</th>
						<th>Старый URL</th>
						<th>ACF</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $pages as $page ) : ?>
						<?php
						$kind     = roverland_service_page_kind( $page->ID );
						$source   = trim( (string) roverland_field( 'legacy_source_url', '', $page->ID ) );
						$sections = roverland_field( 'service_sections', array(), $page->ID );
						$count    = is_array( $sections ) ? count( $sections ) : 0;
						?>
						<tr>
							<td>
								<strong><a href="<?php echo esc_url( get_edit_post_link( $page->ID ) ); ?>"><?php echo esc_html( get_the_title( $page ) ); ?></a></strong><br>
								<code><?php echo esc_html( wp_parse_url( get_permalink( $page ), PHP_URL_PATH ) ); ?></code>
							</td>
							<td><code><?php echo esc_html( $kind ); ?></code></td>
							<td>
								<?php if ( $source ) : ?>
									<a href="<?php echo esc_url( $source ); ?>" target="_blank" rel="noopener"><?php echo esc_html( wp_parse_url( $source, PHP_URL_PATH ) ); ?></a>
								<?php else : ?>
									<span class="description">не задан</span>
								<?php endif; ?>
							</td>
							<td>
								<?php echo $count ? '<strong>' . (int) $count . ' секц.</strong>' : '<span class="description">пусто</span>'; ?>
								<?php if ( get_post_meta( $page->ID, '_roverland_curated_imported_at', true ) ) : ?>
									<br><span class="description">адаптировано</span>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( $source ) : ?>
									<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
										<input type="hidden" name="action" value="roverland_content_import_page">
										<input type="hidden" name="page_id" value="<?php echo (int) $page->ID; ?>">
										<?php wp_nonce_field( 'roverland_content_import_page', 'roverland_content_nonce' ); ?>
										<label class="roverland-content-migration__small"><input type="checkbox" name="download_images" value="1" checked> картинки</label>
										<button class="button" type="submit" onclick="return confirm('Пересобрать эту страницу по старому RoverLand в наши ACF-блоки?');">Адаптировать</button>
									</form>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</section>

		<?php if ( $portfolio ) : ?>
			<section class="roverland-content-migration__card">
				<h2>Портфолио из Bitrix</h2>
				<table class="widefat striped">
					<thead><tr><th>ID</th><th>Работа</th><th>Фото</th><th>URL</th></tr></thead>
					<tbody>
						<?php foreach ( array_slice( $portfolio['items'], 0, 60 ) as $item ) : ?>
							<tr>
								<td><?php echo (int) $item['legacy_id']; ?></td>
								<td><?php echo esc_html( $item['name'] ); ?></td>
								<td><?php echo count( $item['gallery'] ?? array() ); ?></td>
								<td><a href="<?php echo esc_url( $item['source_url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( wp_parse_url( $item['source_url'], PHP_URL_PATH ) ); ?></a></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</section>
		<?php endif; ?>
	</div>

	<style>
	.roverland-content-migration{max-width:1450px}
	.roverland-content-migration__grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;margin-top:22px}
	.roverland-content-migration__card{margin-top:22px;padding:22px 26px;border:1px solid #dcdcde;border-radius:10px;background:#fff}
	.roverland-content-migration__grid .roverland-content-migration__card{margin-top:0}
	.roverland-content-migration td{vertical-align:middle}
	.roverland-content-migration__small{display:block;margin-bottom:6px;font-size:12px}
	.roverland-content-migration__portfolio-actions{display:flex;align-items:center;flex-wrap:wrap;gap:10px;margin-top:16px}
	.roverland-content-migration__portfolio-actions form{margin:0}
	.roverland-content-migration__progress{margin-top:16px}
	.roverland-content-migration__progress-bar{height:10px;overflow:hidden;border-radius:999px;background:#e2e4e7}
	.roverland-content-migration__progress-bar span{display:block;width:0;height:100%;background:#3858e9;transition:width .2s ease}
	.roverland-content-migration__progress-text{margin:8px 0 0}
	@media(max-width:900px){.roverland-content-migration__grid{grid-template-columns:1fr}}
	</style>

	<script>
	(function () {
		var button = document.getElementById('roverland-import-all-portfolio');
		var progress = document.getElementById('roverland-portfolio-progress');

		if (!button || !progress) return;

		var bar = progress.querySelector('.roverland-content-migration__progress-bar span');
		var text = progress.querySelector('.roverland-content-migration__progress-text');

		button.addEventListener('click', async function () {
			if (button.disabled) return;

			button.disabled = true;
			progress.hidden = false;
			bar.style.width = '0%';
			text.textContent = 'Начинаю импорт портфолио…';

			var processedIds = [];
			var imported = 0;
			var media = 0;
			var errors = [];
			var initialTotal = null;

			async function runBatch() {
				var form = new FormData();
				form.append('action', 'roverland_content_import_portfolio_all');
				form.append('nonce', button.dataset.nonce || '');
				form.append('skip_ids', processedIds.join(','));

				var response = await fetch(button.dataset.ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					body: form
				});

				var payload = await response.json();

				if (!payload || !payload.success) {
					throw new Error(payload && payload.data && payload.data.message ? payload.data.message : 'Ошибка AJAX-импорта.');
				}

				var result = payload.data || {};
				var ids = Array.isArray(result.processed_ids) ? result.processed_ids : [];
				processedIds = processedIds.concat(ids);
				imported += Number(result.imported || 0);
				media += Number(result.media || 0);

				if (Array.isArray(result.errors) && result.errors.length) {
					errors = errors.concat(result.errors);
				}

				if (initialTotal === null) {
					initialTotal = Number(result.total || 0);
				}

				var remaining = Number(result.remaining || 0);
				var total = Math.max(initialTotal || 0, imported + remaining);
				var done = Math.max(0, total - remaining);
				var percent = total > 0 ? Math.min(100, Math.round(done / total * 100)) : 100;

				bar.style.width = percent + '%';
				text.textContent = 'Импортировано работ: ' + imported + '. Осталось: ' + remaining + '. Новых файлов: ' + media + '.';

				if (remaining > 0 && ids.length > 0) {
					await runBatch();
					return;
				}

				bar.style.width = '100%';
				text.textContent += errors.length ? ' Ошибок: ' + errors.length + '.' : ' Готово.';

				setTimeout(function () {
					window.location.reload();
				}, 1200);
			}

			try {
				await runBatch();
			} catch (error) {
				text.textContent = 'Импорт остановлен: ' + error.message;
				button.disabled = false;
			}
		});
	})();
	</script>
	<?php
}

function roverland_content_migration_prepare_dump() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Недостаточно прав.' );
	}

	check_admin_referer( 'roverland_content_prepare_dump', 'roverland_content_nonce' );

	@set_time_limit( 90 );
	wp_raise_memory_limit( 'admin' );

	$result = roverland_bitrix_prepare_portfolio_from_upload( $_FILES['bitrix_dump'] ?? array() );

	if ( is_wp_error( $result ) ) {
		roverland_content_migration_finish(
			array(
				'message' => 'Не удалось разобрать дамп.',
				'errors'  => array( $result->get_error_message() ),
			)
		);
	}

	roverland_content_migration_finish(
		array(
			'message' => sprintf(
				'Портфолио разобрано: %d работ, %d фотографий.',
				count( $result['items'] ),
				array_sum( array_map( static function ( $item ) { return count( $item['gallery'] ?? array() ); }, $result['items'] ) )
			),
			'errors' => array(),
		)
	);
}

function roverland_content_migration_import_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Недостаточно прав.' );
	}

	check_admin_referer( 'roverland_content_import_page', 'roverland_content_nonce' );

	$page_id         = isset( $_POST['page_id'] ) ? (int) $_POST['page_id'] : 0;
	$download_images = ! empty( $_POST['download_images'] );
	$report          = array( 'message' => '', 'errors' => array(), 'media' => 0 );

	roverland_content_migration_apply_page( $page_id, $download_images, $report );

	$report['message'] = empty( $report['errors'] ) ? 'Страница адаптирована.' : 'Страница обработана с замечаниями.';
	roverland_content_migration_finish( $report );
}

function roverland_content_migration_import_pages_batch() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Недостаточно прав.' );
	}

	check_admin_referer( 'roverland_content_import_pages_batch', 'roverland_content_nonce' );

	$download_images = ! empty( $_POST['download_images'] );
	$report          = array( 'message' => '', 'errors' => array(), 'media' => 0, 'items' => array() );
	$done            = 0;

	@set_time_limit( $download_images ? 90 : 60 );
	wp_raise_memory_limit( 'admin' );

	foreach ( roverland_content_migration_pages() as $page ) {
		if ( $done >= 2 ) {
			break;
		}

		if ( get_post_meta( $page->ID, '_roverland_curated_imported_at', true ) ) {
			continue;
		}

		if ( ! roverland_field( 'legacy_source_url', '', $page->ID ) ) {
			continue;
		}

		if ( roverland_content_migration_apply_page( $page->ID, $download_images, $report ) ) {
			$report['items'][] = array(
				'title' => get_the_title( $page->ID ),
				'url'   => get_permalink( $page->ID ),
			);
		}
		$done++;
	}

	$report['message'] = $done ? sprintf( 'Адаптировано страниц: %d. Ниже — что именно обновилось.', count( $report['items'] ) ) : 'Необработанных страниц с legacy URL больше нет.';
	roverland_content_migration_finish( $report );
}

function roverland_content_migration_import_portfolio_batch() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Недостаточно прав.' );
	}

	check_admin_referer( 'roverland_content_import_portfolio_batch', 'roverland_content_nonce' );

	$cache  = roverland_bitrix_load_portfolio_cache();
	$report = array( 'message' => '', 'errors' => array(), 'media' => 0 );
	$done   = 0;

	if ( ! $cache ) {
		$report['errors'][] = 'Сначала подготовьте портфолио из SQL-дампа.';
		roverland_content_migration_finish( $report );
	}

	@set_time_limit( 120 );
	wp_raise_memory_limit( 'admin' );

	foreach ( $cache['items'] as $item ) {
		if ( $done >= 2 ) {
			break;
		}

		$existing = roverland_content_migration_find_portfolio( (int) $item['legacy_id'] );

		if ( $existing && get_post_meta( $existing, '_roverland_curated_portfolio_imported', true ) ) {
			continue;
		}

		roverland_content_migration_apply_portfolio( $item, $report );
		$done++;
	}

	$report['message'] = $done ? sprintf( 'Импортировано работ портфолио: %d.', $done ) : 'Портфолио полностью импортировано.';
	roverland_content_migration_finish( $report );
}

function roverland_content_migration_import_portfolio_all_ajax() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Недостаточно прав.' ), 403 );
	}

	check_ajax_referer( 'roverland_content_import_portfolio_all', 'nonce' );

	$cache = roverland_bitrix_load_portfolio_cache();

	if ( ! $cache ) {
		wp_send_json_error( array( 'message' => 'Кеш портфолио не найден. Сначала разберите SQL-дамп.' ), 400 );
	}

	$skip_ids = array();

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

	@set_time_limit( 120 );
	wp_raise_memory_limit( 'admin' );

	$report = array(
		'message' => '',
		'errors'  => array(),
		'media'   => 0,
	);

	$candidates = array();

	foreach ( $cache['items'] as $item ) {
		$legacy_id = (int) ( $item['legacy_id'] ?? 0 );

		if ( ! $legacy_id || in_array( $legacy_id, $skip_ids, true ) ) {
			continue;
		}

		$existing = roverland_content_migration_find_portfolio( $legacy_id );

		if ( $existing && get_post_meta( $existing, '_roverland_curated_portfolio_imported', true ) ) {
			continue;
		}

		$candidates[] = $item;
	}

	$total = count( $candidates );

	// Keep each request small because one portfolio item may contain dozens of large images.
	$batch = array_slice( $candidates, 0, 2 );

	$processed_ids = array();
	$imported      = 0;

	foreach ( $batch as $item ) {
		$legacy_id       = (int) $item['legacy_id'];
		$processed_ids[] = $legacy_id;

		if ( roverland_content_migration_apply_portfolio( $item, $report ) ) {
			$imported++;
		}
	}

	$next_skip = array_merge( $skip_ids, $processed_ids );
	$remaining = 0;

	foreach ( $cache['items'] as $item ) {
		$legacy_id = (int) ( $item['legacy_id'] ?? 0 );

		if ( ! $legacy_id || in_array( $legacy_id, $next_skip, true ) ) {
			continue;
		}

		$existing = roverland_content_migration_find_portfolio( $legacy_id );

		if ( $existing && get_post_meta( $existing, '_roverland_curated_portfolio_imported', true ) ) {
			continue;
		}

		$remaining++;
	}

	wp_send_json_success(
		array(
			'processed_ids' => $processed_ids,
			'imported'      => $imported,
			'media'         => (int) $report['media'],
			'errors'        => $report['errors'],
			'remaining'     => $remaining,
			'total'         => $total,
		)
	);
}

function roverland_content_migration_apply_page( $page_id, $download_images, &$report ) {
	$page_id = (int) $page_id;
	$page    = get_post( $page_id );

	if ( ! $page || 'page' !== $page->post_type ) {
		$report['errors'][] = 'Страница не найдена.';
		return false;
	}

	$kind = roverland_service_page_kind( $page_id );

	if ( ! in_array( $kind, roverland_content_migration_allowed_kinds(), true ) ) {
		$report['errors'][] = 'Страница «' . get_the_title( $page_id ) . '» не относится к услугам или моделям.';
		return false;
	}

	$source_url = trim( (string) roverland_field( 'legacy_source_url', '', $page_id ) );

	if ( ! $source_url ) {
		$report['errors'][] = 'У страницы «' . get_the_title( $page_id ) . '» не указан legacy URL.';
		return false;
	}

	$parsed = roverland_content_migration_fetch_and_parse( $source_url, $kind );

	if ( is_wp_error( $parsed ) ) {
		$report['errors'][] = get_the_title( $page_id ) . ': ' . $parsed->get_error_message();
		return false;
	}

	$sections = $parsed['sections'];

	if ( $download_images ) {
		if ( ! empty( $parsed['hero_image'] ) ) {
			$hero_id = roverland_content_migration_sideload( $parsed['hero_image'], $page_id, $report );

			if ( $hero_id ) {
				update_field( 'service_hero_image', $hero_id, $page_id );
			}
		}

		foreach ( $sections as &$section ) {
			if ( 'text' !== ( $section['acf_fc_layout'] ?? '' ) || empty( $section['_image_url'] ) ) {
				continue;
			}

			$image_id = roverland_content_migration_sideload( $section['_image_url'], $page_id, $report );

			if ( $image_id ) {
				$section['image'] = $image_id;
			}

			unset( $section['_image_url'] );
		}
		unset( $section );
	} else {
		foreach ( $sections as &$section ) {
			unset( $section['_image_url'] );
		}
		unset( $section );
	}

	if ( ! $sections ) {
		$report['errors'][] = get_the_title( $page_id ) . ': не удалось собрать содержательные секции.';
		return false;
	}

	update_field( 'service_page_h1', $parsed['h1'] ?: get_the_title( $page_id ), $page_id );

	if ( $parsed['lead'] ) {
		update_field( 'service_hero_lead', $parsed['lead'], $page_id );
	}

	update_field( 'service_sections', $sections, $page_id );

	if ( $parsed['title'] ) {
		update_post_meta( $page_id, 'rank_math_title', sanitize_text_field( $parsed['title'] ) );
	}

	if ( $parsed['description'] ) {
		update_post_meta( $page_id, 'rank_math_description', sanitize_textarea_field( $parsed['description'] ) );
	}

	update_post_meta( $page_id, '_roverland_curated_imported_at', current_time( 'mysql' ) );
	update_post_meta( $page_id, '_roverland_curated_source_url', esc_url_raw( $source_url ) );

	return true;
}

function roverland_content_migration_fetch_and_parse( $url, $kind ) {
	$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );

	if ( ! in_array( $host, array( 'roverland.ru', 'www.roverland.ru' ), true ) ) {
		return new WP_Error( 'roverland_content_host', 'Разрешён только источник roverland.ru.' );
	}

	$response = wp_remote_get(
		$url,
		array(
			'timeout'     => 20,
			'redirection' => 3,
			'user-agent'  => 'RoverLand WordPress Content Migration/2.0',
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return new WP_Error( 'roverland_content_http', 'Старый сайт вернул HTTP ' . (int) wp_remote_retrieve_response_code( $response ) . '.' );
	}

	$html = (string) wp_remote_retrieve_body( $response );

	if ( ! $html ) {
		return new WP_Error( 'roverland_content_empty', 'Старая страница пустая.' );
	}

	return roverland_content_migration_parse_html( $html, $url, $kind );
}

function roverland_content_migration_parse_html( $html, $source_url, $kind ) {
	if ( ! class_exists( 'DOMDocument' ) ) {
		return new WP_Error( 'roverland_content_dom', 'На сервере отсутствует DOMDocument.' );
	}

	$previous = libxml_use_internal_errors( true );
	$dom      = new DOMDocument();
	$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
	$xpath = new DOMXPath( $dom );

	$h1_nodes = $xpath->query( '//h1' );

	if ( ! $h1_nodes || ! $h1_nodes->length ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );
		return new WP_Error( 'roverland_content_h1', 'На старой странице не найден H1.' );
	}

	$h1_node = $h1_nodes->item( 0 );
	$h1      = roverland_content_migration_node_text( $h1_node );
	$title   = '';

	$title_nodes = $xpath->query( '//title' );
	if ( $title_nodes && $title_nodes->length ) {
		$title = roverland_content_migration_node_text( $title_nodes->item( 0 ) );
	}

	$description = '';
	$meta_nodes  = $xpath->query( '//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="description"]' );
	if ( $meta_nodes && $meta_nodes->length ) {
		$description = trim( (string) $meta_nodes->item( 0 )->getAttribute( 'content' ) );
	}

	$started     = false;
	$lead        = '';
	$hero_image  = '';
	$sections       = array();
	$current        = array( 'title' => '', 'html' => array(), 'image' => '' );
	$seen_fragments = array();
	$nodes          = $xpath->query( '//*' );

	foreach ( $nodes as $node ) {
		if ( $node === $h1_node ) {
			$started = true;
			continue;
		}

		if ( ! $started || roverland_content_migration_is_chrome_node( $node ) ) {
			continue;
		}

		$tag  = strtolower( $node->nodeName );
		$text = roverland_content_migration_node_text( $node );

		if (
			'form' === $tag ||
			( in_array( $tag, array( 'h2', 'h3' ), true ) && preg_match( '/^(записаться|наши работы|станции технического обслуживания|наше расположение)/iu', $text ) )
		) {
			roverland_content_migration_flush_text_section( $sections, $current );
			break;
		}

		if ( in_array( $tag, array( 'h2', 'h3' ), true ) ) {
			if ( ! $text ) {
				continue;
			}

			roverland_content_migration_flush_text_section( $sections, $current );
			$current = array( 'title' => $text, 'html' => array(), 'image' => '' );
			continue;
		}

		if ( 'table' === $tag ) {
			$table_title = '';

			if ( ! empty( $current['title'] ) && empty( $current['html'] ) && empty( $current['image'] ) ) {
				$table_title = $current['title'];
				$current     = array( 'title' => '', 'html' => array(), 'image' => '' );
			} else {
				roverland_content_migration_flush_text_section( $sections, $current );
			}

			$table_section = roverland_content_migration_table_section( $node, $kind );

			if ( $table_section ) {
				if ( $table_title ) {
					$table_section['title'] = $table_title;
				}

				$sections[] = $table_section;
			}

			continue;
		}

		if ( 'img' === $tag ) {
			$image_url = roverland_content_migration_image_url( $node, $source_url );

			if ( ! $image_url ) {
				continue;
			}

			if ( ! $hero_image && ! $lead && ! $sections && empty( $current['html'] ) && ! $current['title'] ) {
				$hero_image = $image_url;
			} elseif ( empty( $current['image'] ) ) {
				$current['image'] = $image_url;
			}

			continue;
		}

		if ( ! in_array( $tag, array( 'p', 'ul', 'ol' ), true ) ) {
			continue;
		}

		if ( roverland_content_migration_ignore_text( $text ) ) {
			continue;
		}

		if ( ! $lead && 'p' === $tag && mb_strlen( $text ) >= 70 ) {
			$lead = roverland_content_migration_trim_lead( $text );
			continue;
		}

		$signature = mb_strtolower( preg_replace( '/\s+/u', ' ', trim( $text ) ) );

		if ( $signature && isset( $seen_fragments[ $signature ] ) ) {
			continue;
		}

		$html_part = roverland_content_migration_safe_fragment( $dom, $node );

		if ( $html_part ) {
			$current['html'][] = $html_part;
			if ( $signature ) {
				$seen_fragments[ $signature ] = true;
			}
		}
	}

	roverland_content_migration_flush_text_section( $sections, $current );

	// Some old model pages keep their first meaningful image inside the article
	// rather than in the hero. Promote it so the new layout does not render an
	// empty visual column, and remove it from that text block to avoid a duplicate.
	if ( ! $hero_image ) {
		foreach ( $sections as &$section ) {
			if ( 'text' !== ( $section['acf_fc_layout'] ?? '' ) || empty( $section['_image_url'] ) ) {
				continue;
			}

			$hero_image = $section['_image_url'];
			$section['_image_url'] = '';
			$section['image_position'] = 'none';
			break;
		}
		unset( $section );
	}

	libxml_clear_errors();
	libxml_use_internal_errors( $previous );

	return array(
		'h1'          => $h1,
		'lead'        => $lead,
		'title'       => $title,
		'description' => $description,
		'hero_image'  => $hero_image,
		'sections'    => $sections,
	);
}

function roverland_content_migration_is_chrome_node( $node ) {
	for ( $parent = $node; $parent; $parent = $parent->parentNode ) {
		if ( ! $parent instanceof DOMElement ) {
			continue;
		}

		$tag = strtolower( $parent->nodeName );

		if ( in_array( $tag, array( 'header', 'nav', 'footer' ), true ) ) {
			return true;
		}
	}

	return false;
}

function roverland_content_migration_node_text( $node ) {
	return trim( preg_replace( '/\s+/u', ' ', html_entity_decode( (string) $node->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
}

function roverland_content_migration_trim_lead( $text, $max_length = 320 ) {
	$text = trim( preg_replace( '/\s+/u', ' ', (string) $text ) );

	if ( '' === $text ) {
		return '';
	}

	$sentences = preg_split( '/(?<=[.!?])\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY );
	$result    = '';

	foreach ( array_slice( $sentences, 0, 2 ) as $sentence ) {
		$candidate = trim( $result . ' ' . $sentence );

		if ( mb_strlen( $candidate ) > $max_length ) {
			break;
		}

		$result = $candidate;
	}

	if ( $result ) {
		return $result;
	}

	if ( mb_strlen( $text ) <= $max_length ) {
		return $text;
	}

	$trimmed = rtrim( mb_substr( $text, 0, $max_length - 1 ) );
	$space   = mb_strrpos( $trimmed, ' ' );

	if ( false !== $space && $space > (int) ( $max_length * 0.7 ) ) {
		$trimmed = mb_substr( $trimmed, 0, $space );
	}

	return rtrim( $trimmed, " \t\n\r\0\x0B,;:-" ) . '…';
}

function roverland_content_migration_ignore_text( $text ) {
	$text = trim( (string) $text );

	if ( ! $text || mb_strlen( $text ) < 3 ) {
		return true;
	}

	return (bool) preg_match(
		'/^(нажимая на кнопку|предпочитаемый адрес|поделиться страницей|предыдущ|следующ|узнать подробнее)/iu',
		$text
	);
}

function roverland_content_migration_safe_fragment( $dom, $node ) {
	$html = $dom->saveHTML( $node );

	return wp_kses(
		$html,
		array(
			'p'      => array(),
			'ul'     => array(),
			'ol'     => array(),
			'li'     => array(),
			'strong' => array(),
			'b'      => array(),
			'em'     => array(),
			'i'      => array(),
			'br'     => array(),
			'a'      => array( 'href' => true, 'title' => true ),
		)
	);
}

function roverland_content_migration_flush_text_section( &$sections, &$current ) {
	if ( empty( $current['title'] ) && empty( $current['html'] ) && empty( $current['image'] ) ) {
		$current = array( 'title' => '', 'html' => array(), 'image' => '' );
		return;
	}

	$sections[] = array(
		'acf_fc_layout' => 'text',
		'title'         => (string) $current['title'],
		'content'       => implode( "\n", $current['html'] ),
		'image'         => 0,
		'image_position'=> ! empty( $current['image'] ) ? ( count( $sections ) % 2 ? 'left' : 'right' ) : 'none',
		'background'    => count( $sections ) % 2 ? 'soft' : 'white',
		'_image_url'    => (string) $current['image'],
	);

	$current = array( 'title' => '', 'html' => array(), 'image' => '' );
}

function roverland_content_migration_table_section( $table, $kind ) {
	$rows = array();

	foreach ( $table->getElementsByTagName( 'tr' ) as $tr ) {
		$cells = array();

		foreach ( $tr->childNodes as $cell ) {
			if ( ! $cell instanceof DOMElement || ! in_array( strtolower( $cell->nodeName ), array( 'th', 'td' ), true ) ) {
				continue;
			}

			$cells[] = roverland_content_migration_node_text( $cell );
		}

		if ( $cells ) {
			$rows[] = $cells;
		}
	}

	if ( count( $rows ) < 2 ) {
		return null;
	}

	$headers = array_shift( $rows );
	$is_price = count( $headers ) >= 3 && (
		false !== mb_stripos( implode( ' ', $headers ), 'Стоимость работы' ) ||
		false !== mb_stripos( implode( ' ', $headers ), 'Стоимость запчаст' )
	);

	if ( $is_price && 'maintenance' !== $kind ) {
		$groups  = array();
		$current = array( 'title' => '', 'rows' => array() );

		foreach ( $rows as $row ) {
			$row = array_pad( $row, 3, '' );

			if ( $row[0] && ! $row[1] && ! $row[2] ) {
				if ( $current['rows'] ) {
					$groups[] = $current;
				}

				$current = array( 'title' => $row[0], 'rows' => array() );
				continue;
			}

			$current['rows'][] = array(
				'name'        => $row[0],
				'work_price'  => $row[1],
				'parts_price' => $row[2],
			);
		}

		if ( $current['rows'] ) {
			$groups[] = $current;
		}

		return array(
			'acf_fc_layout' => 'price_table',
			'title'         => '',
			'note'          => '',
			'groups'        => $groups,
		);
	}

	$matrix_headers = array();

	foreach ( array_slice( $headers, 1 ) as $header ) {
		$matrix_headers[] = array( 'label' => $header );
	}

	$matrix_rows = array();

	foreach ( $rows as $row ) {
		if ( ! $row ) {
			continue;
		}

		$cells = array();

		foreach ( array_slice( $row, 1 ) as $cell ) {
			$cells[] = array( 'value' => $cell );
		}

		$matrix_rows[] = array(
			'name'  => $row[0],
			'cells' => $cells,
		);
	}

	return array(
		'acf_fc_layout' => 'matrix_table',
		'title'         => '',
		'headers'       => $matrix_headers,
		'rows'          => $matrix_rows,
		'note'          => '',
	);
}

function roverland_content_migration_image_url( $img, $source_url ) {
	$src = trim( (string) $img->getAttribute( 'data-src' ) );

	if ( ! $src ) {
		$src = trim( (string) $img->getAttribute( 'src' ) );
	}

	if ( ! $src || false === strpos( $src, '/upload/' ) || preg_match( '/(?:logo|icon|sprite|counter|pixel|captcha)/iu', $src ) ) {
		return '';
	}

	if ( 0 === strpos( $src, '//' ) ) {
		$src = 'https:' . $src;
	} elseif ( 0 === strpos( $src, '/' ) ) {
		$src = 'https://roverland.ru' . $src;
	} elseif ( ! preg_match( '#^https?://#i', $src ) ) {
		$src = trailingslashit( dirname( $source_url ) ) . ltrim( $src, '/' );
	}

	$host = strtolower( (string) wp_parse_url( $src, PHP_URL_HOST ) );

	return in_array( $host, array( 'roverland.ru', 'www.roverland.ru' ), true ) ? esc_url_raw( $src ) : '';
}

function roverland_content_migration_sideload( $url, $post_id, &$report ) {
	$url = esc_url_raw( (string) $url );

	if ( ! $url ) {
		return 0;
	}

	$existing = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_roverland_content_source_url',
			'meta_value'     => $url,
			'no_found_rows'  => true,
		)
	);

	if ( $existing ) {
		return (int) $existing[0];
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$tmp = download_url( $url, 25 );

	if ( is_wp_error( $tmp ) ) {
		$report['errors'][] = 'Не скачалась картинка: ' . $url;
		return 0;
	}

	$path = wp_parse_url( $url, PHP_URL_PATH );
	$file = array(
		'name'     => sanitize_file_name( wp_basename( $path ?: 'roverland-image.jpg' ) ),
		'tmp_name' => $tmp,
	);

	$attachment_id = media_handle_sideload( $file, (int) $post_id );

	if ( is_wp_error( $attachment_id ) ) {
		@unlink( $tmp );
		$report['errors'][] = 'Не удалось сохранить картинку: ' . $url;
		return 0;
	}

	update_post_meta( $attachment_id, '_roverland_content_source_url', $url );
	$report['media'] = (int) ( $report['media'] ?? 0 ) + 1;

	return (int) $attachment_id;
}

function roverland_content_migration_find_portfolio( $legacy_id ) {
	$ids = get_posts(
		array(
			'post_type'      => 'portfolio_item',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_roverland_bitrix_portfolio_id',
			'meta_value'     => (int) $legacy_id,
			'no_found_rows'  => true,
		)
	);

	if ( $ids ) {
		return (int) $ids[0];
	}

	$post = get_page_by_path( (string) (int) $legacy_id, OBJECT, 'portfolio_item' );

	return $post ? (int) $post->ID : 0;
}

function roverland_content_migration_apply_portfolio( $item, &$report ) {
	$legacy_id = (int) ( $item['legacy_id'] ?? 0 );

	if ( ! $legacy_id || empty( $item['name'] ) ) {
		return false;
	}

	$post_id = roverland_content_migration_find_portfolio( $legacy_id );
	$postarr = array(
		'post_type'   => 'portfolio_item',
		'post_status' => 'publish',
		'post_title'  => (string) $item['name'],
		'post_name'   => (string) $legacy_id,
		'menu_order'  => (int) ( $item['sort'] ?? 500 ),
	);

	if ( $post_id ) {
		$postarr['ID'] = $post_id;
		$result        = wp_update_post( wp_slash( $postarr ), true );
	} else {
		$result = wp_insert_post( wp_slash( $postarr ), true );
	}

	if ( is_wp_error( $result ) ) {
		$report['errors'][] = 'Портфолио #' . $legacy_id . ': ' . $result->get_error_message();
		return false;
	}

	$post_id = (int) $result;
	update_post_meta( $post_id, '_roverland_bitrix_portfolio_id', $legacy_id );

	$cover_url = ! empty( $item['cover']['url'] ) ? $item['cover']['url'] : '';

	if ( ! $cover_url && ! empty( $item['gallery'][0]['file']['url'] ) ) {
		$cover_url = $item['gallery'][0]['file']['url'];
	}

	$cover_id = $cover_url ? roverland_content_migration_sideload( $cover_url, $post_id, $report ) : 0;

	if ( $cover_id ) {
		update_field( 'portfolio_card_image', $cover_id, $post_id );
	}

	$gallery_rows = array();

	foreach ( $item['gallery'] ?? array() as $gallery_item ) {
		$url = $gallery_item['file']['url'] ?? '';

		if ( ! $url ) {
			continue;
		}

		$image_id = roverland_content_migration_sideload( $url, $post_id, $report );

		if ( $image_id ) {
			$gallery_rows[] = array( 'image' => $image_id );
		}
	}

	update_field( 'portfolio_h1', (string) $item['name'], $post_id );
	update_field( 'portfolio_summary', wp_strip_all_tags( (string) ( $item['description_html'] ?? '' ) ), $post_id );
	update_field( 'portfolio_content', wp_kses_post( (string) ( $item['description_html'] ?? '' ) ), $post_id );
	update_field( 'portfolio_gallery', $gallery_rows, $post_id );
	update_field( 'portfolio_service_title', 'Нужна такая же работа?', $post_id );

	update_post_meta( $post_id, 'rank_math_title', sanitize_text_field( $item['name'] . ' — Rover Land' ) );

	$description = trim( wp_strip_all_tags( (string) ( $item['description_html'] ?? '' ) ) );
	if ( $description ) {
		update_post_meta( $post_id, 'rank_math_description', sanitize_textarea_field( mb_substr( $description, 0, 280 ) ) );
	}

	update_post_meta( $post_id, '_roverland_curated_portfolio_imported', current_time( 'mysql' ) );

	return true;
}

function roverland_content_migration_finish( $report ) {
	roverland_content_migration_set_report( $report );
	wp_safe_redirect( admin_url( 'admin.php?page=roverland-content-migration' ) );
	exit;
}
