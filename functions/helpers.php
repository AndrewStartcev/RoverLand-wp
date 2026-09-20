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
