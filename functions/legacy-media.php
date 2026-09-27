<?php

defined( 'ABSPATH' ) || exit;

function roverland_legacy_extract_image_urls( $legacy_url, $limit = 3 ) {
	$legacy_url = esc_url_raw( (string) $legacy_url );
	$limit      = max( 1, min( 10, (int) $limit ) );

	if ( ! $legacy_url ) {
		return array();
	}

	$response = wp_remote_get(
		$legacy_url,
		array(
			'timeout'     => 15,
			'redirection' => 3,
			'user-agent'  => 'RoverLand WordPress Migration/1.0',
		)
	);

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return array();
	}

	$html = (string) wp_remote_retrieve_body( $response );

	if ( ! $html ) {
		return array();
	}

	$urls = array();

	if ( class_exists( 'DOMDocument' ) ) {
		$previous = libxml_use_internal_errors( true );
		$dom      = new DOMDocument();
		$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );

		foreach ( $dom->getElementsByTagName( 'img' ) as $img ) {
			foreach ( array( 'data-src', 'src' ) as $attribute ) {
				$src = trim( (string) $img->getAttribute( $attribute ) );

				if ( $src ) {
					$urls[] = $src;
					break;
				}
			}
		}

		libxml_clear_errors();
		libxml_use_internal_errors( $previous );
	} else {
		preg_match_all( '/<img[^>]+(?:data-src|src)=["\']([^"\']+)["\']/iu', $html, $matches );
		$urls = isset( $matches[1] ) ? $matches[1] : array();
	}

	$result = array();

	foreach ( $urls as $url ) {
		$url = html_entity_decode( trim( (string) $url ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		if ( ! $url || false === strpos( $url, '/upload/' ) ) {
			continue;
		}

		if ( preg_match( '/(?:logo|sprite|icon|counter|pixel|captcha)/iu', $url ) ) {
			continue;
		}

		$path = wp_parse_url( $url, PHP_URL_PATH );

		if ( ! $path || ! preg_match( '/\.(?:jpe?g|png|webp)$/i', $path ) ) {
			continue;
		}

		if ( 0 === strpos( $url, '//' ) ) {
			$url = 'https:' . $url;
		} elseif ( 0 === strpos( $url, '/' ) ) {
			$url = 'https://roverland.ru' . $url;
		} elseif ( ! preg_match( '#^https?://#i', $url ) ) {
			$url = trailingslashit( dirname( $legacy_url ) ) . ltrim( $url, '/' );
		}

		$url = esc_url_raw( $url );

		if ( ! $url || in_array( $url, $result, true ) ) {
			continue;
		}

		$result[] = $url;

		if ( count( $result ) >= $limit ) {
			break;
		}
	}

	return $result;
}

function roverland_legacy_find_attachment_by_source( $source_url ) {
	$ids = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_roverland_legacy_source_url',
			'meta_value'     => esc_url_raw( $source_url ),
			'no_found_rows'  => true,
		)
	);

	return $ids ? (int) $ids[0] : 0;
}

function roverland_legacy_sideload_image( $source_url, $post_id, &$report ) {
	$source_url = esc_url_raw( (string) $source_url );

	if ( ! $source_url ) {
		return 0;
	}

	$existing = roverland_legacy_find_attachment_by_source( $source_url );

	if ( $existing ) {
		return $existing;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$tmp = download_url( $source_url, 20 );

	if ( is_wp_error( $tmp ) ) {
		$report['errors'][] = 'Не удалось скачать изображение: ' . $source_url;
		return 0;
	}

	$path = wp_parse_url( $source_url, PHP_URL_PATH );
	$name = $path ? wp_basename( $path ) : 'legacy-image.jpg';

	$file_array = array(
		'name'     => sanitize_file_name( $name ),
		'tmp_name' => $tmp,
	);

	$attachment_id = media_handle_sideload( $file_array, (int) $post_id );

	if ( is_wp_error( $attachment_id ) ) {
		@unlink( $tmp );
		$report['errors'][] = 'WordPress не смог сохранить изображение ' . $source_url . ': ' . $attachment_id->get_error_message();
		return 0;
	}

	update_post_meta( $attachment_id, '_roverland_legacy_source_url', $source_url );
	$report['media'] = isset( $report['media'] ) ? (int) $report['media'] + 1 : 1;

	return (int) $attachment_id;
}

function roverland_legacy_attach_images_to_sections( $sections, $legacy_url, $post_id, &$report ) {
	if ( ! is_array( $sections ) || ! $sections ) {
		return $sections;
	}

	$urls = roverland_legacy_extract_image_urls( $legacy_url, 3 );

	if ( ! $urls ) {
		return $sections;
	}

	$image_ids = array();

	foreach ( $urls as $url ) {
		$id = roverland_legacy_sideload_image( $url, $post_id, $report );

		if ( $id ) {
			$image_ids[] = $id;
		}
	}

	if ( ! $image_ids ) {
		return $sections;
	}

	$image_index = 0;
	$text_index  = 0;

	foreach ( $sections as &$section ) {
		if ( 'text' !== ( $section['acf_fc_layout'] ?? '' ) || $image_index >= count( $image_ids ) ) {
			continue;
		}

		$section['image']          = $image_ids[ $image_index ];
		$section['image_position'] = 0 === $text_index % 2 ? 'right' : 'left';
		$image_index++;
		$text_index++;
	}
	unset( $section );

	return $sections;
}
