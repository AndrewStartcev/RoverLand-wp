<?php defined('ABSPATH') || exit;
$offer = $args['offer'];
$image = $offer['image'] ?? array();
$image_url = roverland_image_url($image);
?>
<article class="offer-card swiper-slide">
  <?php if ($image_url): ?><img class="offer-card__image" src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($offer['title'] ?? ''); ?>" loading="lazy" decoding="async"><?php endif; ?>
  <div class="offer-card__body"><h3><?php echo esc_html($offer['title'] ?? ''); ?></h3><p><?php echo esc_html($offer['text'] ?? ''); ?></p>
    <a class="text-link" href="<?php echo esc_url(home_url($offer['url'])); ?>">Узнать больше</a>
  </div>
</article>
