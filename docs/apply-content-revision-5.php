<?php
require __DIR__ . '/revision-bootstrap.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
$data = json_decode(file_get_contents($root . '/data/service-content-revision.json'), true);
foreach($data as $path=>$item) {
    $id=$page($path); $content=$item['content'];
    foreach(array_unique($item['images']) as $source) {
        if (strpos($source, 'https://roverland.ru/') !== 0) { continue; }
        $existing=get_posts(array('post_type'=>'attachment','post_status'=>'inherit','posts_per_page'=>1,'meta_key'=>'_source_url','meta_value'=>$source));
        $attachment=$existing ? $existing[0]->ID : media_sideload_image($source,$id,null,'id');
        if(is_wp_error($attachment)) { WP_CLI::warning($source . ': ' . $attachment->get_error_message()); continue; }
        $content=str_replace($source,wp_get_attachment_url($attachment),$content);
        if($path==='servis/off-road' && empty($first_offroad)) {$first_offroad=$attachment; $set($id,'service_hero_image',$attachment);}
    }
    $set($id,'service_revision_content',$content);
    $terms=array('remont/akpp'=>'АКПП','remont/dvigatel'=>'двигател','remont/kompressora-pnevmopodveski'=>'компрессор','remont/polnaya-pokraska-avto'=>'покраск','remont/rulevoy-reyki'=>'рейк','servis/khimchistka-salona'=>'салон','servis/mojka-kuzova'=>'салон','servis/off-road'=>'Defender','servis/professionalnaya-polirovka'=>'полиров','servis/shinomontazh'=>'колес','remont/kuzovnoj-remont'=>'кузов');
    $cases=get_posts(array('post_type'=>'portfolio_item','post_status'=>'publish','posts_per_page'=>-1,'orderby'=>array('menu_order'=>'ASC','date'=>'DESC')));
    $chosen=array();
    foreach($cases as $case) {if(!empty($terms[$path]) && mb_stripos($case->post_title,$terms[$path])!==false){$chosen[]=$case->ID;}}
    if(!$chosen) {foreach(array(25,26,40) as $legacy){$case=roverland_content_migration_find_portfolio($legacy);if($case){$chosen[]=$case;}}}
    $set($id,'service_selected_cases',array_slice($chosen,0,6));
    if (in_array($path,array('zapchasti','zapchasti/aksessuary-i-tyuning'),true)) {$set($id,'service_hero_lead','Широчайший ассортимент запчастей в наличии в магазине и на заказ.');}
}
$set($page('servis'),'service_secondary_button_label','Каталог услуг');
$set($page('servis/to'),'service_auto_related',0);
$to_cases=array();foreach(array(40,41) as $legacy){$case=roverland_content_migration_find_portfolio($legacy);if($case){$to_cases[]=$case;}}
$set($page('servis/to'),'service_selected_cases',$to_cases);
WP_CLI::success('Service copy restored from verified source pages.');

