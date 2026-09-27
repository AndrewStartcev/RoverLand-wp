<?php

defined( 'ABSPATH' ) || exit;

function roverland_legacy_normalize_path( $url ) {
	$path = wp_parse_url( trim( (string) $url ), PHP_URL_PATH );
	$path = $path ? '/' . ltrim( $path, '/' ) : '/';

	$path = preg_replace( '#/index\.php$#i', '/', $path );
	$path = preg_replace( '#/+#', '/', $path );

	return $path ?: '/';
}

function roverland_legacy_cache_dir() {
	$upload = wp_upload_dir();
	$dir    = trailingslashit( $upload['basedir'] ) . 'roverland-legacy';

	if ( ! is_dir( $dir ) ) {
		wp_mkdir_p( $dir );
	}

	return $dir;
}

function roverland_legacy_cache_file() {
	return trailingslashit( roverland_legacy_cache_dir() ) . 'legacy-pages.json';
}

function roverland_legacy_source_file() {
	$zip = trailingslashit( roverland_legacy_cache_dir() ) . 'source.zip';
	$sql = trailingslashit( roverland_legacy_cache_dir() ) . 'source.sql';

	if ( is_readable( $zip ) ) {
		return $zip;
	}

	return is_readable( $sql ) ? $sql : '';
}

function roverland_legacy_load_cache() {
	$file = roverland_legacy_cache_file();

	if ( ! is_readable( $file ) ) {
		return array();
	}

	$data = json_decode( file_get_contents( $file ), true );

	if ( ! is_array( $data ) || empty( $data['pages'] ) || ! is_array( $data['pages'] ) ) {
		return array();
	}

	return $data;
}

function roverland_legacy_index() {
	static $index = null;

	if ( null !== $index ) {
		return $index;
	}

	$index = array();
	$data  = roverland_legacy_load_cache();

	foreach ( isset( $data['pages'] ) ? $data['pages'] : array() as $page ) {
		if ( empty( $page['url'] ) ) {
			continue;
		}

		$index[ roverland_legacy_normalize_path( $page['url'] ) ] = $page;
	}

	return $index;
}

function roverland_legacy_source_for_page( $page_id ) {
	$page_id = (int) $page_id;

	if ( ! $page_id ) {
		return null;
	}

	$source = trim( (string) roverland_field( 'legacy_source_url', '', $page_id ) );

	if ( ! $source ) {
		$source = get_permalink( $page_id );
	}

	$path  = roverland_legacy_normalize_path( $source );
	$index = roverland_legacy_index();

	if ( isset( $index[ $path ] ) ) {
		return $index[ $path ];
	}

	$aliases = array(
		'/discovery-sport/' => '/discovery-sports/',
		'/discovery-sports/' => '/discovery-sport/',
		'/remont/rulevoy-reyki/' => '/remont/remont-rejki/',
		'/remont/remont-rejki/' => '/remont/rulevoy-reyki/',
	);

	if ( isset( $aliases[ $path ] ) && isset( $index[ $aliases[ $path ] ] ) ) {
		return $index[ $aliases[ $path ] ];
	}

	return null;
}

