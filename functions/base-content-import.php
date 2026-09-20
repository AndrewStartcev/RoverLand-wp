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
			главную, «Контакты», «О компании», «Историю», «Вакансии», юридические страницы и отдельные вакансии,
			заполняет глобальные настройки и автоматически загружает только SVG-иконки в медиатеку.
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
				<div><strong>Страницы</strong><span>Создаёт/обновляет базовые страницы и вакансии и назначает нужные шаблоны.</span></div>
				<div><strong>ACF</strong><span>Заполняет поля страниц, глобальные данные и общие блоки.</span></div>
				<div><strong>Медиа</strong><span>Автоматически импортируются только SVG. PNG/JPG/WebP импортёр не трогает — их загружаем вручную.</span></div>
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
			$current_value = get_field( $field_name, 'option' );

			update_field(
				$field_name,
				roverland_base_import_resolve_value( $value, $report, $current_value ),
				'option'
			);
		}
	}

	$page_ids    = array();
	$vacancy_ids = array();

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

			if ( 'home' === $page_data['key'] ) {
				update_option( 'show_on_front', 'page' );
				update_option( 'page_on_front', $page_id );
			}

			if ( ! empty( $page_data['fields'] ) && is_array( $page_data['fields'] ) ) {
				foreach ( $page_data['fields'] as $field_name => $value ) {
					$current_value = get_field( $field_name, $page_id );

					update_field(
						$field_name,
						roverland_base_import_resolve_value( $value, $report, $current_value ),
						$page_id
					);
				}
			}

			if ( ! empty( $page_data['seo'] ) && is_array( $page_data['seo'] ) ) {
				roverland_base_import_apply_rank_math( $page_id, $page_data['seo'] );
			}
		}
	}

	if ( ! empty( $data['vacancies'] ) && is_array( $data['vacancies'] ) ) {
		foreach ( $data['vacancies'] as $vacancy_data ) {
			$vacancy_id = roverland_base_import_upsert_vacancy( $vacancy_data, $report );

			if ( ! $vacancy_id ) {
				continue;
			}

			$vacancy_ids[ $vacancy_data['key'] ] = $vacancy_id;

			if ( ! empty( $vacancy_data['fields'] ) && is_array( $vacancy_data['fields'] ) ) {
				foreach ( $vacancy_data['fields'] as $field_name => $value ) {
					$current_value = get_field( $field_name, $vacancy_id );

					update_field(
						$field_name,
						roverland_base_import_resolve_value( $value, $report, $current_value ),
						$vacancy_id
					);
				}
			}

			if ( ! empty( $vacancy_data['seo'] ) && is_array( $vacancy_data['seo'] ) ) {
				roverland_base_import_apply_rank_math( $vacancy_id, $vacancy_data['seo'] );
			}
		}
	}

	if ( ! empty( $page_ids['privacy'] ) ) {
		update_option( 'wp_page_for_privacy_policy', (int) $page_ids['privacy'] );
	}

	roverland_base_import_fix_primary_menu_hierarchy( $page_ids, $vacancy_ids );

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

function roverland_base_import_resolve_value( $value, &$report, $current = null ) {
	if ( ! is_array( $value ) ) {
		return $value;
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
