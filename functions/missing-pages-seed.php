<?php

defined( 'ABSPATH' ) || exit;

/**
 * One-time seed for the five legacy URLs that are intentionally outside
 * the large service/model migration.
 *
 * Runs automatically on the first admin request after deploy.
 * Existing content is never overwritten.
 */
add_action( 'admin_init', 'roverland_seed_missing_legacy_pages', 40 );

function roverland_seed_missing_legacy_pages() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$version = '2026-09-30-v1';

	if ( $version === get_option( 'roverland_missing_pages_seed_version' ) ) {
		return;
	}

	if ( get_transient( 'roverland_missing_pages_seed_lock' ) ) {
		return;
	}

	set_transient( 'roverland_missing_pages_seed_lock', 1, 5 * MINUTE_IN_SECONDS );

	$items = array(
		array(
			'type'       => 'promotion',
			'slug'       => 'filtr-salona-v-podarok',
			'title'      => 'Фильтр салона в подарок',
			'source_url' => 'https://roverland.ru/aktsii/filtr-salona-v-podarok/',
			'summary'    => 'Получите подарок при выполнении антибактериальной обработки.',
			'content'    => '<p>Получите салонный фильтр в подарок при выполнении антибактериальной обработки автомобиля в Rover Land.</p>',
		),
		array(
			'type'       => 'promotion',
			'slug'       => 'shchetki-stekloochistiteley-v-podarok',
			'title'      => 'Стеклоочистители в подарок',
			'source_url' => 'https://roverland.ru/aktsii/shchetki-stekloochistiteley-v-podarok/',
			'summary'    => 'Получите подарок, если сумма заказ-наряда превышает 45 000 ₽.',
			'content'    => '<p>Если сумма заказ-наряда превышает 45 000 ₽, щётки ветрового стекла вы получаете в подарок. Работа по замене оплачивается отдельно.</p>',
		),
		array(
			'type'       => 'page',
			'slug'       => 'otziv',
			'title'      => 'Расскажите о своем визите в наш центр',
			'source_url' => 'https://roverland.ru/otziv/',
			'after'      => '[roverland_feedback_form]',
		),
		array(
			'type'       => 'page',
			'slug'       => 'partnerstvo',
			'title'      => 'Партнерская программа Rover Land',
			'source_url' => 'https://roverland.ru/partnerstvo/',
			'after'      => '[roverland_partner_form]',
		),
		array(
			'type'       => 'page',
			'slug'       => 'pokupka-avto-iz-korei-i-evropy',
			'title'      => 'Оригинальные автомобили Rover из Европы и Кореи',
			'source_url' => 'https://roverland.ru/pokupka-avto-iz-korei-i-evropy/',
			'appointment'=> true,
		),
	);

	$all_ready = true;

	foreach ( $items as $item ) {
		$result = roverland_seed_missing_legacy_item( $item );

		if ( is_wp_error( $result ) ) {
			$all_ready = false;
		}
	}

	if ( $all_ready ) {
		update_option( 'roverland_missing_pages_seed_version', $version, false );
	}

	delete_transient( 'roverland_missing_pages_seed_lock' );
}

