<?php
// Checks the real handler with mail intercepted in this CLI process only.
if (!defined('WP_CLI') || !WP_CLI) {exit;}
$captured=false;
add_filter('pre_wp_mail',function($return,$mail) use (&$captured) {
    $captured=true;
    if(!is_email($mail['to'])) {WP_CLI::error('Invalid configured recipient');}
    foreach(array('Заявка на выкуп автомобиля','Марка: Land Rover','Модель: Discovery','Год выпуска: 2020','Пробег: 12345','Желаемая цена: 3000000') as $expected) {
        if(strpos($mail['subject'].' '.$mail['message'],$expected)===false) {WP_CLI::error('Missing buyout field: '.$expected);}
    }
    WP_CLI::log('PASS: configured recipient, buyout subject and all vehicle fields. Mail intercepted, no message sent.');
    return true;
},PHP_INT_MAX,2);
$_POST=array('_roverland_form_nonce'=>wp_create_nonce('roverland_form_submit'),'form_type'=>'sell','name'=>'Проверка формы','phone'=>'+7 (000) 000-00-00','consent'=>'1','car_make'=>'Land Rover','car_model'=>'Discovery','car_year'=>'2020','car_mileage'=>'12345','car_price'=>'3000000','page_title'=>'CLI validation','page_url'=>home_url('/servis/prodat-avtomobil/'));
$_REQUEST=$_POST;
register_shutdown_function(function() use (&$captured) {if(!$captured) {fwrite(STDERR,'FAIL: handler did not reach mail assembly'.PHP_EOL);}});
roverland_handle_form_submit();
