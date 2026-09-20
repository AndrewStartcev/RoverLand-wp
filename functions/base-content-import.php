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
	?>
	<div class="wrap roverland-import">
		<h1>Импорт базовых данных Rover Land</h1>
		<p class="description">
			Источник: <code>data/base-content.json</code>. Импорт создаёт или обновляет страницы
			«Контакты», «О компании», «История», «Политика конфиденциальности»,
			заполняет глобальные настройки и загружает используемые изображения в медиатеку.
		</p>

		<?php if ( is_array( $report ) ) : ?>
			<div class="notice notice-<?php echo empty( $report['errors'] ) ? 'success' : 'warning'; ?> is-dismissible">
				<p><strong><?php echo empty( $report['errors'] ) ? 'Импорт завершён.' : 'Импорт завершён с замечаниями.'; ?></strong></p>
				<p>
					Создано страниц: <?php echo (int) $report['created']; ?>,
					обновлено: <?php echo (int) $report['updated']; ?>,
					медиа: <?php echo (int) $report['media']; ?>.
				</p>
				<?php if ( ! empty( $report['errors'] ) ) : ?>
					<ul>
						<?php foreach ( $report['errors'] as $error ) : ?>
							<li><?php echo esc_html( $error ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="roverland-import__card">
			<h2>Что делает импорт</h2>
			<div class="roverland-import__grid">
				<div><strong>Страницы</strong><span>Создаёт/обновляет 4 базовые страницы и назначает нужные шаблоны.</span></div>
				<div><strong>ACF</strong><span>Заполняет поля страниц, глобальные данные и общие блоки.</span></div>
				<div><strong>Медиа</strong><span>Файлы из темы, указанные в JSON, один раз копируются в медиатеку.</span></div>
				<div><strong>Повторный запуск</strong><span>Безопасно обновляет созданные записи по стабильному ключу, без дублей.</span></div>
			</div>

			<p>
				<strong>ACF Pro:</strong>
				<?php echo $acf_ready ? '<span style="color:#16833b">активен</span>' : '<span style="color:#b32d2e">не активен</span>'; ?>
			</p>

			<?php if ( is_wp_error( $data ) ) : ?>
				<p style="color:#b32d2e"><?php echo esc_html( $data->get_error_message() ); ?></p>
			<?php endif; ?>

			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="roverland_base_import">
				<?php wp_nonce_field( 'roverland_base_import', 'roverland_base_import_nonce' ); ?>
				<?php
				submit_button(
					'Импортировать / обновить данные',
					'primary large',
					'submit',
					false,
					$can_run
						? array( 'onclick' => "return confirm('Обновить базовые страницы и глобальные данные из data/base-content.json?');" )
						: array( 'disabled' => 'disabled' )
				);
				?>
			</form>
		</div>
	</div>
	<style>
	.roverland-import{max-width:1050px}
	.roverland-import__card{margin-top:24px;padding:26px 28px;border:1px solid #dcdcde;border-radius:12px;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.04)}
	.roverland-import__grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin:20px 0}
	.roverland-import__grid>div{padding:18px;border:1px solid #e2e4e7;border-radius:10px;background:#f8f9fa}
	.roverland-import__grid strong,.roverland-import__grid span{display:block}
	.roverland-import__grid span{margin-top:6px;color:#50575e;line-height:1.45}
	@media(max-width:782px){.roverland-import__grid{grid-template-columns:1fr}}
	</style>
	<?php
}

function roverland_base_import_handle() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Недостаточно прав.' );
	}

	check_admin_referer( 'roverland_base_import', 'roverland_base_import_nonce' );

	$report = array(
		'created' => 0,
		'updated' => 0,
		'media'   => 0,
		'errors'  => array(),
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

	@set_time_limit( 120 );
	wp_raise_memory_limit( 'admin' );

	if ( ! empty( $data['options'] ) && is_array( $data['options'] ) ) {
		foreach ( $data['options'] as $field_name => $value ) {
			update_field(
				$field_name,
				roverland_base_import_resolve_value( $value, $report ),
				'option'
			);
		}
	}

	$page_ids = array();

	if ( ! empty( $data['pages'] ) && is_array( $data['pages'] ) ) {
		foreach ( $data['pages'] as $page_data ) {
			$parent_id = 0;

			if ( ! empty( $page_data['parent_key'] ) && ! empty( $page_ids[ $page_data['parent_key'] ] ) ) {
				$parent_id = (int) $page_ids[ $page_data['parent_key'] ];
			}

			$page_id = roverland_base_import_upsert_page( $page_data, $report, $parent_id );

			if ( ! $page_id ) {
				continue;
			}

			$page_ids[ $page_data['key'] ] = $page_id;

			if ( ! empty( $page_data['fields'] ) && is_array( $page_data['fields'] ) ) {
				foreach ( $page_data['fields'] as $field_name => $value ) {
					update_field(
						$field_name,
						roverland_base_import_resolve_value( $value, $report ),
						$page_id
					);
				}
			}
		}
	}

	if ( ! empty( $page_ids['privacy'] ) ) {
		update_option( 'wp_page_for_privacy_policy', (int) $page_ids['privacy'] );
	}

	roverland_base_import_fix_primary_menu_hierarchy( $page_ids );

	flush_rewrite_rules();
	roverland_base_import_finish( $report );
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

	if ( ! $page_id ) {
		$page = get_page_by_path( $page_data['slug'] );

		if ( $page ) {
			$page_id = (int) $page->ID;
		}
	}

	$postarr = array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => $page_data['title'],
		'post_name'    => $page_data['slug'],
		'post_parent'  => (int) $parent_id,
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

function roverland_base_import_fix_primary_menu_hierarchy( $page_ids ) {
	if ( empty( $page_ids['about'] ) || empty( $page_ids['history'] ) ) {
		return;
	}

	$locations = get_theme_mod( 'nav_menu_locations', array() );

	if ( empty( $locations['primary-menu'] ) ) {
		return;
	}

	$menu_id = (int) $locations['primary-menu'];
	$items   = wp_get_nav_menu_items( $menu_id );

	if ( ! is_array( $items ) ) {
		return;
	}

	$about_item   = null;
	$history_item = null;

	foreach ( $items as $item ) {
		if ( 'post_type' !== $item->type || 'page' !== $item->object ) {
			continue;
		}

		if ( (int) $item->object_id === (int) $page_ids['about'] ) {
			$about_item = $item;
		}

		if ( (int) $item->object_id === (int) $page_ids['history'] ) {
			$history_item = $item;
		}
	}

	if ( ! $about_item || ! $history_item ) {
		return;
	}

	wp_update_nav_menu_item(
		$menu_id,
		(int) $history_item->ID,
		array(
			'menu-item-object-id' => (int) $page_ids['history'],
			'menu-item-object'    => 'page',
			'menu-item-type'      => 'post_type',
			'menu-item-status'    => 'publish',
			'menu-item-parent-id' => (int) $about_item->ID,
		)
	);
}

function roverland_base_import_resolve_value( $value, &$report ) {
	if ( ! is_array( $value ) ) {
		return $value;
	}

	if ( ! empty( $value['theme_image'] ) ) {
		return roverland_base_import_media(
			$value['theme_image'],
			isset( $value['alt'] ) ? $value['alt'] : '',
			$report
		);
	}

	$result = array();

	foreach ( $value as $key => $item ) {
		$result[ $key ] = roverland_base_import_resolve_value( $item, $report );
	}

	return $result;
}

function roverland_base_import_media( $relative_path, $alt, &$report ) {
	$relative_path = ltrim( str_replace( '\\', '/', (string) $relative_path ), '/' );
	$source        = get_template_directory() . '/' . $relative_path;

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
