<?php
define('ABSPATH','/');
function add_filter(...$args){}
require dirname(__DIR__) . '/functions/usability.php';
function check($condition,$message){if(!$condition) throw new Exception($message);}
$input='<p>Сохраняем вступление</p><table id="prices"><thead><tr><th>Работа</th><th>Цена</th></tr></thead><tbody><tr><td>До категорий</td><td>100</td></tr><tr><th colspan="2">Двигатель &amp; КПП</th></tr><tr><td>Замена масла</td><td>1 500 ₽</td></tr><tr><th colspan="2">Тормоза</th></tr><tr><td>Замена колодок</td><td>2 000 ₽</td></tr></tbody></table>';
$output=roverland_usability_price_tables($input);
check(substr_count($output,'<details')===2,'Two native categories');
foreach(['Сохраняем вступление','До категорий','Замена масла','1 500 ₽','Замена колодок','2 000 ₽'] as $text) check(substr_count($output,$text)===1,'Missing or duplicated '.$text);
check(strpos($output,'Двигатель &amp; КПП')!==false,'Escaped group title');
check(strpos($output,'<script')===false,'No JS required');
$plain='<table><tr><td>Текст</td><td>12</td></tr></table>';
check(roverland_usability_price_tables($plain)===$plain,'Ungrouped table unchanged');
check(roverland_usability_label('50К+',0,['name'=>'value'])==='50 000+','Cyrillic short number');
check(roverland_usability_label('Наши возможности',0,['name'=>'home_services_title'])==='Услуги и возможности Rover Land','Home label');
check(roverland_usability_label('Специализированный сервис Ленд Ровер и Ягуар в Москве!',0,['name'=>'home_hero_title_first'])==='Специализированный сервис Ленд Ровер и Ягуар в Москве!','Hero preserved');
echo "PASS: native grouped HTML, Unicode, values exactly once, ungrouped tables and hero preservation\n";
$matrix=['first_header'=>'Диагностика автомобиля','rows'=>[['name'=>'Осмотр','cells'=>[['value'=>'760'],['value'=>'']]],['name'=>'Двигатель','cells'=>[['value'=>''],['value'=>'']]],['name'=>'Замена АКБ','cells'=>[['value'=>''],['value'=>'']]],['name'=>'Замена масла','cells'=>[['value'=>'1140'],['value'=>'5800']]],['name'=>'Тормозная система','cells'=>[['value'=>''],['value'=>'']]],['name'=>'Замена колодок','cells'=>[['value'=>'1900'],['value'=>'3500']]]]];
$groups=roverland_usability_matrix_groups($matrix);
check(count($groups)===3,'Three imported matrix categories');
check(count($groups[1]['rows'])===2,'Service with missing prices retained');
check($groups[1]['rows'][1]['cells'][1]['value']==='5800','Matrix value preserved');
echo "PASS: migrated categories and services with missing prices\n";
