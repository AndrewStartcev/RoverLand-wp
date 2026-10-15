<?php
// wp eval-file wp-content/themes/rover/docs/apply-model-revision.php [path|all] [apply|rollback]
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) { return; }
if ( ! function_exists( 'update_field' ) ) { WP_CLI::error( 'ACF is required.' ); }
$paths = json_decode( file_get_contents( dirname( __DIR__ ) . '/data/model-revision-paths.json' ), true );
$target = $args[0] ?? 'defender/diagnostika';
$mode = $args[1] ?? 'preview';
if ( 'all' !== $target && ! in_array( $target, $paths, true ) ) { WP_CLI::error( 'Path is outside the approved TZ.' ); }
$fields = array( 'service_sections' => 'field_rl_service_sections', 'service_hero_lead' => 'field_rl_service_page_lead', 'service_selected_cases' => 'field_rl_service_selected_cases' );
$backup = get_option( 'roverland_model_revision_20261016_backup', array() );
$default_cases = get_posts( array( 'post_type' => 'portfolio_item', 'post_status' => 'publish', 'posts_per_page' => 3, 'fields' => 'ids' ) );
$ok = 0; $failed = 0;
foreach ( 'all' === $target ? $paths : array( $target ) as $path ) {
	$post = get_page_by_path( $path );
	if ( ! $post || ! in_array( roverland_service_page_kind( $post->ID ), array( 'model', 'model_service', 'maintenance' ), true ) ) { WP_CLI::warning( 'Skipped missing/model kind: ' . $path ); $failed++; continue; }
	$id = $post->ID;
	if ( 'apply' === $mode && get_post_meta( $id, '_roverland_model_revision_20261016', true ) ) { WP_CLI::log( 'Already applied: ' . $path ); continue; }
	if ( 'rollback' === $mode ) {
		if ( empty( $backup[$id] ) ) { WP_CLI::warning( 'No snapshot: ' . $path ); continue; }
		foreach ( $fields as $name => $key ) { update_field( $key, $backup[$id][$name], $id ); }
		delete_post_meta( $id, '_roverland_model_revision_20261016' );
		WP_CLI::log( 'Restored ' . $path ); continue;
	}
	$parsed = roverland_content_migration_fetch_and_parse( 'https://roverland.ru/' . $path . '/', 'model_revision' );
	if ( is_wp_error( $parsed ) || empty( $parsed['sections'] ) ) { WP_CLI::warning( 'Source failed: ' . $path ); $failed++; continue; }
	$old_sections = get_field( 'service_sections', $id ) ?: array();
	$images = array();
	foreach ( $old_sections as $old ) { if ( ! empty( $old['image'] ) ) { $images[$old['title'] ?? ''] = $old; } }
	$sections = $parsed['sections'];
	foreach ( $sections as &$section ) {
		if ( ! empty( $section['_image_url'] ) && isset( $images[$section['title'] ?? ''] ) ) {
			$previous = $images[$section['title'] ?? ''];
			$section['image'] = is_array( $previous['image'] ) ? $previous['image']['ID'] : $previous['image'];
			$section['image_position'] = $previous['image_position'];
		} else { $section['image_position'] = 'none'; }
		unset( $section['_image_url'] );
	}
	unset( $section );
	$tables = count( array_filter( $sections, static function( $section ) { return 'matrix_table' === $section['acf_fc_layout']; } ) );
	if ( 'apply' === $mode ) {
		if ( ! isset( $backup[$id] ) ) {
			foreach ( $fields as $name => $key ) { $backup[$id][$name] = get_field( $name, $id, false ); }
			update_option( 'roverland_model_revision_20261016_backup', $backup, false );
		}
		update_field( $fields['service_sections'], $sections, $id );
		// The full source introduction is now in the text sections, never a truncated legal note.
		update_field( $fields['service_hero_lead'], '', $id );
		if ( ! get_field( 'service_selected_cases', $id ) && $default_cases ) { update_field( $fields['service_selected_cases'], $default_cases, $id ); }
		update_post_meta( $id, '_roverland_model_revision_20261016', current_time( 'mysql' ) );
	}
	WP_CLI::log( $path . ': ' . count( $sections ) . ' sections, ' . $tables . ' tables (' . $mode . ')' );
	$ok++;
}
WP_CLI::success( $ok . ' processed, ' . $failed . ' failed. Snapshot: roverland_model_revision_20261016_backup' );