function roverland_seed_missing_legacy_item( $item ) {
	$post_type = 'promotion' === $item['type'] ? 'promotion' : 'page';
	$existing  = get_page_by_path( $item['slug'], OBJECT, $post_type );

	if ( $existing ) {
		return (int) $existing->ID;
	}

	$legacy = roverland_seed_fetch_legacy_page(
		$item['source_url'],
		'promotion' === $item['type'] ? 'promotion' : 'page'
	);

	$title   = ! is_wp_error( $legacy ) && ! empty( $legacy['h1'] ) ? $legacy['h1'] : $item['title'];
	$content = ! is_wp_error( $legacy ) && ! empty( $legacy['content'] ) ? $legacy['content'] : ( $item['content'] ?? '' );

	if ( ! empty( $item['after'] ) ) {
		$content .= "\n\n" . $item['after'];
	}

	$post_id = wp_insert_post(
		wp_slash(
			array(
				'post_type'    => $post_type,
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_name'    => $item['slug'],
				'post_content' => 'page' === $post_type ? $content : '',
			)
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	$post_id = (int) $post_id;

	update_post_meta( $post_id, '_roverland_missing_page_seeded', current_time( 'mysql' ) );
	update_post_meta( $post_id, '_roverland_legacy_source_url', esc_url_raw( $item['source_url'] ) );

	if ( ! empty( $item['appointment'] ) ) {
		update_post_meta( $post_id, '_roverland_seed_append_appointment', '1' );
	}

	if ( ! is_wp_error( $legacy ) ) {
		if ( ! empty( $legacy['seo_title'] ) ) {
			update_post_meta( $post_id, 'rank_math_title', sanitize_text_field( $legacy['seo_title'] ) );
		}

		if ( ! empty( $legacy['seo_description'] ) ) {
			update_post_meta( $post_id, 'rank_math_description', sanitize_textarea_field( $legacy['seo_description'] ) );
		}
	}

	if ( 'promotion' === $post_type && function_exists( 'update_field' ) ) {
		$summary = ! empty( $item['summary'] ) ? $item['summary'] : roverland_seed_excerpt( wp_strip_all_tags( $content ), 180 );
		$promo_content = ! is_wp_error( $legacy ) && ! empty( $legacy['content'] ) ? $legacy['content'] : ( $item['content'] ?? '' );

		update_field( 'promotion_summary', $summary, $post_id );
		update_field( 'promotion_h1', $title, $post_id );
		update_field( 'promotion_content', $promo_content, $post_id );
		update_field(
			'promotion_note',
			'Все сведения и цены носят информационный характер и не являются публичной офертой. Актуальные условия уточняйте у менеджеров Rover Land.',
			$post_id
		);
		update_field( 'promotion_show_branches', 1, $post_id );
		update_field( 'promotion_show_appointment', 1, $post_id );

		if ( ! is_wp_error( $legacy ) && ! empty( $legacy['image_urls'][0] ) ) {
			$image_id = roverland_seed_sideload_image( $legacy['image_urls'][0], $post_id, $title );

			if ( $image_id ) {
				update_field( 'promotion_card_image', $image_id, $post_id );
				update_field( 'promotion_hero_image', $image_id, $post_id );
			}
		}
	}

	return $post_id;
}

function roverland_seed_fetch_legacy_page( $url, $mode = 'page' ) {
	$response = wp_remote_get(
		$url,
		array(
			'timeout'     => 12,
			'redirection' => 3,
			'user-agent'  => 'RoverLand WordPress legacy page seed/1.0',
		)
	);

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return new WP_Error( 'roverland_seed_fetch', 'Не удалось получить старую страницу: ' . $url );
	}

	$html = (string) wp_remote_retrieve_body( $response );

	if ( ! $html || ! class_exists( 'DOMDocument' ) ) {
		return new WP_Error( 'roverland_seed_dom', 'Не удалось разобрать старую страницу.' );
	}

	$previous = libxml_use_internal_errors( true );
	$dom      = new DOMDocument();
	$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
	$xpath = new DOMXPath( $dom );

	$h1s = $xpath->query( '//h1' );

	if ( ! $h1s || ! $h1s->length ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );
		return new WP_Error( 'roverland_seed_h1', 'H1 старой страницы не найден.' );
	}

	$target_h1 = 'promotion' === $mode && $h1s->length > 1
		? $h1s->item( $h1s->length - 1 )
		: $h1s->item( 0 );

	$h1 = roverland_seed_node_text( $target_h1 );

	$seo_title = '';
	$title_nodes = $xpath->query( '//title' );
	if ( $title_nodes && $title_nodes->length ) {
		$seo_title = roverland_seed_node_text( $title_nodes->item( 0 ) );
	}

	$seo_description = '';
	$meta_nodes = $xpath->query( '//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="description"]' );
	if ( $meta_nodes && $meta_nodes->length ) {
		$seo_description = trim( (string) $meta_nodes->item( 0 )->getAttribute( 'content' ) );
	}

	$started    = false;
	$fragments  = array();
	$image_urls = array();
	$seen       = array();
	$image_max  = 'promotion' === $mode ? 1 : 8;

	foreach ( $xpath->query( '//*' ) as $node ) {
		if ( $node === $target_h1 ) {
			$started = true;
			continue;
		}

		if ( ! $started || roverland_seed_node_is_chrome( $node ) ) {
			continue;
		}

		$tag  = strtolower( $node->nodeName );
		$text = roverland_seed_node_text( $node );

		if (
			in_array( $tag, array( 'h2', 'h3' ), true ) &&
			preg_match( '/^(наше расположение|станции технического обслуживания|o нас|о нас)$/iu', $text )
		) {
			break;
		}

		if ( 'promotion' === $mode && 'p' === $tag && preg_match( '/^адреса проведения акции/iu', $text ) ) {
			break;
		}

		if ( 'form' === $tag ) {
			continue;
		}

		if ( 'img' === $tag ) {
			if ( count( $image_urls ) >= $image_max ) {
				continue;
			}

			$src = roverland_seed_legacy_image_url( $node, $url );

			if ( ! $src || in_array( $src, $image_urls, true ) ) {
				continue;
			}

			$image_urls[] = $src;

			if ( 'page' === $mode ) {
				$image_id = roverland_seed_sideload_image( $src, 0, $h1 );
				if ( $image_id ) {
					$image_src = wp_get_attachment_image_url( $image_id, 'large' );
					if ( $image_src ) {
						$fragments[] = '<figure class="seeded-content-image"><img src="' . esc_url( $image_src ) . '" alt="' . esc_attr( $h1 ) . '" loading="lazy" decoding="async"></figure>';
					}
				}
			}

			continue;
		}

		if ( ! in_array( $tag, array( 'h2', 'h3', 'p', 'ul', 'ol' ), true ) ) {
			continue;
		}

		if ( '' === $text || roverland_seed_ignore_legacy_text( $text ) ) {
			continue;
		}

		$signature = mb_strtolower( preg_replace( '/\s+/u', ' ', $text ) );
		if ( isset( $seen[ $signature ] ) ) {
			continue;
		}
		$seen[ $signature ] = true;

		$fragment = $dom->saveHTML( $node );
		$fragment = wp_kses(
			$fragment,
			array(
				'h2'     => array(),
				'h3'     => array(),
				'p'      => array(),
				'ul'     => array(),
				'ol'     => array(),
				'li'     => array(),
				'strong' => array(),
				'b'      => array(),
				'em'     => array(),
				'i'      => array(),
				'br'     => array(),
				'a'      => array( 'href' => true, 'title' => true, 'target' => true, 'rel' => true ),
			)
		);

		if ( $fragment ) {
			$fragments[] = $fragment;
		}
	}

	libxml_clear_errors();
	libxml_use_internal_errors( $previous );

	return array(
		'h1'              => $h1,
		'content'         => implode( "\n", $fragments ),
		'image_urls'      => $image_urls,
		'seo_title'       => $seo_title,
		'seo_description' => $seo_description,
	);
}

