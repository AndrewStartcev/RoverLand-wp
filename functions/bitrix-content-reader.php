<?php

defined( 'ABSPATH' ) || exit;

/**
 * Targeted Bitrix SQL reader.
 *
 * This intentionally reads only the tables required for portfolio migration.
 * It never imports service requests, users or other private Bitrix data.
 */

function roverland_bitrix_migration_dir() {
	$upload = wp_upload_dir();
	$dir    = trailingslashit( $upload['basedir'] ) . 'roverland-content-migration';

	if ( ! is_dir( $dir ) ) {
		wp_mkdir_p( $dir );
	}

	return $dir;
}

function roverland_bitrix_portfolio_cache_file() {
	return trailingslashit( roverland_bitrix_migration_dir() ) . 'portfolio.json';
}

function roverland_bitrix_load_portfolio_cache() {
	$file = roverland_bitrix_portfolio_cache_file();

	if ( ! is_readable( $file ) ) {
		return array();
	}

	$data = json_decode( file_get_contents( $file ), true );

	return is_array( $data ) && ! empty( $data['items'] ) && is_array( $data['items'] ) ? $data : array();
}

function roverland_bitrix_prepare_portfolio_from_upload( $file ) {
	if ( empty( $file['tmp_name'] ) || UPLOAD_ERR_OK !== (int) $file['error'] ) {
		return new WP_Error( 'roverland_bitrix_upload', 'SQL-дамп не был загружен.' );
	}

	$name = sanitize_file_name( (string) $file['name'] );
	$ext  = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );

	if ( ! in_array( $ext, array( 'zip', 'sql' ), true ) ) {
		return new WP_Error( 'roverland_bitrix_type', 'Поддерживаются только .zip и .sql.' );
	}

	if ( (int) $file['size'] > 120 * MB_IN_BYTES ) {
		return new WP_Error( 'roverland_bitrix_size', 'Дамп больше 120 МБ.' );
	}

	$sql = '';

	if ( 'zip' === $ext ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new WP_Error( 'roverland_bitrix_zip', 'На сервере нет ZipArchive. Загрузите распакованный .sql.' );
		}

		$zip = new ZipArchive();

		if ( true !== $zip->open( $file['tmp_name'] ) ) {
			return new WP_Error( 'roverland_bitrix_zip_open', 'Не удалось открыть ZIP.' );
		}

		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$entry = $zip->getNameIndex( $i );

			if ( $entry && 'sql' === strtolower( pathinfo( $entry, PATHINFO_EXTENSION ) ) ) {
				$sql = $zip->getFromIndex( $i );
				break;
			}
		}

		$zip->close();
	} else {
		$sql = file_get_contents( $file['tmp_name'] );
	}

	if ( ! $sql ) {
		return new WP_Error( 'roverland_bitrix_sql_empty', 'В файле не найден SQL-дамп.' );
	}

	$sections = roverland_bitrix_sql_table( $sql, 'b_iblock_section' );
	$elements = roverland_bitrix_sql_table( $sql, 'b_iblock_element' );
	$links    = roverland_bitrix_sql_table( $sql, 'b_iblock_section_element' );
	$files    = roverland_bitrix_sql_table( $sql, 'b_file' );

	if ( is_wp_error( $sections ) || is_wp_error( $elements ) || is_wp_error( $links ) || is_wp_error( $files ) ) {
		return new WP_Error( 'roverland_bitrix_parse', 'Не удалось прочитать необходимые таблицы Bitrix.' );
	}

	$files_by_id    = array();
	$elements_by_id = array();
	$links_by_section = array();

	foreach ( $files as $row ) {
		$files_by_id[ (int) $row['ID'] ] = $row;
	}

	foreach ( $elements as $row ) {
		$elements_by_id[ (int) $row['ID'] ] = $row;
	}

	foreach ( $links as $row ) {
		$section_id = (int) $row['IBLOCK_SECTION_ID'];
		$links_by_section[ $section_id ][] = (int) $row['IBLOCK_ELEMENT_ID'];
	}

	$items = array();

	foreach ( $sections as $section ) {
		if ( 8 !== (int) ( $section['IBLOCK_ID'] ?? 0 ) || 'Y' !== ( $section['ACTIVE'] ?? '' ) ) {
			continue;
		}

		$section_id = (int) $section['ID'];
		$gallery    = array();

		foreach ( $links_by_section[ $section_id ] ?? array() as $element_id ) {
			if ( empty( $elements_by_id[ $element_id ] ) ) {
				continue;
			}

			$element = $elements_by_id[ $element_id ];

			if ( 'Y' !== ( $element['ACTIVE'] ?? '' ) ) {
				continue;
			}

			$file_id = (int) ( $element['DETAIL_PICTURE'] ?: $element['PREVIEW_PICTURE'] );

			if ( ! $file_id || empty( $files_by_id[ $file_id ] ) ) {
				continue;
			}

			$gallery[] = array(
				'element_id' => $element_id,
				'name'       => (string) $element['NAME'],
				'sort'       => (int) ( $element['SORT'] ?: 500 ),
				'file'       => roverland_bitrix_file_data( $files_by_id[ $file_id ] ),
			);
		}

		usort(
			$gallery,
			static function ( $a, $b ) {
				if ( $a['sort'] === $b['sort'] ) {
					return $a['element_id'] <=> $b['element_id'];
				}

				return $a['sort'] <=> $b['sort'];
			}
		);

		$cover_id = (int) ( $section['PICTURE'] ?: $section['DETAIL_PICTURE'] );
		$cover    = $cover_id && ! empty( $files_by_id[ $cover_id ] )
			? roverland_bitrix_file_data( $files_by_id[ $cover_id ] )
			: null;

		$items[] = array(
			'legacy_id'        => $section_id,
			'name'             => (string) $section['NAME'],
			'code'             => (string) $section['CODE'],
			'sort'             => (int) ( $section['SORT'] ?: 500 ),
			'description_html' => (string) ( $section['DESCRIPTION'] ?? '' ),
			'source_url'       => 'https://roverland.ru/portfolio/' . $section_id . '/',
			'cover'            => $cover,
			'gallery'          => $gallery,
		);
	}

	usort(
		$items,
		static function ( $a, $b ) {
			if ( $a['sort'] === $b['sort'] ) {
				return $a['legacy_id'] <=> $b['legacy_id'];
			}

			return $a['sort'] <=> $b['sort'];
		}
	);

	$data = array(
		'generated_at' => current_time( 'mysql' ),
		'iblock_id'    => 8,
		'items'        => $items,
	);

	$written = file_put_contents(
		roverland_bitrix_portfolio_cache_file(),
		wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
	);

	if ( false === $written ) {
		return new WP_Error( 'roverland_bitrix_cache', 'Не удалось записать кеш портфолио.' );
	}

	return $data;
}