function roverland_legacy_sql_extract( $sql ) {
	$sql = (string) $sql;

	if ( '' === $sql ) {
		return new WP_Error( 'roverland_legacy_empty_sql', 'SQL-файл пуст.' );
	}

	$table_info = roverland_legacy_detect_search_table( $sql );

	if ( is_wp_error( $table_info ) ) {
		return $table_info;
	}

	$table_name      = $table_info['name'];
	$default_columns = $table_info['columns'];
	$pages           = array();
	$offset          = 0;
	$insert_count    = 0;
	$row_count       = 0;
	$main_count      = 0;
	$public_count    = 0;
	$sql_length      = strlen( $sql );

	while ( $offset < $sql_length ) {
		$insert_pos = stripos( $sql, 'INSERT INTO', $offset );

		if ( false === $insert_pos ) {
			break;
		}

		$values_pos = stripos( $sql, 'VALUES', $insert_pos );

		if ( false === $values_pos ) {
			break;
		}

		$header = substr( $sql, $insert_pos, $values_pos - $insert_pos );

		if ( ! roverland_legacy_insert_targets_table( $header, $table_name ) ) {
			$offset = $values_pos + 6;
			continue;
		}

		$insert_count++;
		$columns = roverland_legacy_insert_columns( $header );

		if ( ! $columns ) {
			$columns = $default_columns;
		}

		if ( ! $columns ) {
			return new WP_Error(
				'roverland_legacy_no_columns',
				'Таблица ' . $table_name . ' найдена, но не удалось определить порядок колонок.'
			);
		}

		$body_start = $values_pos + 6;
		$body_end   = roverland_legacy_find_statement_end( $sql, $body_start );

		if ( false === $body_end ) {
			break;
		}

		$rows = roverland_legacy_parse_values( substr( $sql, $body_start, $body_end - $body_start ) );

		foreach ( $rows as $row ) {
			$row_count++;

			if ( count( $row ) !== count( $columns ) ) {
				continue;
			}

			$item = array_combine( $columns, $row );

			if ( ! is_array( $item ) ) {
				continue;
			}

			$module = trim( (string) ( $item['MODULE_ID'] ?? '' ) );

			if ( 'main' !== $module ) {
				continue;
			}

			$main_count++;

			$url = trim( (string) ( $item['URL'] ?? '' ) );

			if (
				! $url ||
				'/' !== substr( $url, 0, 1 ) ||
				false !== strpos( $url, '=' ) ||
				preg_match( '/index\(\d+\)\.php$/i', $url )
			) {
				continue;
			}

			$public_count++;
			$url  = roverland_legacy_normalize_path( $url );
			$body = roverland_legacy_clean_text( $item['BODY'] ?? '' );

			if ( '' === $body ) {
				continue;
			}

			$page = array(
				'source_id'   => (int) ( $item['ID'] ?? 0 ),
				'url'         => $url,
				'title'       => roverland_legacy_clean_text( $item['TITLE'] ?? '' ),
				'body'        => $body,
				'date_change' => (string) ( $item['DATE_CHANGE'] ?? '' ),
			);

			if ( ! isset( $pages[ $url ] ) ) {
				$pages[ $url ] = $page;
				continue;
			}

			$current = $pages[ $url ];

			if (
				$page['date_change'] > ( $current['date_change'] ?? '' ) ||
				(
					$page['date_change'] === ( $current['date_change'] ?? '' ) &&
					strlen( $page['body'] ) > strlen( $current['body'] ?? '' )
				)
			) {
				$pages[ $url ] = $page;
			}
		}

		$offset = $body_end + 1;
	}

	if ( ! $pages ) {
		return new WP_Error(
			'roverland_legacy_no_pages',
			sprintf(
				'Таблица %1$s найдена, но публичные страницы не извлечены. INSERT: %2$d, строк: %3$d, MODULE_ID=main: %4$d, публичных URL: %5$d.',
				$table_name,
				$insert_count,
				$row_count,
				$main_count,
				$public_count
			)
		);
	}

	ksort( $pages );

	return array_values( $pages );
}

function roverland_legacy_detect_search_table( $sql ) {
	if ( ! preg_match_all(
		'/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?(?:`([^`]+)`|([a-zA-Z0-9_]+))\s*\((.*?)\)\s*(?:ENGINE|TYPE|;)/isu',
		$sql,
		$matches,
		PREG_SET_ORDER
	) ) {
		return new WP_Error( 'roverland_legacy_no_tables', 'В дампе не найдены определения CREATE TABLE.' );
	}

	foreach ( $matches as $match ) {
		$name = ! empty( $match[1] ) ? $match[1] : $match[2];

		if ( ! preg_match( '/(?:^|_)b_search_content$/i', $name ) && ! preg_match( '/search_content$/i', $name ) ) {
			continue;
		}

		$columns = array();

		foreach ( preg_split( '/\r?\n/', $match[3] ) as $line ) {
			$line = trim( $line );

			if ( preg_match( '/^`([^`]+)`\s+/u', $line, $column_match ) ) {
				$columns[] = $column_match[1];
			} elseif ( preg_match( '/^([a-zA-Z0-9_]+)\s+(?:int|bigint|varchar|char|text|mediumtext|longtext|datetime|date|timestamp|float|double|decimal|tinyint|smallint|mediumint|blob|mediumblob|longblob)\b/i', $line, $column_match ) ) {
				$columns[] = $column_match[1];
			}
		}

		return array(
			'name'    => $name,
			'columns' => $columns,
		);
	}

	return new WP_Error( 'roverland_legacy_no_search_table', 'В дампе не найдена таблица b_search_content (или таблица с префиксом, оканчивающаяся на search_content).' );
}

