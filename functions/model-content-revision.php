<?php
defined( 'ABSPATH' ) || exit;

function roverland_price_disclaimer() {
	return '* Все сведения, включая указанные на сайте цены, носят информационный характер и не являются публичной офертой, определяемой положениями статьи 437 Гражданского кодекса Российской Федерации. Фактическая стоимость товаров и услуг может отличаться от размещённой на сайте. Подробную информацию и актуальные цены уточняйте по телефону у наших менеджеров.';
}

function roverland_revision_stat( $item ) {
	$label = mb_strtolower( $item['label'] ?? '' );
	if ( false !== mb_strpos( $label, 'клиент' ) ) {
		$item['value'] = '30 000+';
	} elseif ( false !== mb_strpos( $label, 'гаранти' ) ) {
		$item['label'] = 'Гарантия 1 год на все работы';
		$item['description'] = 'Сохранение заводской гарантии';
	} elseif ( false !== mb_strpos( $label, 'опыт' ) ) {
		$item['description'] = 'Обслуживаем Land Rover с 1998 года';
	}
	return $item;
}

/** Keep line breaks in source cells while storing plain text in ACF. */
function roverland_revision_cell_text( $node ) {
	$copy = $node->cloneNode( true );
	$xpath = new DOMXPath( $copy->ownerDocument );
	foreach ( $xpath->query( './/br', $copy ) as $br ) {
		$br->parentNode->replaceChild( $copy->ownerDocument->createTextNode( "\n" ), $br );
	}
	$text = html_entity_decode( $copy->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	return trim( preg_replace( '/[^\S\n]+/u', ' ', $text ) );
}

/** Preserve the source column order, including mileage and oil specifications. */
function roverland_revision_model_table( $table ) {
	$rows = array();
	foreach ( $table->getElementsByTagName( 'tr' ) as $tr ) {
		$cells = array();
		foreach ( $tr->childNodes as $cell ) {
			if ( $cell instanceof DOMElement && in_array( strtolower( $cell->nodeName ), array( 'th', 'td' ), true ) ) {
				$cells[] = roverland_revision_cell_text( $cell );
			}
		}
		if ( $cells ) { $rows[] = $cells; }
	}
	if ( count( $rows ) < 2 ) { return null; }
	$headers = array_shift( $rows );
	$first = array_shift( $headers );
	if ( in_array( mb_strtolower( trim( $first ) ), array( 'работа', 'наименование работ' ), true ) ) { $first = 'Услуга'; }
	return array(
		'acf_fc_layout' => 'matrix_table', 'title' => '', 'first_header' => $first,
		'headers' => array_map( static function( $value ) { return array( 'label' => $value ); }, $headers ),
		'rows' => array_map( static function( $row ) {
			return array( 'name' => array_shift( $row ), 'cells' => array_map( static function( $value ) { return array( 'value' => $value ); }, $row ) );
		}, $rows ),
		'note' => roverland_price_disclaimer(),
	);
}
