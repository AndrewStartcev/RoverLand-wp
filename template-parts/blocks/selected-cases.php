<?php
defined('ABSPATH') || exit;
$ids = roverland_field('service_selected_cases', array());
if (!$ids && in_array(roverland_service_page_kind(), array('model', 'model_service', 'maintenance'), true)) {
	$ids = get_posts(array('post_type'=>'portfolio_item','post_status'=>'publish','posts_per_page'=>3,'fields'=>'ids'));
}
if (!$ids) { return; }
$cases = get_posts(array('post_type'=>'portfolio_item','post_status'=>'publish','post__in'=>array_map('intval',$ids),'orderby'=>'post__in','posts_per_page'=>-1));
if (!$cases) { return; }
?>
<section class="service-selected-cases"><div class="container"><div class="service-selected-cases__head"><h2 class="section-heading__title"><?php echo esc_html(roverland_field('service_cases_title','Наши работы')); ?></h2><div class="service-selected-cases__controls"><button type="button" data-case-direction="-1" aria-label="Предыдущие работы"><span class="rl-chevron rl-chevron--left" aria-hidden="true"></span></button><button type="button" data-case-direction="1" aria-label="Следующие работы"><span class="rl-chevron rl-chevron--right" aria-hidden="true"></span></button></div></div><div class="service-selected-cases__track" tabindex="0" aria-label="Карусель выполненных работ">
<?php foreach($cases as $case): $image=roverland_field('portfolio_card_image',array(),$case->ID); $url=roverland_image_url($image); ?>
<article class="portfolio-card service-selected-case"><?php if($url): ?><img class="portfolio-card__image" src="<?php echo esc_url($url); ?>" alt="<?php echo esc_attr(get_the_title($case)); ?>" loading="lazy"><?php endif; ?><div class="portfolio-card__body"><h2><a href="<?php echo esc_url(get_permalink($case)); ?>"><?php echo esc_html(get_the_title($case)); ?></a></h2><a class="text-link card-link" href="<?php echo esc_url(get_permalink($case)); ?>">Подробнее</a></div></article>
<?php endforeach; ?></div></div></section>
