<?php

defined( 'ABSPATH' ) || exit;

function roverland_yandex_feed_path() {
	$path = trim( (string) roverland_option( 'yandex_feed_path', 'yandex-services.yml' ) );
	$path = trim( wp_parse_url( $path, PHP_URL_PATH ), '/' );

	return $path ? $path : 'yandex-services.yml';
}

function roverland_yandex_feed_xml_escape( $value ) {
	return htmlspecialchars( (string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8' );
}

function roverland_yandex_feed_price( $value ) {
	if ( '' === $value || null === $value ) {
		return '';
	}

	$value = str_replace( ',', '.', trim( (string) $value ) );

	if ( ! is_numeric( $value ) || (float) $value <= 0 ) {
		return '';
	}

	return rtrim( rtrim( number_format( (float) $value, 2, '.', '' ), '0' ), '.' );
}

function roverland_yandex_feed_category_id( $category ) {
	$unsigned = sprintf( '%u', crc32( 'roverland|' . mb_strtolower( trim( (string) $category ) ) ) );

	return $unsigned ? $unsigned : '1';
}

function roverland_yandex_feed_collect_offers() {
	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => 'service_page_kind',
					'value'   => '',
					'compare' => '!=',
				),
			),
			'orderby'        => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'no_found_rows'  => true,
		)
	);

	$default_category = trim( (string) roverland_option( 'yandex_feed_default_category', 'Автосервис' ) );
	$offers           = array();

	foreach ( $pages as $page ) {
		$rows = roverland_field( 'yandex_feed_offers', array(), $page->ID );

		if ( ! is_array( $rows ) || ! $rows ) {
			continue;
		}

		foreach ( $rows as $index => $row ) {
			if ( empty( $row['enabled'] ) ) {
				continue;
			}

			$name  = trim( (string) ( $row['name'] ?? '' ) );
			$price = roverland_yandex_feed_price( $row['price'] ?? '' );

			if ( ! $name || ! $price ) {
				continue;
			}

			$category = trim( (string) ( $row['category'] ?? '' ) );
			if ( ! $category ) {
				$category = $default_category ?: 'Автосервис';
			}

			$picture     = $row['picture'] ?? array();
			$picture_url = roverland_image_url( $picture );

			if ( ! $picture_url ) {
				$picture_url = roverland_image_url( roverland_field( 'service_hero_image', array(), $page->ID ) );
			}

			$offers[] = array(
				'id'                => 'page-' . $page->ID . '-' . ( (int) $index + 1 ),
				'name'              => mb_substr( $name, 0, 250 ),
				'category'          => mb_substr( $category, 0, 250 ),
				'price'             => $price,
				'description'       => mb_substr( trim( (string) ( $row['description'] ?? '' ) ), 0, 3000 ),
				'short_description' => mb_substr( trim( (string) ( $row['short_description'] ?? '' ) ), 0, 250 ),
				'picture'           => $picture_url,
				'url'               => get_permalink( $page ),
			);
		}
	}

	return $offers;
}

function roverland_render_yandex_services_feed() {
	if ( ! roverland_option( 'yandex_feed_enabled', true ) ) {
		status_header( 404 );
		exit;
	}

	$offers   = roverland_yandex_feed_collect_offers();
	$vendor   = trim( (string) roverland_option( 'yandex_feed_vendor', 'Rover Land' ) );
	$currency = trim( (string) roverland_option( 'yandex_feed_currency', 'RUB' ) );

	if ( ! in_array( $currency, array( 'RUB', 'KZT', 'BYN' ), true ) ) {
		$currency = 'RUB';
	}

	$categories = array();

	foreach ( $offers as $offer ) {
		$categories[ $offer['category'] ] = roverland_yandex_feed_category_id( $offer['category'] );
	}

	nocache_headers();
	header( 'Content-Type: application/xml; charset=UTF-8' );

	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	echo '<yml_catalog>' . "\n";
	echo '  <shop>' . "\n";
	echo '    <categories>' . "\n";

	foreach ( $categories as $category => $category_id ) {
		echo '      <category id="' . esc_attr( $category_id ) . '">' . roverland_yandex_feed_xml_escape( $category ) . '</category>' . "\n";
	}

	echo '    </categories>' . "\n";
	echo '    <offers>' . "\n";

	foreach ( $offers as $offer ) {
		echo '      <offer id="' . esc_attr( $offer['id'] ) . '">' . "\n";
		echo '        <name>' . roverland_yandex_feed_xml_escape( $offer['name'] ) . '</name>' . "\n";
		echo '        <vendor>' . roverland_yandex_feed_xml_escape( $vendor ) . '</vendor>' . "\n";
		echo '        <price>' . roverland_yandex_feed_xml_escape( $offer['price'] ) . '</price>' . "\n";
		echo '        <currencyId>' . roverland_yandex_feed_xml_escape( $currency ) . '</currencyId>' . "\n";
		echo '        <categoryId>' . esc_html( $categories[ $offer['category'] ] ) . '</categoryId>' . "\n";

		if ( $offer['picture'] ) {
			echo '        <picture>' . roverland_yandex_feed_xml_escape( $offer['picture'] ) . '</picture>' . "\n";
		}

		if ( $offer['description'] ) {
			echo '        <description>' . roverland_yandex_feed_xml_escape( $offer['description'] ) . '</description>' . "\n";
		}

		if ( $offer['short_description'] ) {
			echo '        <shortDescription>' . roverland_yandex_feed_xml_escape( $offer['short_description'] ) . '</shortDescription>' . "\n";
		}

		echo '        <url>' . roverland_yandex_feed_xml_escape( $offer['url'] ) . '</url>' . "\n";
		echo '      </offer>' . "\n";
	}

	echo '    </offers>' . "\n";
	echo '  </shop>' . "\n";
	echo '</yml_catalog>' . "\n";
	exit;
}

add_action(
	'template_redirect',
	static function () {
		$request_path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
		$request_path = trim( (string) $request_path, '/' );

		if ( roverland_yandex_feed_path() === $request_path ) {
			roverland_render_yandex_services_feed();
		}
	},
	0
);
