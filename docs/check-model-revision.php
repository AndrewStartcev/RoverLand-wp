<?php
// Focused regression checks for source extraction; never writes content.
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) { return; }
$check = static function( $condition, $message ) { if ( ! $condition ) { WP_CLI::error( $message ); } WP_CLI::log( 'PASS: ' . $message ); };
$html = '<h1>Model</h1><p>Introduction</p><ul><li>One</li><li>Two</li></ul><p>One</p><h2>Price</h2><table><tr><th>Работа</th><th>Стоимость</th></tr><tr><td><p>Oil</p></td><td>10<br>20</td></tr><tr><td><table><tr><td>Nested name</td></tr></table></td><td>30</td></tr></table><p>* Все сведения, включая указанные на сайте цены, носят информационный характер.</p><form></form>';
$parsed = roverland_content_migration_parse_html( $html, 'https://roverland.ru/defender/diagnostika/', 'model_revision' );
$tables = array_values( array_filter( $parsed['sections'], static function( $s ) { return 'matrix_table' === $s['acf_fc_layout']; } ) );
$check( 1 === count( $tables ), 'One source table stays one table' );
$check( 'Услуга' === $tables[0]['first_header'], 'Work column is renamed to service' );
$check( 2 === count( $tables[0]['rows'] ), 'Nested table does not create a duplicate row' );
$check( "10\n20" === $tables[0]['rows'][0]['cells'][0]['value'], 'Explicit cell line breaks survive' );
$check( roverland_price_disclaimer() === $tables[0]['note'], 'Complete legal note belongs to table' );
$text = implode( '', array_column( $parsed['sections'], 'content' ) );
$check( 1 === substr_count( $text, 'One' ) && false === strpos( $text, 'Oil' ), 'List items and table paragraphs are not duplicated as body text' );
$check( '' === $parsed['lead'], 'Legal note is never extracted into hero lead' );
WP_CLI::success( 'Model source parser checks passed.' );
$check( roverland_is_consultation_section( array( 'content' => '<p>Если вы не нашли в списке своего пробега, пожалуйста свяжитесь с нами для консультации.</p>' ) ), 'Standalone mileage notice is a consultation callout' );
$check( ! roverland_is_consultation_section( array( 'title' => 'Описание услуги', 'content' => '<p>Если вы не нашли своего пробега, свяжитесь с нами.</p>' ) ), 'Editorial sections with headings keep their layout' );
$check( ! roverland_is_consultation_section( array( 'content' => '<p>Автомобиль Discovery надежен. Если требуется ремонт, обращайтесь в наш автосервис.</p>' ) ), 'Service introduction is not mistaken for a consultation block' );