function roverland_legacy_insert_targets_table( $header, $table_name ) {
	if ( ! preg_match( '/INSERT\s+INTO\s+(?:`([^`]+)`|([a-zA-Z0-9_]+))/iu', $header, $match ) ) {
		return false;
	}

	$name = ! empty( $match[1] ) ? $match[1] : $match[2];

	return 0 === strcasecmp( $name, $table_name );
}

function roverland_legacy_insert_columns( $header ) {
	if ( ! preg_match( '/INSERT\s+INTO\s+(?:`[^`]+`|[a-zA-Z0-9_]+)\s*\((.*?)\)\s*$/isu', trim( $header ), $match ) ) {
		return array();
	}

	preg_match_all( '/`([^`]+)`|\b([a-zA-Z_][a-zA-Z0-9_]*)\b/', $match[1], $matches, PREG_SET_ORDER );

	$columns = array();

	foreach ( $matches as $column_match ) {
		$name = ! empty( $column_match[1] ) ? $column_match[1] : $column_match[2];

		if ( $name ) {
			$columns[] = $name;
		}
	}

	return $columns;
}

function roverland_legacy_find_statement_end( $sql, $start ) {
	$length = strlen( $sql );
	$quoted = false;
	$escape = false;

	for ( $i = (int) $start; $i < $length; $i++ ) {
		$char = $sql[ $i ];

		if ( $quoted ) {
			if ( $escape ) {
				$escape = false;
			} elseif ( '\\' === $char ) {
				$escape = true;
			} elseif ( "'" === $char ) {
				$quoted = false;
			}

			continue;
		}

		if ( "'" === $char ) {
			$quoted = true;
			continue;
		}

		if ( ';' === $char ) {
			return $i;
		}
	}

	return false;
}

function roverland_legacy_parse_values( $input ) {
	$rows         = array();
	$row          = array();
	$value        = '';
	$depth        = 0;
	$quoted       = false;
	$escape       = false;
	$value_quoted = false;
	$length       = strlen( $input );

	for ( $i = 0; $i < $length; $i++ ) {
		$char = $input[ $i ];

		if ( $quoted ) {
			if ( $escape ) {
				$map   = array( 'n' => "\n", 'r' => "\r", 't' => "\t", '0' => "\0" );
				$value .= isset( $map[ $char ] ) ? $map[ $char ] : $char;
				$escape = false;
			} elseif ( '\\' === $char ) {
				$escape = true;
			} elseif ( "'" === $char ) {
				$quoted = false;
			} else {
				$value .= $char;
			}

			continue;
		}

		if ( "'" === $char ) {
			$quoted       = true;
			$value_quoted = true;
			continue;
		}

		if ( '(' === $char ) {
			if ( 0 === $depth ) {
				$row          = array();
				$value        = '';
				$value_quoted = false;
			} else {
				$value .= $char;
			}

			$depth++;
			continue;
		}

		if ( ')' === $char && $depth > 0 ) {
			$depth--;

			if ( 0 === $depth ) {
				$row[]  = roverland_legacy_sql_value( $value, $value_quoted );
				$rows[] = $row;
				$value  = '';
				$value_quoted = false;
			} else {
				$value .= $char;
			}

			continue;
		}

		if ( ',' === $char && 1 === $depth ) {
			$row[]        = roverland_legacy_sql_value( $value, $value_quoted );
			$value        = '';
			$value_quoted = false;
			continue;
		}

		if ( $depth > 0 ) {
			$value .= $char;
		}
	}

	return $rows;
}

