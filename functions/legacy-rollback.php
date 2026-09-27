<?php

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', 'roverland_legacy_rollback_register_page', 120 );
add_action( 'admin_post_roverland_legacy_rollback', 'roverland_legacy_rollback_handle' );

function roverland_legacy_rollback_register_page() {
	$count = roverland_legacy_rollback_count();

	if ( ! $count ) {
		return;
	}

	add_submenu_page(
		'roverland-settings',
		'Откат legacy-импорта',
		'Откат legacy-импорта',
		'manage_options',
		'roverland-legacy-rollback',
		'roverland_legacy_rollback_render'
	);
}

function roverland_legacy_rollback_count() {
	$query = new WP_Query(
		array(
			'post_type'      => 'page',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_roverland_legacy_imported_at',
			'meta_compare'   => 'EXISTS',
			'no_found_rows'  => false,
		)
	);

	return (int) $query->found_posts;
}

function roverland_legacy_rollback_render() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$count  = roverland_legacy_rollback_count();
	$report = get_transient( 'roverland_legacy_rollback_' . get_current_user_id() );

	if ( $report ) {
		delete_transient( 'roverland_legacy_rollback_' . get_current_user_id() );
	}
	?>
	<div class="wrap">
		<h1>Откат legacy-импорта</h1>

		<?php if ( is_array( $report ) ) : ?>
			<div class="notice notice-<?php echo empty( $report['errors'] ) ? 'success' : 'warning'; ?> is-dismissible">
				<p><strong><?php echo esc_html( $report['message'] ?? 'Откат завершён.' ); ?></strong></p>
				<?php if ( ! empty( $report['errors'] ) ) : ?>
					<ul>
						<?php foreach ( $report['errors'] as $error ) : ?>
							<li><?php echo esc_html( $error ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div style="max-width:850px;padding:24px 28px;background:#fff;border:1px solid #dcdcde;border-radius:10px">
			<p>Страниц, затронутых автоматическим legacy-импортом: <strong><?php echo (int) $count; ?></strong>.</p>
			<p>
				Откат восстановит из <code>data/base-content.json</code> наши исходные
				<code>service_sections</code>, H1, lead и Rank Math.
				Если до legacy-импорта секции были пустыми — они снова станут пустыми.
			</p>
			<p><strong>Скачанные изображения из Media Library не удаляются.</strong></p>

			<?php if ( $count ) : ?>
				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
					<input type="hidden" name="action" value="roverland_legacy_rollback">
					<?php wp_nonce_field( 'roverland_legacy_rollback', 'roverland_legacy_rollback_nonce' ); ?>
					<?php submit_button(
						'Откатить автоматический импорт',
						'primary',
						'submit',
						false,
						array(
							'onclick' => "return confirm('Вернуть страницы к состоянию base-content.json до автоматического legacy-импорта?');",
						)
					); ?>
				</form>
			<?php else : ?>
				<p>Откатывать больше нечего.</p>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

function roverland_legacy_rollback_handle() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Недостаточно прав.' );
	}

	check_admin_referer( 'roverland_legacy_rollback', 'roverland_legacy_rollback_nonce' );

	$report = array(
		'message' => '',
		'errors'  => array(),
	);

	if ( ! function_exists( 'update_field' ) ) {
		$report['errors'][] = 'ACF Pro не активен.';
		roverland_legacy_rollback_finish( $report );
	}

	$data = roverland_base_import_data();

	if ( is_wp_error( $data ) ) {
		$report['errors'][] = $data->get_error_message();
		roverland_legacy_rollback_finish( $report );
	}

	$by_key = array();

	foreach ( isset( $data['pages'] ) && is_array( $data['pages'] ) ? $data['pages'] : array() as $page_data ) {
		if ( ! empty( $page_data['key'] ) ) {
			$by_key[ sanitize_key( $page_data['key'] ) ] = $page_data;
		}
	}

	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'meta_key'       => '_roverland_legacy_imported_at',
			'meta_compare'   => 'EXISTS',
			'no_found_rows'  => true,
		)
	);

	$restored = 0;
	$cleared  = 0;

	foreach ( $pages as $page ) {
		$key       = sanitize_key( (string) get_post_meta( $page->ID, '_roverland_base_import_key', true ) );
		$page_data = $key && isset( $by_key[ $key ] ) ? $by_key[ $key ] : null;

		if ( is_array( $page_data ) ) {
			$fields = isset( $page_data['fields'] ) && is_array( $page_data['fields'] ) ? $page_data['fields'] : array();
			$dummy_report = array(
				'media'  => 0,
				'errors' => array(),
			);

			foreach ( array( 'service_sections', 'service_page_h1', 'service_hero_lead' ) as $field_name ) {
				if ( array_key_exists( $field_name, $fields ) ) {
					$current = get_field( $field_name, $page->ID );
					update_field(
						$field_name,
						roverland_base_import_resolve_value( $fields[ $field_name ], $dummy_report, $current ),
						$page->ID
					);
				} elseif ( 'service_sections' === $field_name ) {
					update_field( 'service_sections', array(), $page->ID );
				}
			}

			if ( ! empty( $page_data['seo'] ) && is_array( $page_data['seo'] ) ) {
				roverland_base_import_apply_rank_math( $page->ID, $page_data['seo'] );
			} else {
				delete_post_meta( $page->ID, 'rank_math_title' );
				delete_post_meta( $page->ID, 'rank_math_description' );
			}

			if ( ! empty( $dummy_report['errors'] ) ) {
				foreach ( $dummy_report['errors'] as $error ) {
					$report['errors'][] = $error;
				}
			}

			$restored++;
		} else {
			update_field( 'service_sections', array(), $page->ID );
			delete_post_meta( $page->ID, 'rank_math_title' );
			delete_post_meta( $page->ID, 'rank_math_description' );
			$cleared++;
		}

		delete_post_meta( $page->ID, '_roverland_legacy_imported_at' );
		delete_post_meta( $page->ID, '_roverland_legacy_source_id' );
	}

	$cache_dir = wp_upload_dir();
	$cache_dir = trailingslashit( $cache_dir['basedir'] ) . 'roverland-legacy';

	foreach ( array( 'legacy-pages.json', 'source.sql', 'source.zip' ) as $filename ) {
		$file = trailingslashit( $cache_dir ) . $filename;
		if ( is_file( $file ) ) {
			@unlink( $file );
		}
	}

	$report['message'] = sprintf(
		'Откат завершён. Восстановлено из base-content.json: %d, очищено до пустого состояния: %d.',
		$restored,
		$cleared
	);

	roverland_legacy_rollback_finish( $report );
}

function roverland_legacy_rollback_finish( $report ) {
	set_transient(
		'roverland_legacy_rollback_' . get_current_user_id(),
		$report,
		5 * MINUTE_IN_SECONDS
	);

	wp_safe_redirect( admin_url( 'admin.php?page=roverland-legacy-rollback' ) );
	exit;
}
