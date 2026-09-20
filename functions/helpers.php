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
