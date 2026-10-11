<?php
defined('ABSPATH') || exit;
$ids = roverland_field('service_selected_cases', array());
if (!$ids) { return; }
$cases = get_posts(array('post_type'=>'portfolio_item','post_status'=>'publish','post__in'=>array_map('intval',$ids),'orderby'=>'post__in','posts_per_page'=>-1));
if (!$cases) { return; }
?>
<section class="service-selected-cases"><div class="container"><div class="service-selected-cases__head"><h2><?php echo esc_html(roverland_field('service_cases_title','Наши работы')); ?></h2><div class="service-selected-cases__controls"><button type="button" data-case-direction="-1" aria-label="Предыдущие работы"><span class="rl-chevron rl-chevron--left" aria-hidden="true"></span></button><button type="button" data-case-direction="1" aria-label="Следующие работы"><span class="rl-chevron rl-chevron--right" aria-hidden="true"></span></button></div></div><div class="service-selected-cases__track" tabindex="0" aria-label="Карусель выполненных работ">
<?php foreach($cases as $case): $image=roverland_field('portfolio_card_image',array(),$case->ID); $url=roverland_image_url($image); ?>
<a class="service-selected-case" href="<?php echo esc_url(get_permalink($case)); ?>"><?php if($url): ?><img src="<?php echo esc_url($url); ?>" alt="<?php echo esc_attr(get_the_title($case)); ?>" loading="lazy"><?php endif; ?><h3><?php echo esc_html(get_the_title($case)); ?></h3><span>Подробнее <span class="rl-chevron rl-chevron--right" aria-hidden="true"></span></span></a>
<?php endforeach; ?></div></div></section>
