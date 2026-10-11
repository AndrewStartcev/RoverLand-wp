<?php
require __DIR__ . '/revision-bootstrap.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
$images=json_decode(file_get_contents($root.'/data/model-images-revision.json'),true);
foreach(get_posts(array('post_type'=>'rover_model','post_status'=>'publish','posts_per_page'=>-1)) as $model) {
    $page=roverland_get_service_page_for_model($model->ID);
    if(!$page){continue;}
    $path=trim(wp_parse_url(get_permalink($page),PHP_URL_PATH),'/');
    if(empty($images[$path])) {continue;}
    $source='https://roverland.ru'.$images[$path];
    $existing=get_posts(array('post_type'=>'attachment','post_status'=>'inherit','posts_per_page'=>1,'meta_key'=>'_source_url','meta_value'=>$source));
    $image=$existing ? $existing[0]->ID : media_sideload_image($source,$page->ID,null,'id');
    if(is_wp_error($image)){WP_CLI::warning($source);continue;}
    $set($model->ID,'model_image',$image);
}
WP_CLI::success('Original model card images applied.');