function roverland_legacy_sql_value( $value, $quoted ) {
	$value = trim( (string) $value );

	if ( $quoted ) {
		return $value;
	}

	if ( 'NULL' === strtoupper( $value ) ) {
		return null;
	}

	if ( preg_match( '/^-?\d+$/', $value ) ) {
		return (int) $value;
	}

	if ( preg_match( '/^-?\d+\.\d+$/', $value ) ) {
		return (float) $value;
	}

	return $value;
}

function roverland_legacy_clean_text( $text ) {
	$text = html_entity_decode( (string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = str_replace( array( "\r\n", "\r" ), "\n", $text );
	$text = preg_replace( '/[ \t]+/u', ' ', $text );
	$text = preg_replace( '/\n[ \t]+/u', "\n", $text );
	$text = preg_replace( '/\n{3,}/u', "\n\n", $text );

	return trim( $text );
}

function roverland_legacy_content_to_sections( $body, $page_title = '' ) {
	$body  = roverland_legacy_clean_text( $body );
	$lines = preg_split( '/\n+/u', $body );
	$lines = array_values( array_filter( array_map( 'trim', $lines ), 'strlen' ) );

	if ( ! $lines ) {
		return array();
	}

	if ( $page_title && roverland_legacy_similar_text( $lines[0], $page_title ) ) {
		array_shift( $lines );
	}

	$lines = array_values(
		array_filter(
			$lines,
			static function ( $line ) {
				return ! preg_match( '/^(поделиться страницей|записаться(?:\s|$)|©\s*vinogradov)/iu', $line );
			}
		)
	);

	if ( ! $lines ) {
		return array();
	}

	$maintenance = roverland_legacy_parse_maintenance_sections( $lines );

	if ( $maintenance ) {
		return $maintenance;
	}

	$sections = array();
	$current  = array(
		'title'      => '',
		'paragraphs' => array(),
	);

	foreach ( $lines as $index => $line ) {
		$next       = isset( $lines[ $index + 1 ] ) ? $lines[ $index + 1 ] : '';
		$is_heading = roverland_legacy_is_heading( $line, $next );

		if ( $is_heading ) {
			if ( $current['paragraphs'] ) {
				$sections[] = roverland_legacy_text_section( $current['title'], $current['paragraphs'] );
			}

			$current = array(
				'title'      => $line,
				'paragraphs' => array(),
			);
			continue;
		}

		$current['paragraphs'][] = $line;

		if ( ! $current['title'] && count( $current['paragraphs'] ) >= 3 && strlen( implode( ' ', $current['paragraphs'] ) ) > 1500 ) {
			$sections[] = roverland_legacy_text_section( '', $current['paragraphs'] );
			$current    = array( 'title' => '', 'paragraphs' => array() );
		}
	}

	if ( $current['paragraphs'] ) {
		$sections[] = roverland_legacy_text_section( $current['title'], $current['paragraphs'] );
	}

	return array_values(
		array_filter(
			$sections,
			static function ( $section ) {
				return ! empty( $section['content'] ) || ! empty( $section['rows'] );
			}
		)
	);
}

function roverland_legacy_text_section( $title, $paragraphs ) {
	$html = '';

	foreach ( $paragraphs as $paragraph ) {
		$paragraph = trim( (string) $paragraph );

		if ( '' === $paragraph ) {
			continue;
		}

		$html .= '<p>' . esc_html( $paragraph ) . '</p>';
	}

	return array(
		'acf_fc_layout' => 'text',
		'title'         => trim( (string) $title ),
		'content'       => $html,
		'image'         => 0,
		'image_position'=> 'none',
		'background'    => 'white',
	);
}

function roverland_legacy_is_heading( $line, $next = '' ) {
	$line = trim( (string) $line );

	if ( mb_strlen( $line ) < 4 || mb_strlen( $line ) > 105 ) {
		return false;
	}

	if ( preg_match( '/(?:[.!?;:]|₽)$/u', $line ) || preg_match( '/\d{3,}/u', $line ) ) {
		return false;
	}

	if ( preg_match( '/^(цены|стоимость|преимущества|поломки|распространенные|почему|характеристика|ремонт|диагностика|техническое обслуживание)/iu', $line ) ) {
		return true;
	}

	if ( $next && mb_strlen( $next ) > 150 ) {
		return true;
	}

	return false;
}

function roverland_legacy_similar_text( $a, $b ) {
	$normalize = static function ( $value ) {
		$value = mb_strtolower( wp_strip_all_tags( (string) $value ) );
		$value = preg_replace( '/[^\p{L}\p{N}]+/u', ' ', $value );
		return trim( preg_replace( '/\s+/u', ' ', $value ) );
	};

	$a = $normalize( $a );
	$b = $normalize( $b );

	return $a && $b && ( $a === $b || false !== strpos( $a, $b ) || false !== strpos( $b, $a ) );
}

function roverland_legacy_parse_maintenance_sections( $lines ) {
	$has_to = false;

	foreach ( $lines as $line ) {
		if ( preg_match( '/^ТО\s+\d/u', $line ) ) {
			$has_to = true;
			break;
		}
	}

	if ( ! $has_to ) {
		return array();
	}

	$sections = array();
	$count    = count( $lines );
	$i        = 0;

	while ( $i < $count ) {
		if ( ! preg_match( '/(?:дизель|бензин)/iu', $lines[ $i ] ) ) {
			$i++;
			continue;
		}

		$title = $lines[ $i ];
		$i++;

		while ( $i < $count && 'Разбивка' !== $lines[ $i ] ) {
			$i++;
		}

		if ( $i >= $count ) {
			break;
		}

		$i++;
		$headers = array();

		while ( $i < $count && ! preg_match( '/^ТО\s+\d/u', $lines[ $i ] ) ) {
			if ( preg_match( '/(?:сохранение|гарантия|обслуживаем|записаться)/iu', $lines[ $i ] ) ) {
				break;
			}
			$headers[] = $lines[ $i ];
			$i++;
		}

		if ( count( $headers ) < 2 ) {
			continue;
		}

		$rows = array();

		$stop_rows = false;

		while ( $i < $count && preg_match( '/^ТО\s+\d/u', $lines[ $i ] ) ) {
			$name = $lines[ $i ];
			$i++;
			$payload = array();

			while ( $i < $count && ! preg_match( '/^ТО\s+\d/u', $lines[ $i ] ) && ! preg_match( '/(?:дизель|бензин)/iu', $lines[ $i ] ) ) {
				if ( preg_match( '/(?:сохранение|гарантия|обслуживаем|если вы не нашли|\* все сведения|записаться)/iu', $lines[ $i ] ) ) {
					$stop_rows = true;
					break;
				}
				$payload[] = $lines[ $i ];
				$i++;
			}

			$cell_count = count( $headers );
			$price_count = max( 1, $cell_count - 1 );
			$prices = array();

			while ( $payload && count( $prices ) < $price_count ) {
				$candidate = end( $payload );
				if ( ! preg_match( '/\d/u', $candidate ) ) {
					break;
				}
				array_unshift( $prices, array_pop( $payload ) );
			}

			$cells = array(
				array( 'value' => implode( ', ', $payload ) ),
			);

			foreach ( $prices as $price ) {
				$cells[] = array( 'value' => $price );
			}

			while ( count( $cells ) < $cell_count ) {
				$cells[] = array( 'value' => '' );
			}

			$rows[] = array(
				'name'  => $name,
				'cells' => $cells,
			);

			if ( $stop_rows ) {
				break;
			}
		}

		if ( $rows ) {
			$sections[] = array(
				'acf_fc_layout' => 'matrix_table',
				'title'         => $title,
				'headers'       => array_map(
					static function ( $label ) {
						return array( 'label' => $label );
					},
					$headers
				),
				'rows'          => $rows,
				'note'          => '',
			);
		}
	}

	return $sections;
}

function roverland_legacy_excerpt( $body, $length = 260 ) {
	$text = preg_replace( '/\s+/u', ' ', roverland_legacy_clean_text( $body ) );
	$text = trim( (string) $text );

	if ( mb_strlen( $text ) <= $length ) {
		return $text;
	}

	return rtrim( mb_substr( $text, 0, $length - 1 ) ) . '…';
}
