<?php defined('ABSPATH') || exit;
$offers = array(
 array('newdiscount.jpg','Дисконтная накопительная программа','до 6%','/aktsii/diskontnaya-programma/'),
 array('newpodmena.jpg','Круглосуточный эвакуатор','','/servis/sluzhba-evakuatsii/'),
 array('newevak.jpg','Подменный автомобиль на время ремонта','','/servis/podmennyy-avtomobil/')
);
$image_base='assets/images/banners/';
if (!is_singular('portfolio_item')) {
    $image_base='assets/images/generated/';
    $offers[0][0]='discount-2026.webp';
    $offers[1][0]='tow-2026.webp';
    $offers[2][0]='loaner-2026.webp';
}
?>
<section class="service-offers content-revision-section<?php echo !is_singular('portfolio_item') ? ' service-offers--generated' : ''; ?>" aria-label="Предложения Rover Land"><div class="container service-offers__grid">
 <?php foreach ($offers as $item) : ?><a class="service-offers__card" href="<?php echo esc_url(home_url($item[3])); ?>">
   <img src="<?php echo esc_url(roverland_asset($image_base.$item[0])); ?>" alt="" loading="lazy">
   <span class="service-offers__copy"><strong><?php echo esc_html($item[1]); ?></strong><?php if ($item[2]) : ?><span><?php echo esc_html($item[2]); ?></span><?php endif; ?><span class="service-offers__link">Узнать подробнее <span class="rl-chevron rl-chevron--right" aria-hidden="true"></span></span></span>
 </a><?php endforeach; ?>
</div></section>