function roverland_bitrix_file_data( $row ) {
	$subdir = trim( (string) ( $row['SUBDIR'] ?? '' ), '/' );
	$name   = (string) ( $row['FILE_NAME'] ?? '' );
	$path   = '/upload/' . ( $subdir ? $subdir . '/' : '' ) . $name;

	return array(
		'id'            => (int) $row['ID'],
		'path'          => $path,
		'url'           => 'https://roverland.ru' . $path,
		'original_name' => (string) ( $row['ORIGINAL_NAME'] ?? '' ),
		'width'         => (int) ( $row['WIDTH'] ?? 0 ),
		'height'        => (int) ( $row['HEIGHT'] ?? 0 ),
		'content_type'  => (string) ( $row['CONTENT_TYPE'] ?? '' ),
	);
}

function roverland_bitrix_sql_table( $sql, $table ) {
	$sql    = (string) $sql;
	$table  = (string) $table;
	$needle = 'INSERT INTO `' . $table . '`';
	$offset = 0;
	$result = array();
	$length = strlen( $sql );

	while ( $offset < $length ) {
		$start = strpos( $sql, $needle, $offset );

		if ( false === $start ) {
			break;
		}

		$values_pos = strpos( $sql, ' VALUES', $start );

		if ( false === $values_pos ) {
			break;
		}

		$header = substr( $sql, $start, $values_pos - $start );
		preg_match_all( '/`([^`]+)`/', $header, $matches );
		$columns = isset( $matches[1] ) ? array_slice( $matches[1], 1 ) : array();

		if ( ! $columns ) {
			return new WP_Error( 'roverland_bitrix_columns', 'Не удалось определить колонки ' . $table . '.' );
		}

		$body_start = $values_pos + 7;
		$body_end   = roverland_bitrix_sql_statement_end( $sql, $body_start );

		if ( false === $body_end ) {
			break;
		}

		foreach ( roverland_bitrix_sql_values( substr( $sql, $body_start, $body_end - $body_start ) ) as $row ) {
			if ( count( $row ) === count( $columns ) ) {
				$result[] = array_combine( $columns, $row );
			}
		}

		$offset = $body_end + 1;
	}

	return $result;
}

function roverland_bitrix_sql_statement_end( $sql, $start ) {
	$quoted = false;
	$escape = false;
	$length = strlen( $sql );

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
		} elseif ( ';' === $char ) {
			return $i;
		}
	}

	return false;
}

function roverland_bitrix_sql_values( $input ) {
	$rows         = array();
	$row          = array();
	$value        = '';
	$depth        = 0;
	$quoted       = false;
	$escape       = false;
	$value_quoted = false;

	for ( $i = 0, $length = strlen( $input ); $i < $length; $i++ ) {
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
				$row[]        = roverland_bitrix_sql_scalar( $value, $value_quoted );
				$rows[]       = $row;
				$value        = '';
				$value_quoted = false;
			} else {
				$value .= $char;
			}

			continue;
		}

		if ( ',' === $char && 1 === $depth ) {
			$row[]        = roverland_bitrix_sql_scalar( $value, $value_quoted );
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

function roverland_bitrix_sql_scalar( $value, $quoted ) {
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
