<?php defined('ABSPATH') || exit;
$original=is_singular('portfolio_item');
$base=$original?'assets/images/banners/':'assets/images/generated/';
$offers=array(
 array('image'=>$original?'newdiscount.jpg':'discount-2026.webp','title'=>'Дисконтная накопительная программа','text'=>'Скидка до 6%','url'=>'/aktsii/diskontnaya-programma/'),
 array('image'=>$original?'newpodmena.jpg':'tow-2026.webp','title'=>'Круглосуточный эвакуатор','text'=>'Эвакуация автомобиля в сервис Rover Land','url'=>'/servis/sluzhba-evakuatsii/'),
 array('image'=>$original?'newevak.jpg':'loaner-2026.webp','title'=>'Подменный автомобиль','text'=>'На время ремонта вашего автомобиля','url'=>'/servis/podmennyy-avtomobil/')
);
?>
<section class="offers content-revision-section" aria-label="Предложения Rover Land"><div class="container">
 <div class="section-heading section-heading--center"><h2 class="section-heading__title">Наши предложения</h2></div>
 <div class="offers-slider swiper" data-offers-slider><div class="swiper-wrapper">
 <?php foreach($offers as $offer): $offer['image']=array('url'=>roverland_asset($base.$offer['image'])); get_template_part('template-parts/elements/offer-card',null,array('offer'=>$offer)); endforeach; ?>
 </div><div class="offers-slider__pagination swiper-pagination"></div></div>
</div></section>
