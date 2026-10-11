<?php
if (!defined('WP_CLI') || !WP_CLI) { exit; }
require __DIR__ . '/revision-bootstrap.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
$labels=array('tow'=>'Эвакуатор','cleaning'=>'Химчистка','inspection'=>'Предпродажная проверка','loaner'=>'Подменный автомобиль','discount'=>'Дисконтная программа','filter'=>'Фильтр салона в подарок','wipers'=>'Стеклоочистители в подарок','improve'=>'Улучшим любое предложение','refund'=>'Возвращаем деньги');
$images=array();
foreach($labels as $slug=>$label) {
    $found=get_posts(array('post_type'=>'attachment','post_status'=>'inherit','posts_per_page'=>1,'meta_key'=>'_roverland_generated_slug','meta_value'=>$slug.'-2026'));
    if($found) {$images[$slug]=$found[0]->ID; continue;}
    $filename=$slug.'-2026.webp';
    $temp=wp_tempnam($filename);
    if(!copy($root.'/assets/images/generated/'.$filename,$temp)) {WP_CLI::error('Cannot copy '.$filename);}
    $id=media_handle_sideload(array('name'=>$filename,'tmp_name'=>$temp),0,$label);
    if(is_wp_error($id)) {WP_CLI::error($id->get_error_message());}
    update_post_meta($id,'_roverland_generated_slug',$slug.'-2026');
    update_post_meta($id,'_wp_attachment_image_alt',$label);
    $images[$slug]=$id;
}
$home=(int)get_option('page_on_front');
$services=$normalize(get_field('home_services',$home,false)) ?: array();
$service_map=array('Эвакуатор'=>'tow','Химчистка'=>'cleaning','Предпродажная проверка'=>'inspection','Подменное авто'=>'loaner');
foreach($services as &$row) {if(isset($service_map[$row['title']])) {$row['image']=$images[$service_map[$row['title']]];}} unset($row);
$set($home,'home_services',$services);
$promos=array('diskontnaya-programma'=>'discount','filtr-salona-v-podarok'=>'filter','shchetki-stekloochistiteley-v-podarok'=>'wipers','uluchshim-lyuboe-predlozhenie'=>'improve','vozvrashchaem-dengi'=>'refund');
foreach($promos as $slug=>$image) {
    $post=get_page_by_path($slug,OBJECT,'promotion');
    if(!$post) {WP_CLI::error('Missing promotion: '.$slug);}
    $set($post->ID,'promotion_hero_image',$images[$image]);
    $set($post->ID,'promotion_card_image',$images[$image]);
}
$offers=$normalize(get_field('home_offers',$home,false)) ?: array();
foreach($offers as &$row) {foreach($promos as $slug=>$image) {if(strpos($row['url'] ?? '', '/'.$slug.'/')!==false) {$row['image']=$images[$image];}}} unset($row);
$set($home,'home_offers',$offers);
foreach(array('servis/sluzhba-evakuatsii'=>'tow','servis/khimchistka-salona'=>'cleaning','servis/predprodazhnaya-proverka-avtomobilya'=>'inspection','servis/podmennyy-avtomobil'=>'loaner') as $path=>$image) {
    $set($page($path),'service_hero_image',$images[$image]);
}
WP_CLI::success('Nine generated illustrations applied. Portfolio and real facility photos unchanged.');