function roverland_seed_node_text( $node ) {
	return trim(
		preg_replace(
			'/\s+/u',
			' ',
			html_entity_decode( (string) $node->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8' )
		)
	);
}

function roverland_seed_node_is_chrome( $node ) {
	for ( $parent = $node; $parent; $parent = $parent->parentNode ) {
		if ( ! $parent instanceof DOMElement ) {
			continue;
		}

		if ( in_array( strtolower( $parent->nodeName ), array( 'header', 'nav', 'footer' ), true ) ) {
			return true;
		}
	}

	return false;
}

function roverland_seed_ignore_legacy_text( $text ) {
	return (bool) preg_match(
		'/^(нажимая на кнопку|назад|вперёд|отмена|поделиться страницей|предпочитаемый адрес проведения работ)/iu',
		trim( (string) $text )
	);
}

function roverland_seed_legacy_image_url( $img, $source_url ) {
	$src = trim( (string) $img->getAttribute( 'data-src' ) );

	if ( ! $src ) {
		$src = trim( (string) $img->getAttribute( 'src' ) );
	}

	if (
		! $src ||
		false === strpos( $src, '/upload/' ) ||
		preg_match( '/(?:logo|icon|sprite|counter|pixel|captcha)/iu', $src )
	) {
		return '';
	}

	if ( 0 === strpos( $src, '//' ) ) {
		$src = 'https:' . $src;
	} elseif ( 0 === strpos( $src, '/' ) ) {
		$src = 'https://roverland.ru' . $src;
	} elseif ( ! preg_match( '#^https?://#i', $src ) ) {
		$src = trailingslashit( dirname( $source_url ) ) . ltrim( $src, '/' );
	}

	$host = strtolower( (string) wp_parse_url( $src, PHP_URL_HOST ) );

	return in_array( $host, array( 'roverland.ru', 'www.roverland.ru' ), true ) ? esc_url_raw( $src ) : '';
}

function roverland_seed_sideload_image( $url, $post_id = 0, $alt = '' ) {
	$existing = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_roverland_seed_source_url',
			'meta_value'     => $url,
			'no_found_rows'  => true,
		)
	);

	if ( $existing ) {
		return (int) $existing[0];
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$tmp = download_url( $url, 20 );

	if ( is_wp_error( $tmp ) ) {
		return 0;
	}

	$path = wp_parse_url( $url, PHP_URL_PATH );
	$file = array(
		'name'     => sanitize_file_name( wp_basename( $path ?: 'roverland-image.jpg' ) ),
		'tmp_name' => $tmp,
	);

	$attachment_id = media_handle_sideload( $file, $post_id );

	if ( is_wp_error( $attachment_id ) ) {
		@unlink( $tmp );
		return 0;
	}

	update_post_meta( $attachment_id, '_roverland_seed_source_url', esc_url_raw( $url ) );

	if ( $alt ) {
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
	}

	return (int) $attachment_id;
}

function roverland_seed_excerpt( $text, $length = 180 ) {
	$text = trim( preg_replace( '/\s+/u', ' ', (string) $text ) );

	if ( mb_strlen( $text ) <= $length ) {
		return $text;
	}

	return rtrim( mb_substr( $text, 0, $length - 1 ) ) . '…';
}
