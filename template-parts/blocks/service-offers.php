<?php defined('ABSPATH') || exit;
$offers = array(
 array('newdiscount.jpg','Дисконтная накопительная программа','до 6%','/aktsii/diskontnaya-programma/'),
 array('newpodmena.jpg','Круглосуточный эвакуатор','','/servis/sluzhba-evakuatsii/'),
 array('newevak.jpg','Подменный автомобиль на время ремонта','','/servis/podmennyy-avtomobil/')
);
?>
<section class="service-offers content-revision-section" aria-label="Предложения Rover Land"><div class="container service-offers__grid">
 <?php foreach ($offers as $item) : ?><a class="service-offers__card" href="<?php echo esc_url(home_url($item[3])); ?>">
   <img src="<?php echo esc_url(roverland_asset('assets/images/banners/'.$item[0])); ?>" alt="" loading="lazy">
   <span class="service-offers__copy"><strong><?php echo esc_html($item[1]); ?></strong><?php if ($item[2]) : ?><span><?php echo esc_html($item[2]); ?></span><?php endif; ?><span class="service-offers__link">Узнать подробнее →</span></span>
 </a><?php endforeach; ?>
</div></section>
