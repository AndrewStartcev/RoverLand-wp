<?php

defined( 'ABSPATH' ) || exit;

function roverland_asset( $path ) {
	return get_theme_file_uri( ltrim( (string) $path, '/' ) );
}

function roverland_field( $name, $default = '', $post_id = false ) {
	if ( ! function_exists( 'get_field' ) ) {
		return $default;
	}

	$value = get_field( $name, $post_id );

	return ( null === $value || false === $value || '' === $value ) ? $default : $value;
}

function roverland_option( $name, $default = '' ) {
	return roverland_field( $name, $default, 'option' );
}

function roverland_phone_href( $phone ) {
	$phone = trim( (string) $phone );

	if ( '' === $phone ) {
		return '';
	}

	$has_plus = isset( $phone[0] ) && '+' === $phone[0];
	$digits   = preg_replace( '/\D+/', '', $phone );

	return $digits ? ( $has_plus ? '+' : '' ) . $digits : '';
}

function roverland_page_url( $slug ) {
	$page = get_page_by_path( trim( (string) $slug, '/' ) );

	return $page ? get_permalink( $page ) : home_url( '/' . trim( (string) $slug, '/' ) . '/' );
}

function roverland_image_url( $image, $fallback = '' ) {
	if ( is_array( $image ) && ! empty( $image['url'] ) ) {
		return $image['url'];
	}

	if ( is_numeric( $image ) ) {
		$url = wp_get_attachment_image_url( (int) $image, 'full' );

		if ( $url ) {
			return $url;
		}
	}

	return $fallback ? roverland_asset( $fallback ) : '';
}

function roverland_image_alt( $image, $fallback = '' ) {
	if ( is_array( $image ) && ! empty( $image['alt'] ) ) {
		return $image['alt'];
	}

	if ( is_numeric( $image ) ) {
		$alt = get_post_meta( (int) $image, '_wp_attachment_image_alt', true );

		if ( $alt ) {
			return $alt;
		}
	}

	return $fallback;
}

function roverland_get_branches() {
	$branches = roverland_option( 'site_branches', array() );

	return is_array( $branches ) ? $branches : array();
}

function roverland_get_social_links() {
	$items = roverland_option( 'site_social_links', array() );

	return is_array( $items ) ? $items : array();
}


function roverland_service_page_kind( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();

	return trim( (string) roverland_field( 'service_page_kind', '', $post_id ) );
}

function roverland_get_service_page_for_model( $model_id ) {
	$model_id = (int) $model_id;

	if ( ! $model_id ) {
		return null;
	}

	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'   => 'service_page_kind',
					'value' => 'model',
				),
				array(
					'key'   => 'service_related_model',
					'value' => $model_id,
				),
			),
			'no_found_rows'  => true,
		)
	);

	return $pages ? $pages[0] : null;
}

function roverland_get_related_service_pages( $post_id = 0, $limit = -1 ) {
	$post_id    = $post_id ? (int) $post_id : get_the_ID();
	$kind       = roverland_service_page_kind( $post_id );
	$model_id   = (int) roverland_field( 'service_related_model', 0, $post_id );
	$service_id = (int) roverland_field( 'service_related_service', 0, $post_id );

	$meta_query = array( 'relation' => 'AND' );

	if ( 'model' === $kind && $model_id ) {
		$meta_query[] = array(
			'key'     => 'service_related_model',
			'value'   => $model_id,
			'compare' => '=',
		);
		$meta_query[] = array(
			'key'     => 'service_page_kind',
			'value'   => array( 'model_service', 'maintenance' ),
			'compare' => 'IN',
		);
	} elseif ( 'service' === $kind && $service_id ) {
		$meta_query[] = array(
			'key'     => 'service_related_service',
			'value'   => $service_id,
			'compare' => '=',
		);
		$meta_query[] = array(
			'key'     => 'service_page_kind',
			'value'   => array( 'model_service', 'maintenance' ),
			'compare' => 'IN',
		);
	} elseif ( in_array( $kind, array( 'model_service', 'maintenance' ), true ) && $model_id ) {
		$meta_query[] = array(
			'key'     => 'service_related_model',
			'value'   => $model_id,
			'compare' => '=',
		);
		$meta_query[] = array(
			'key'     => 'service_page_kind',
			'value'   => array( 'model_service', 'maintenance' ),
			'compare' => 'IN',
		);
	} else {
		return array();
	}

	return get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => (int) $limit,
			'post__not_in'   => array( $post_id ),
			'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
			'meta_query'     => $meta_query,
			'no_found_rows'  => true,
		)
	);
}

function roverland_service_breadcrumb_items( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	$items   = array(
		array(
			'label' => 'Главная',
			'url'   => home_url( '/' ),
		),
	);

	$ancestors = array_reverse( get_post_ancestors( $post_id ) );

	foreach ( $ancestors as $ancestor_id ) {
		$items[] = array(
			'label' => get_the_title( $ancestor_id ),
			'url'   => get_permalink( $ancestor_id ),
		);
	}

	$items[] = array(
		'label' => get_the_title( $post_id ),
	);

	return $items;
}
