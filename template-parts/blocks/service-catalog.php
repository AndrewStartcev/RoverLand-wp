<?php
defined('ABSPATH') || exit;
$path=trim(wp_parse_url(get_permalink(),PHP_URL_PATH),'/');
if(!in_array($path,array('remont','servis/to','servis'),true)) {return;}
$items=array();
if($path==='servis') {
    $pages=get_pages(array('parent'=>get_the_ID(),'sort_column'=>'menu_order,post_title'));
    foreach($pages as $p) {$items[]=array('title'=>get_the_title($p),'url'=>get_permalink($p),'image'=>roverland_field('service_hero_image',array(),$p->ID));}
} else {
    $models=get_posts(array('post_type'=>'rover_model','post_status'=>'publish','posts_per_page'=>-1,'orderby'=>array('menu_order'=>'ASC','title'=>'ASC')));
    foreach($models as $model) {
        $related=get_posts(array('post_type'=>'page','post_status'=>'publish','posts_per_page'=>-1,'meta_key'=>'service_related_model','meta_value'=>$model->ID));
        foreach($related as $p) {
            $url=get_permalink($p);$url_path=trim(wp_parse_url($url,PHP_URL_PATH),'/');
            if(($path==='servis/to' && preg_match('~/to$~',$url_path)) || ($path==='remont' && roverland_service_page_kind($p->ID)==='model')) {
                $items[]=array('title'=>get_the_title($model),'url'=>$url,'image'=>roverland_field('model_image',roverland_field('service_hero_image',array(),$p->ID),$model->ID));break;
            }
        }
    }
}
if(!$items){return;}
?>
<section class="service-content-block" id="service-catalog"><div class="container"><h2><?php echo $path==='servis'?'Каталог услуг':'Выберите модель автомобиля'; ?></h2><div class="service-catalog-grid">
<?php foreach($items as $item):$url=roverland_image_url($item['image']); ?>
<a class="service-catalog-card" href="<?php echo esc_url($item['url']); ?>"><?php if($url): ?><img src="<?php echo esc_url($url); ?>" alt="<?php echo esc_attr($item['title']); ?>" loading="lazy"><?php endif; ?><h3><?php echo esc_html($item['title']); ?></h3><span>Подробнее <span class="rl-chevron rl-chevron--right" aria-hidden="true"></span></span></a>
<?php endforeach; ?></div></div></section>
