<?php
if(!defined('WP_CLI') || !WP_CLI){return;}
require_once ABSPATH.'wp-admin/includes/file.php';
require_once ABSPATH.'wp-admin/includes/media.php';
require_once ABSPATH.'wp-admin/includes/image.php';
$map=get_option('roverland_local_video_urls',array());
$sources=array('https://roverland.ru/video/RoverLand_Volokolamka_01.mp4','https://roverland.ru/video/RoverLand_Nikulinskaya.mp4','https://roverland.ru/video/RoverLand_Volokolamka_01_poster.jpg','https://roverland.ru/video/RoverLand_Nikulinskaya.jpg');
$data=json_decode(file_get_contents(dirname(__DIR__).'/data/portfolio-video-source.json'),true);
foreach($data as $item){foreach($item['videos'] as $video){$sources[]=$video['url'];}}
foreach(array_unique($sources) as $source){
 if(!empty($map[$source])){WP_CLI::log('Already local: '.basename($source));continue;}
 $tmp=download_url($source,300);
 if(is_wp_error($tmp)){WP_CLI::warning($tmp->get_error_message());continue;}
 $id=media_handle_sideload(array('name'=>basename($source),'tmp_name'=>$tmp),0);
 if(is_wp_error($id)){@unlink($tmp);WP_CLI::warning($id->get_error_message());continue;}
 update_post_meta($id,'_source_url',$source);$map[$source]=wp_get_attachment_url($id);update_option('roverland_local_video_urls',$map,false);
 WP_CLI::log('Localized: '.basename($source));
}
foreach($data as $legacy=>$item){
 $id=roverland_content_migration_find_portfolio($legacy);$videos=get_field('portfolio_videos',$id);
 foreach($videos as &$video){$video['url']=$map[$video['url']] ?? $video['url'];}unset($video);
 update_field('field_rl_portfolio_videos',$videos,$id);
}
WP_CLI::success('Video localization finished. Check warnings and playback.');
