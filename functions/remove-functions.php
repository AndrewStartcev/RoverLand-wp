<?php

defined( 'ABSPATH' ) || exit;

/**
 * Remove unnecessary output from wp_head().
 */
$roverland_wp_head_actions = array(
	array( 'wlwmanifest_link', 10 ),
	array( 'rsd_link', 10 ),
	array( 'wp_generator', 10 ),
	array( 'wp_shortlink_wp_head', 10 ),
	array( 'adjacent_posts_rel_link_wp_head', 10 ),
	array( 'feed_links_extra', 3 ),
	array( 'wp_oembed_add_discovery_links', 10 ),
	array( 'wp_oembed_add_host_js', 10 ),
	array( 'rest_output_link_wp_head', 10 ),
);

foreach ( $roverland_wp_head_actions as $roverland_wp_head_action ) {
	remove_action(
		'wp_head',
		$roverland_wp_head_action[0],
		$roverland_wp_head_action[1]
	);
}

unset( $roverland_wp_head_actions, $roverland_wp_head_action );

remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
remove_action( 'template_redirect', 'rest_output_link_header', 11 );

add_filter( 'the_generator', '__return_empty_string' );

/**
 * Disable Emoji scripts and styles.
 */
$roverland_emoji_actions = array(
	array( 'wp_head', 'print_emoji_detection_script', 7 ),
	array( 'admin_print_scripts', 'print_emoji_detection_script', 10 ),
	array( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles', 10 ),
	array( 'admin_print_styles', 'wp_enqueue_emoji_styles', 10 ),
	array( 'wp_print_styles', 'print_emoji_styles', 10 ),
	array( 'admin_print_styles', 'print_emoji_styles', 10 ),
);

foreach ( $roverland_emoji_actions as $roverland_emoji_action ) {
	remove_action(
		$roverland_emoji_action[0],
		$roverland_emoji_action[1],
		$roverland_emoji_action[2]
	);
}

unset( $roverland_emoji_actions, $roverland_emoji_action );

$roverland_emoji_filters = array(
	array( 'wp_mail', 'wp_staticize_emoji_for_email' ),
	array( 'the_content_feed', 'wp_staticize_emoji' ),
	array( 'comment_text_rss', 'wp_staticize_emoji' ),
);

foreach ( $roverland_emoji_filters as $roverland_emoji_filter ) {
	remove_filter(
		$roverland_emoji_filter[0],
		$roverland_emoji_filter[1]
	);
}

unset( $roverland_emoji_filters, $roverland_emoji_filter );

/**
 * Disable comments and pingbacks site-wide.
 */
function roverland_disable_comments_support() {
	foreach ( get_post_types() as $post_type ) {
		if ( post_type_supports( $post_type, 'comments' ) ) {
			remove_post_type_support( $post_type, 'comments' );
		}

		if ( post_type_supports( $post_type, 'trackbacks' ) ) {
			remove_post_type_support( $post_type, 'trackbacks' );
		}
	}
}
add_action( 'init', 'roverland_disable_comments_support', 100 );

add_filter( 'comments_open', '__return_false', 100 );
add_filter( 'pings_open', '__return_false', 100 );
add_filter( 'comments_array', '__return_empty_array', 100 );

/**
 * Remove admin screens that are not used by this classic custom theme.
 */
function roverland_remove_admin_menu_pages() {
	$menu_pages = array(
		'edit-comments.php',
		'site-editor.php',
		'font-library.php',
	);

	foreach ( $menu_pages as $menu_page ) {
		remove_menu_page( $menu_page );
	}

	$submenu_pages = array(
		array( 'options-general.php', 'options-discussion.php' ),
		array( 'themes.php', 'site-editor.php' ),
		array( 'themes.php', 'font-library.php' ),
	);

	foreach ( $submenu_pages as $submenu_page ) {
		remove_submenu_page( $submenu_page[0], $submenu_page[1] );
	}
}
add_action( 'admin_menu', 'roverland_remove_admin_menu_pages', 999 );

function roverland_disable_unused_admin_pages() {
	wp_safe_redirect( admin_url() );
	exit;
}

foreach ( array( 'site-editor.php', 'font-library.php' ) as $roverland_disabled_admin_page ) {
	add_action(
		'load-' . $roverland_disabled_admin_page,
		'roverland_disable_unused_admin_pages'
	);
}
unset( $roverland_disabled_admin_page );

function roverland_remove_comments_admin_bar( $wp_admin_bar ) {
	$wp_admin_bar->remove_node( 'comments' );
}
add_action( 'admin_bar_menu', 'roverland_remove_comments_admin_bar', 999 );

function roverland_remove_comments_dashboard_widget() {
	remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
}
add_action( 'wp_dashboard_setup', 'roverland_remove_comments_dashboard_widget' );

/**
 * Classic editor only: content structure is controlled by templates and ACF.
 */
add_filter( 'use_block_editor_for_post_type', '__return_false', 100 );
add_filter( 'gutenberg_use_widgets_block_editor', '__return_false' );
add_filter( 'use_widgets_block_editor', '__return_false' );

function roverland_disable_block_patterns() {
	remove_theme_support( 'core-block-patterns' );
}
add_action( 'after_setup_theme', 'roverland_disable_block_patterns', 100 );
add_filter( 'should_load_remote_block_patterns', '__return_false' );

/**
 * Remove Gutenberg styles from the public site.
 */
function roverland_remove_block_styles() {
	foreach (
		array(
			'wp-block-library',
			'wp-block-library-theme',
			'global-styles',
			'classic-theme-styles',
		) as $style
	) {
		wp_dequeue_style( $style );
	}
}
add_action( 'wp_enqueue_scripts', 'roverland_remove_block_styles', 100 );

remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
remove_action( 'wp_enqueue_scripts', 'wp_enqueue_stored_styles' );
remove_action( 'wp_footer', 'wp_enqueue_stored_styles', 1 );
remove_action( 'wp_enqueue_scripts', 'wp_enqueue_classic_theme_styles' );
remove_action( 'enqueue_block_assets', 'wp_enqueue_classic_theme_styles' );
remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
